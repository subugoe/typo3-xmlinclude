<?php
namespace Subugoe\Xmlinclude\Controller;

/*******************************************************************************
 * Copyright notice
 *
 * Copyright (C) 2012-2013 by Sven-S. Porst, SUB Göttingen
 * <porst@sub.uni-goettingen.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 ******************************************************************************/

use Subugoe\Xmlinclude\Utility\Array2XML;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;


/**
 * XMLInclude controller for the XMLInclude extension.
 * Provides the main controller for the xmlinclude plug-in.
 */
class IncludeController extends ActionController
{

    /**
     * Instance variable providing an array for error strings.
     * @var array
     */
    protected $errors;

    /**
     * @param string $message
     * @param string $fileInfo
     */
    protected function addError($message, $fileInfo = null)
    {
        $this->errors[] = ['message' => $message, 'fileInfo' => $fileInfo];
        GeneralUtility::devLog('Error: ' . $message . '(' . $fileInfo . ')', 'xmlinclude', 3);
    }


    /**
     * Array for debug information.
     * @var array
     */
    protected $debugInformation;


    /**
     * Initialiser
     *
     * @return void
     */
    public function initializeAction()
    {
        $this->errors = [];
        $this->debugInformation = [
            'settings' => $this->settings
        ];
    }


    /**
     * Index
     *
     * @return void
     */
    public function indexAction()
    {
        $this->addResourcesToHead();

        $XML = $this->XML();
        if ($XML) {
            $XML->formatOutput = true;
            $this->view->assign('xml', $XML->saveHTML($XML->firstChild));
        }

        $this->view->assign('settings', $this->settings);
        $this->view->assign('errors', $this->errors);
        $this->view->assign('debugInformation', $this->debugInformation);
    }


    /**
     * Loads and transforms XML according to settings.
     * Returns the resulting XML document.
     *
     * @return \DOMDocument
     */
    protected function XML()
    {

        $XML = new \DOMDocument();

        // Configure connection.
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true
        ];

        // Deal with Form submission:
        // Detect forms by the formMethod parameter and use its value to submit the form.
        // The form’s fields are expected to be in the formParamters variable.
        $additionalURLParameters = [];
        $arguments = $this->request->getArguments();
        $this->debugInformation['arguments'] = $arguments;
        if (array_key_exists('formParameters', $arguments)) {
            if ($arguments['formMethod'] === 'POST') {
                $curlOptions[CURLOPT_POST] = true;
                $curlOptions[CURLOPT_POSTFIELDS] = $arguments['formParameters'];
            } else {
                // For GET requests append the additional parameters to the request URL.
                $additionalURLParameters = $arguments['formParameters'];
            }
        }

        // Forward whitelisted cookies of the request to the server.
        $cookieParts = [];
        foreach ($_COOKIE as $cookieName => $cookieContent) {
            if ($this->settings['cookiePassthrough'] && in_array($cookieName, $this->settings['cookiePassthrough'])) {
                $cookieParts[] = urlencode($cookieName) . '=' . urlencode($cookieContent);
            }
        }
        $curlOptions[CURLOPT_COOKIE] = implode('; ', $cookieParts);

        // Run curl.
        $curl = curl_init();
        $remoteURL = $this->remoteURL($additionalURLParameters);

        if ($remoteURL !== '') {
            $curlOptions[CURLOPT_URL] = $remoteURL;
            $isHTTPTransfer = (strpos($remoteURL, 'http') === 0);
            $this->debugInformation['curlOptions'] = $curlOptions;
            curl_setopt_array($curl, $curlOptions);
            $loadedString = curl_exec($curl);
            $contentString = $loadedString;

            if ($loadedString) {
                if ($isHTTPTransfer) {
                    // We have a header: Deal with cookies.
                    $downloadParts = explode("\r\n\r\n", $loadedString, 2);
                    $cookiePath = $this->settings['cookiePath'];
                    if ($cookiePath === '.') {
                        // Get relative path to current page.
                        $cookiePath = $this->uriBuilder->reset()->build();

                        // Prepend base URL parts if necessary.
                        $siteURL = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
                        $sitePath = parse_url($siteURL, PHP_URL_PATH);
                        if (strpos($cookiePath, $sitePath) !== 0) {
                            $pathSeparator = '';
                            if ($cookiePath[0] !== '/' && $sitePath[strlen($sitePath) - 1] !== '/') {
                                $pathSeparator = '/';
                            }
                            $cookiePath = $sitePath . $pathSeparator . $cookiePath;
                        }
                    }

                    // Read cookies from download.
                    $cookies = $this->cookiesFromHeader($downloadParts[0]);


                    // Pass the relevant cookies on to the user.
                    foreach ($cookies as $cookieName => $cookieContent) {
                        // TODO: handle expiry etc?
                        if (in_array($cookieName, $this->settings['cookiePassthrough'])) {
                            setrawcookie($cookieName, $cookieContent['value'], 0, $cookiePath);
                        }
                    }

                    // Replace content string with the body.
                    $contentString = $downloadParts[1];
                }

                // Parse file.
                $XML = $this->stringToXML($contentString);
            } else {
                $this->addError('Failed to load XML from', $remoteURL);
            }
        } else {
            $XML = $this->stringToXML('<xmlinclude-root/>');
        }

        return $XML;
    }


    /**
     * Attempts to transfor the passed $string to a XML DOMDocument.
     * Depending on our configuration, allow try parsing the string as XML
     * (straightforward XML parsing), HTML (dogy XML parsing) or JSON (JSON
     * parsing plus conversion to a XML document).
     *
     * @param String $string
     * @return \DOMDocument
     */
    protected function stringToXML($string)
    {
        $XML = new \DOMDocument();
        $parseSuccess = false;
        if ($this->settings['parser'] === 'html') {
            // Assume we have UTF-8 encoding and escape based on that assumption.
            // (To work around the poor handling of encodings in DOMDocument.)
            $string = mb_convert_encoding($string, 'HTML-ENTITIES', "UTF-8");
            $string = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $string);
            libxml_use_internal_errors(true);
            $parseSuccess = $XML->loadHTML($string);
        } else {
            if ($this->settings['parser'] === 'json') {
                $parseSuccess = $this->JSONStringToXML($string, $XML);
            } else {
                $parseSuccess = $XML->loadXML($string);
            }
        }

        if ($parseSuccess) {
            // Apply array of XSLTs.
            ksort($this->settings['XSL']);
            foreach ($this->settings['XSL'] as $XSLPath) {
                $XML = $this->transformXMLWithXSLAtPath($XML, $XSLPath);
                if (!$XML) {
                    $XML = null;
                    break;
                }
            }
        } else {
            $this->addError('Failed to parse XML.');
        }

        return $XML;
    }


    /**
     * Converts the passed JSON string to a XML DOMDocument.
     *
     * @param String $JSONString
     * @param \DOMDocument $XML
     * @return boolean
     */
    protected function JSONStringToXML($JSONString, &$XML)
    {
        $parseSuccess = false;
        $JSONArray = json_decode($JSONString, true);
        if ($JSONArray) {
            require_once(ExtensionManagementUtility::extPath('xmlinclude') . 'Classes/Utility/Array2XML.php');
            $JSONXML = Array2XML::createXML('fromJSON', $JSONArray);
            if ($JSONXML) {
                $XML = $JSONXML;
                $parseSuccess = true;
            }
        } else {
            $this->addError('Failed to parse JSON (Error ' . json_last_error() . ').');
        }

        return $parseSuccess;
    }


    /**
     * Builds the remote URL to load the XML from. Uses:
     * * the baseURL set in the FlexForm
     * * the URL argument
     * * the parameters TypoScript variable
     * * the parameters passed in $additionalURLParameters
     *
     * @param array $additionalURLParameters [defaults to []]
     * @return string
     */
    protected function remoteURL($additionalURLParameters = [])
    {
        $remoteURL = '';

        $arguments = $this->request->getArguments();

        if (strlen($this->settings['startURL']) > 0 || strlen($this->settings['baseURL']) > 0) {
            if (array_key_exists('URL', $arguments) && strlen($arguments['URL']) > 0) {
                // Ensure we only fetch URLs beginning with our base URL.
                if (strpos($arguments['URL'], $this->settings['baseURL']) !== 0) {
                    $remoteURL .= $this->settings['baseURL'];
                }
                $remoteURL .= $arguments['URL'];
            } else {
                $remoteURL .= $this->settings['startURL'];
            }

            // Take parameters from the target URL and add those from the parameters TypoScript variable.
            $URLParameters = null;
            $remoteURLComponents = explode('?', $remoteURL, 2);
            parse_str($remoteURLComponents[1], $URLParameters);
            $queryURLParameters = null;

            $queryURLComponents = explode('?', $this->request->getRequestUri(), 2);
            if (count($queryURLComponents) === 2) {
                parse_str($queryURLComponents[1], $queryURLParameters);
                $URLParameters = array_merge($URLParameters, $queryURLParameters);
            }
            $URLParameters = array_merge($URLParameters, $this->settings['URLParameters']);
            $URLParameters = array_merge($URLParameters, $additionalURLParameters);

            // Reassemble the URL with its new set of parameters.
            $newParameterString = http_build_query($URLParameters);
            if ($newParameterString) {
                $remoteURL = $remoteURLComponents[0] . '?' . $newParameterString;
            }
        }

        return $remoteURL;
    }


    /**
     * Loads XSL from the given path and applies it to the given passed XML.
     * Returns the transformed XML document.
     *
     * @param string $XSLPath
     * @param \DOMDocument $XML
     * @return \DOMDocument|Null transformed XML
     */
    protected function transformXMLWithXSLAtPath($XML, $XSLPath)
    {
        // Let TYPO3 analyse  the path settings to resolve potential 'EXT:'.
        $processedPath = $GLOBALS['TSFE']->tmpl->getFileName($XSLPath);
        if ($processedPath) {
            $XSLPath = PATH_site . $processedPath;
        }

        // Load XSL.
        $XSLString = GeneralUtility::getUrl($XSLPath);
        $XSL = new \DOMDocument();
        if ($XSL->loadXML($XSLString)) {
            $XSL->documentURI = pathinfo($XSLPath, PATHINFO_DIRNAME);
            $xsltproc = new \XSLTProcessor();

            // Add our own XML parsing function to XSL.
            $xsltproc->registerPHPFunctions('XmlUtiliy::parseXML');

            $xsltproc->importStylesheet($XSL);

            // Pass parameters to XSL.
            $parameters = $this->XSLParametersForXSLPath($XSLPath);
            $xsltproc->setParameter('', $parameters);

            // Transform the document.
            $XML = $xsltproc->transformToDoc($XML);
            if (!$XML) {
                $this->addError('Failed to apply XSL', $XSLPath);
            }
        } else {
            $this->addError('Failed to load XSL', $XSLPath);
            $XML = null;
        }

        return $XML;
    }


    /**
     * Returns the array of parameters to pass to the XSL transformation.
     *
     * @return array parameters to pass to the XSL Transformation
     */
    protected function XSLParametersForXSLPath($XSLPath)
    {
        // Settings from TypoScript.
        $parameters = $this->flattenedArray($this->settings, 'setting');

        // Query arguments.
        $parameters += $this->flattenedArray($this->request->getArguments(), 'argument');

        // fullPageURL: URL of current page.
        // The fullPageURL is the current URL called by the browser without parameters.
        // We determine it by removing the URL argument from the end of the page URL.
        $fullPageURLComponents = explode('?', $this->request->getRequestUri(), 2);
        $fullPageURL = $fullPageURLComponents[0];
        $parameters['fullPageURL'] = $fullPageURL;

        // basePageURL: URL of current base page (RealURL corresponding to page ID).
        // It does not include the parameters appended to the path by RealURL.
        if ($this->settings['useRealURL'] == '1') {
            $basePageURL = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
            $basePageURL .= urldecode($this->uriBuilder->buildFrontendUri());
            // Remove duplicated slashes.
            $basePageURL = preg_replace('/([^:])\/\//', '$1/', $basePageURL);
        }
        $parameters['basePageURL'] = $basePageURL;

        // Name of the target host.
        $hostName = parse_url($this->settings['baseURL'], PHP_URL_HOST);
        $parameters['hostName'] = $hostName;

        // File system paths of TYPO3, the XSL file and the folder containing it.
        // These can be helpful for loading other XSL files from XSL as the path handling in PHP’s is unclear.
        $parameters['sitePath'] = PATH_site;
        $parameters['currentXSLPath'] = $XSLPath;
        $parameters['currentXSLFolder'] = pathinfo($XSLPath, PATHINFO_DIRNAME) . '/';

        $this->debugInformation['XSLParameters'] = $parameters;
        return $parameters;
    }


    /**
     * Returns a flattened Array of the passed arguments.
     *
     * @param array $array
     * @param string $prefix
     * @return array
     */
    protected function flattenedArray($array, $prefix = 'array')
    {
        $list = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $list += $this->flattenedArray($value, $prefix . '-' . $key);
            } else {
                $list[$prefix . '-' . $key] = $value;
            }
        }

        return $list;
    }


    /**
     * Takes the header of a http reply and returns an array containing
     * the cookies from the Set-Cookie lines in that header. Keys in that array
     * are the cookie names, the value is an array which has the cookie value
     * in the field 'value' and other cookie fields in fields named like
     * the field name
     *
     * If multiple cookies with the same name are set, the last one is used.
     *
     * @param string $headerString
     * @return array
     */
    protected function cookiesFromHeader($headerString)
    {
        $cookies = [];

        $headerLines = explode("\r\n", $headerString);
        foreach ($headerLines as $headerLine) {
            $headerParts = explode(':', $headerLine, 2);
            if (count($headerParts) === 2) {
                $headerName = trim(strtolower($headerParts[0]));
                $headerValue = trim($headerParts[1]);
                if ($headerName === 'set-cookie') {
                    $cookieParts = explode(';', $headerValue);
                    $cookieMainParts = explode('=', $cookieParts[0]);
                    if (count($cookieMainParts) === 2) {
                        $cookieName = $cookieMainParts[0];
                        $cookieValue = $cookieMainParts[1];
                        $cookies[$cookieName] = ['value' => $cookieValue];
                        if (count($cookieParts) > 1) {
                            $cookieOptions = array_slice($cookieParts, 1);
                            foreach ($cookieOptions as $cookieOption) {
                                $cookieOptionParts = explode('=', $cookieOption, 2);
                                if (count($cookieOptionParts) === 2) {
                                    $cookieOptionName = trim($cookieOptionParts[0]);
                                    $cookieOptionValue = trim($cookieOptionParts[1]);
                                    $cookies[$cookieName][$cookieOptionName] = $cookieOptionValue;
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->debugInformation['cookiesFromServer'] = $cookies;
        return $cookies;
    }

    /**
     * Helper: Inserts style and script tags into the page’s head.
     *
     * @return void
     */
    protected function addResourcesToHead()
    {
        if (array_key_exists('headCSS', $this->settings)) {
            foreach ($this->settings['headCSS'] as $CSSPath) {
                $styleTag = new TagBuilder('link');
                $styleTag->addAttribute('rel', 'stylesheet');
                $styleTag->addAttribute('type', 'text/css');
                $styleTag->addAttribute('href', $CSSPath);
                $styleTag->addAttribute('media', 'all');
                $this->response->addAdditionalHeaderData($styleTag->render());
            }
        }

        if (array_key_exists('headJavaScript', $this->settings)) {
            foreach ($this->settings['headJavaScript'] as $JSPath) {
                $scriptTag = new TagBuilder('script');
                $scriptTag->addAttribute('type', 'text/javascript');
                $scriptTag->addAttribute('src', $JSPath);
                $scriptTag->forceClosingTag(true);
                $this->response->addAdditionalHeaderData($scriptTag->render());
            }
        }
    }

}

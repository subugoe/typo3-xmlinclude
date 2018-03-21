<?php

namespace Subugoe\Xmlinclude\Service;

use GuzzleHttp\Client;
use Subugoe\Xmlinclude\Utility\Array2XML;
use Subugoe\Xmlinclude\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

final class IncludeService
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @var UriBuilder
     */
    private $uriBuilder;

    /**
     * @var string
     */
    private $requestUri;

    public function __construct(Client $client, UriBuilder $uriBuilder)
    {
        $this->client = $client;
        $this->uriBuilder = $uriBuilder;
    }

    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param array $arguments
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @param string $requestUri
     */
    public function setRequestUri(string $requestUri)
    {
        $this->requestUri = $requestUri;
    }

    /**
     * Loads and transforms XML according to settings.
     * Returns the resulting XML document.
     *
     * @return \DOMDocument
     */
    public function XML(): \DOMDocument
    {
        $XML = new \DOMDocument();

        // Configure connection.
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
        ];

        // Deal with Form submission:
        // Detect forms by the formMethod parameter and use its value to submit the form.
        // The form’s fields are expected to be in the formParamters variable.
        $additionalURLParameters = [];

        DebugUtility::$data['arguments'] = $this->arguments;
        if (array_key_exists('formParameters', $this->arguments)) {
            if ('POST' === $this->arguments['formMethod']) {
                $curlOptions[CURLOPT_POST] = true;
                $curlOptions[CURLOPT_POSTFIELDS] = $this->arguments['formParameters'];
            } else {
                // For GET requests append the additional parameters to the request URL.
                $additionalURLParameters = $this->arguments['formParameters'];
            }
        }

        // Forward whitelisted cookies of the request to the server.
        $cookieParts = [];
        foreach ($_COOKIE as $cookieName => $cookieContent) {
            if ($this->settings['cookiePassthrough'] && in_array($cookieName, $this->settings['cookiePassthrough'])) {
                $cookieParts[] = urlencode($cookieName).'='.urlencode($cookieContent);
            }
        }
        $curlOptions[CURLOPT_COOKIE] = implode('; ', $cookieParts);

        // Run curl.
        $curl = curl_init();
        $remoteURL = $this->remoteURL($additionalURLParameters);

        if ('' !== $remoteURL) {
            $remoteContent = $this->client->get($remoteURL)->getBody()->getContents();

            $curlOptions[CURLOPT_URL] = $remoteURL;
            $isHTTPTransfer = (0 === strpos($remoteURL, 'http'));
            DebugUtility::$data['curlOptions'] = $curlOptions;
            curl_setopt_array($curl, $curlOptions);
            $loadedString = $remoteContent;
            $contentString = $loadedString;

            if ($loadedString) {
                if ($isHTTPTransfer) {
                    // We have a header: Deal with cookies.
                    $downloadParts = explode("\r\n\r\n", $loadedString, 2);
                    $cookiePath = $this->settings['cookiePath'];
                    if ('.' === $cookiePath) {
                        // Get relative path to current page.
                        $cookiePath = $this->uriBuilder->reset()->build();

                        // Prepend base URL parts if necessary.
                        $siteURL = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
                        $sitePath = parse_url($siteURL, PHP_URL_PATH);
                        if (0 !== strpos($cookiePath, $sitePath)) {
                            $pathSeparator = '';
                            if ('/' !== $cookiePath[0] && '/' !== $sitePath[strlen($sitePath) - 1]) {
                                $pathSeparator = '/';
                            }
                            $cookiePath = $sitePath.$pathSeparator.$cookiePath;
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
                try {
                    $XML = $this->stringToXML($contentString);
                } catch (\Exception $e) {
                    DebugUtility::addError($e->getMessage());
                }
            } else {
                DebugUtility::addError('Failed to load XML from', $remoteURL);
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
     * @param string $string
     *
     * @return \DOMDocument
     */
    private function stringToXML($string): \DOMDocument
    {
        $XML = new \DOMDocument();
        if ('html' === $this->settings['parser']) {
            // Assume we have UTF-8 encoding and escape based on that assumption.
            // (To work around the poor handling of encodings in DOMDocument.)
            $string = mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8');
            $string = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $string);
            libxml_use_internal_errors(true);
            $parseSuccess = $XML->loadHTML($string);
        } else {
            if ('json' === $this->settings['parser']) {
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
            DebugUtility::addError('Failed to parse XML.');
        }

        return $XML;
    }

    /**
     * Returns the array of parameters to pass to the XSL transformation.
     *
     * @return array parameters to pass to the XSL Transformation
     */
    public function XSLParametersForXSLPath(string $XSLPath): array
    {
        // Settings from TypoScript.
        $parameters = $this->flattenedArray($this->settings, 'setting');

        // Query arguments.
        $parameters += $this->flattenedArray($this->arguments, 'argument');

        // fullPageURL: URL of current page.
        // The fullPageURL is the current URL called by the browser without parameters.
        // We determine it by removing the URL argument from the end of the page URL.
        $fullPageURLComponents = explode('?', $this->requestUri, 2);
        $fullPageURL = $fullPageURLComponents[0];
        $parameters['fullPageURL'] = $fullPageURL;

        // basePageURL: URL of current base page (RealURL corresponding to page ID).
        // It does not include the parameters appended to the path by RealURL.
        if ('1' == $this->settings['useRealURL']) {
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
        $parameters['currentXSLFolder'] = pathinfo($XSLPath, PATHINFO_DIRNAME).'/';

        DebugUtility::$data['XSLParameters'] = $parameters;

        return $parameters;
    }

    /**
     * Returns a flattened Array of the passed arguments.
     *
     * @param array  $array
     * @param string $prefix
     *
     * @return array
     */
    private function flattenedArray(array $array, string $prefix = 'array'): array
    {
        $list = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $list += $this->flattenedArray($value, $prefix.'-'.$key);
            } else {
                $list[$prefix.'-'.$key] = $value;
            }
        }

        return $list;
    }

    /**
     * Converts the passed JSON string to a XML DOMDocument.
     *
     * @param string       $JSONString
     * @param \DOMDocument $XML
     *
     * @return bool
     */
    private function JSONStringToXML(string $JSONString, \DOMDocument &$XML): bool
    {
        $parseSuccess = false;
        $JSONArray = json_decode($JSONString, true);
        if ($JSONArray) {
            $JSONXML = Array2XML::createXML('fromJSON', $JSONArray);
            if ($JSONXML) {
                $XML = $JSONXML;
                $parseSuccess = true;
            }
        } else {
            DebugUtility::addError('Failed to parse JSON (Error '.json_last_error().').');
        }

        return $parseSuccess;
    }

    /**
     * Builds the remote URL to load the XML from. Uses:
     * * the baseURL set in the FlexForm
     * * the URL argument
     * * the parameters TypoScript variable
     * * the parameters passed in $additionalURLParameters.
     *
     * @param array $additionalURLParameters [defaults to []]
     *
     * @return string
     */
    private function remoteURL(array $additionalURLParameters = []): string
    {
        $remoteURL = '';

        if (strlen($this->settings['startURL']) > 0 || strlen($this->settings['baseURL']) > 0) {
            if (array_key_exists('URL', $this->arguments) && strlen($this->arguments['URL']) > 0) {
                // Ensure we only fetch URLs beginning with our base URL.
                if (0 !== strpos($this->arguments['URL'], $this->settings['baseURL'])) {
                    $remoteURL .= $this->settings['baseURL'];
                }
                $remoteURL .= $this->arguments['URL'];
            } else {
                $remoteURL .= $this->settings['startURL'];
            }

            // Take parameters from the target URL and add those from the parameters TypoScript variable.
            $URLParameters = null;
            $remoteURLComponents = explode('?', $remoteURL, 2);
            parse_str($remoteURLComponents[1], $URLParameters);
            $queryURLParameters = null;

            $queryURLComponents = explode('?', $this->requestUri, 2);
            if (2 === count($queryURLComponents)) {
                parse_str($queryURLComponents[1], $queryURLParameters);
                $URLParameters = array_merge($URLParameters, $queryURLParameters);
            }
            $URLParameters = array_merge($URLParameters, $this->settings['URLParameters']);
            $URLParameters = array_merge($URLParameters, $additionalURLParameters);

            // Reassemble the URL with its new set of parameters.
            $newParameterString = http_build_query($URLParameters);
            if ($newParameterString) {
                $remoteURL = $remoteURLComponents[0].'?'.$newParameterString;
            }
        }

        return $remoteURL;
    }

    /**
     * Loads XSL from the given path and applies it to the given passed XML.
     * Returns the transformed XML document.
     *
     * @param \DOMDocument $XML
     * @param string       $XSLPath
     *
     * @return \DOMDocument|null transformed XML
     */
    private function transformXMLWithXSLAtPath(\DOMDocument $XML, string $XSLPath)
    {
        // Let TYPO3 analyse  the path settings to resolve potential 'EXT:'.
        $processedPath = $GLOBALS['TSFE']->tmpl->getFileName($XSLPath);
        if ($processedPath) {
            $XSLPath = PATH_site.$processedPath;
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
                DebugUtility::addError('Failed to apply XSL', $XSLPath);
            }
        } else {
            DebugUtility::addError('Failed to load XSL', $XSLPath);
            $XML = null;
        }

        return $XML;
    }

    /**
     * Takes the header of a http reply and returns an array containing
     * the cookies from the Set-Cookie lines in that header. Keys in that array
     * are the cookie names, the value is an array which has the cookie value
     * in the field 'value' and other cookie fields in fields named like
     * the field name.
     *
     * If multiple cookies with the same name are set, the last one is used.
     *
     * @param string $headerString
     *
     * @return array
     */
    private function cookiesFromHeader(string $headerString): array
    {
        $cookies = [];

        $headerLines = explode("\r\n", $headerString);
        foreach ($headerLines as $headerLine) {
            $headerParts = explode(':', $headerLine, 2);
            if (2 === count($headerParts)) {
                $headerName = trim(strtolower($headerParts[0]));
                $headerValue = trim($headerParts[1]);
                if ('set-cookie' === $headerName) {
                    $cookieParts = explode(';', $headerValue);
                    $cookieMainParts = explode('=', $cookieParts[0]);
                    if (2 === count($cookieMainParts)) {
                        $cookieName = $cookieMainParts[0];
                        $cookieValue = $cookieMainParts[1];
                        $cookies[$cookieName] = ['value' => $cookieValue];
                        if (count($cookieParts) > 1) {
                            $cookieOptions = array_slice($cookieParts, 1);
                            foreach ($cookieOptions as $cookieOption) {
                                $cookieOptionParts = explode('=', $cookieOption, 2);
                                if (2 === count($cookieOptionParts)) {
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

        DebugUtility::$data['cookiesFromServer'] = $cookies;

        return $cookies;
    }
}

<?php
/*******************************************************************************
 * Copyright notice
 *
 * Copyright (C) 2012 by Sven-S. Porst, SUB Göttingen
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


/**
 * XMLIncludeController.php
 *
 * Provides the main controller for the xmlinclude plug-in.
 *
 * @author Sven-S. Porst <porst@sub-uni-goettingen.de>
 */



/**
 * XMLInclude controller for the XMLInclude extension.
 */
class Tx_XMLInclude_Controller_XMLIncludeController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * Instance variable providing an array for error strings.
	 * @var Array
	 */
	private $errors;

	/**
	 * @param string $newError
	 */
	protected function addError ($message, $fileInfo = Null) {
		$this->errors[] = Array('message' => $message, 'fileInfo' => $fileInfo);
		t3lib_div::devLog('Error: ' . $message . '(' . $fileInfo . ')' , 'xmlinclude', 3);
	}



	/**
	 * Initialiser
	 *
	 * @return void
	 */
	public function initializeAction () {
		$this->errors = Array();
	}



	/**
	 * Index
	 *
	 * @return void
	 */
	public function indexAction () {
		$XML = $this->XML();
		if ($XML) {	$this->view->assign('xml', $XML->saveXML($XML->firstChild)); }
		$this->view->assign('conf', $this->settings);
		$this->view->assign('errors', $this->errors);
	}



	/**
	 * Loads and transforms XML according to settings.
	 * Returns the resulting XML document.
	 *
	 * @return DOMDocument
	 */
	protected function XML () {
		// Configure connection.
		$curlOptions = Array(
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HEADER => TRUE
		);

		// Deal with Form submission:
		// Detect forms by the formMethod parameter and use its value to submit the form.
		// The form’s fields are expected to be in the formParamters variable.
		$additionalURLParameters = Array();
		$arguments = $this->request->getArguments();
		if (array_key_exists('formParameters', $arguments)) {
			if ($arguments['formMethod'] === 'POST') {
				$curlOptions[CURLOPT_POST] = TRUE;
				$curlOptions[CURLOPT_POSTFIELDS] = $arguments['formParameters'];
			}
			else {
				// For GET requests append the additional parameters to the request URL.
				$additionalURLParameters = $arguments['formParameters'];
			}
		}

		// Send cookies
		$cookieParts = Array();
		foreach ($_COOKIE as $cookieName => $cookieContent) {
			if (in_array($cookieName, $this->settings['cookiePassthrough'])) {
				$cookieParts[] = urlencode($cookieName) . '=' . urlencode($cookieContent);
			}
		}
		$curlOptions[CURLOPT_COOKIE] = implode('; ', $cookieParts);

		// Run curl.
		$curl = curl_init();
		$curlOptions[CURLOPT_URL] = $this->remoteURL($additionalURLParameters);
		curl_setopt_array($curl, $curlOptions);
		$loadedString = curl_exec($curl);

		if ($loadedString) {
			$downloadParts = explode("\r\n\r\n", $loadedString, 2);

			// Read cookies and pass the interesting ones on to our user.
			$cookies = $this->cookiesFromHeader($downloadParts[0]);
			foreach ($cookies as $cookieName => $cookieContent) {
				// TODO: Handle path (how?), expiry etc?
				if (in_array($cookieName, $this->settings['cookiePassthrough'])) {
					setcookie($cookieName, $cookieContent['value']);
				}
			}

			// Parse file.
			$XMLString = $downloadParts[1];
			$XML = new DOMDocument();
			$parseSuccess = FALSE;

			if ($this->settings['parseAsHTML'] == 1) {
				// Assume we have UTF-8 encoding and escape based on that assumption.
				// (To work around the poor handling of encodings in DOMDocument.)
				$XMLString = mb_convert_encoding($XMLString, 'HTML-ENTITIES', "UTF-8");
				$parseSuccess = $XML->loadHTML($XMLString);
			}
			else {
				$parseSuccess = $XML->loadXML($XMLString);
			}

			if ($parseSuccess) {
				// Apply array of XSLTs.
				ksort($this->settings['XSL']);
				foreach ($this->settings['XSL'] as $XSLPath) {
					$XML = $this->transformXMLWithXSLAtPath($XML, $XSLPath);
					if (!$XML) {
						$XML = Null;
						break;
					}
				}
			}
			else {
				$this->addError('Failed to parse XML from', $this->remoteURL());
			}
		}
		else {
			$this->addError('Failed to load XML from', $this->remoteURL());
		}

		return $XML;
	}

	
	
	/**
	 * Builds the remote URL to load the XML from. Uses:
	 * * the baseURL set in the FlexForm
	 * * the URL argument
	 * * the parameters TypoScript variable
	 * * the parameters passed in $additionalURLParameters
	 *
	 * @param Array $additionalURLParameters [defaults to []]
	 * @return string 
	 */
	private function remoteURL($additionalURLParameters = Array()) {
		$arguments = $this->request->getArguments();

		$remoteURL = '';
		if (array_key_exists('URL', $arguments) && strlen($arguments['URL']) > 0) {
			// Ensure we only fetch URLs beginning with our base URL.
			if (strpos($arguments['URL'], $this->settings['baseURL']) !== 0) {
				$remoteURL .= $this->settings['baseURL'];
			}
			$remoteURL .= $arguments['URL'];
		}
		else {
			$remoteURL .= $this->settings['startURL'];
		}

		// Take parameters from the target URL and add those from the parameters TypoScript variable.
		$URLParameters = Null;
		$URLComponents = explode('?', $remoteURL, 2);
		parse_str($URLComponents[1], $URLParameters);
		$URLParameters = array_merge($URLParameters, $this->settings['URLParameters']);
		$URLParameters = array_merge($URLParameters, $additionalURLParameters);
		
		// Reassemble the URL with its new set of parameters.
		$newParameterString = http_build_query($URLParameters);
		if ($newParameterString) {
			$remoteURL = $URLComponents[0] . '?' . $newParameterString;
		}

		return $remoteURL;
	}



	/**
	 * Loads XSL from the given path and applies it to the given passed XML.
	 * Returns the trasnformed XML document.
	 *
	 * @param string $XSLPath
	 * @param DOMDocument $XML
	 * @return DOMDocument|Null transformed XML
	 */
	private function transformXMLWithXSLAtPath ($XML, $XSLPath) {
		// Let TYPO3 analyse  the path settings to resolve potential 'EXT:'.
		$processedPath = $GLOBALS['TSFE']->tmpl->getFileName($XSLPath);
		if ($processedPath) {
			$XSLPath = PATH_site . $processedPath;
		}

		// Load XSL.
		$XSLString = t3lib_div::getUrl($XSLPath);
		$XSL = new DOMDocument();
		if ($XSL->loadXML($XSLString)) {
			$XSL->documentURI = pathinfo($XSLPath, PATHINFO_DIRNAME);
			$xsltproc = new XSLTProcessor();
			$xsltproc->importStylesheet($XSL);

			// Add parameters to XSL:
			// * everything in $this->settings
			$parameters = $this->settings;

			// * URL of current page as fullPageURL
			// The fullPageURL is the current URL called by the browser wihout parameters.
			// We determine it by removing a the URL argument from the end of the page URL.
			$pageURLComponents = explode('?', $this->request->getRequestUri(), 2);
			$pageURL = $pageURLComponents[0];
			$parameters['fullPageURL'] = $pageURL;

			// * URL of current base page (RealURL corresponding to page ID) as basePageURL
			// The basePageURL is the URL of the current _page_, defined by its page ID.
			// It does not include the parameters appended to the path by RealURL.
			if ($this->settings['useRealURL'] == '1') {
				$pageURL = (t3lib_div::getIndpEnv('TYPO3_SITE_URL'));
				$pageURL .= urldecode($this->uriBuilder->buildFrontendUri());
				$pageURL = str_replace('//', '/', $pageURL);
			}
			$parameters['basePageURL'] = $pageURL;

			// * host name of target host
			$hostName = parse_url($this->settings['baseURL'], PHP_URL_HOST);
			$parameters['hostName'] = $hostName;
			$xsltproc->setParameter('', $parameters);

			// Transform the document.
			$XML = $xsltproc->transformToDoc($XML);
			if (!$XML) {
				$this->addError('Failed to apply XSL', $XSLPath);
			}
		}
		else {
			$this->addError('Failed to load XSL', $XSLPath);
			$XML = Null;
		}

		return $XML;
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
	 * @return Array
	 */
	protected function cookiesFromHeader($headerString) {
		$cookies = Array();

		$headerLines = explode("\r\n", $headerString);
		foreach ($headerLines as $headerLine) {
			$headerParts =  explode(':', $headerLine, 2);
			if (count($headerParts) === 2) {
				$headerName = trim(strtolower($headerParts[0]));
				$headerValue = trim($headerParts[1]);
				if ($headerName === 'set-cookie') {
					$cookieParts = explode(';', $headerValue);
					$cookieMainParts = explode('=', $cookieParts[0]);
					if (count($cookieMainParts) === 2) {
						$cookieName = $cookieMainParts[0];
						$cookieValue = urldecode($cookieMainParts[1]);
						$cookies[$cookieName] = Array('value' => $cookieValue);
						if (count($cookieParts) > 1) {
							$cookieOptions = array_slice($cookieParts, 1);
							foreach($cookieOptions as $cookieOption) {
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
		return $cookies;
	}
}

?>

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
		// Retrieve and load XML.
		$XMLString = t3lib_div::getUrl($this->remoteURL());
		$XML = new DOMDocument();
		$XML->loadXML($XMLString);
		if ($XML !== FALSE) {
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
			$this->addError('Failed to load XML from', $this->remoteURL());
		}

		return $XML;
	}

	
	
	/**
	 * Builds the remote URL to load the XML from. Uses:
	 * * the baseURL set in the FlexForm
	 * * the URL argument
	 * * the parameters TypoScript variable
	 * 
	 * @return string 
	 */
	private function remoteURL() {
		// Build the remote request URL from the base URL and the URL parameter.
		$arguments = $this->request->getArguments();
		$remoteURL = $this->settings['baseURL'] . $arguments['URL'];
		
		// Take parameters from the target URL and add those from the parameters TypoScript variable.
		$URLParameters = Null;
		$URLComponents = explode('?', $remoteURL, 2);
		parse_str($URLComponents[1], $URLParameters);
		$URLParameters = array_merge($URLParameters, $this->settings['URLParameters']);
		
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
			$xsltproc = new XSLTProcessor();
			$xsltproc->importStylesheet($XSL);

			// Add parameters to XSL:
			// * everything in $this->settings
			// * URL of current page as pageURL (to be used for URL building)
			// * host name of target host
			$parameters = $this->settings;
			$pageURLComponents = explode('?', $this->request->getRequestUri(), 2);
			$parameters['pageURL'] = $pageURLComponents[0];
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
}

?>

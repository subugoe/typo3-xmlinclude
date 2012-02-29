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
	 * Initialiser
	 *
	 * @return void
	 */
	public function initializeAction () {
	}



	/**
	 * Index:
	 *
	 * @return void
	 */
	public function indexAction () {
		$this->addResourcesToHead();
		$XML = $this->XML();
		if ($XML) {
			$this->view->assign('xml', $XML->saveXML());
		}
	}



	/**
	 *
	 * @param arrray $conf
	 * @return DOMDocument
	 */
	protected function XML () {
		// Retrieve XML.
		$arguments = $this->request->getArguments();
		$URL = $this->settings['baseURL'] . $arguments['URL'];
		debugster($URL);
		$XMLString = t3lib_div::getUrl($URL);
		$XML = new DOMDocument();
		$XML->loadXML($XMLString);

		if ($XML !== FALSE) {
			// Apply array of XSLTs.
			foreach ($this->settings['XSL'] as $XSLPath) {
				// Let TYPO3 try to process path settings as a path so we can use EXT: in the paths.
				$processedPath = PATH_site . $GLOBALS['TSFE']->tmpl->getFileName($XSLPath);
				if ($processedPath) {
					$XSLPath = $processedPath;
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
					$parameters = $this->settings;
					$pageURLComponents = explode('?', $this->request->getRequestUri(), 2);
					$parameters['pageURL'] = $pageURLComponents[0];
					$hostName = parse_url($this->settings['baseURL'], PHP_URL_HOST);
					$parameters['hostName'] = $hostName;
					$parameters['XSL'] ='';
					debugster($parameters);
					$xsltproc->setParameter('', $parameters);

					// Transform the document.
					$XML = $xsltproc->transformToDoc($XML);
				}
				else {
					t3lib_div::devLog('Failed to load XSL ' . $XSLPath . ', stopping.', 'xmlinclude', 3);
					break;
				}
				if (!$XML) {
					t3lib_div::devLog('Failed to apply XSL ' . $XSLPath . ', stopping.', 'xmlinclude', 3);
					break;
				}
			}
		}
		else {
			t3lib_div::devLog('Failed to load XML from ' . $URL . '.', 'xmlinclude', 3);
		}

		return $XML;
	}



	/**
	 * Helper: Inserts headers into page.
	 *
	 * @return void
	 */
	protected function addResourcesToHead () {
	}

}
?>

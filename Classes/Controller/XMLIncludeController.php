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
		foreach ( $this->settings as $key => $value ) {
			// Transfer settings to conf
			$this->conf[$key] = $value;

			if (strpos($key, 'Path') !== False) {
				// Let TYPO3 try to process path settings as a path, so we can
				// use EXT: in the paths.
				$processedPath = $GLOBALS['TSFE']->tmpl->getFileName($value);
				if ($processedPath) {
					$this->conf[$key] = $processedPath;
				}
			}
		}
	}



	/**
	 * Index:
	 *
	 * @return void
	 */
	public function indexAction () {
		debugster($this->conf);
		$this->addResourcesToHead();
		$XMLString = $this->XMLString($this->conf);
		debugster($XMLString);
		$this->view->assign('xml', $XMLString);
	}


	/**
	 *
	 * @param arrray $conf
	 * @return string
	 */
	protected function XMLString ($conf) {
		// Retrieve XML
		$URL = $conf['baseURL'] . $conf['URLPath'];
		$XMLString = file_get_contents($URL);
		debugster($XMLString);
		$XML = DOMDocument::loadXML($XMLString);
		if ($XML !== False) {
			// Transform XML
			debugster($XML);
		}
		else {

		}

		

		return $XML->saveXML();
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

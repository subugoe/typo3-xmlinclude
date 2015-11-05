<?php
namespace Subugoe\Xmlinclude\Utility;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
 *      Goettingen State Library
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * Utility for XML manipulations
 */
class XmlUtility {

	/**
	 * Static XML parsing function to be used from XSL to parse strings as XML and process them
	 *
	 * @param string \XMLString
	 * @return \DOMDocument|Boolean
	 */
	static function parseXML($string) {
		$XML = new \DOMDocument();
		// Strip leading whitespace which may get in the way of parsing.
		$strippedString = preg_replace('/^\s*/', '', $string);
		$XML->loadXML($strippedString);

		return $XML;
	}

}

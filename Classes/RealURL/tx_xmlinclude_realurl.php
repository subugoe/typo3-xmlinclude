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
class tx_xmlinclude_realurl extends \tx_realurl_advanced {
	/**
	 * RealURL splits the request’s path up in its components but we want to
	 * process all of them (as we want to map paths on a different server onto ours).
	 * As a consequence this userFunc should only be used as the _last_ one.
	 *
	 * @param    array - Parameters passed from parent object, "tx_realurl". Some values are passed by reference! (paramKeyValues, pathParts and pObj)
	 * @param    tx_realurl - Copy of parent object. Not used.
	 * @return    mixed - Depends on branching.
	 */
	public function main(array $params, \tx_realurl $parent) {
		$result = false;

		/**
		 * This function is called multiple times with different parameters.
		 * there is no clear documentation on what the differences between the
		 * different calls are but the right thing seems to happen if we do so
		 * in (typically?) the first run of the function which can be detected
		 * by the fact that $GLOBALS['TSFE']->id is not set yet.
		 *
		 * In that case we do the following:
		 * 1. return path given by $params['value'] + $params['pathParts'] as the result
		 * 2. empty the array $params['pathParts']
		 */
		if (!$GLOBALS['TSFE']->id) {
			// Grab all remaining 'pathParts' to create the full path we want.
			$result = $params['value'];
			if (count($params['pathParts']) > 0) {
				$result .= '/' . implode('/', $params['pathParts']);
				$params['pathParts'] = Array();
			}
		}

		return $result;
	}

}


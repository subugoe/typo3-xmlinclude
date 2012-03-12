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

class tx_xmlinclude_realurl {
	/**
	 * Main function, called for both encoding and deconding of URLs.
	 * Based on the "mode" key in the $params array it branches out to either decode or encode functions.
	 *
	 * RealURL splits the request’s path up in its components but we want to
	 * process all of them (as we want to map paths on a different server onto ours).
	 * As a consequence this userFunc should only be used as the _last_ one.
	 *
	 * @param	array		Parameters passed from parent object, "tx_realurl". Some values are passed by reference! (paramKeyValues, pathParts and pObj)
	 * @param	tx_realurl		Copy of parent object. Not used.
	 * @return	mixed		Depends on branching.
	 */
	public function main(array $params, tx_realurl $parent) {
		// Grab all remaining 'pathParts' to create the full path we want.
		$result = $params['value']. '/' . implode('/', $params['pathParts']);
		// Remove the remaining 'pathParts' to prevent further processing.
		array_splice($params['pathParts'], 0, count($params['pathParts']));
		
		return $result;
	}
}

?>

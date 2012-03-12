# xmlinclude TYPO3 extension

A TYPO3 extension for loading and transforming XML that is inserted into a content element.

2012 by [Sven-S. Porst](http://earthlingsoft.net/ssp/), [SUB Göttingen][http://www.sub.uni-goettingen.de] <[porst@sub.uni-goettingen.de](mailto:porst@sub.uni-goettingen.de?subject=xmlinclude%20TYPO3%20Extension)>

If you have questions or remarks please send in comments or contribute improvements. You can fork the extension’s [repository at github](https://github.com/ssp/xmlinclude).



## Requirements
To run this extension you need:

* TYPO3 ≥ 4.6.4 (not tested on earlier versions)
* with Extbase/Fluid ≥ 1.4
* the [fed](http://fedext.net/fed-viewhelpers/) extension



## Description
This extension enables inclusion of remote XML content into TYPO3 content elements. It does so in a 3 step process:

### 1. Fetch the XML
The base URL XML is fetched from can be set in the Plug-In’s FlexForm. The URL given there can be changed in two ways:

1. a path given in the tx_xmlinclude_xmlinclude[URL] parameter will be appended to the base URL
2. parameters set in the plugin.tx_xmlinclude.settings.URLParameters TypoScript array will be merged into the existing parameters of the URL

Keep in mind that fetching the file will fail if is cannot be parsed as XML.

### 2. Transform the XML
After reading and parsing the XML, it can be transformed by XSL stylesheets. Paths to those stylesheets are set in the plugin.tx_xmlinclude.settings.XSL TypoScript array. Stylesheets will be applied in the order of the keys in the array.

A scenario this works well for is the following: given an XHTML webpage you can supply a stylesheet which extracts the content you are interested in and removes the rest. Then you can apply another stylesheet which transforms the links inside the XHTML to links pointing to your TYPO3 page. This lets you include other web resources in the design and context of your web site.

### 3. Insert the transformed XML into the web page
The xmlinclude content element creates a div.xmlinclude and puts the transformed XML in there. In case there were errors during the conversion h4.error and p.error elements are inserted at the beginning of that div to indicate the problem.



## Usage

* Include the »Include XML« content element in your page
* Set the start URL for your page
* Add the »xmlinclude Settings« template to your page
* (if necessary) adjust the TypoScript settings for XSL conversion, CSS or JavaScript



## Configuration
A number of settings can be adjusted with TypoScript values inside plugin.tx_xmlinclude.settings:

* parseAsHTML [0]: If set to 1, uses the HTML parser instead of the XML parser.
* XSL [{50 = EXT:xmlinclude/Resources/Private/XSL/rewrite-urls.xsl}]: Array of paths of stylesheets that are applied to the downloaded XML. See below for a description of the stylesheet used in the default setting. All values set in TypoScript will be passed to the stylesheet as XSL parameters.
* URLParameters [{}]: Array of parameters that are added to the request URL. For example you could set plugin.tx_xmlinclude.settings.URLParameters.format = XML if the service you are reading needs a format=XML parameter that to deliver XML format.
* headCSS [{}]: Array of paths or URLs of CSS files that should be included in the page’s head using style tags.
* headJavaScript [{}]: Array of paths or URLs of stylesheets that should be included in the page’s head using script tags.
* rewriteOnClass [rewrite-on]: String with a class name used in the default stylesheet to detect a tags whose links must be rewritten.
* rewriteOffClass [rewrite-off]: String with a class name used by the default stylesheet to detect a tags whose links must not be rewritten.
* cookiePassthrough [{}]: List of strings. Cookies with those names are passed between the connection to load the XML file and the connection to the browser.
* useRealURL [0]: Use RealURL to pass URLs to the extension. Read the section on RealURL for further instructions.



## The default stylesheet rewrite-urls.xsl
The rewrite-urls.xsl stylesheet is included in the XSL processing be default. It expects to find XHTML (with the http://www.w3.org/1999/xhtml namespace) or non-namespaced content in the XML it receives and will rewrite the urls for a, form, img, script and link tags in various ways:

* URLs for `a` and `form` tags:
	* are rewritten to go through TYPO3 if they are
		* without a `target` attribute AND
		* not marked with the class name set in the `rewriteOffClass` TypoScript variable [defaults to *rewrite-on*] AND
		* relative links OR http(s) links whose host name is the same as our target site’s
	* are *not* rewritten to go through TYPO3 if they
		* do not satisfy the conditions above OR
		* are marked with the class set in the `rewriteOffClass` TypoScript variable [defaults to *rewrite-off*]
* URLs in `img`, `link` and `script` tags are prepended with the content of the base URL to create absolute links. The baseURL is determined using the baseURL setting as well as the content of html/head/base/@href, in case it exists.



## RealURL ##
You can use RealURL to transparently include the path on the remote server into your site. This is a bit unusual as we need to pass a full path through RealURL which usually splits up the path components. To deal with that this setup will use *all* remaining path components and may cause problems if other extensions add their rewritten path components as well.

To use RealURL support, first turn it on in TypoScript using:

	plugin.tx_xmlinclude.settings.useRealURL = 1

Then add the following array to the (or a relevant) `fixedPostVars` entry of your RealURL configuration (e.g. `$TYPO3_CONF_VARS['EXTCONF']['realurl']['_DEFAULT']['fixedPostVars']`):

	array (
		'xmlinclude' => array (
			array(
				'GETvar' => 'tx_xmlinclude_xmlinclude[URL]',
				'userFunc' => 'EXT:xmlinclude/Classes/RealURL/tx_xmlinclude_realurl.php:&tx_xmlinclude_realurl->main'
			)
		),
		'2' => 'xmlinclude',
	)

This creates a setup `xmlinclude` which is only used on page ID 2. Add further lines

		'3' => 'xmlinclude',
		'73' => 'xmlinclude',
		…

to enable the same rewriting for page IDs 3, 73, ….



## Version History ##

* 0.9 (2012-03-01): initial beta
* 0.9.1 (2012-03-07): iron out problems with HTML vs XML parsing
* 0.9.2 (2012-03-08): add cookie handling, add form handling for GET and POST, work around encoding issues for HTML content
* 0.9.3 (2012-03-12): improve URL rewriting, include set up for RealURL


## License ##
MIT License to keep the people happy who need it.


Copyright (C) 2012 by Sven-S. Porst

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
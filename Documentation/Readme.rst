xmlinclude TYPO3 extension
==========================

A TYPO3 extension for loading and transforming XML that is inserted into
a content element.

2012-2013 by `Sven-S. Porst <http://earthlingsoft.net/ssp/>`_, `SUB
Göttingen <http://www.sub.uni-goettingen.de>`_
<`porst@sub.uni-goettingen.de <mailto:porst@sub.uni-goettingen.de?subject=xmlinclude%20TYPO3%20Extension>`_\ >

If you have questions or remarks please send in comments or contribute
improvements. You can fork the extension’s `repository at
github <https://github.com/subugoe/xmlinclude>`_.

Requirements
------------

To run this extension you need:

-  TYPO3 ≥ 6.2.0

Description
-----------

This extension enables inclusion of remote XML content into TYPO3
content elements. It does so in a 3 step process:

1. Fetch the data
~~~~~~~~~~~~~~~~~

The base URL data is fetched from can be set in the Plug-In’s FlexForm.
The URL given there can be changed in two ways:

1. a path given in the tx\_xmlinclude\_xmlinclude[URL] parameter will be
   appended to the base URL; when `used with RealURL <#realurl>`_ this
   can be appended to the page’s path
2. parameters set in the ``plugin.tx_xmlinclude.settings.URLParameters``
   TypoScript array will be merged into the existing parameters of the
   URL

By default fetching the file will fail if it cannot be parsed as XML. In
case you want to fetch non-XML files like broken HTML, or JSON you can
`configure <#configuration>`_ a more lenient or different parsing mode.

When a blank no URL is given, no data is loaded and XSL processing is
started with a document containing just a »xmlinclude-root« node.

2. Transform the XML
~~~~~~~~~~~~~~~~~~~~

After reading and parsing the data, it can be transformed by XSL
stylesheets. Paths to those stylesheets are set in the
``plugin.tx_xmlinclude.settings.XSL`` TypoScript array. Stylesheets will
be applied in the order of the keys in the array.

A scenario this works well for is the following: given an XHTML webpage
you can supply a stylesheet which extracts the content you are
interested in and removes the rest. Then you can apply another
stylesheet which transforms the links inside the XHTML to links pointing
to your TYPO3 page. This lets you include other web resources in the
design and context of your web site.

A facility to parse additional XML which may be entered by the user in a
form field is provided as an XSL function. It is called like
``php:function('XmlUtility::parseXML', string($xml-string))``

3. Insert the transformed XML into the web page
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The xmlinclude content element creates a ``div.xmlinclude`` and puts the
transformed XML in there. In case there were errors during the
conversion ``h4.error`` and ``p.error`` elements are inserted at the
beginning of that div to indicate the problem.

Usage
-----

-  Include the »Include XML« content element in your page
-  Set the start URL for your page
-  Add the »xmlinclude Settings« template to your page
-  (if necessary) adjust the TypoScript settings for XSL conversion, CSS
   or JavaScript

Configuration
-------------

A number of settings can be adjusted with TypoScript values inside
``plugin.tx_xmlinclude.settings``:

-  ``parser`` [``xml``\ ]: The parse type used. Allowed settings are
   ``xml`` for proper XML parsing, ``html`` for unreliable HTML parsing
   which tolerates errors and ``json`` for JSON parsing followed by
   conversion to an XML document.
-  ``XSL``
   [``{50 = EXT:xmlinclude/Resources/Private/XSL/rewrite-urls.xsl}``\ ]:
   Array of paths of stylesheets that are applied to the downloaded XML.
   See below for a description of the stylesheet used in the default
   setting. All values set in TypoScript will be passed to the
   stylesheet as XSL parameters.
-  ``URLParameters`` [``{}``\ ]: Array of parameters that are added to
   the request URL. For example you could set
   ``plugin.tx_xmlinclude.settings.URLParameters.format = XML`` if the
   service you are reading needs a format=XML parameter that to deliver
   XML format.
-  ``headCSS`` [``{}``\ ]: Array of paths or URLs of CSS files that
   should be included in the page’s head using style tags.
-  ``headJavaScript`` [``{}``\ ]: Array of paths or URLs of stylesheets
   that should be included in the page’s head using script tags.
-  ``rewriteOnClass`` [``rewrite-on``\ ]: String with a class name used
   in the default stylesheet to detect a tags whose links must be
   rewritten.
-  ``rewriteOffClass`` [``rewrite-off``\ ]: String with a class name
   used by the default stylesheet to detect a tags whose links must not
   be rewritten.
-  ``cookiePassthrough`` [``{}``\ ]: List of strings. Cookies with those
   names are passed between the connection to load the XML file and the
   connection to the browser.
-  ``cookiePath`` [``.``\ ]: String. If ``.`` the current TYPO3 page’s
   path is used for the cookie. If set to a string, that string is used
   for the cookie path.
-  ``useRealURL`` [``0``\ ]: Use RealURL to pass URLs to the extension.
   Read the section on RealURL for further instructions.

The default stylesheet rewrite-urls.xsl
---------------------------------------

The rewrite-urls.xsl stylesheet is included in the XSL processing by
default. It expects to find XHTML (with the http://www.w3.org/1999/xhtml
namespace) or non-namespaced content in the XML it receives and will
rewrite the URLs for ``a``, ``form``, ``img``, ``script`` and ``link``
tags in various ways:

-  URLs for ``a`` and ``form`` tags:

   -  are rewritten to go through TYPO3 if they are

      -  without a ``target`` attribute AND
      -  not marked with the class name set in the ``rewriteOffClass``
         TypoScript variable [defaults to ``rewrite-on``] AND
      -  relative links OR http(s) links whose host name is the same as
         our target site’s

   -  are *not* rewritten to go through TYPO3 if they

      -  do not satisfy the conditions above OR
      -  are marked with the class set in the ``rewriteOffClass``
         TypoScript variable [defaults to ``rewrite-off``]

-  URLs in ``img``, ``link`` and ``script`` tags are prepended with the
   content of the base URL to create absolute links. The baseURL is
   determined using the baseURL setting as well as the content of
   ``html/head/base/@href``, in case it exists.

XSL Parameters
--------------

A number of parameters are passed to each XSL that is called by default:

-  ``argument-*``: arguments passed to the xmlinclude extension with
   name where ``*`` is a dash separated list of the key hierarchy (e.g.
   ``tx_xmlinclude_xmlinclude[formParameters][xml]`` is passed as
   ``argument-formParameters-xml``)
-  ``setting-*``: TypoScript settings in
   ``plugin.tx_xmlinclude.settings.`` where ``*`` is a dash separated
   list of the key hierarchy
-  ``fullPageURL``: the full URL of the page without parameters
-  ``basePageURL``: the URL of current base page (RealURL corresponding
   to page ID)
-  ``hostName``: the host name in the ``basePageURL``
-  ``sitePath``: full path to the site’s folder in the host’s file
   system (can be useful for loading external files from XSL)
-  custom: all parameters configured in
   ``plugin.tx_xmlinclude.settings.XSLParameters``

RealURL
-------

You can use RealURL to transparently include the path on the remote
server into the URLs on your site. This is a bit unusual as we need to
pass a full path through RealURL which usually splits up the path
components. To deal with that, this setup will use *all* remaining path
components and may cause problems if other extensions add their
rewritten path components as well.

To use RealURL support, first turn it on in TypoScript using:

::

    plugin.tx_xmlinclude.settings.useRealURL = 1

Then add the following array to the (or a relevant) ``fixedPostVars``
entry of your RealURL configuration (e.g.
``$TYPO3_CONF_VARS['EXTCONF']['realurl']['_DEFAULT']['fixedPostVars']``):

::

    array (
        'xmlinclude' => array (
            array(
                'GETvar' => 'tx_xmlinclude_xmlinclude[URL]',
                'userFunc' => 'EXT:xmlinclude/Classes/RealURL/tx_xmlinclude_realurl.php:&tx_xmlinclude_realurl->main'
            )
        ),
        '2' => 'xmlinclude',
    )

This creates a setup ``xmlinclude`` which is only used on page ID 2. Add
further lines

::

        '3' => 'xmlinclude',
        '73' => 'xmlinclude',
        …

to enable the same rewriting for page IDs 3, 73, ….

License
-------

GPL-2.0 - See LICENSE.md for details

License for Array2XML.php class
-------------------------------

This extension includes the
`Array2XML <http://www.lalit.org/lab/convert-php-array-to-xml-with-attributes/>`_
PHP class by Lalit Patel. It is licensed under the `Apache License,
Version 2.0 <http://www.apache.org/licenses/LICENSE-2.0>`_.

<?php

########################################################################
# Extension Manager/Repository config file for ext "xmlinclude".
#
# Auto generated 24-09-2012 16:42
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Include XML',
	'description' => 'Loads, transforms and includes XML',
	'category' => 'plugin',
	'version' => '1.1.0',
	'state' => 'stable',
	'author' => 'Sven-S. Porst',
	'author_email' => 'porst@sub.uni-goettingen.de',
	'author_company' => 'Göttingen State and University Library, Germany http://www.sub.uni-goettingen.de',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.6.4-0.0.0',
			'extbase' => '1.4.2-0.0.0',
			'fluid' => '1.4.1-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'dependencies' => 'extbase,fluid',
	'conflicts' => '',
	'suggests' => '',
	'priority' => '',
	'loadOrder' => '',
	'shy' => '',
	'module' => '',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'_md5_values_when_last_written' => 'a:14:{s:17:"ext_localconf.php";s:4:"e1d4";s:14:"ext_tables.php";s:4:"b06b";s:15:"README.markdown";s:4:"88e6";s:43:"Classes/Controller/XMLIncludeController.php";s:4:"fd38";s:41:"Classes/RealURL/tx_xmlinclude_realurl.php";s:4:"4a65";s:38:"Configuration/FlexForms/XMLInclude.xml";s:4:"acc9";s:34:"Configuration/TypoScript/setup.txt";s:4:"8926";s:49:"Resources/Private/Language/locallang-flexform.xml";s:4:"4e46";s:40:"Resources/Private/Language/locallang.xml";s:4:"cf90";s:49:"Resources/Private/Templates/XMLInclude/Index.html";s:4:"9dde";s:38:"Resources/Private/XSL/rewrite-urls.xsl";s:4:"9e61";s:35:"Resources/Private/XSL/test/adw.html";s:4:"96b9";s:57:"Resources/Private/XSL/test/goescholar-community-list.html";s:4:"91fc";s:42:"Resources/Private/XSL/test/goescholar.html";s:4:"8b0c";}',
);

?>
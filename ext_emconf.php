<?php

########################################################################
# Extension Manager/Repository config file for ext "xmlinclude".
#
# Auto generated 01-03-2012 19:13
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Include XML',
	'description' => 'Loads, transforms and includes XML',
	'category' => 'plugin',
	'version' => '0.9.0',
	'state' => 'beta',
	'author' => 'Sven-S. Porst',
	'author_email' => 'porst@sub.uni-goettingen.de',
	'author_company' => 'Göttingen State and University Library, Germany http://www.sub.uni-goettingen.de',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.6.4-0.0.0',
			'extbase' => '1.4.2-0.0.0',
			'fluid' => '1.4.1-0.0.0',
			'fed' => '1.4.11-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'dependencies' => 'extbase,fluid,fed',
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
	'_md5_values_when_last_written' => 'a:14:{s:15:"README.markdown";s:4:"d035";s:17:"ext_localconf.php";s:4:"e1d4";s:14:"ext_tables.php";s:4:"590d";s:43:"Classes/Controller/XMLIncludeController.php";s:4:"a12d";s:38:"Configuration/FlexForms/XMLInclude.xml";s:4:"d763";s:38:"Configuration/TypoScript/constants.txt";s:4:"d41d";s:34:"Configuration/TypoScript/setup.txt";s:4:"325f";s:49:"Resources/Private/Language/locallang-flexform.xml";s:4:"4044";s:40:"Resources/Private/Language/locallang.xml";s:4:"abb9";s:49:"Resources/Private/Templates/XMLInclude/Index.html";s:4:"8348";s:38:"Resources/Private/XSL/rewrite-urls.xsl";s:4:"eba3";s:35:"Resources/Private/XSL/test/adw.html";s:4:"96b9";s:57:"Resources/Private/XSL/test/goescholar-community-list.html";s:4:"91fc";s:42:"Resources/Private/XSL/test/goescholar.html";s:4:"8b0c";}',
);

?>
<?php

$EM_CONF[$_EXTKEY] = array(
	'title' => 'xmlinclude',
	'description' => 'Loads, transforms and includes XML',
	'category' => 'plugin',
	'version' => '0.9.0',
	'state' => 'alpha',
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
);

?>
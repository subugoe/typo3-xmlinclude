<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

// Register plug-in to be listed in the backend.
// The dispatcher is configured in ext_localconf.php.
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Subugoe.'.$_EXTKEY,
    'xmlinclude',
    'Include XML'
);

// Add flexform for both plug-ins.
$plugInFlexForms = [
    [
        'plugIn' => 'xmlinclude',
        'flexForm' => 'XMLInclude',
    ],
];

$extensionName = strtolower(\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY));

foreach ($plugInFlexForms as $plugInFlexFormInfo) {
    $fullPlugInName = $extensionName.'_'.$plugInFlexFormInfo['plugIn'];
    $TCA['tt_content']['types']['list']['subtypes_addlist'][$fullPlugInName] = 'pi_flexform';
    $flexFormPath = 'FILE:EXT:'.$_EXTKEY.
        '/Configuration/FlexForms/'.$plugInFlexFormInfo['flexForm'].'.xml';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($fullPlugInName, $flexFormPath);
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript',
    'xmlinclude Settings');

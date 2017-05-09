<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Subugoe.'.$_EXTKEY,
    'xmlinclude',
    [
        'Include' => 'index',
    ],
    [
        'Include' => 'index',
    ]
);

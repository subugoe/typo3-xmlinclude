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


if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}


// Register plug-in to be listed in the backend.
// The dispatcher is configured in ext_localconf.php.
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Subugoe.' . $_EXTKEY,
    'xmlinclude', // Name used internally by Typo3
    'Include XML' // Name shown in the backend dropdown field.
);


// Add flexform for both plug-ins.
$plugInFlexForms = [
    [
        'plugIn' => 'xmlinclude',
        'flexForm' => 'XMLInclude'
    ],
];

$extensionName = strtolower(\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY));

foreach ($plugInFlexForms as $plugInFlexFormInfo) {
    $fullPlugInName = $extensionName . '_' . $plugInFlexFormInfo['plugIn'];
    $TCA['tt_content']['types']['list']['subtypes_addlist'][$fullPlugInName] = 'pi_flexform';
    $flexFormPath = 'FILE:EXT:' . $_EXTKEY .
        '/Configuration/FlexForms/' . $plugInFlexFormInfo['flexForm'] . '.xml';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($fullPlugInName, $flexFormPath);
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript',
    'xmlinclude Settings');

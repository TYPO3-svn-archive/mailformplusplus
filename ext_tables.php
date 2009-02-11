<?php
/**
 * ext tables config file for ext: "mailformplusplus"
 * 
 * @author Reinhard F�hricht <rf@typoheads.at>

 * @package	F3_MailformPlusPlus
 */

if (!defined ('TYPO3_MODE')) die ('Access denied.');



# dynamic flexform
include_once(t3lib_extMgm::extPath($_EXTKEY).'/Resources/PHP/class.tx_dynaflex.php');

t3lib_div::loadTCA("tt_content");
// Add flexform field to plugin options
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY."_pi1"]='pi_flexform';



// Add flexform DataStructure
t3lib_extMgm::addPiFlexFormValue($_EXTKEY."_pi1", 'FILE:EXT:' . $_EXTKEY . '/Resources/XML/flexform_ds.xml');


if (TYPO3_MODE=="BE")   {
	//t3lib_extMgm::addModule('web','txtestingmoduleM1','',t3lib_extMgm::extPath($_EXTKEY).'mod1/');
    t3lib_extMgm::addModule("web","txmailformplusplusmoduleM1","",t3lib_extMgm::extPath($_EXTKEY)."Classes/Controller/Module/");
}


t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/Settings/', 'Settings');
t3lib_extMgm::addPlugin(array('MailformPlusPlus', $_EXTKEY."_pi1"), 'list_type');
t3lib_extMgm::addPlugin(array('MailformPlusPlus Listing', $_EXTKEY."_pi2"), 'list_type');
?>
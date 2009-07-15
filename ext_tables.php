<?php
/**
 * ext tables config file for ext: "mailformplusplus"
 *
 * @author Reinhard Führicht <rf@typoheads.at>

 * @package	F3_MailformPlusPlus
 */
 
 /**
	\mainpage 	
	
	 @version V1.0.0 Beta

	Released under the terms of the GNU General Public License version 2 as published by
	the Free Software Foundation.
	
	The swiss army knife for all kinds of mailforms, completely new written using the MVC concept. 
	Result: Flexibility, Flexibility, Flexibility. MailformPlus MVC is a total redesign of the getting-old
	MailformPlus (aka th_mailformplus). MailformPlus MVC has now a new core, new architecture, new features.

	Beside the reach set of features provided by MailformPlus MVC, you may like the flexibility in the sense
	of possible different configuration. Projects have all their own specificities. One customer want this 
	component while the other one want to have this other one. I think it is very challenging to come up 
	with an extension that is features reach without overloading the code basis.
	
	MailformPlus MVC solves the problem by having a very modular approach. The extension is piloted 
	mainly by some nice TypoScript where is is possible to define exactly what to implement. You may
	want to play with some interceptor, finisher, logger, validators etc... For more information,
	you should have a look into the folder "Examples" of the extension which refers many interesting samples.
	
	When installing the extension, you will notice it has a dependency to "gimmefive". MailformPlus MVC
	definitely looks into the future as it integrate some bricks from the branch v5 of TYPO3. For the 
	moment, "gimmefive" is a transition framework for us. Our plans is to upgrade our code to use "ExtBase" 
	eventually when this one will be enough stable.
	
	Latest development version on
	http://forge.typo3.org/repositories/show/extension-mailformplusplus
	  
 */

if (!defined ('TYPO3_MODE')) die ('Access denied.');

if (TYPO3_MODE == 'BE')   {

	# dynamic flexform
	include_once(t3lib_extMgm::extPath($_EXTKEY) . '/Resources/PHP/class.tx_dynaflex.php');
	
	t3lib_div::loadTCA('tt_content');
	
	// Add flexform field to plugin options
	$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_pi1'] = 'pi_flexform';
	
	if(!is_object($GLOBALS['BE_USER'])) {
		$GLOBALS['BE_USER'] = t3lib_div::makeInstance('t3lib_beUserAuth');
		
		// New backend user object
		$GLOBALS['BE_USER']->start(); // Object is initialized
		$GLOBALS['BE_USER']->backendCheckLogin(); 
		$GLOBALS['BE_USER']->fetchGroupData();
	}
	
	$file = 'FILE:EXT:' . $_EXTKEY . '/Resources/XML/flexform_ds.xml';

	$tsConfig = t3lib_BEfunc::getModTSconfig(0, 'plugin.F3_MailformPlusPlus');
	$tsConfig = $tsConfig['properties'];
	if($tsConfig['flexformFile']) {
		$file = $tsConfig['flexformFile'];
	}
	
	// Add flexform DataStructure
	t3lib_extMgm::addPiFlexFormValue($_EXTKEY . '_pi1', $file);

	t3lib_extMgm::addModule('web', 'txmailformplusplusmoduleM1', '', t3lib_extMgm::extPath($_EXTKEY) . 'Classes/Controller/Module/');
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_mailformplusplus_wizicon'] = t3lib_extMgm::extPath($_EXTKEY) . 'Resources/PHP/class.tx_mailformplusplus_wizicon.php';
} elseif($GLOBALS['TSFE']->id) {

	$sysPageObj = t3lib_div::makeInstance('t3lib_pageSelect');
	
	if(!$GLOBALS['TSFE']->sys_page) {
		$GLOBALS['TSFE']->sys_page = $sysPageObj;
	}
	
	$rootLine = $sysPageObj->getRootLine($GLOBALS['TSFE']->id);
	$TSObj = t3lib_div::makeInstance('t3lib_tsparser_ext');
	$TSObj->tt_track = 0;
	$TSObj->init();
	$TSObj->runThroughTemplates($rootLine);
	$TSObj->generateConfig();
	if(!$TSObj->setup['plugin.']['F3_MailformPlusPlus.']['userFunc']) {
		t3lib_div::debug('No static template found! Make sure to include "Settings (mailformplusplus)" in your TypoScript template!');
	}

}

t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/Settings/', 'Settings');
t3lib_extMgm::addPlugin(array('MailformPlus MVC', $_EXTKEY . '_pi1'), 'list_type');
t3lib_extMgm::addPlugin(array('MailformPlus MVC Listing', $_EXTKEY . '_pi2'), 'list_type');
?>
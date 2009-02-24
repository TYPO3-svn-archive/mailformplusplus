<?php
/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */


require_once(PATH_t3lib."class.t3lib_page.php");
require_once(PATH_t3lib."class.t3lib_tsparser_ext.php");

/**
 * Flexform class for MailformPlusPlus spcific needs
 *
 * @author Thomas Hempel <thomas@typo3-unleashed.net>
 * @author Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Resources
 */
class tx_dynaflex_mailformplusplus {

	/**
	 * Sets the items for the "Type" dropdown.
	 *
	 * @param array $config
	 * @return array The config including the items for the dropdown
	 * @author Reinhard Führicht
	 */
	function addFields_controller($config) {
		$optionList[0] = array(0 => "Single Page", 1 => "F3_MailformPlusPlus_Controller_Default");
		$optionList[1] = array(0 => "Multi Page", 1 => "F3_MailformPlusPlus_Controller_Multistep");
		$config['items'] = $optionList;
		return $config;
	}
	
	/**
	 * Sets the items for the "Predefined" dropdown.
	 *
	 * @param array $config
	 * @return array The config including the items for the dropdown
	 * @author Reinhard Führicht
	 */
	function addFields_predefined ($config) {
		
		global $LANG;

		$ts = $this->loadTS($config['row']['pid']);

		# no config available
		# TODO: OBSOLETE CODE SINCE A DEFAULT CONFIG IS LOADED. WAITING A BIT BEFORE DELETING THIS
		#if (!is_array($ts['plugin.']['F3_MailformPlusPlus.']['settings.']['predef.']) || sizeof($ts['plugin.']['F3_MailformPlusPlus.']['settings.']['predef.']) == 0) {
		#	$optionList[] = array(0 => $LANG->sL('LLL:EXT:mailformplusplus/Resources/Language/locallang_db.xml:be_missing_config'), 1 => '');
		#	return $config['items'] = array_merge($config['items'],$optionList);
		#}

		$predef = array();
		# for each view
		foreach($ts['plugin.']['F3_MailformPlusPlus.']['settings.']['predef.'] as $key=>$view) {

			if (is_array($view)) {
					$beName = $view['name'];
					if (!$predef[$key]) $predef[$key] = $beName;
			}
		}
		
		$optionList = array();
		# TODO: OBSOLETE CODE SINCE A DEFAULT CONFIG IS LOADED. WAITING A BIT BEFORE DELETING THIS
		#$optionList[] = array(0 => $LANG->sL('LLL:EXT:mailformplusplus/Resources/Language/locallang_db.xml:be_please_select'), 1 => '');
		foreach($predef as $k => $v) {
			$optionList[] = array(0 => $v, 1 => $k);
		}
		$config['items'] = array_merge($config['items'],$optionList);
		return $config;
	}

	/**
	 * Loads the TypoScript for the current page
	 *
	 * @param int $pageUid
	 * @return array The TypoScript setup
	 * @author Reinhard Führicht
	 */
	function loadTS($pageUid) {
		$sysPageObj = t3lib_div::makeInstance('t3lib_pageSelect');
		$rootLine = $sysPageObj->getRootLine($pageUid);
		$TSObj = t3lib_div::makeInstance('t3lib_tsparser_ext');
		$TSObj->tt_track = 0;
		$TSObj->init();
		$TSObj->runThroughTemplates($rootLine);
		$TSObj->generateConfig();
		return $TSObj->setup;
	}
}

?>

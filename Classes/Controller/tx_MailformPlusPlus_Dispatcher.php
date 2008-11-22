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

require_once (t3lib_extMgm::extPath('gimmefive') . 'Classes/Component/F3_GimmeFive_Component_Manager.php');


require_once(PATH_tslib.'class.tslib_pibase.php');
 
/**
 * The Dispatcher instatiates the Component Manager and delegates the process to the given controller.
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Controller
 */
class tx_MailformPlusPlus_Dispatcher extends tslib_pibase {
	
	/**
	 * Adds JavaScript for xajax and registers callable methods.
	 * Passes AJAX requests to requested methods.
	 *
	 * @return void
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function handleAjax() {
		if(t3lib_extMgm::isLoaded('xajax')) {
			require (t3lib_extMgm::extPath('xajax') . 'class.tx_xajax.php');
			$this->xajax = t3lib_div::makeInstance('tx_xajax');
			$this->xajax->statusMessagesOn();
			$this->xajax->debugOn();
			$this->prefixId = "F3_MailformPlusPlus";
			$view = $this->componentManager->getComponent("F3_MailformPlusPlus_View_Default");
			$this->xajax->registerFunction(array($this->prefixId.'_removeUploadedFile', &$view, 'removeUploadedFile'));
			$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] = $this->xajax->getJavascript(t3lib_extMgm::siteRelPath('xajax'));
			$this->xajax->processRequests();
		}
	}
	
	/**
	 * Main method of the dispatcher. This method is called as a user function.
	 *
	 * @return string rendered view
	 * @param string $content
	 * @param array $setup The TypoScript config
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	public function main($content, $setup) {
		
		$this->pi_USER_INT_obj = 1;
		$this->componentManager = F3_GimmeFive_Component_Manager::getInstance();
		$componentManager = $this->componentManager;
		
		//handle AJAX stuff
		$this->handleAjax();
		
		//init flexform
		$this->pi_initPIflexForm();
		
		/*
		 * set controller:
		 * 1. Flexform
		 * 2. TypoScript
		 * 3. Default controller
		 */
		$controller = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'controller','sDEF');
		if(!$controller) {
			$controller = $setup['controller'];	
		}
		if(!$controller) {
			$controller = "F3_MailformPlusPlus_Controller_Default";
		}
		$controller = $componentManager->getComponent($controller);
		
		/*
		 * Parse values from flexform:
		 * - Template file
		 * - Translation file
		 * - Predefined form
		 * - E-mail settings
		 * - Required fields
		 * - Redirect page
		 */
		$templateFile = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template_file','sDEF');
		$langFile = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'lang_file','sDEF');
		$predef = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'predefined','sDEF');
		$emailSettings = $this->parseEmailSettings();
		$redirect = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'redirect_page','sMISC');
		$requiredFields = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'required_fields','sMISC');
		if(strlen($requiredFields) > 0) {
			$requiredFields = t3lib_div::trimExplode(",",$requiredFields);
			$controller->setRequiredFields($requiredFields);
		}
		if(strlen($redirect) > 0) {
			$controller->setRedirectPage($redirect);
		}
		
		if (isset($content)) {
			$controller->setContent($componentManager->getComponent('F3_MailformPlusPlus_Content', $content));
		}
		$controller->setEmailSettings($emailSettings);
		if(strlen($templateFile) > 0) {
			$controller->setTemplateFile($templateFile);
		}
		if(strlen($langFile) > 0) {
			$controller->setLangFile($langFile);
		}
		if(strlen($predef) > 0) {
			$controller->setPredefined($predef);
		}
		return $controller->process();
	}
	
	/**
	 * Parses the email settings in flexform and stores them in an array.
	 *
	 * @return array The parsed email settings
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function parseEmailSettings() {
		$emailSettings = array();
		
		//*************************
		//ADMIN settings
		//*************************
		$emailSettings['admin'] = array();
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'email_to','sEMAILADMIN');
		if(strlen($value) > 0) {
			$emailSettings['admin']['to_email'] = $value;
		}
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'email_subject','sEMAILADMIN');
		if(strlen($value) > 0) {
			$emailSettings['admin']['subject'] = $value;
		}
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'email_sender','sEMAILADMIN');
		if(strlen($value) > 0) {
			$emailSettings['admin']['sender_email'] = $value;
		}
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'email_sendername','sEMAILADMIN');
		if(strlen($value) > 0) {
			$emailSettings['admin']['sender_name'] = $value;
		}
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'email_replyto','sEMAILADMIN');
		if(strlen($value) > 0) {
			$emailSettings['admin']['replyto_email'] = $value;
		}
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'email_replytoname','sEMAILADMIN');
		if(strlen($value) > 0) {
			$emailSettings['admin']['replyto_name'] = $value;
		}
		
		//*************************
		//USER settings
		//*************************
		$emailSettings['user'] = array();
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'email_touser','sEMAILUSER');
		if(strlen($value) > 0) {
			$emailSettings['user']['to_email'] = $value;
		}
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'email_subjectuser','sEMAILUSER');
		if(strlen($value) > 0) {
			$emailSettings['user']['subject'] = $value;
		}
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'email_senderuser','sEMAILUSER');
		if(strlen($value) > 0) {
			$emailSettings['user']['sender_email'] = $value;
		}
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'email_sendernameuser','sEMAILUSER');
		if(strlen($value) > 0) {
			$emailSettings['user']['sender_name'] = $value;
		}
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'email_replytouser','sEMAILUSER');
		if(strlen($value) > 0) {
			$emailSettings['user']['replyto_email'] = $value;
		}
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'email_replytonameuser','sEMAILUSER');
		if(strlen($value) > 0) {
			$emailSettings['user']['replyto_name'] = $value;
		}
		return $emailSettings;
	}
}	
?>
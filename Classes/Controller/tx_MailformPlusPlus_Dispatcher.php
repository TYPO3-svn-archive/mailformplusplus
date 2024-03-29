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
 *
 * $Id$
 *                                                                        */

require_once (t3lib_extMgm::extPath('mailformplusplus') . 'Classes/Component/F3_GimmeFive_Component_Manager.php');


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
	 */
	protected function handleAjax() {
		if(t3lib_extMgm::isLoaded('xajax', 0) && !class_exists('tx_xajax') && !$this->xajax) {
			require_once(t3lib_extMgm::extPath('xajax') . 'class.tx_xajax.php');
				
				
		}
		if (!$this->xajax && class_exists('tx_xajax')) {
			$view = $this->componentManager->getComponent('F3_MailformPlusPlus_View_Form');

			$this->xajax = t3lib_div::makeInstance('tx_xajax');
			$this->xajax->decodeUTF8InputOn();
			$this->prefixId = 'F3_MailformPlusPlus';
			$this->xajax->setCharEncoding('utf-8');
			#$this->xajax->setWrapperPrefix($this->prefixId);
				
			$this->xajax->registerFunction(array($this->prefixId . '_removeUploadedFile', &$view, 'removeUploadedFile'));
			
			// Do you wnat messages in the status bar?
			$this->xajax->statusMessagesOn();
			
			// Turn only on during testing
			$this->xajax->debugOff();
			
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
	 */
	public function main($content, $setup) {

		$this->pi_USER_INT_obj = 1;
		$this->componentManager = F3_GimmeFive_Component_Manager::getInstance();

		F3_MailformPlusPlus_StaticFuncs::$cObj = $this->cObj;

		//handle AJAX stuff
		$this->handleAjax();

		//init flexform
		$this->pi_initPIflexForm();

		/*
		 * set controller:
		 * 1. Default controller
		 * 2. TypoScript
		 */
		$controller = 'F3_MailformPlusPlus_Controller_Form';
		if($setup['controller']) {
			$controller = $setup['controller'];
		}
		
		//F3_MailformPlusPlus_StaticFuncs::debugMessage('using_controller', $controller);
		$controller = F3_MailformPlusPlus_StaticFuncs::prepareClassName($controller);
		$controller = $this->componentManager->getComponent($controller);

		/*
		 * Parse values from flexform:
		 * - Template file
		 * - Translation file
		 * - Predefined form
		 * - E-mail settings
		 * - Required fields
		 * - Redirect page
		 */
		$templateFile = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template_file', 'sDEF');
		$langFile = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'lang_file', 'sDEF');
		$predef = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'predefined', 'sDEF');

		if (isset($content)) {
			$controller->setContent($this->componentManager->getComponent('F3_MailformPlusPlus_Content', $content));
		}
		if(strlen($templateFile) > 0) {
			$controller->setTemplateFile($templateFile);
		}
		if(strlen($langFile) > 0) {
			$controller->setLangFile($langFile);
		}
		if(strlen($predef) > 0) {
			$controller->setPredefined($predef);
		}
		//try {
			$result = $controller->process();
		//} catch(Exception $e) {
		//	$result = '<div style="color:red; font-weight: bold">Caught exception: ' . $e->getMessage() . '</div>';
		//}
		return $result;
	}
}
?>
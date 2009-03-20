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
 * $Id: F3_MailformPlusPlus_AbstractValidator.php 17657 2009-03-10 11:17:52Z reinhardfuehricht $
 *                                                                        */

/**
 * Abstract class for validators for MailformPlusPlus
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Validator
 */
abstract class F3_MailformPlusPlus_AbstractErrorCheck {

	/**
	 * The GimmeFive component manager
	 *
	 * @access protected
	 * @var F3_GimmeFive_Component_Manager
	 */
	protected $componentManager;

	/**
	 * The global MailformPlusPlus configuration
	 *
	 * @access protected
	 * @var F3_MailformPlusPlus_Configuration
	 */
	protected $configuration;

	/**
	 * The GET/POST parameters
	 *
	 * @access protected
	 * @var array
	 */
	protected $gp;

	/**
	 * The cObj to render TypoScript objects
	 *
	 * @access protected
	 * @var array
	 */
	protected $cObj;

	/**
	 * The constructor for an interceptor setting the component manager and the configuration.
	 *
	 * @param F3_GimmeFive_Component_Manager $componentManager
	 * @param F3_MailformPlusPlus_Configuration $configuration
	 * @return void
	 */
	public function __construct(F3_GimmeFive_Component_Manager $componentManager, F3_MailformPlusPlus_Configuration $configuration) {
		$this->componentManager = $componentManager;
		$this->configuration = $configuration;
		if($GLOBALS['TSFE']->id) {
			$this->cObj = F3_MailformPlusPlus_StaticFuncs::$cObj;
		}
	}

	/**
	 * Performs the specific error check.
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @param array &$gp The current GET/POST parameters
	 * @return string The error string
	 */
	abstract public function check(&$check,$name,&$gp);

	
	/**
	 * Sets the suitable string for the checkFailed message parsed in view.
	 *
	 * @param array $check The parsed check settings
	 * @return string The check failed string
	 */
	protected function getCheckFailed($check) {
		$checkFailed = $check['check'];
		if(is_array($check['params'])) {
			$checkFailed .= ";";
			foreach($check['params'] as $key=>$value) {
				$checkFailed .= $key."::".$value.";";
			}
			$checkFailed = substr($checkFailed,0,strlen($checkFailed)-1);
		}
		return $checkFailed;
	}

}
?>

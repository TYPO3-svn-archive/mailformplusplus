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

/**
 * Abstract class for Finisher Classes used by MailformPlusPlus
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Finisher
 * @abstract
 */
abstract class F3_MailformPlusPlus_AbstractFinisher {
	
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
     * The cObj 
     * 
     * @access protected
     * @var tslib_cObj
     */
	protected $cObj;
	
	/**
     * The settings array passed to the finisher.
     * 
     * @access protected
     * @var array
     */
	protected $settings;
	
	/**
     * The constructor for a finisher setting the component manager, configuration and the repository.
     * 
     * @param F3_GimmeFive_Component_Manager $componentManager
     * @param F3_MailformPlusPlus_Configuration $configuration
     * @param F3_DataProvider_Repository $repository
     * @return void
     */
	public function __construct(F3_GimmeFive_Component_Manager $componentManager, F3_MailformPlusPlus_Configuration $configuration) {
		$this->componentManager = $componentManager;
		$this->configuration = $configuration;
		
		//make cObj instance for pageLink creation
		#$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		#$this->cObj->setCurrentVal($GLOBALS['TSFE']->id);
		$this->cObj = F3_MailformPlusPlus_StaticFuncs::$cObj;
		
	}
	
	/**
     * The main method called by the controller
     * 
     * @return array The probably modified GET/POST parameters
     */
	abstract public function process();
	
	/**
     * Method to set GET/POST for this class and load the configuration
     * 
     * @param array The GET/POST values
     * @param array The TypoScript configuration
     * @return void
     */
	public function loadConfig($gp,$tsConfig) {
		$this->settings = $tsConfig;
		$this->gp = $gp;
	}
	
	/**
     * Method to define whether the config is valid or not. If no, display a warning on the frontend.
     * The default value is TRUE. This up to the finisher to overload this method
     * 
     */
	public function validateConfig() {
		
	}
	
}
?>

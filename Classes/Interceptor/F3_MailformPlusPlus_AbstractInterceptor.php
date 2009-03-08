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

/**
 * Abstract interceptor class
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Interceptor
 * @abstract
 */
abstract class F3_MailformPlusPlus_AbstractInterceptor {
	
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
     * The constructor for an interceptor setting the component manager and the configuration.
     * 
     * @param F3_GimmeFive_Component_Manager $componentManager
     * @param F3_MailformPlusPlus_Configuration $configuration
     * @return void
     */
	public function __construct(F3_GimmeFive_Component_Manager $componentManager, F3_MailformPlusPlus_Configuration $configuration) {
		$this->componentManager = $componentManager;
		$this->configuration = $configuration;
		$this->cObj = F3_MailformPlusPlus_StaticFuncs::$cObj;
	}
	
	/**
     * The main method called by the controller
     * 
     * @param array $gp The GET/POST parameters
     * @param array $settings The defined TypoScript settings for the finisher
     * @return array The probably modified GET/POST parameters
     */
	abstract public function process($gp,$settings);
	
}
?>

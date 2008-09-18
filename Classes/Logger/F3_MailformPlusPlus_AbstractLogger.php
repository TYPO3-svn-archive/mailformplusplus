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
 * Abstract class for loggers
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Logger
 * @abstract
 */
abstract class F3_MailformPlusPlus_AbstractLogger {
	
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
     * The constructor for an interceptor setting the component manager and the configuration.
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @param F3_GimmeFive_Component_Manager $componentManager
     * @param F3_MailformPlusPlus_Configuration $configuration
     * @return void
     */
	public function __construct(F3_GimmeFive_Component_Manager $componentManager, F3_MailformPlusPlus_Configuration $configuration) {
		$this->componentManager = $componentManager;
		$this->configuration = $configuration;
	}
	
	/**
     * Logs the given values.
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @param array $gp The current GET/POST parameters
     * @param array $settings The settings for the logger
     * @return void
     */
	abstract public function log($gp,$settings);
	
}
?>
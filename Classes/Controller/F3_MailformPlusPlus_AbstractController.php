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
 * Abstract class for Controller Classes used by MailformPlusPlus.
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Controller
 * @abstract
 */
abstract class F3_MailformPlusPlus_AbstractController implements F3_MailformPlusPlus_ControllerInterface {
	
	/**
     * The content returned by the controller
     * 
     * @access protected
     * @var F3_MailformPlusPlus_Content
     */
	protected $content;
	
	/**
     * The key of a possibly selected predefined form
     * 
     * @access protected
     * @var string
     */
	protected $predefined;
	
	/**
     * The path to a possibly selected translation file
     * 
     * @access protected
     * @var string
     */
	protected $langFile;
	
	/**
     * An array of possibly entered required fields
     * 
     * @access protected
     * @var array
     */
	protected $requiredFields;
	
	/**
     * An array with email settings entered by the user
     * 
     * @access protected
     * @var array
     */
	protected $emailSettings;
	
	/**
     * The page ID to redirect to, the user may have entered in the plugin record.
     * 
     * @access protected
     * @var array
     */
	protected $redirectPage;
	
	/**
     * Sets the content attribute of the controller
     * 
     * @param F3_MailformPlusPlus_Content $content
     * @author Reinhard Führicht <rf@typoheads.at>
     * @return void
     */
	public function setContent($content) {
		$this->content = $content;
	}
	
	/**
     * Returns the content attribute of the controller
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @return F3_MailformPlusPlus_Content
     */
	public function getContent() {
		return $this->content;
	}
	
	/**
     * Sets the internal attribute "predefined"
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @param string $key
     * @return void
     */
	public function setPredefined($key) {
		$this->predefined = $key;
	}
	
	/**
     * Sets the internal attribute "redirectPage"
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @param integer $new
     * @return void
     */
	public function setRedirectPage($new) {
		$this->redirectPage = $new;
	}
	
	/**
     * Sets the internal attribute "requiredFields"
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @param array $new
     * @return void
     */
	public function setRequiredFields($new) {
		$this->requiredFields = $new;
	}
	
	/**
     * Sets the internal attribute "langFile"
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @param string $langFile
     * @return void
     */
	public function setLangFile($langFile) {
		$this->langFile = $langFile;
	}
	
	/**
     * Sets the internal attribute "emailSettings"
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @param array $new
     * @return void
     */
	public function setEmailSettings($new) {
		$this->emailSettings = $new;
	}
	
	/**
     * Returns the right settings for the mailformplusplus (Checks if predefined form was selected)
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @return array The settings
     */
	public function getSettings() {
		$settings = $this->configuration->getSettings();
		
		if($this->predefined) {
			
			
			$settings = $settings['predef.'][$this->predefined];
		}
		return $settings;
	}
}
?>
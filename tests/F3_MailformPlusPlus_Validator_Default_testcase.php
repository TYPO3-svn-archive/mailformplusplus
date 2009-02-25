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

/**
 * Test for the Component "F3_MailformPlusPlus_Logger_DB" of the extension 'mailformplusplus'
 *
 * @package	F3_MailformPlusPlus
 * @subpackage	Tests
 */
class F3_MailformPlusPlus_Validator_Default_testcase extends PHPUnit_Framework_TestCase {

	protected $components;
	protected $validator;

	protected function setUp() {
		$this->componentManager = F3_GimmeFive_Component_Manager::getInstance();
		$this->validator = $this->componentManager->getComponent("F3_MailformPlusPlus_Validator_Default");
	}

	protected function tearDown() {
		unset($this->validator);
		unset($this->componentManager);
	}
	
	public function test_required() {
		$fakeGp = array();
		$fakeGp['lastname'] = "Test";
		
		$fakeSettings = array();
		$fakeSettings['fieldConf.']['lastname.']['errorCheck.'][1] = "required";
		$errors = array();
		
		//field is filled out
		$this->assertTrue($this->validator->validate($fakeGp,$fakeSettings,$errors));
		
		$fakeGp['lastname'] = "";
		
		//field is not filled out
		$this->assertFalse($this->validator->validate($fakeGp,$fakeSettings,$errors));
		
	}
	
	public function test_email() {
		$fakeGp = array();
		$fakeGp['email'] = "email@host.com";
		
		$fakeSettings = array();
		$fakeSettings['fieldConf.']['email.']['errorCheck.'][1] = "email";
		$errors = array();
		
		//valid email
		$this->assertTrue($this->validator->validate($fakeGp,$fakeSettings,$errors));
		
		$fakeGp['email'] = "!%&$/@$/$%&.com";
		
		//invalid characters
		$this->assertFalse($this->validator->validate($fakeGp,$fakeSettings,$errors));
		
		$fakeGp['email'] = "ajksdhf";
		
		//invalid syntax
		$this->assertFalse($this->validator->validate($fakeGp,$fakeSettings,$errors));
		
		$fakeGp['email'] = "ajksdhf.com";
		
		//invalid syntax 2
		$this->assertFalse($this->validator->validate($fakeGp,$fakeSettings,$errors));
		
		$fakeGp['email'] = "aasd@ajksdhfcom";
		
		//invalid syntax 3
		$this->assertFalse($this->validator->validate($fakeGp,$fakeSettings,$errors));
		
		$fakeGp['email'] = "aasd@127.0.0.1";
		
		//valid syntax
		$this->assertFalse($this->validator->validate($fakeGp,$fakeSettings,$errors));
		
	}

	
}
?>
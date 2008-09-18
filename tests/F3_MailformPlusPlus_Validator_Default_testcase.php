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
 * Test for the Component "F3_MailformPlusPlus_Validator_Default" of the extension 'mailformplusplus'
 *
 * @package	F3_MailformPlusPlus
 * @subpackage	Tests
 */
class F3_MailformPlusPlus_Validator_Default_testcase extends PHPUnit_Framework_TestCase {

	protected $componentManager;
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
		$fakeGp['firstname'] = "";
		$fakeSettings = array();
		$fakeSettings['fieldConf.']['firstname.']['errorCheck.']['1'] = "required";
		$errors = array();
		$res = $this->validator->validate($fakeGp,$fakeSettings,$errors);
		$this->assertFalse($res);
		$expectedErrors = array(
			"firstname" => array(
				"required"
			)
		);
		$this->assertEquals($errors,$expectedErrors);
		$fakeGp['firstname'] = "something";
		$errors = array();
		$res = $this->validator->validate($fakeGp,$fakeSettings,$errors);
		$this->assertTrue($res);
		
		
	}
	
	public function test_email() {
		
	}

	
}
?>
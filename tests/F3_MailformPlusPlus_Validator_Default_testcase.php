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

require_once (t3lib_extMgm::extPath('mailformplusplus') . 'Classes/Component/F3_GimmeFive_Component_Manager.php');

/**
 * Test for the Component "F3_MailformPlusPlus_Logger_DB" of the extension 'mailformplusplus'
 *
 * @package	F3_MailformPlusPlus
 * @subpackage	Tests
 */
class F3_MailformPlusPlus_Validator_Default_testcase extends tx_phpunit_testcase {

	/**
	 *
	 * @var String
	 */
	protected $message = 'Tested value:';
	/**
	 *
	 * @var F3_GimmeFive_Component_Manager
	 */
	protected $components;
	/**
	 *
	 * @var F3_MailformPlusPlus_Validator_Default
	 */
	protected $validator;

	protected function setUp() {
		$this->componentManager = F3_GimmeFive_Component_Manager::getInstance();
		$this->validator = $this->componentManager->getComponent("F3_MailformPlusPlus_Validator_Default");
	}

	protected function tearDown() {
		unset($this->validator);
		unset($this->componentManager);
	}

	/**
	 * Test require
	 *
	 * @test
	 * @see t3lib_div::myFunction
	 */
	public function testRequired() {
		$fakeGP = array();
		$fakeGP['lastname'] = "dummy_lastname";

		$fakeSettings = array();
		$fakeSettings['fieldConf.']['lastname.']['errorCheck.']['1'] = "required";
		$errors = array();

		// Loads configuration
		$this->validator->loadConfig($fakeGP, $fakeSettings);

		// Tests filled out fields
		t3lib_div::debug($fakeGP['lastname'], $this->message);
		$this->assertTrue($this->validator->validate($errors));

		// Tests *not* filled out fields
		$fakeGP['lastname'] = "";
		t3lib_div::debug(' ', $this->message);
		$this->validator->loadConfig($fakeGP, $fakeSettings);
		$this->assertFalse($this->validator->validate($errors));
	}

	public function testBetweenItems() {
		$fakeGP = array();
		$fakeGP['customField'] = array('Sports','Music');

		$fakeSettings = array();

		$fakeSettings['fieldConf.']['customField.']['errorCheck.']['1'] = "betweenItems";
		$fakeSettings['fieldConf.']['customField.']['errorCheck.']['1.']['minValue'] = 1;
		$fakeSettings['fieldConf.']['customField.']['errorCheck.']['1.']['maxValue'] = 3;
		$errors = array();

		// Loads configuration and tests
		$this->validator->loadConfig($fakeGP, $fakeSettings);
		t3lib_div::debug($fakeGP['customField'], $this->message);
		$this->assertTrue($this->validator->validate($errors));

		$fakeGP['customField'] = array('Sports','Music', 'Science', 'Cars');
		$this->validator->loadConfig($fakeGP, $fakeSettings);
		t3lib_div::debug($fakeGP['customField'], $this->message);
		$this->assertFalse($this->validator->validate($errors));
	}

	/**
	 * Test email
	 */
	public function testEmail() {
		$fakeGP = array();
		$fakeGP['email'] = "email@host.com";

		$fakeSettings = array();
		$fakeSettings['fieldConf.']['email.']['errorCheck.']['1'] = "email";
		$errors = array();

		// Loads configuration
		$this->validator->loadConfig($fakeGP, $fakeSettings);

		//valid email
		t3lib_div::debug($fakeGP['email'], $this->message);
		$this->assertTrue($this->validator->validate($errors));

		$values = array(
			'ajksdhf', //invalid syntax 1
			'ajksdhf.com', //invalid syntax 2
			'aasd@ajksdhfcom', //invalid syntax 3
			'aasd@127.0.0.1', //invalid syntax 4
			'!%&$/@$/$%&.com', //invalid characters
		);

		foreach ($values as $value) {
			$fakeGP['email'] = $value;
			$this->validator->loadConfig($fakeGP, $fakeSettings);
			$this->validator->validate($errors);
			t3lib_div::debug($value, $this->message);
			$this->assertFalse($this->validator->validate($errors));
		}
	}



}
?>
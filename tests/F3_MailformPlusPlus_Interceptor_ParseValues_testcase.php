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
 * Test for the Component "F3_MailformPlusPlus_Interceptor_ParseValues" of the extension 'mailformplusplus'
 *
 * @package	F3_MailformPlusPlus
 * @subpackage	Tests
 */
class F3_MailformPlusPlus_Interceptor_ParseValues_testcase extends PHPUnit_Framework_TestCase {

	protected $components;
	protected $interceptor;

	protected function setUp() {
		$this->componentManager = F3_GimmeFive_Component_Manager::getInstance();
		$this->interceptor = $this->componentManager->getComponent("F3_MailformPlusPlus_Interceptor_ParseValues");
	}

	protected function tearDown() {
		unset($this->interceptor);
		unset($this->componentManager);
	}

	public function test_parseFloats() {
		$fixture = array (
				  0 => 22,
				  1 => 22,
				  2 => 22000.76,
				  3 => 22000.76,
				  4 => 22000.76,
				  5 => 22000.76,
				  6 => 22000,
				  7 => 22000,
				  8 => 22000,
				  9 => 22000.76,
				  10 => 22000.76,
				  11 => 1022000.76,
				  12 => 1022000.76,
				  13 => 1000000,
				  14 => 1000000,
				  15 => 1022000.76,
				  16 => 1022000.76,
				  17 => 1022000,
				  18 => 0.76,
				  19 => 0.76,
				  20 => 0,
				  21 => 0,
				  22 => 1,
				  23 => 1,
				  24 => -22000.76,
				  25 => -22000.76,
				  26 => -22000.76,
				  27 => -22000,
				  28 => -22000,
				  29 => -22000,
				  30 => -22000.76,
				  31 => -22000.76,
				  32 => -1022000.76,
				  33 => -1022000.76,
				  34 => -1000000,
				  35 => -1000000,
				  36 => -1022000.76,
				  37 => -1022000.76,
				  38 => -1022000,
				  39 => -0.76,
				  40 => -0.76,
				  41 => -0,
				  42 => -0,
				  43 => -1,
				  44 => -1,
				);

		$stringFloats = array(
				  0 => '22,-',
				  1 => '22,--',
				  2 => '22\'000,76',
				  3 => '22 000,76',
				  4 => '22.000,76',
				  5 => '22,000.76',
				  6 => '22 000',
				  7 => '22,000',
				  8 => '22.000',
				  9 => '22000.76',
				  10 => '22000,76',
				  11 => '1.022.000,76',
				  12 => '1,022,000.76',
				  13 => '1,000,000',
				  14 => '1.000.000',
				  15 => '1022000.76',
				  16 => '1022000,76',
				  17 => '1022000',
				  18 => '0.76',
				  19 => '0,76',
				  20 => '0.00',
				  21 => '0,00',
				  22 => '1.00',
				  23 => '1,00',
				  24 => '-22 000,76',
				  25 => '-22.000,76',
				  26 => '-22,000.76',
				  27 => '-22 000',
				  28 => '-22,000',
				  29 => '-22.000',
				  30 => '-22000.76',
				  31 => '-22000,76',
				  32 => '-1.022.000,76',
				  33 => '-1,022,000.76',
				  34 => '-1,000,000',
				  35 => '-1.000.000',
				  36 => '-1022000.76',
				  37 => '-1022000,76',
				  38 => '-1022000',
				  39 => '-0.76',
				  40 => '-0,76',
				  41 => '-0.00',
				  42 => '-0,00',
				  43 => '-1.00',
				  44 => '-1,00',
				);
		//take the keys as fieldList to parse
		$fakeConfig = array('parseFloatFields' => implode(",", array_keys($stringFloats)));
		//$stringFloats as Fake GP
		$floats = $this->interceptor->process($stringFloats,$fakeConfig);
        
		$this->assertEquals($floats, $fixture);
	}

}
?>
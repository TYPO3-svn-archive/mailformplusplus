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
 * Test for the Component "F3_MailformPlusPlus_MarkerUtils" of the extension 'mailformplusplus'
 *
 * @package	F3_MailformPlusPlus
 * @subpackage	Tests
 */
class F3_MailformPlusPlus_StaticFuncs_testcase extends PHPUnit_Framework_TestCase {

	protected $components;
	protected $repository;

	protected function setUp() {
		require_once(t3lib_extMgm::extPath('mailformplusplus')."Classes/Utils/F3_MailformPlusPlus_StaticFuncs.php");
	}

	protected function tearDown() {
	}

	public function test_getDocumentRoot() {	
		$this->assertEquals(F3_MailformPlusPlus_StaticFuncs::getDocumentRoot(),t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT'));
	}
	
	public function test_getHostname() {
		$this->assertEquals(F3_MailformPlusPlus_StaticFuncs::getHostname(),t3lib_div::getIndpEnv('TYPO3_SITE_URL'));
	}
	
	public function test_sanitizePath() {
		$path = "fileadmin/test";
		$this->assertEquals(F3_MailformPlusPlus_StaticFuncs::sanitizePath($path),"/fileadmin/test/");
		$path = "/fileadmin/test";
		$this->assertEquals(F3_MailformPlusPlus_StaticFuncs::sanitizePath($path),"/fileadmin/test/");
		$path = "fileadmin/test/";
		$this->assertEquals(F3_MailformPlusPlus_StaticFuncs::sanitizePath($path),"/fileadmin/test/");
		$path = "/fileadmin/test/example.html";
		$this->assertEquals(F3_MailformPlusPlus_StaticFuncs::sanitizePath($path),"/fileadmin/test/example.html");
	}
	
	public function test_getFilledLangMarkers() {
		$fakeTemplate = '
			<div>###LLL:firstname###</div>
			<div>###LLL:lastname###</div>
		';
		
		$langFile = 'EXT:mailformplusplus/tests/locallang.xml';
		$langMarkers = F3_MailformPlusPlus_StaticFuncs::getFilledLangMarkers($fakeTemplate,$langFile);
		
		$this->assertEquals(
			$langMarkers,
			array(
				"###LLL:firstname###" => "Firstname_translated",
				"###LLL:FIRSTNAME###" => "Firstname_translated",
				"###LLL:lastname###" => "Lastname_translated",
				"###LLL:LASTNAME###" => "Lastname_translated"
			));
	}
	
	public function test_getFilledValueMarkers() {
		$fakeGp = array();
		$fakeGp['firstname'] = "Test";
		$fakeGp['lastname'] = "Test";
		
		$markers = F3_MailformPlusPlus_StaticFuncs::getFilledValueMarkers($fakeGp);
		
		$this->assertEquals($markers,array("###value_firstname###" => "Test","###value_lastname###" => "Test"));
	}
	
	public function test_removeUnfilledMarkers() {
		$fakeTemplate = '###LLL:firstname######error_sthg#####abcdeföäü!"§$$%&/###';
		$this->assertEquals(F3_MailformPlusPlus_StaticFuncs::removeUnfilledMarkers($fakTemplate),"");
	}
	
	public function test_resolvePath() {
		$path = 'EXT:mailformplusplus/Resources/PHP/fake.php';
		$resolvedPath = F3_MailformPlusPlus_StaticFuncs::resolvePath($path);
		$expectedPath = F3_MailformPlusPlus_StaticFuncs::getDocumentRoot().'/typo3conf/ext/mailformplusplus/Resources/PHP/fake.php';
		$this->assertEquals($resolvedPath,$expectedPath);
	}
	
	public function test_resolveRelPath() {
		$path = 'EXT:mailformplusplus/Resources/PHP/fake.php';
		$resolvedPath = F3_MailformPlusPlus_StaticFuncs::resolveRelPath($path);
		$expectedPath = '../typo3conf/ext/mailformplusplus/Resources/PHP/fake.php';
		$this->assertEquals($resolvedPath,$expectedPath);
	}
}
?>
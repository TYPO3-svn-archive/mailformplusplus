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
 * Validates that a specified field's value matches a regular expression case insensitive
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	ErrorChecks
 */
class F3_MailformPlusPlus_ErrorCheck_Eregi extends F3_MailformPlusPlus_AbstractErrorCheck {

	/**
	 * Validates that a specified field's value matches a regular expression case insensitive
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @param array &$gp The current GET/POST parameters
	 * @return string The error string
	 */
	public function check(&$check, $name, &$gp) {
		$checkFailed = '';
		
		if(isset($gp[$name]) && !empty($gp[$name])) {
			$eregi = $check['params']['value'];
			if($eregi && !eregi($eregi, $gp[$name])) {
				$checkFailed = $this->getCheckFailed($check);
			}
		}
		return $checkFailed;
	}


}
?>
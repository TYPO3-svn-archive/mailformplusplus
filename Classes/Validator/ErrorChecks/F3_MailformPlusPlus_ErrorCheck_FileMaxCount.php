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
 * Validates that up to x files get uploaded via the specified upload field.
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	ErrorChecks
 */
class F3_MailformPlusPlus_ErrorCheck_FileMaxCount extends F3_MailformPlusPlus_AbstractErrorCheck {

	/**
	 * Validates that up to x files get uploaded via the specified upload field.
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @param array &$gp The current GET/POST parameters
	 * @return string The error string
	 */
	public function check(&$check,$name,&$gp) {
		$checkFailed = "";

		session_start();
		#print_r($_SESSION['mailformplusplusFiles'][$name]);
		$maxCount = $check['params']['maxCount'];
		#print $maxCount.' == '.count($_SESSION['mailformplusplusFiles'][$name]);
		if(	is_array($_SESSION['mailformplusplusFiles'][$name]) &&
		count($_SESSION['mailformplusplusFiles'][$name]) >= $maxCount &&
		$_SESSION['mailformplusplusSettings']['currentStep'] == $_SESSION['mailformplusplusSettings']['lastStep']) {
				
			$checkFailed = $this->getCheckFailed($check);
		} elseif (is_array($_SESSION['mailformplusplusFiles'][$name]) &&
		$_SESSION['mailformplusplusSettings']['currentStep'] > $_SESSION['mailformplusplusSettings']['lastStep']) {
				
			foreach($_FILES as $idx=>$info) {
				if(strlen($info['name'][$name]) > 0 && count($_SESSION['mailformplusplusFiles'][$name]) >= $maxCount) {
					$checkFailed = $this->getCheckFailed($check);
				}
			}
				
		}
		return $checkFailed;
	}


}
?>
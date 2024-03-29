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
 * Validates that the correct image of possible images displayed by the extension "simple_captcha" got selected.
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	ErrorChecks
 */
class F3_MailformPlusPlus_ErrorCheck_SimpleCaptcha extends F3_MailformPlusPlus_AbstractErrorCheck {

	/**
	 * Validates that the correct image of possible images displayed by the extension "simple_captcha" got selected.
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @param array &$gp The current GET/POST parameters
	 * @return string The error string
	 */
	public function check(&$check, $name, &$gp) {
		$checkFailed = '';
		if (t3lib_extMgm::isLoaded('simple_captcha')) {
			require_once(t3lib_extMgm::extPath('simple_captcha') . 'class.tx_simplecaptcha.php');
			$simpleCaptcha_className = t3lib_div::makeInstanceClassName('tx_simplecaptcha');
			$this->simpleCaptcha = new $simpleCaptcha_className();
			if (!$this->simpleCaptcha->checkCaptcha()) {
				$checkFailed = $this->getCheckFailed($check);
			}
		}
		return $checkFailed;
	}


}
?>
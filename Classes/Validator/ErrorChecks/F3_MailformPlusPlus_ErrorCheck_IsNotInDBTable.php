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
 * Validates that a specified field's value is not found in a specified db table
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	ErrorChecks
 */
class F3_MailformPlusPlus_ErrorCheck_IsNotInDBTable extends F3_MailformPlusPlus_AbstractErrorCheck {

	/**
	 * Validates that a specified field's value is not found in a specified db table
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @param array &$gp The current GET/POST parameters
	 * @return string The error string
	 */
	public function check(&$check, $name, &$gp) {
		$checkFailed = '';
		
		if(isset($gp[$name]) && !empty($gp[$name])) {
			$checkTable = $check['params']['table'];
			$checkField = $check['params']['field'];
			$where = $check['params']['additionalWhere'];
			if (!empty($checkTable) && !empty($checkField)) {
				$where = $checkField . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($gp[$name], $checkTable) . ' ' . $additionalWhere;
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($checkField, $checkTable, $where);
				if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
					$checkFailed = $this->getCheckFailed($check);
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
		}
		return $checkFailed;
	}


}
?>
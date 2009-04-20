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
 * $Id$
 *                                                                        */

/**
 * An interceptor preparing values of a form used by ControlPanel for storing them in LDAP
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Interceptor
 */
class F3_MailformPlusPlus_Interceptor_Save extends F3_MailformPlusPlus_AbstractInterceptor {

	/**
	 * The main method called by the controller
	 *
	 * @param array $gp The GET/POST parameters
	 * @param array $settings The defined TypoScript settings for the finisher
	 * @return array The probably modified GET/POST parameters
	 */
	public function process($gp, $settings) {
		$this->gp = $gp;

		return $this->gp;
	}

}
?>

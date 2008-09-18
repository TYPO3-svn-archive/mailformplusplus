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

/**
 * This finisher clears the cache for the current page. It needs no further configuration.
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Finisher
 */
class F3_MailformPlusPlus_Finisher_ClearCache extends F3_MailformPlusPlus_AbstractFinisher {
	
	/**
     * The main method called by the controller
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @param array $gp The GET/POST parameters
     * @param array $settings The defined TypoScript settings for the finisher
     * @return array The probably modified GET/POST parameters
     */
	public function process($gp,$settings) {
		$this->gp = $gp;
		$this->settings = $settings;
		
		require_once("t3lib/class.t3lib_tcemain.php");
		$tce = t3lib_div::makeInstance('t3lib_tcemain'); 
		$tce->clear_cacheCmd($GLOBALS['TSFE']->id);
		return $this->gp;
	}
}
?>
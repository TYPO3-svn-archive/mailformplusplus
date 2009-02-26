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
 * An interceptor checking if form got submitted too often by an IP address or globally.
 * Settings how often a form is allowed to be submitted and the period of time are set in TypoScript.
 * 
 * This interceptor uses log entries made by F3_MailformPlusPlus_Logger_DB.
 * 
 * Example:
 * <code>
 * saveInterceptors.1.class = F3_MailformPlusPlus_Interceptor_IPBlocking
 * saveInterceptors.1.config.ip.timebase.value = 5
 * saveInterceptors.1.config.ip.timebase.unit = minutes
 * saveInterceptors.1.config.ip.threshold = 2
 *
 * saveInterceptors.1.config.global.timebase.value = 5
 * saveInterceptors.1.config.global.timebase.unit = minutes
 * saveInterceptors.1.config.global.threshold = 30
 * </code>
 * 
 * This example configuration says that the form is allowed to be submitted twice in a period of 5 minutes and 30 times in 5 minutes globally.
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @see F3_MailformPlusPlus_Logger_DB
 * @package	F3_MailformPlusPlus
 * @subpackage	Interceptor
 */
class F3_MailformPlusPlus_Interceptor_IPBlocking extends F3_MailformPlusPlus_AbstractInterceptor {
	
	/**
     * The main method called by the controller
     * 
     * @param array $gp The GET/POST parameters
     * @param array $settings The defined TypoScript settings for the finisher
     * @return array The probably modified GET/POST parameters
     */
	public function process($gp,$settings) {
		$this->gp = $gp;
		$this->settings = $settings;
		
		
		$ipTimebaseValue = $this->settings['ip.']['timebase.']['value'];
		$ipTimebaseUnit = $this->settings['ip.']['timebase.']['unit'];
		$ipMaxValue = $this->settings['ip.']['threshold'];
		
		$this->check($ipTimebaseValue,$ipTimebaseUnit,$ipMaxValue,true);
		
		$globalTimebaseValue = $this->settings['global.']['timebase.']['value'];
		$globalTimebaseUnit = $this->settings['global.']['timebase.']['unit'];
		$globalMaxValue = $this->settings['global.']['threshold'];
		
		$this->check($globalTimebaseValue,$globalTimebaseUnit,$globalMaxValue,true);
		
		return $this->gp;
	}
	
	/**
     * Checks if the form got submitted too often and throws Exception if true.
     * 
     * @param int Timebase value
     * @param string Timebase unit (seconds|minutes|hours|days)
     * @param int maximum amount of submissions in given time base.
     * @param boolean add IP address to where clause
     * @return void
     */
	private function check($value,$unit,$maxValue,$addIPToWhere = false) {
		$timestamp = $this->getTimestamp($value,$unit);
		$where = 'crdate >= '.$timestamp;
		if($addIPToWhere) {
			$where = 'ip=\''.t3lib_div::getIndpEnv('REMOTE_ADDR').'\' AND '.$where;
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid','tx_mailformplusplus_log',$where);
		
		if($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) >= $maxValue) {
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			$message = 'You are not allowed to send more mails because form got submitted too many times ';
			if($addIPToWhere) {
				$message .= 'by your IP address ';
			}
			$message .= 'in the last '.$value.' '.$unit.'!';
			throw new Exception($message);
		}
	}
	
	/**
     * Parses given value and unit and creates a timestamp now-timebase.
     * 
     * @param int Timebase value
     * @param string Timebase unit (seconds|minutes|hours|days)
     * @return long The timestamp
     */
	private function getTimestamp($value,$unit) {
		$now = time();
		$convertedValue = 0;
		switch($unit) {
			case "days":
				$convertedValue = $value * 24 * 60 * 60;
			break;
			case "hours":
				$convertedValue = $value * 60 * 60;
			break;
			case "minutes":
				$convertedValue = $value * 60;
			break;
		}
		return $now-$convertedValue;
	}
	
}
?>

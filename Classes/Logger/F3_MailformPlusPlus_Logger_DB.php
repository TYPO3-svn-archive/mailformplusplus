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
 * A logger to store submission information in TYPO3 database
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Logger
 */
class F3_MailformPlusPlus_Logger_DB {
	
	/**
     * Logs the given values.
     * 
     * @param array $gp The current GET/POST parameters
     * @param array $settings The settings for the logger
     * @return void
     */
	public function log(&$gp,$settings) {
		
		//set params
		$table = "tx_mailformplusplus_log";
		
		$fields['ip'] = t3lib_div::getIndpEnv('REMOTE_ADDR');
		if(isset($settings['disableIPlog']) && intval($settings['disableIPlog']) == 1) {
			$fields['ip'] = NULL;
		}
		$fields['tstamp'] = time();
		$fields['crdate'] = time();
		$fields['pid'] = $GLOBALS['TSFE']->id;
		$keys = array_keys($gp);
		ksort($gp);
		sort($keys);
		$serialized = serialize($gp);
		$hash = hash("md5",serialize($keys));
		$fields['params'] = $serialized;
		$fields['key_hash'] = $hash;
		
		#$fields = $GLOBALS['TYPO3_DB']->fullQuoteArray($fields,$table);
		
		//query the database
		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($table,$fields);
		if(!$settings['nodebug']) {
			F3_MailformPlusPlus_StaticFuncs::debugMessage('logging',$table,implode(",",$fields));
			if(strlen($GLOBALS['TYPO3_DB']->sql_error()) > 0) {
				F3_MailformPlusPlus_StaticFuncs::debugMessage('error',$GLOBALS['TYPO3_DB']->sql_error());
			}
			
		}
		
	}
	
}
?>

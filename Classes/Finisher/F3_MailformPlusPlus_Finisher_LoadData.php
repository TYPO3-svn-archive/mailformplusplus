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
 * Finisher to load data from a db table and store the result in GET/POST.
 * 
 * A sample configuration looks like this:
 * 
 * <code>
 * finishers.1.class = F3_MailformPlusPlus_Finisher_LoadData
 * finishers.1.config.table = tt_address
 * finishers.1.config.fields = name,email
 * finishers.1.config.uidField = uid_member
 * 
 * </code>
 * 
 * The finishers stores the selected data in the following way:
 * 
 * <code>
 * $gp['loadedData'][table][uid][fieldname]
 * </code>
 * 
 * Example:
 * 
 * <code>
 * $gp['loadedData']['tt_address'][5]['email']
 * </code>
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Finisher
 */
class F3_MailformPlusPlus_Finisher_LoadData extends F3_MailformPlusPlus_AbstractFinisher {
	
	/**
     * The main method called by the controller
     * 
     * @return array The probably modified GET/POST parameters
     */
	public function process() {
		
		//check if config is sufficient
		if(!isset($this->settings['table']) || !isset($this->settings['fields']) || !isset($this->settings['uidField'])) {
			throw new Exception('insufficient_config','F3_MailformPlusPlus_Finisher_LoadData');
		}
		
		if(!empty($this->gp[$this->settings['uidField']]) && is_numeric($this->gp[$this->settings['uidField']])) {
			$this->selectData($this->settings['table'],$this->settings['fields'],$this->gp[$this->settings['uidField']]);
		}
		
		return $this->gp;
	}
	
	/**
     * Selects given fields from given table where uid equals given value and stores result dat in GET/POST.
     * 
     * @param string $table
     * @param string $fields Comma seperated list of database field names
     * @param int $uidValue
     * @return void
     */
	protected function selectData($table,$fields,$uidValue) {
		
		//select data
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,'.$fields,$table,'uid='.$uidValue);
		if($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				
				//store current row in GET/POST
				$this->gp['loadedData'][$table][$row['uid']] = $row;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		
	}
}	
	

?>
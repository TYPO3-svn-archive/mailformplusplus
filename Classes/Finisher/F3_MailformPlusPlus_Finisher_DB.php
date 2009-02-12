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
 * This finisher stores the submitted values into a table in the TYPO3 database according to the configuration
 * 
 * Example configuration:
 * 
 * <code>
 * finishers.1.class = F3_MailformPlusPlus_Finisher_DB
 * 
 * #The table to store the records in
 * finishers.1.config.table = tt_content
 * 
 * #The uid field. Default: uid
 * finishers.1.config.key = uid
 * 
 * #Do not insert the record, but update an existing one.
 * #The uid of the existing record must exist in Get/Post
 * finishers.1.config.updateInsteadOfInsert = 1
 * 
 * #map a form field to a db field.
 * finishers.1.config.fields.header.mapping = name
 * 
 * #if form field is empty, insert this
 * finishers.1.config.fields.header.if_is_empty = None given
 * finishers.1.config.fields.bodytext.mapping = interests
 * 
 * #if form field is an array, implode using this seperator. Default: ,
 * finishers.1.config.fields.bodytext.seperator = ,
 * 
 * #add static values for some fields
 * finishers.1.config.fields.hidden = 1
 * finishers.1.config.fields.pid = 39
 * 
 * #add special values
 * finishers.1.config.fields.subheader.special = sub_datetime
 * finishers.1.config.fields.crdate.special = sub_tstamp
 * finishers.1.config.fields.tstamp.special = sub_tstamp
 * finishers.1.config.fields.imagecaption.special = ip
 * </code>
 * 
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Finisher
 */
class F3_MailformPlusPlus_Finisher_DB extends F3_MailformPlusPlus_AbstractFinisher {
	
	/**
     * The name of the table to put the values into.
     * 
     * @access protected
     * @var string
     */
	protected $table;
	
	/**
     * The field in the table holding the primary key.
     * 
     * @access protected
     * @var string
     */
	protected $key;
	
	/**
     * A flag to indicate if to insert the record or to update an existing one
     * 
     * @access protected
     * @var boolean
     */
	protected $doUpdate;
	
	/**
     * The settings array passed to the finisher.
     * 
     * @access protected
     * @var array
     */
	protected $settings;
	
	/**
     * A cObj to be able to call cObjGetSingle, ...
     * @access protected
     * @var tslib_cObj
     */
	protected $cObj;
	
	/**
     * The main method called by the controller
     * 
     * @param array $gp The GET/POST parameters
     * @param array $settings The defined TypoScript settings for the finisher
     * @return array The probably modified GET/POST parameters
     */
	public function process($gp,$settings) {
		
		//set GET/POST parameters
		$this->gp = $gp;
		
		//set settings
		$this->settings = $settings;
		
		//initialize
		$this->init();
		
		//set fields to insert/update
		$queryFields = $this->parseFields();
		$queryFields = $this->escapeFields($queryFields,$this->table);
		
		//query the database
		$this->save($queryFields);
		
		return $this->gp;
	}
	
	/**
     * Escapes all values in given array for insert query into given table.
     * 
     * @param array $queryFields Array with values (either associative or non-associative array) 
     * @param string $table Table name for which to quote 
     * @return array The input array with the values quoted
     */
	protected function escapeFields($queryFields,$table) {
		return $GLOBALS['TYPO3_DB']->fullQuoteArray($queryFields,$table);
	}
	
	/**
     * Method to query the database making an insert or update statement using the given fields.
     * 
     * @param array &$queryFields Array holding the query fields
     * @return void
     */
	protected function save(&$queryFields) {
		
		//insert query
		if(!$this->doUpdate) {
			$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->table,$queryFields);
			
		//update query
		} else {
			
			//check if uid of record to update is in GP
			$uid =$this->gp['uid'];
			if(!$uid) {
				$uid = $this->gp[$this->key];
			}
			if($uid) {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->table,$this->key."=".$uid,$queryFields);
			} else {
				F3_MailformPlusPlus_StaticFuncs::debugMessage("UID parameter not found in GP. Cannot make update!");
			}
		}
	}
	
	/**
     * Inits the finisher mapping settings values to internal attributes.
     * 
     * @return void
     */
	protected function init() {
		
		//make cObj instance
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->cObj->setCurrentVal($GLOBALS['TSFE']->id);
		
		//set table
		$this->table = $this->settings['table'];
		if(!$this->table || !is_array($this->settings['fields.'])) {
			throw new Exception("No table configured!");
			return;
		}
		
		//set primary key field
		$this->key = $this->settings['key'];
		if(!$this->key) {
			$this->key = "uid"; 
		}
		
		//check whether to update or to insert a record
		$this->doUpdate = false;
		if($this->settings['updateInsteadOfInsert']) {
			$this->doUpdate = true;
		}
	}
	
	/**
     * Parses mapping settings and builds an array holding the query fields information.
     * 
     * @return array The query fields
     */
	protected function parseFields() {
		$queryFields = array();
		
		//parse mapping
		foreach($this->settings['fields.'] as $fieldname=>$options) {
			$fieldname = str_replace(".","",$fieldname);
			
			if(isset($options) && is_array($options) && !isset($options['special'])) {
			
				//if no mapping default to the name of the form field
				if(!$options['mapping']) {
					$options['mapping'] = $fieldname;
				}
				$queryFields[$fieldname] = $this->gp[$options['mapping']];
				
				//process empty value handling
				if($options['ifIsEmpty'] && strlen($this->gp[$options['mapping']]) == 0) {
					
					//if given settings is a TypoScript object
					if(isset($options['if_is_empty.']) && is_array($options['if_is_empty.'])) {
						$queryFields[$fieldname] = $this->cObj->cObjGetSingle($options['ifIsEmpty'],$options['ifIsEmpty.']);
					} else {
						$queryFields[$fieldname] = $options['ifIsEmpty'];
					}
				}
				
				//process array handling
				if(isset($this->gp[$options['mapping']]) && is_array($this->gp[$options['mapping']])) {
					$seperator = ",";
					if($options['seperator']) {
						$seperator = $options['seperator'];
					}
					$queryFields[$fieldname] = implode($seperator,$this->gp[$options['mapping']]);
				}
				
			//special mapping
			} elseif(isset($options) && is_array($options) && isset($options['special'])) {
				switch($options['special']) {
					case "sub_datetime":
						$now = date("Y-m-d H:i:s", time());
						$queryFields[$fieldname] = $now;
					break;
					case "sub_tstamp":
						$queryFields[$fieldname] = time();
					break;
					case "ip":
						$queryFields[$fieldname] = t3lib_div::getIndpEnv('REMOTE_ADDR');
					break;
				}
			} else {
				$queryFields[$fieldname] = $options;
			}
		}
		return $queryFields;
	}
	
}
?>

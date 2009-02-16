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
 * Controller for Backend Module of MailformPlusPlus
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Controller
 */
class F3_MailformPlusPlus_Controller_Backend extends F3_MailformPlusPlus_AbstractController {
	
	
	/**
     * The GimmeFive component manager
     * 
     * @access protected
     * @var F3_GimmeFive_Component_Manager
     */
	protected $componentManager;
	
	/**
     * The global MailformPlusPlus configuration
     * 
     * @access protected
     * @var F3_MailformPlusPlus_Configuration
     */
	protected $configuration;
	
	/**
     * The table to select the logged records from
     * 
     * @access protected
     * @var string
     */
	protected $logTable;
	
	/**
     * The constructor for a finisher setting the component manager and the configuration.
     * 
     * @param F3_GimmeFive_Component_Manager $componentManager
     * @param F3_MailformPlusPlus_Configuration $configuration
     * @author Reinhard Führicht <rf@typoheads.at>
     * @return void
     */
	public function __construct(F3_GimmeFive_Component_Manager $componentManager, F3_MailformPlusPlus_Configuration $configuration) {
		$this->componentManager = $componentManager;
		$this->configuration = $configuration;
	}
	
	/**
	 * init method to load translation data and set log table.
	 *
	 * @global $LANG
	 * @return void
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function init() {
		global $LANG;
		$LANG->includeLLFile('EXT:mailformplusplus/Resources/Language/locallang.xml');
		$this->logTable = 'tx_mailformplusplus_log';
	}
	
	/**
	 * Main method of the controller.
	 *
	 * @return string rendered view
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	public function process() {
		global $LANG;
		
		//init 
		$this->init();
		
		//init gp params
		$params = t3lib_div::_GP('mailformplusplus');
		
		//should delete records
		if($params[delete] && isset($params['markedUids']) && is_array($params['markedUids'])) {
			
			//delete records
			$this->deleteRecords($params['markedUids']);
			
			//select all records
			$records = $this->fetchRecords();
			
			//show table
			$table = $this->getTable($records);
			
			return $table;
		}
		
		//should show index
		if(!$params['detailId'] && !$params['markedUids']) {
			
			//if log table doesn't exist, show error
			$tables = $GLOBALS['TYPO3_DB']->admin_get_tables();
			if(!in_array($this->logTable,array_keys($tables))) {
				return $this->getErrorMessage();
				
			//show index table
			} else {
				
				//select all records
				$records = $this->fetchRecords();
				
				//show table
				$table = $this->getTable($records);
				return $table;
			}
			
		//should export to some format
		} elseif(!$params['delete']) {
			
			//should show detail view of a single record
			if(!$params['renderMethod']) {
				
				return $this->showSingleView($params['detailId']);
				
			//PDF generation
			} elseif(!strcasecmp($params['renderMethod'],"pdf")) {
				
				//render a single record to PDF
				if($params['detailId']) {
					return $this->generatePDF($params['detailId']);
					
				//render many records to PDF
				} elseif(isset($params['markedUids']) && is_array($params['markedUids'])) {
					return $this->generatePDF($params['markedUids']);
				}
				
			//CSV
			} elseif(!strcasecmp($params['renderMethod'],"csv")) {
				
				//save single record as CSV
				if($params['detailId']) {
					return $this->generateCSV($params['detailId']);
					
				//save many records as CSV
				} elseif(isset($params['markedUids']) && is_array($params['markedUids'])) {
					return $this->generateCSV($params['markedUids']);
				}
				
			}
		}
	}
	
	/**
	 * Function to delete one ore more records from log table
	 *
	 * @param array $uids The record uids to delete
	 * @return void
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function deleteRecords($uids) {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery($this->logTable,"uid IN (".implode(",",$uids).")");
	}
	
	/**
	 * Function to handle the generation of a PDF file.
	 * Before the data gets exported, the user is able to select which fields to export in a selection view.
	 * This enables the user to get rid of fields like submitted or mp-step.
	 *
	 * @param misc $detailId The record uids to export to pdf
	 * @return void/string selection view
	 * @author Reinhard Führicht
	 */
	protected function generatePDF($detailId) {
		
		/* 
		 * if there is only one record to export, initialize an array with the one uid
		 * to ensure that foreach loops will not crash
		 */
		if(!is_array($detailId)) {
			$detailId = array($detailId);
		}
		
		//init gp params
		$gp = t3lib_div::_GP('mailformplusplus');
		
		//select the records
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid,pid,crdate,ip,params",$this->logTable,"uid IN (".implode(",",$detailId).")");
		
		//if records were found
		if($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
			$records = array();
			$allParams = array();
			
			//loop through records
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				
				//unserialize params and save the array
				$row['params'] = unserialize($row['params']);
				$records[] = $row;
				if(!is_array($row['params'])) {
					$row['params'] = array();
				}
				
				//sum up all params for selection view
				$allParams = array_merge($allParams,$row['params']);
			}
			
			//if fields were chosen in selection view, export the records using the selected fields
			if(isset($gp['exportParams'])) {
				$generator = $this->componentManager->getComponent("F3_MailformPlusPlus_Generator_PDF");
				$generator->generateModulePDF($records,$gp['exportParams']);
				
			/*
			 * show selection view to find out which fields to export.
			 * This enables the user to get rid of fields like submitted or mp-step
			 */
			} else {
				return $this->generatePDFExportFieldsSelector($allParams);
			}	
		}
	}
	
	/**
	 * Function to handle the generation of a CSV file.
	 * Before the data gets exported, the data is checked and the user gets informed about different formats of the data.
	 * Each format has to be exported in an own file. After the format selection, the user is able to select which fields to export in a selection view.
	 * This enables the user to get rid of fields like submitted or mp-step.
	 *
	 * @param misc $detailId The record uids to export to csv
	 * @return void/string selection view
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function generateCSV($detailId) {
		
		/* 
		 * if there is only one record to export, initialize an array with the one uid
		 * to ensure that foreach loops will not crash
		 */
		if(!is_array($detailId)) {
			$detailId = array($detailId);
		}
		
		//init gp params
		$params = t3lib_div::_GP('mailformplusplus');
		
		//select the records to export
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid,pid,crdate,ip,params,key_hash",$this->logTable,"uid IN (".implode(",",$detailId).")");
		
		//if record were found
		if($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
			$records = array();
			$count = 0;
			$hashes = array();
			$availableFormats = array();
			
			//loop through records
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				
				//unserialize the params array
				$row['params'] = unserialize($row['params']);
				
				//find the amount of different formats to inform the user.
				if(!in_array($row['key_hash'],$hashes)) {
					$hashes[] = $row['key_hash'];
					$availableFormats[] = $row['params'];
				}
				$records[] = $row;
			}
			
			$availableFormatsCount = count($hashes);
			
			//only one format found
			if($availableFormatsCount == 1) {
				
				//if fields were chosen in the selection view, perform the export
				if(isset($params['exportParams'])) {
					$generator = $this->componentManager->getComponent("F3_MailformPlusPlus_Generator_CSV");
					$generator->generateModuleCSV($renderRecords,$params['exportParams']);
					
				//no fields chosen, show selection view.
				} else {
					return $this->generateCSVExportFieldsSelector($renderRecords[0]['params']);
				}
				
			//more than one format and user has chosen a format to export
			} elseif(isset($params['csvFormat'])) {
				
				//select the format
				$format = $hashes[$params['csvFormat']];
				$renderRecords = array();
				
				//find out which records belong to this format
				foreach($records as $record) {
					if(!strcmp($record['key_hash'],$format)) {
						$renderRecords[] = $record;
					}
				}
				
				//if fields were chosen in the selection view, perform the export
				if(isset($params['exportParams'])) {
					$generator = $this->componentManager->getComponent("F3_MailformPlusPlus_Generator_CSV");
					$generator->generateModuleCSV($renderRecords,$params['exportParams']);
					
				//no fields chosen, show selection view.
				} else {
					return $this->generateCSVExportFieldsSelector($renderRecords[0]['params']);
				}
				
			//more than one format and none chosen by now, show format selection view.
			} else {
				return $this->generateFormatsSelector($availableFormats,$detailId);
			}
		}
	}
	
	/**
	 * This function returns a list of all available fields to export for CSV export. 
	 * The user can choose several fields and start the export.
	 *
	 * @param array $params The available fields to export.
	 * @return string fields selection view
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function generateCSVExportFieldsSelector($params) {
		global $LANG;
		
		//if there are no params, initialize the array to ensure that foreach loops will not crash
		if(!is_array($params)) {
			$params = array();
		}
		
		//init gp params
		$gp = t3lib_div::_GP('mailformplusplus');
				
		//header
		$selector = '<h3>' . $LANG->getLL('select_export_fields') . '</h3><br />';
		$selector .= '
			<div style="width:200px;float:right">
				<input type="button" onclick="selectAll()" value="'.$LANG->getLL('select_all').'" />
				<input type="button" onclick="deselectAll()" value="'.$LANG->getLL('deselect_all').'" />
			</div>
		';
		
		//start form with hidden fields holding the values of params of the steps before
		$selector .= '<form id="mailformplusplus_module_form" action="'.$_SERVER['PHP_SELF'].'" method="post">';
		
		//the selected format to export
		$selector .= '<input type="hidden" name="mailformplusplus[csvFormat]" value="'.$gp['csvFormat'].'" />';
		
		//the selected render method (csv/pdf), should be csv here
		$selector .= '<input type="hidden" name="mailformplusplus[renderMethod]" value="'.$gp['renderMethod'].'" />';
		
		/* 
		 * if there is only one record to export, initialize an array with the one uid
		 * to ensure that foreach loops will not crash.
		 * UIDs could be in param "markedUids" if more records where selected or in "detailId" if only one record get exported.
		 */
		$detailId = $gp['markedUids'];
		if(!$detailId) {
			$detailId = $gp['detailId'];
		}
		if(!is_array($detailId)) {
			$detailId = array($detailId);
		}
		
		//the selected records in a previous step
		foreach($detailId as $id) {
			$selector .= '<input type="hidden" name="mailformplusplus[markedUids][]" value="'.$id.'" />';
		}
		
		//start output table
		$selector .= '<table>';
		
		//add a label and a checkbox for each available parameter
		foreach($params as $field=>$value) {
			$selector .= '<tr><td><input type="checkbox" name="mailformplusplus[exportParams][]" value="'.$field.'">'.$field.'</td></tr>';
		}
		
		//add submite button and close form
		$selector .= '</table><input type="submit" value="'.$LANG->getLL('export').'" /></form>';
		
		//add javascript for "select all" and "deselect all"
		$selector .= $this->getSelectionJS();
		return $selector;
	}
	
	/**
	 * This function returns a list of all available fields to export for PDF export. 
	 * The user can choose several fields and start the export.
	 *
	 * @param array $params The available fields to export.
	 * @return string fields selection view
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function generatePDFExportFieldsSelector($params) {
		global $LANG;
		
		//if there are no params, initialize the array to ensure that foreach loops will not crash
		if(!is_array($params)) {
			$params = array();
		}
		
		//init gp params
		$gp = t3lib_div::_GP('mailformplusplus');
		
		//header
		$selector = '<h3>' . $LANG->getLL('select_export_fields') . '</h3><br />';
		$selector .= '
			<div style="width:200px;float:right">
				<input type="button" onclick="selectAll()" value="'.$LANG->getLL('select_all').'" />
				<input type="button" onclick="deselectAll()" value="'.$LANG->getLL('deselect_all').'" />
			</div>
		';
		
		//start form with hidden fields holding the values of params of the steps before
		$selector .= '<form id="mailformplusplus_module_form" action="'.$_SERVER['PHP_SELF'].'" method="post">';
		
		//the selected render method (csv/pdf), should be pdf here
		$selector .= '<input type="hidden" name="mailformplusplus[renderMethod]" value="'.$gp['renderMethod'].'" />';
		
		/* 
		 * if there is only one record to export, initialize an array with the one uid
		 * to ensure that foreach loops will not crash.
		 * UIDs could be in param "markedUids" if more records where selected or in "detailId" if only one record get exported.
		 */
		$detailId = $gp['markedUids'];
		if(!$detailId) {
			$detailId = $gp['detailId'];
		}
		if(!is_array($detailId)) {
			$detailId = array($detailId);
		}
		
		//the selected records in a previous step
		foreach($detailId as $id) {
			$selector .= '<input type="hidden" name="mailformplusplus[markedUids][]" value="'.$id.'" />';
		}
		
		//start output table with the default fields that can be exported (ip address, submission date and PID)
		$selector .= '<table>';
		$selector .= '<tr><td><input type="checkbox" name="mailformplusplus[exportParams][]" value="ip">'.$LANG->getLL('ip_address').'</td></tr>';
		$selector .= '<tr><td><input type="checkbox" name="mailformplusplus[exportParams][]" value="submission_date">'.$LANG->getLL('submission_date').'</td></tr>';
		$selector .= '<tr><td><input type="checkbox" name="mailformplusplus[exportParams][]" value="pid">'.$LANG->getLL('page_id').'</td></tr>';
		$selector .= '</table>';
		$selector .= '<table>';
		
		//add a label and a checkbox for each available parameter
		foreach($params as $field=>$value) {
			$selector .= '<tr><td><input type="checkbox" name="mailformplusplus[exportParams][]" value="'.$field.'">'.$field.'</td></tr>';
		}
		
		//add submit button and close form
		$selector .= '</table><input type="submit" value="'.$LANG->getLL('export').'" /></form>';
		
		//add javascript for "select all" and "deselect all"
		$selector .= $this->getSelectionJS();
		return $selector;
	}
	
	/**
	 * This function returns JavaScript code to select/deselect all checkboxes in a form
	 *
	 * @return string JavaScript code
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function getSelectionJS() {
		return '<script type="text/javascript">
				function selectAll() {
				  var form = document.getElementById("mailformplusplus_module_form");
				  var inputs = form.getElementsByTagName("input");
				  for(var i=0;i<inputs.length;i++) {
				    if(inputs[i].type == "checkbox") {
				      inputs[i].checked = "checked";	
					}	
				  }	
				}
				
				function deselectAll() {
				  var form = document.getElementById("mailformplusplus_module_form");
				  var inputs = form.getElementsByTagName("input");
				  for(var i=0;i<inputs.length;i++) {
				    if(inputs[i].type == "checkbox") {
				      inputs[i].checked = null;	
					}	
				  }	
				}
			</script>
		';
	}
	
	/**
	 * This function returns a list of all available formats to export to CSV.
	 * The user has to choose one ny another and export them to different files.
	 *
	 * @param array $formats The available formats
	 * @param array $detailId The selected records to export
	 * @return string formats selection view
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function generateFormatsSelector($formats,$detailId) {
		global $LANG;
		/* 
		 * if there is only one record to export, initialize an array with the one uid
		 * to ensure that foreach loops will not crash.
		 */
		if(!is_array($detailId)) {
			$detailId = array($detailId);
		}
		
		//header
		$selector .= '<h3>'.sprintf($LANG->getLL('formats_found'),count($formats)).'</h3><br />';
		
		//start table
		$selector .= '<table>';
		
		//loop through formats
		foreach($formats as $key=>$format) {
			
			//if format is valid
			if(isset($format) && is_array($format)) {
				
				//start a form
				$selector .= '
					<tr><td>
							<form action="'.$_SERVER['PHP_SELF'].'" method="post">
				';
				
				//add hidden fields for all selected records to export
				foreach($detailId as $id) {
					$selector .= '<input type="hidden" name="mailformplusplus[markedUids][]" value="'.$id.'" />';
				}
				
				//add hidden fields for the current format and the render method CSV
				$selector .= '
							<input type="hidden" name="mailformplusplus[csvFormat]" value="'.$key.'" />
							<input type="hidden" name="mailformplusplus[renderMethod]" value="csv" />
							<input type="submit" value="'.$LANG->getLL('export').'" />
							</form>
						</td>
						<td>'.implode(",",array_keys($format)).'</td>
					</tr>
				';
			}
		}
		
		//close table, add back link and return
		$selector .= '</table><br /><hr /><a href="'.$_SERVER['PHP_SELF'].'">'.$LANG->getLL('back').'</a>';
		return $selector;
	}
	
	/**
	 * This function returns a single view of a record
	 *
	 * @param int $singleUid The UID of the record to show
	 * @return string single view
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function showSingleView($singleUid) {
		global $LANG;
		
		//select the record
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid,pid,crdate,ip,params",$this->logTable,"uid=".$singleUid);
		
		//if UID was valid
		if($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			
			//unserialize params
			$params = unserialize($row['params']);
			
			//start with default fields (IP address, submission date, PID)
			$view = '
				<div>
					<div>
						<div style="width:100px;float:left"><h4>'.$LANG->getLL('page_id').'</h4></div>
						<div style="width:100px;float:left">'.$row['pid'].'</div>
						<div style="clear:both"></div>
					</div>
					<div>
						<div style="width:100px;float:left"><h4>'.$LANG->getLL('crdate').'</h4></div>
						<div style="width:200px;float:left">'.date("Y/m/d H:i",$row['crdate']).'</div>
						<div style="clear:both"></div>
					</div>
					<div>
						<div style="width:100px;float:left"><h4>'.$LANG->getLL('ip_address').'</h4></div>
						<div style="width:100px;float:left">'.$row['ip'].'</div>
						<div style="clear:both"></div>
					</div>
					<div>
						<div style="width:100px;"><h4>'.$LANG->getLL('params').'</h4></div>
						<div style="width:300px;margin-left:30px;">
			';
			
			//add the submitted params
			if(isset($params) && is_array($params)) {
				$view .= '<table>';
				foreach($params as $key=>$value) {
					if(is_array($value)) {
						$value = implode(",",$value);
					}
					$view .= '
						<tr>
							<td style="font-weight:bold">'.$key.'</td>
							<td>'.$value.'</td>
						</tr>
					';
				}
				$view .= '</table>';
			}
			
			//add buttons to export the record and a back link
			$view .= '			
						</div>
					</div>
					<hr />
					<div>
						<strong>'.$LANG->getLL('export_as').' </strong><a href="'.$_SERVER['PHP_SELF'].'?mailformplusplus[detailId]='.$row['uid'].'&mailformplusplus[renderMethod]=pdf">'.$LANG->getLL('pdf').'</a>
						/<a href="'.$_SERVER['PHP_SELF'].'?mailformplusplus[detailId]='.$row['uid'].'&mailformplusplus[renderMethod]=csv">'.$LANG->getLL('csv').'</a>
					</div>
					<div style="margin-top:30px;font-weight:bold;text-decoration:underline;">
						<a href="'.$_SERVER['PHP_SELF'].'">'.$LANG->getLL('back').'</a>
					</div>
				</div>
			';
			return $view;
		}
	}
	
	/**
	 * This function returns an error message if the log table was not found
	 *
	 * @return string HTML code with error message
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function getErrorMessage() {
		return '
			<div style="color:dd7777;font-weight:bold;font-size:11px;">
				<br />
				<p style="color:dd7777;font-weight:bold;font-size:11px;">'.$LANG->getLL('noLogTable').'</p>
				<br />
				<br />
				<p style="color:dd7777;font-weight:bold;font-size:11px;">TypoScript:</p><br />
				<span style="font-family:Monospace;color:dd7777;font-weight:bold;font-size:11px;">
					plugin.F3_MailformPlusPlus.settings.loggers.1 {<br />
						class = F3_MailformPlusPlus_Logger_Default	<br />
					} <br />
				</span>
			</div>
		';
	}
	
	/**
	 * This function selects all logged records from the log table using the filter settings.
	 *
	 * @return array The selected records
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */ 
	protected function fetchRecords() {
		$records = array();
		
		//build WHERE clause
		$where = $this->buildWhereClause();
		
		//select the records
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid,pid,crdate,ip,params",$this->logTable,$where);
		
		//if records found
		if($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
			$count = 0;
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$records[$count] = $row;
				$count++;
			}
		}
		return $records;
	}
	
	/**
	 * This function applies the filter settings and builds an according WHERE clause for the SELECT statement
	 *
	 * @return string WHERE clause for the SELECT statement
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function buildWhereClause() {
		
		//init gp params
		$params = t3lib_div::_GP('mailformplusplus');
		$where = array();
		
		//if only records of a specific PID should be shown
		if(strlen(trim($params['pidFilter'])) > 0) {
			$where[] = "pid IN (".$params['pidFilter'].")";
		}
		
		//only records submitted after given timestamp
		if(strlen(trim($params['startdateFilter'])) > 0) {
			$tstamp = $this->dateToTimestamp($params['startdateFilter']);
			$where[] = "crdate >= ".$tstamp;
		}
		
		//only records submitted before given timestamp
		if(strlen(trim($params['enddateFilter'])) > 0) {
			$tstamp = $this->dateToTimestamp($params['enddateFilter'],true);
			$where[] = "crdate <= ".$tstamp;
		}
		
		//if filter was applied, return the WHERE clause
		if(count($where) > 0) {
			return implode(" AND ",$where);
		}
	}
	
	/**
	 * This function formats a date
	 *
	 * @param long $date The timestamp to format
	 * @param boolean $end Is end date or start date
	 * @return string formatted date
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function dateToTimestamp($date,$end = false) {
		$dateArr = explode(".",$date);
		if($end) {
			return mktime(23,59,59,$dateArr[1],$dateArr[0],$dateArr[2]);
		}
		return mktime(0,0,0,$dateArr[1],$dateArr[0],$dateArr[2]);
	}
	
	/**
	 * This function returns the filter fields on top.
	 *
	 * @return string HTML
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function getFilterSection() {
		global $LANG;
		
		//init gp params
		$params = t3lib_div::_GP('mailformplusplus');
		
		//generate form
		$filter = '
			<form action="'.$_SERVER['PHP_SELF'].'" method="post">
			<div>
				<h2 style="text-align:center">'.$LANG->getLL('filter').'</h2>
				<table>
					<tr>
						<td><strong>'.$LANG->getLL('pid_label').'</strong></td>
						<td>
							<input type="text" name="mailformplusplus[pidFilter]" value="'.$params['pidFilter'].'"/>
						</td>
					</tr>
					<tr>
						<td><strong>'.$LANG->getLL('startdate').'</strong></td>
						<td>
							<input type="text" readonly="readonly" id="startdate" value="'.$params['startdateFilter'].'" name="mailformplusplus[startdateFilter]" />
							<input type="button" id="trigger_startdate" value="'.$LANG->getLL('cal').'"/>
						</td>
					</tr>
					<tr>
						<td><strong>'.$LANG->getLL('enddate').'</strong></td>
						<td>
							<input type="text" readonly="readonly" id="enddate" value="'.$params['enddateFilter'].'" name="mailformplusplus[enddateFilter]" />
							<input type="button" id="trigger_enddate" value="'.$LANG->getLL('cal').'"/>
						</td>
					</tr>
					<tr>
						<td><input type="submit" value="'.$LANG->getLL('filter').'" /></td>
						<td>&nbsp;</td>
					</tr>
				</table>
			</div>
			</form>';
			
		//add JavaScript for Popup calendar
		$filter .= '
			<hr />
		';
		$filter .= $this->getCalendarJS();
			
		return $filter;
	}

	/**
	 * This function returns the JavaScript code to initialize the popup calendar
	 *
	 * @return string HTML and JavaScript
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function getCalendarJS() {
		return '<style type="text/css">@import url(../../../Resources/JS/jscalendar-1.0/skins/aqua/theme.css);</style>
			<script type="text/javascript" src="../../../Resources/JS/jscalendar-1.0/calendar.js"></script>
			<script type="text/javascript" src="../../../Resources/JS/jscalendar-1.0/lang/calendar-en.js"></script>
			<script type="text/javascript" src="../../../Resources/JS/jscalendar-1.0/calendar-setup.js"></script>

			<script type="text/javascript">
				Calendar.setup(
				    {
				      inputField  : "startdate",         // ID of the input field
				      ifFormat    : "%d.%m.%Y",    // the date format
				      button      : "trigger_startdate"       // ID of the button
				    }
				  );
				 Calendar.setup(
				    {
				      inputField  : "enddate",         // ID of the input field
				      ifFormat    : "%d.%m.%Y",    // the date format
				      button      : "trigger_enddate"       // ID of the button
				    }
				  );
	
			</script>
		';
	}
	
	/**
	 * This function returns the index table.
	 *
	 * @param array &$records The records to show in table
	 * @return string HTML
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function getTable(&$records) {
		global $LANG;
		
		if(count($records) == 0) {
			return "<div>'.$LANG->getLL('no_records').'</div>";
		}
		
		
		//init gp params
		$params = t3lib_div::_GP('mailformplusplus');
		
		//get filter
		$table = $this->getFilterSection();
		
		//add JavaScript
		$table .= $this->getSelectionJS();
		
		//add "Export as" and "Select all", "Deselect all" options
		$table .= '
			<form id="mailformplusplus_module_form" action="'.$_SERVER['PHP_SELF'].'" method="post">
			<div>
			<div style="width:250px;float:left">
				Export selected as: 
				<input type="submit" value="'.$LANG->getLL('pdf').'" name="mailformplusplus[renderMethod]" />
				<input type="submit" value="'.$LANG->getLL('csv').'" name="mailformplusplus[renderMethod]" />
			</div>
			<div style="width:200px;float:left;">
				<input type="submit" value="'.$LANG->getLL('delete_selected').'" name="mailformplusplus[delete]" />
			</div>
			<div style="width:200px;float:right">
				<input type="button" onclick="selectAll()" value="'.$LANG->getLL('select_all').'" />
				<input type="button" onclick="deselectAll()" value="'.$LANG->getLL('deselect_all').'" />
			</div>
			<div style="clear:both;"></div>
			</div>
		';
		
		//start table
		$table .= '
			<!--<script src="../../../Resources/JS/sorttable.js"></script>-->
			
			<table class="sortable">
				<tr style="font-size:large;font-weight:bold;background: #cccccc;">
					<td>'.$LANG->getLL('page_id').'</th>
					<td>'.$LANG->getLL('submission_date').'</th>
					<td>'.$LANG->getLL('ip_address').'</th>
					<td>'.$LANG->getLL('detail_view').'</th>
					<td>'.$LANG->getLL('export').'</th>
					<td>&nbsp;</th>
				</tr>
			';
		
		$count = 1;
		
		//add records
		foreach($records as $record) {
			if($count % 2 == 0) {
				$style="";		
			} else {
				$style="background-color:#dedede";
			}
			$table .= '
				<tr style="'.$style.'">
					<td>'.$record['pid'].'</td>
					<td>'.date('Y/m/d H:i',$record['crdate']).'</td>
					<td>'.$record['ip'].'</td>
					<td><a href="'.$_SERVER['PHP_SELF'].'?mailformplusplus[detailId]='.$record['uid'].'">'.$LANG->getLL('show').'</a></td>
					<td>
						<a href="'.$_SERVER['PHP_SELF'].'?mailformplusplus[detailId]='.$record['uid'].'&mailformplusplus[renderMethod]=pdf">PDF</a>
						/<a href="'.$_SERVER['PHP_SELF'].'?mailformplusplus[detailId]='.$record['uid'].'&mailformplusplus[renderMethod]=csv">CSV</a>
					</td>
					<td><input type="checkbox" name="mailformplusplus[markedUids][]" value="'.$record['uid'].'" ';
				if(isset($params['markedUids']) && is_array($params['markedUids']) && in_array($record['uid'],$params['markedUids'])) {
					$table .= 'checked="checked"';
				}
				$table .= '	
					/></td>
				</tr>
			';
			$count++;
		}
		
		//add Export as option
		$table .= '
			</table>
			Export selected as: 
			<input type="submit" value="PDF" name="mailformplusplus[renderMethod]" />
			<input type="submit" value="CSV" name="mailformplusplus[renderMethod]" />
			</form>
		';
		return $table;
	}

}
?>

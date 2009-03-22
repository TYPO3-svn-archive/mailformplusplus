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
 * Controller for Backend Module of MailformPlusPlus handling the "clear log" option
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Controller
 */
class F3_MailformPlusPlus_Controller_BackendClearLogs extends F3_MailformPlusPlus_AbstractController {


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
	 * The constructor for a finisher setting the component manager and the configuration.
	 *
	 * @param F3_GimmeFive_Component_Manager $componentManager
	 * @param F3_MailformPlusPlus_Configuration $configuration
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
	 */
	protected function init() {
		global $LANG;
		$LANG->includeLLFile('EXT:mailformplusplus/Resources/Language/locallang.xml');
		$templatePath = t3lib_extMgm::extPath('mailformplusplus').'Resources/HTML/backend/';
		$templateFile = $templatePath.'template.html';
		$this->templateCode = t3lib_div::getURL($templateFile);
	}

	/**
	 * Main method of the controller.
	 *
	 * @return string rendered view
	 */
	public function process() {
		

		//init
		$this->init();

		//init gp params
		$params = t3lib_div::_GP('mailformplusplus');
		
		if(isset($params['clearTables']) && is_array($params['clearTables'])) {
			$this->clearTables($params['clearTables']);
		}

		return $this->getOverview();
	}
	
	/**
	 * Truncates tables.
	 *
	 * @param array The names of the tables to truncate
	 * @return void
	 */
	protected function clearTables($tablesArray) {
		foreach($tablesArray as $table) {
			$GLOBALS['TYPO3_DB']->sql_query('TRUNCATE '.$table);
		}
	}
	
	/**
	 * Returns HTML code for an overview table showing all found tables and how many rows are in them.
	 *
	 * @global $LANG
	 * @return string
	 */
	protected function getOverview() {
		global $LANG;
		$existingTables = $GLOBALS['TYPO3_DB']->admin_get_tables();
		$code = F3_MailformPlusPlus_StaticFuncs::getSubpart($this->templateCode,'###CLEAR_LOGS###');
		$markers = array();
		$markers['###URL###'] = $_SERVER['PHP_SELF'];
		$markers['###LLL:table###'] = $LANG->getLL('table');
		$markers['###LLL:total_rows###'] = $LANG->getLL('total_rows');
		
		$markers['###TABLES###'] = '';
		foreach($existingTables as $table=>$tableSettings) {
			
			
			if(strpos($table,'tx_mailformplusplus_') > -1) {
				$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT COUNT(*) as rowCount FROM '.$table);
				if($res) {
					$rowCode = F3_MailformPlusPlus_StaticFuncs::getSubpart($this->templateCode,'###CLEAR_LOGS_TABLE###');
					$tableMarkers = array();
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
					$tableMarkers['###TABLE###'] = $table;
					$tableMarkers['###ROW_COUNT###'] = $row['rowCount'];
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
					$markers['###TABLES###'] .= F3_MailformPlusPlus_StaticFuncs::substituteMarkerArray($rowCode,$tableMarkers);
				}
				
			}
			
		}
		$markers['###LLL:clear###'] = $LANG->getLL('clear_selected_tables');
		return F3_MailformPlusPlus_StaticFuncs::substituteMarkerArray($code,$markers);
	}

}
?>

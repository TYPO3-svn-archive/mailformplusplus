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
 * Class to generate CSV files in Backend and Frontend
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Generator
 * @uses export2CSV in csv.lib.php
 */
class F3_MailformPlusPlus_Generator_CSV {

	/**
	 * The internal CSV object
	 *
	 * @access protected
	 * @var export2CSV
	 */
	protected $csv;

	/**
	 * The GimmeFive component manager
	 *
	 * @access protected
	 * @var F3_GimmeFive_Component_Manager
	 */
	protected $componentManager;

	/**
	 * Default Constructor
	 *
	 * @param F3_GimmeFive_Component_Manager $componentManager The component manager of GimmeFive
	 * @return void
	 */
	public function __construct(F3_GimmeFive_Component_Manager $componentManager) {
		$this->componentManager = $componentManager;

	}

	/**
	 * Function to generate a CSV file from submitted form values. This function is called by F3_MailformPlusPlus_Controller_Backend
	 *
	 * @param array $records The records to export to CSV
	 * @param array $exportParams A list of fields to export. If not set all fields are exported
	 * @see F3_MailformPlusPlus_Controller_Backend::generateCSV()
	 * @return void
	 */
	public function generateModuleCSV($records, $exportParams = array()) {

		//require class for $this->csv
		require_once('../../../Resources/PHP/csv.lib.php');
		$data = array();

		//build data array
		foreach($records as $record) {
			if(!is_array($record['params'])) {
				$record['params'] = array();
			}
			foreach($record['params'] as &$param) {
				if(is_array($param)) {
					$param = implode(';', $param);
				}
			}
			$data[] = $record['params'];
		}
		if(count($exportParams) > 0) {
			foreach($data as &$params) {
				foreach($params as $key => $value) {
					if(!in_array($key, $exportParams)) {
						unset($params[$key]);
					}
				}
			}
		}

		//init csv object
		$this->csv = new export2CSV(',', "\n");

		//generate file
		$this->csv = $this->csv->create_csv_file($data);
		header('Content-type: application/eml');
		header('Content-Disposition: attachment; filename=mailformplusplus.csv');
		echo $this->csv;
		die();
	}

	/**
	 * Function to generate a CSV file from submitted form values. This function is called by F3_MailformPlusPlus_Finisher_Confirmation
	 *
	 * @param array $params The values to export to CSV
	 * @param array $exportParams A list of fields to export. If not set all fields are exported
	 * @see F3_MailformPlusPlus_Finisher_Confirmation::process()
	 * @return void
	 */
	public function generateFrontendCSV($params, $exportParams = array()) {
		//require class for $this->csv
		require_once('typo3conf/ext/mailformplusplus/Resources/PHP/csv.lib.php');

		//build data
		foreach($params as $key => &$value) {
			if(is_array($value)) {
				$value = implode(',', $value);
			}
			if(count($exportParams) > 0 && !in_array($key, $exportParams)) {
				unset($params[$key]);
			}
			$value = str_replace('"', '""', $value);
		}

		//init csv object
		$this->csv = new export2CSV(',', "\n");
		$data[0] = $params;

		//generate file
		$this->csv = $this->csv->create_csv_file($data);
		header('Content-type: application/eml');
		header('Content-Disposition: attachment; filename=mailformplusplus.csv');
		echo $this->csv;
		die();
	}
}
?>

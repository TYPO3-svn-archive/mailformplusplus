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
 * A view for Finisher_Confirmation used by MailformPlusPlus
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	View
 */
class F3_MailformPlusPlus_View_Confirmation extends F3_MailformPlusPlus_View_Default {

	/**
     * Main method called by the controller.
     * 
     * @param array $gp The current GET/POST parameters
     * @param array $errors The errors occurred in validation
     * @return string content
     */
	public function render($gp,$errors) {
		
		//set GET/POST parameters
		$this->gp = $gp;
		
		//set template
		$this->template = $this->subparts['template'];
		
		//set settings
		$this->settings = $this->parseSettings();
		
		#print_r($this->settings);
		
		//set language file
		if(!$this->langFile) {
			$this->readLangFile();
		}
		
		//fill Typoscript markers
		if(is_array($this->settings['markers.'])) {
			$this->fillTypoScriptMarkers();
		}

		//fill default markers
		$this->fillDefaultMarkers();
		
		//fill value_[fieldname] markers
		$this->fillValueMarkers();
		
		//fill LLL:[language_key] markers
		$this->fillLangMarkers();
		
		$markers = F3_MailformPlusPlus_StaticFuncs::substituteIssetSubparts($this->template);
		$this->template = $this->cObj->substituteMarkerArray($this->template, $markers);

		//remove markers that were not substituted
		$content = F3_MailformPlusPlus_StaticFuncs::removeUnfilledMarkers($this->template);
		
		return $this->pi_wrapInBaseClass($content);
	}
	
	/**
     * This function fills the default markers:
     * 
     * ###PRINT_LINK###
     * ###PDF_LINK###
     * ###CSV_LINK###
     * 
     * @return string Template with replaced markers
     */
	protected function fillDefaultMarkers() {
		parent::fillDefaultMarkers();
		if($this->settings['formValuesPrefix']) {
			$params[$this->settings['formValuesPrefix']] = $this->gp;
		} else {
			$params = $this->gp;
		}
		$params['type'] = 98;
		$markers['###PRINT_LINK###'] = $this->cObj->getTypolink("Print",$GLOBALS['TSFE']->id,$params);
		unset($params['type']);
		if($this->settings['formValuesPrefix']) {
			$params[$this->settings['formValuesPrefix']]['renderMethod'] = "pdf";
		} else {
			$params['renderMethod'] = "pdf";
		}
		$markers['###PDF_LINK###'] = $this->cObj->getTypolink("Save as PDF",$GLOBALS['TSFE']->id,$params);
		if($this->settings['formValuesPrefix']) {
			$params[$this->settings['formValuesPrefix']]['renderMethod'] = "csv";
		} else {
			$params['renderMethod'] = "csv";
		}
		$markers['###CSV_LINK###'] = $this->cObj->getTypolink("Save as CSV",$GLOBALS['TSFE']->id,$params);
		$this->template = $this->cObj->substituteMarkerArray($this->template, $markers);
	}
}
?>

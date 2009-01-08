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
 * A finisher showing the content of ###TEMPLATE_CONFIRMATION### replacing all common MailformPlusPlus markers
 * plus ###PRINT_LINK###, ###PDF_LINK### and ###CSV_LINK###.
 * 
 * The finisher sets a flag in $_SESSION, so that MailformPlusPlus will only call this finisher and nothing else if the user reloads the page.
 * 
 * A sample configuration looks like this:
 * <code>
 * finishers.3.class = F3_MailformPlusPlus_Finisher_Confirmation
 * finishers.3.returns = 1
 * finishers.3.config.pdf.class = F3_MailformPlusPlus_Generator_PDF
 * finishers.3.config.pdf.exportFields = firstname,lastname,interests,pid,ip,submission_date
 * finishers.3.config.pdf.export2File = 1
 * finishers.3.config.csv.class = F3_MailformPlusPlus_Generator_CSV
 * finishers.3.config.csv.exportFields = firstname,lastname,interests
 * </code>
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Finisher
 */
class F3_MailformPlusPlus_Finisher_Confirmation extends F3_MailformPlusPlus_AbstractFinisher {
	
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
		
		//make cObj instance for pageLink creation
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->cObj->setCurrentVal($GLOBALS['TSFE']->id);
		
		
		//set session value to prevent another validation or finisher circle. MailformPlusPlus will call only this Finisher if the user reloads the page.
		session_start();
		$_SESSION['submitted_ok'] = 1;
		
		//read template file
		if(!$this->templateFile) {
			$templateFile = $settings['templateFile'];
			if(isset($settings['templateFile.']) && is_array($settings['templateFile.'])) {
				$this->templateFile = $this->cObj->cObjGetSingle($settings['templateFile'],$settings['templateFile.']);
			} else {
				$this->templateFile = t3lib_div::getURL(F3_MailformPlusPlus_StaticFuncs::resolvePath($templateFile));
			}
		}
		
		//set view
		$view = $this->componentManager->getComponent("F3_MailformPlusPlus_View_Confirmation");
		
		#print_r($gp);
		//render pdf
		if(!strcasecmp($gp['renderMethod'],"pdf")) {
			
			//set language file
			if(isset($settings['langFile.']) && is_array($settings['langFile.'])) {
				$langFile = $this->cObj->cObjGetSingle($settings['langFile'],$settings['langFile.']);
			} else {
				$langFile = F3_MailformPlusPlus_StaticFuncs::resolvePath($settings['langFile']);
			}
			$generatorClass = $settings['pdf.']['class'];
			if(!$generatorClass) {
				$generatorClass = "F3_MailformPlusPlus_Generator_PDF";
			}
			$generator = $this->componentManager->getComponent($generatorClass);
			$exportFields = array();
			if($settings['pdf.']['exportFields']) {
				$exportFields = t3lib_div::trimExplode(",",$settings['pdf.']['exportFields']);
			}
			$file = "";
			if($settings['pdf.']['export2File']) {
				//tempnam seems to be buggy and insecure
				//$file = tempnam("typo3temp/","/mailformplusplus_").".pdf";
				
				//using random numbered file for now
				$file = 'typo3temp/mailformplusplus__'.rand(0,getrandmax()).".pdf";
			}
			$generator->generateFrontendPDF($gp,$langFile,$exportFields,$file);
			
		//render csv
		} elseif(!strcasecmp($gp['renderMethod'],"csv")) {
			$generatorClass = $settings['csv.']['class'];
			if(!$generatorClass) {
				$generatorClass = "F3_MailformPlusPlus_Generator_CSV";
			}
			$generator = $this->componentManager->getComponent($generatorClass);
			$exportFields = array();
			if($settings['csv.']['exportFields']) {
				$exportFields = t3lib_div::trimExplode(",",$settings['csv.']['exportFields']);
			}
			$generator->generateFrontendCSV($gp,$exportFields);
		}
		
		//show TEMPLATE_CONFIRMATION
		$view->setTemplate($this->templateFile, 'CONFIRMATION');
		$view->setSettings($settings);
		return $view->render($gp,array());
	}
	
	
}
?>
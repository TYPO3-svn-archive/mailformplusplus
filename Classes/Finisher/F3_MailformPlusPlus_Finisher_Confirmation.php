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
 * A finisher showing the content of ###TEMPLATE_CONFIRMATION### replacing all common MailformPlusPlus markers
 * plus ###PRINT_LINK###, ###PDF_LINK### and ###CSV_LINK###.
 *
 * The finisher sets a flag in $_SESSION, so that MailformPlusPlus will only call this finisher and nothing else if the user reloads the page.
 *
 * A sample configuration looks like this:
 * <code>
 * finishers.3.class = F3_MailformPlusPlus_Finisher_Confirmation
 * finishers.3.config.returns = 1
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
	 * @return array The probably modified GET/POST parameters
	 */
	public function process() {

		//set session value to prevent another validation or finisher circle. MailformPlusPlus will call only this Finisher if the user reloads the page.
		session_start();
		$_SESSION['submitted_ok'] = 1;

		//read template file
		if(!$this->templateFile) {
			if (!isset($this->settings['templateFile'])) {
				t3lib_div::debug($this->settings);
				F3_MailformPlusPlus_StaticFuncs::throwException('no_config_confirmation', 'F3_MailformPlusPlus_Finisher_Confirmation', 'templateFile');
			}
			$templateFile = $this->settings['templateFile'];
			if(isset($this->settings['templateFile.']) && is_array($this->settings['templateFile.'])) {
				$this->templateFile = $this->cObj->cObjGetSingle($this->settings['templateFile'], $this->settings['templateFile.']);
			} else {
				$this->templateFile = t3lib_div::getURL(F3_MailformPlusPlus_StaticFuncs::resolvePath($templateFile));
			}
		}

		//set view
		$view = $this->componentManager->getComponent('F3_MailformPlusPlus_View_Confirmation');
			
		//render pdf
		if(!strcasecmp($this->gp['renderMethod'], 'pdf')) {
				
			//set language file
			if(isset($this->settings['langFile.']) && is_array($this->settings['langFile.'])) {
				$langFile = $this->cObj->cObjGetSingle($this->settings['langFile'], $this->settings['langFile.']);
			} else {
				$langFile = F3_MailformPlusPlus_StaticFuncs::resolveRelPathFromSiteRoot($this->settings['langFile']);
			}
			$generatorClass = $this->settings['pdf.']['class'];
			if(!$generatorClass) {
				$generatorClass = 'F3_MailformPlusPlus_Generator_PDF';
			}
			$generatorClass = F3_MailformPlusPlus_StaticFuncs::prepareClassName($generatorClass);
			$generator = $this->componentManager->getComponent($generatorClass);
			$exportFields = array();
			if($this->settings['pdf.']['exportFields']) {
				$exportFields = t3lib_div::trimExplode(',', $this->settings['pdf.']['exportFields']);
			}
			$file = "";
			if($this->settings['pdf.']['export2File']) {
				//tempnam seems to be buggy and insecure
				//$file = tempnam("typo3temp/","/mailformplusplus_").".pdf";

				//using random numbered file for now
				$file = 'typo3temp/mailformplusplus__' . rand(0,getrandmax()) . '.pdf';
			}
			$generator->setTemplateCode($this->templateFile);
			$generator->generateFrontendPDF($this->gp, $langFile, $exportFields, $file);
				
			//render csv
		} elseif(!strcasecmp($this->gp['renderMethod'],"csv")) {
			$generatorClass = $this->settings['csv.']['class'];
			if(!$generatorClass) {
				$generatorClass = 'F3_MailformPlusPlus_Generator_CSV';
			}
			$generatorClass = F3_MailformPlusPlus_StaticFuncs::prepareClassName($generatorClass);
			$generator = $this->componentManager->getComponent($generatorClass);
			$exportFields = array();
			if($this->settings['csv.']['exportFields']) {
				$exportFields = t3lib_div::trimExplode(',', $this->settings['csv.']['exportFields']);
			}
			$generator->generateFrontendCSV($this->gp, $exportFields);
		}

		//show TEMPLATE_CONFIRMATION
		$view->setTemplate($this->templateFile, 'CONFIRMATION');
		$view->setSettings($this->settings);
		return $view->render($this->gp,array());
	}

}
?>

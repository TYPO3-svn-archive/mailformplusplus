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
 * $Id: F3_MailformPlusPlus_View_Default.php 17657 2009-03-10 11:17:52Z reinhardfuehricht $
 *                                                                        */

/**
 * A default view for MailformPlusPlus
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	View
 */
class F3_MailformPlusPlus_View_Mail extends F3_MailformPlusPlus_View_Default {

	/**
	 * Main method called by the controller.
	 *
	 * @param array $gp The current GET/POST parameters
	 * @param array $errors The errors occurred in validation
	 * @return string content
	 */
	public function render($gp,$errors) {


		session_start();

		//set GET/POST parameters
		$this->gp = $gp;

		//set template
		$this->template = $this->subparts['template'];

		//set settings
		$this->settings = $this->parseSettings();

		//set language file
		if(!$this->langFile) {
			$this->readLangFile();
		}
		
		//substitute ISSET markers
		$this->substituteIssetSubparts();

		//fill TypoScript markers
		if(is_array($this->settings['markers.'])) {
			$this->fillTypoScriptMarkers();
		}

		//fill default markers
		$this->fillDefaultMarkers();

		//fill value_[fieldname] markers
		$this->fillValueMarkers();

		//fill LLL:[language_key] markers
		$this->fillLangMarkers();


		//remove markers that were not substituted
		$content = F3_MailformPlusPlus_StaticFuncs::removeUnfilledMarkers($this->template);


		return $content;
	}

}
?>
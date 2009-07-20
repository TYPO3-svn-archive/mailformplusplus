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
 * $Id: F3_MailformPlusPlus_View_Default.php 18270 2009-03-24 15:41:29Z fabien_u $
 *          
 *                                                                        
 *                                                                       */

/**
 * A default view for MailformPlusPlus
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	View
 */
class F3_MailformPlusPlus_View_PDF extends F3_MailformPlusPlus_View_Form {

	/**
	 * Main method called by the controller.
	 *
	 * @param array $gp The current GET/POST parameters
	 * @param array $errors The errors occurred in validation
	 * @return string content
	 */
	public function render($gp, $errors) {
		$this->gp = $gp;
		session_start();
		$this->settings = $this->parseSettings();
		$this->sanitizeMarkers();
		$content = parent::render($this->gp, $errors);

		return $this->pi_wrapInBaseClass($content);
	}
	
	/**
	 * Sanitizes GET/POST parameters by processing the 'checkBinaryCrLf' setting in TypoScript
	 *
	 * @return void
	 */
	protected function sanitizeMarkers() {
		$checkBinaryCrLf = $this->settings['checkBinaryCrLf'];
		if ($checkBinaryCrLf != '') {
			$paramsToCheck = t3lib_div::trimExplode(',', $checkBinaryCrLf);
			foreach($paramsToCheck as &$val) {
				
				$val = str_replace (chr(13), '<br />', $val);
				$val = str_replace ('\\', '', $val);

			}
		}
		foreach($this->gp as $field => &$value) {
			$value = nl2br($value);
		}
	}
}
?>
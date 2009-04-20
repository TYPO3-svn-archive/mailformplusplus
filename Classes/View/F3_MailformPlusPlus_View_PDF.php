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
class F3_MailformPlusPlus_View_PDF extends F3_MailformPlusPlus_View_Default {

	/**
	 * Main method called by the controller.
	 *
	 * @param array $gp The current GET/POST parameters
	 * @param array $errors The errors occurred in validation
	 * @return string content
	 */
	public function render($gp, $errors) {
		$content = parent::render($gp, $errors);
		$markers = array();
		$markers['###ip###'] = t3lib_div::getIndpEnv('REMOTE_ADDR');
		$markers['###submission_date###'] = date('d.m.Y H:i:s', time());
		$markers['###pid###'] = $GLOBALS['TSFE']->id;
		
		$content = $this->cObj->substituteMarkerArray($content, $markers);

		return $this->pi_wrapInBaseClass($content);
	}
}
?>
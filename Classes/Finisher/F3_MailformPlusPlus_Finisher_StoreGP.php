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
 * $Id: F3_MailformPlusPlus_Finisher_StoreUploadedFiles.php 17980 2009-03-18 12:14:40Z erep $
 *                                                                        */

/**
 * This finisher stores GP to session for further use in other plugins and update $_SESSION 
 * to not loose changes in gp made by other finishers (e.g. insert_id from Finisher_DB)
 * Automaically called if plugin.F3_MailformPlusPlus.settings.predef.example.storeGP = 1 is set
 * No further configuration.
 *
 * @author Johannes Feustel
 * @package	F3_MailformPlusPlus
 * @subpackage	Finisher
 */
class F3_MailformPlusPlus_Finisher_StoreGP extends F3_MailformPlusPlus_AbstractFinisher {

	/**
	 * The main method called by the controller
	 *
	 * @return array The probably modified GET/POST parameters
	 */
	public function process() {

		//store in Session for further use by other plugins
		$this->storeUserGPinSession();

		//update $_SESSION['mailformplusplusValues']
		$this->updateSession();

		return $this->gp;
	}

	/**
	 * Stores the GP in session.
	 *
	 * @return void
	 */
	protected function storeUserGPinSession() {
		foreach ($this->gp as $key => $value) {
			$GLOBALS['TSFE']->fe_user->setKey('ses', $key, $value);
		}
	}

	/**
	 * Stores $this->gp parameters in SESSION
	 * actually only needed for finisher_confirmation
	 *
	 * @return void
	 */
	protected function updateSession() {
		session_start();

		//reset session
		unset($_SESSION['mailformplusplusValues']);

		//set the variables in session
		//no need to seperate steps in finishers, so simply store to step 1
		foreach($this->gp as $key => $value) {
			$_SESSION['mailformplusplusValues'][1][$key] = $value;
		}
	}

}
?>

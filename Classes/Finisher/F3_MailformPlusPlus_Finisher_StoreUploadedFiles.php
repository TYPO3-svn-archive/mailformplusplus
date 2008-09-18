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
 * This finisher stores uploaded files by a user to a final folder. At the time this finisher is called, it is assured, that the form was fully submitted and valid.
 * Use this finisher to move the uploaded files to a save folder where they are not cleared by a possibly time based deletion.
 * This class needs a parameter "finishedUploadFolder" to be set in TS.
 *
 * Sample configuration:
 * 
 * <code>
 * finishers.1.class = F3_MailformPlusPlus_Finisher_StoreUploadedFiles
 * finishers.1.config.finishedUploadFolder = uploads/mailformplusplus/finished/
 * </code>
 *
 * @author	Reinhard F�hricht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Finisher
 */
class F3_MailformPlusPlus_Finisher_StoreUploadedFiles extends F3_MailformPlusPlus_AbstractFinisher {
	
	/**
     * The main method called by the controller
     * 
     * @author Reinhard F�hricht <rf@typoheads.at>
     * @param array $gp The GET/POST parameters
     * @param array $settings The defined TypoScript settings for the finisher
     * @return array The probably modified GET/POST parameters
     */
	public function process($gp,$settings) {
		$this->gp = $gp;
		$this->settings = $settings;
		
		//read redirect page
		$email_redirect = $settings['redirect_page'];
		if($this->settings['finishedUploadFolder']) {
			
			//move the uploaded files
			$this->moveUploadedFiles();
		}
		
		return $this->gp;
	}
	
	/**
     * Moves uploaded files from temporary upload folder to a specified new folder.
     * This enables you to move the files from a successful submission to another folder and clean the files in temporary upload folder from time to time.
     * 
     * TypoScript example:
     * 
     * 1. Set the temporary upload folder and set cleaning
     * <code>
     * plugin.F3_MailformPlusPlus.settings.files.clearTempFilesOlderThanHours = 24
	 * plugin.F3_MailformPlusPlus.settings.files.tmpUploadFolder = uploads/mailformplusplus/tmp
     * </code>
     * 
     * 2. Set the folder to move the files to after submission
     * <code>
     * plugin.F3_MailformPlusPlus.settings.finishers.1.class = F3_MailformPlusPlus_Finisher_StoreUploadedFiles
     * plugin.F3_MailformPlusPlus.settings.finishers.1.config.finishedUploadFolder = uploads/mailformplusplus/finishedFiles/
     * </code>
     * 
     * @return void
     * @author Reinhard F�hricht <rf@typoheads.at>
     */
	protected function moveUploadedFiles() {
		session_start();
		$filesToCopy = array();
		if(is_array($_SESSION['mailformplusplusFiles'])) {
			foreach($_SESSION['mailformplusplusFiles'] as $field=>$files) {
				foreach($files as $file) {
					$fullFilename['path'] = $file['uploaded_path'];
					$fullFilename['name'] = $file['uploaded_name'];
					array_push($filesToCopy,$fullFilename);
				}
			}
			
			if(count($filesToCopy) > 0) {
				$newFolder = $this->settings['finishedUploadFolder'];
				if(strlen($newFolder) > 0 ) {
					$newFolder = F3_MailformPlusPlus_StaticFuncs::sanitizePath($newFolder);
					$uploadPath = F3_MailformPlusPlus_StaticFuncs::getDocumentRoot().$newFolder;
					foreach($filesToCopy as $file) {
						F3_MailformPlusPlus_StaticFuncs::debugMessage("Copying file '".$file['path'].$file['name']."' to '".$uploadPath.$file['name']."'!",false);
						copy($file['path'].$file['name'],$uploadPath.$file['name']);
						unlink($file['path'].$file['name']);
					}
				}
			}
		}
	}
	
}
?>
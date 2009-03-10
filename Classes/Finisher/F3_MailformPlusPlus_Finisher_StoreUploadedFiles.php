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
 * This finisher stores uploaded files by a user to a final folder. At the time this finisher is called, it is assured, that the form was fully submitted and valid.
 * Use this finisher to move the uploaded files to a save folder where they are not cleared by a possibly time based deletion.
 * This class needs a parameter "finishedUploadFolder" to be set in TS.
 *
 * Sample configuration:
 * 
 * <code>
 * finishers.1.class = F3_MailformPlusPlus_Finisher_StoreUploadedFiles
 * finishers.1.config.finishedUploadFolder = uploads/mailformplusplus/finished/
 * finishers.1.config.renameScheme = [pid]_[filename]_[md5]_[time]_[marker1]_[marker2]
 * finishers.1.config.schemeMarkers.marker1 = Value
 * finishers.1.config.schemeMarkers.marker2 = TEXT
 * finishers.1.config.schemeMarkers.marker2.value = Textvalue
 * </code>
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Finisher
 */
class F3_MailformPlusPlus_Finisher_StoreUploadedFiles extends F3_MailformPlusPlus_AbstractFinisher {
	
	/**
     * The main method called by the controller
     * 
     * @return array The probably modified GET/POST parameters
     */
	public function process() {
		
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
     * plugin.F3_MailformPlusPlus.settings.finishers.1.config.renameScheme = [filename]_[md5]_[time]
     * </code>
     * 
     * @return void
     */
	protected function moveUploadedFiles() {
		session_start();
		$filesToCopy = array();
		if(isset($_SESSION['mailformplusplusFiles']) && is_array($_SESSION['mailformplusplusFiles'])) {
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
					
					$newFilename = $this->getNewFilename($file['name']);
					
					foreach($filesToCopy as $file) {
						F3_MailformPlusPlus_StaticFuncs::debugMessage('copy_file',$file['path'].$file['name'],$uploadPath.$newFilename);
						copy($file['path'].$file['name'],$uploadPath.$newFilename);
						unlink($file['path'].$file['name']);
					}
				}
			}
		}
	}
	
	/**
     * Generates a new filename for an uploaded file using settings in TypoScript.
     * 
     * @param string The current filename
     * @return string The new filename
     * 
     **/
	protected function getNewFilename($oldName) {
		$fileparts = explode('.',$oldName);
		$fileext = '.'.$fileparts[count($fileparts)-1];
		array_pop($fileparts);
		$filename = implode('.',$fileparts);
		$namingScheme = $this->settings['renameScheme'];
		if(!$namingScheme) {
			$namingScheme = '[filename]_[time]';
		}
		$newFilename = $namingScheme;
		$newFilename = str_replace('[filename]',$filename,$newFilename);
		$newFilename = str_replace('[time]',time(),$newFilename);
		$newFilename = str_replace('[md5]',md5($filename),$newFilename);
		$newFilename = str_replace('[pid]',$GLOBALS['TSFE']->id,$newFilename);
		if(is_array($this->settings['schemeMarkers.'])) {
			foreach($this->settings['schemeMarkers.'] as $markerName=>$options) {
				if(!(strpos($markerName,'.') > 0)) {
					$value = $options;
					if(isset($this->settings['schemeMarkers.'][$markerName.'.'])) {
						$value = $this->cObj->cObjGetSingle($this->settings['schemeMarkers.'][$markerName],$this->settings['schemeMarkers.'][$markerName.'.']);
					}
					$newFilename = str_replace('['.$markerName.']',$value,$newFilename);
				}
			}
		}
		$newFilename .= $fileext;
		return $newFilename;
	}
	
}
?>

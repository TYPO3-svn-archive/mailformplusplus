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
 * A default view for MailformPlusPlus
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	View
 */
class F3_MailformPlusPlus_View_Default extends F3_MailformPlusPlus_AbstractView {

	/**
	 * Removes an uploaded file from $_SESSION. This method is called via an AJAX request.
	 *
	 * @param string $fieldname The field holding the file to delete
	 * @param string $filename The file to delete
	 * @return void
	 */
	public function removeUploadedFile($fieldname,$filename) {
		if(!t3lib_extMgm::isLoaded('xajax')) {
			return;
				
		}

		// Instantiate the tx_xajax_response object
		require (t3lib_extMgm::extPath('xajax') . 'class.tx_xajax.php');

		$objResponse = new tx_xajax_response();

		session_start();

		if(is_array($_SESSION['mailformplusplusFiles'])) {
			foreach($_SESSION['mailformplusplusFiles'] as $field=>$files) {

				if(!strcmp($field,$fieldname)) {
					foreach($files as $key=>&$fileInfo) {
						if(!strcmp($fileInfo['uploaded_name'],$filename)) {
							unset($_SESSION['mailformplusplusFiles'][$field][$key]);
						}
					}
				}
			}
		}

		// Add the content to or Result Box: #formResult
		if(is_array($_SESSION['mailformplusplusFiles'])) {
			$markers = array();
			$this->fillFileMarkers($markers);
			$content = $markers['###'.$fieldname.'_uploadedFiles###'];
			$objResponse->addAssign("F3_MailformPlusPlus_UploadedFiles_".$fieldname, "innerHTML", $content);
				
		} else {
			$objResponse->addAssign("F3_MailformPlusPlus_UploadedFiles_".$fieldname, "innerHTML", "");
		}

		//return the XML response
		return $objResponse->getXML();
	}


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

		$this->errors = $errors;

		//set language file
		if(!$this->langFile) {
			$this->readLangFile();
		}


		if(!$this->gp['submitted']) {
			$this->storeStartEndBlock();
		} else {
			$this->fillStartEndBlock();
		}
		
		//substitute ISSET markers
		$this->substituteIssetSubparts();

		//fill Typoscript markers
		if(is_array($this->settings['markers.'])) {
			$this->fillTypoScriptMarkers();
		}

		//fill default markers
		$this->fillDefaultMarkers();

		//fill value_[fieldname] markers
		$this->fillValueMarkers();

		//fill selected_[fieldname]_value markers and checked_[fieldname]_value markers
		$this->fillSelectedMarkers();

		//fill LLL:[language_key] markers
		$this->fillLangMarkers();

		#print_r($this->template);

		//fill error_[fieldname] markers
		if(!empty($errors)) {
			$this->fillErrorMarkers($errors);
		}

		//remove markers that were not substituted
		$content = F3_MailformPlusPlus_StaticFuncs::removeUnfilledMarkers($this->template);


		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Reads the translation file entered in TS setup.
	 *
	 * @return void
	 */
	protected function readLangFile() {
		if(is_array($this->settings['langFile.'])) {
			$this->langFile = $this->cObj->cObjGetSingle($this->settings['langFile'],$this->settings['langFile.']);
		} else {
			$this->langFile = F3_MailformPlusPlus_StaticFuncs::resolveRelPathFromSiteRoot($this->settings['langFile']);
		}
	}
	
	/**
	 * Helper method used by substituteIssetSubparts()
	 *
	 * @see F3_MailformPlusPlus_StaticFuncs::substituteIssetSubparts()
	 * @author  Stephan Bauer <stephan_bauer(at)gmx.de>
	 * @return boolean
	 */
	protected function markersCountAsSet($markers, $conditionValue) {

		// Find first || or && or !
		$pattern = '/(_*([A-Za-z0-9]+)_*(\|\||&&)_*([^_]+)_*)|(_*(!)_*([A-Za-z0-9]+))/';
		
		session_start();
		// recurse if there are more
		if( preg_match($pattern, $conditionValue, $matches) ){
			$isset = isset($this->gp[$matches[2]]);
			if($matches[3] == '||' && $isset) {
				$return = true;
			} elseif($matches[3] == '||' && !$isset) {
				$return = $this->markersCountAsSet($markers, $matches[4]);
			} elseif($matches[3] == '&&' && $isset) {
				$return = $this->markersCountAsSet($markers, $matches[4]);
			} elseif($matches[3] == '&&' && !$isset) {
				$return = false;
			} elseif($matches[6] == '!' && !$isset) {
				return !(isset($this->gp[$matches[7]]) && $this->gp[$matches[7]] != '');
			} elseif($_SESSION['mailformplusplusSettings']['debugMode'] == 1) {
				F3_MailformPlusPlus_StaticFuncs::debugMessage('invalid_isset',$matches[2]);
			}
		} else {

			// remove underscores
			$pattern = '/_*/';
			$str = preg_replace($pattern, $str, '');

			// end of recursion
			
			$return = isset($this->gp[$conditionValue]) && ($this->gp[$conditionValue] != '');
		}
		return $return;
	}
	
	/**
	 * Use or remove subparts with ISSET_[fieldname] patterns (thx to Stephan Bauer <stephan_bauer(at)gmx.de>)
	 *
	 * @param	string		$subpart: html content with markers
	 * @param	array		$markers: array with markername->substitution value
	 * @author  Stephan Bauer <stephan_bauer(at)gmx.de>
	 * @return	string		substituted HTML content
	 */
	protected function substituteIssetSubparts(){
		$flags = array();
		$nowrite = false;
		$out = array();
		foreach(split(chr(10), $this->template) as $line){

			// works only on it's own line
			$pattern = '/###isset_+([^#]*)_*###/i';

			// set for odd ISSET_xyz, else reset
			if(preg_match($pattern, $line, $matches)) {
				if(!$flags[$matches[1]]) { // set
					$flags[$matches[1]] = true;

					// set nowrite flag if required until the next ISSET_xyz
					// (only if not already set by envelop)
					if((!$this->markersCountAsSet($markers, $matches[1])) && (!$nowrite)) {
						$nowrite = $matches[1];
					}
				} else { // close it
					$flags[$matches[1]] = false;
					if($nowrite == $matches[1]) {
						$nowrite = 0;
					}
				}
			} else { // It is no ISSET_line. Write if permission is given.
				if(!$nowrite) {
					$out[] = $line;
				}
			}
		}
		//print_r($out);
		$out = implode(chr(10),$out);
		
		$this->template = $out;
	}

	/**
	 * Copies the subparts ###FORM_STARTBLOCK### and ###FORM_ENDBLOCK### and stored them in $_SESSION.
	 * This is needed to replace the markers ###FORM_STARTBLOCK### and ###FORM_ENDBLOCK### in the next steps.
	 *
	 * @return void
	 */
	protected function storeStartEndBlock() {
		session_start();
		if(!isset($_SESSION['startblock']) || empty($_SESSION['startblock'])) {
			$_SESSION['startblock'] = $this->cObj->getSubpart($this->template, '###FORM_STARTBLOCK###');
		}
		if(!isset($_SESSION['endblock']) || empty($_SESSION['endblock'])) {
			$_SESSION['endblock'] = $this->cObj->getSubpart($this->template, '###FORM_ENDBLOCK###');
		}
	}

	/**
	 * Fills the markers ###FORM_STARTBLOCK### and ###FORM_ENDBLOCK### with the stored values from $_SESSION.
	 *
	 * @return void
	 */
	protected function fillStartEndBlock() {
		session_start();
		$markers = array (
			'###FORM_STARTBLOCK###' => $_SESSION['startblock'],
			'###FORM_ENDBLOCK###' => $_SESSION['endblock']
		);

		$this->template = $this->cObj->substituteMarkerArray($this->template, $markers);
	}

	/**
	 * Returns the global TypoScript settings of MailformPlusPlus
	 *
	 * @return array The settings
	 */
	protected function parseSettings() {
		$settings = $this->configuration->getSettings();
		if(is_array($this->settings)) {
			return $this->settings;
		}
		if($this->predefined) {
			$settings = $settings['predef.'][$this->predefined];
		}
		session_start();
		if($_SESSION['mailformplusplusSettings']['settings']) {
			$settings = $_SESSION['mailformplusplusSettings']['settings'];
		}
		return $settings;
	}

	/**
	 * Substitutes markers
	 * 		###selected_[fieldname]_[value]###
	 * 		###checked_[fieldname]_[value]###
	 * in $this->template
	 *
	 * @return void
	 */
	protected function fillSelectedMarkers() {
		if (is_array($this->gp)) {
			foreach($this->gp as $k=>$v) {
				if (is_array($v)) {
					foreach ($v as $field=>$value) {
						$markers['###checked_'.$k.'_'.$value.'###'] = 'checked="checked"';
						$markers['###selected_'.$k.'_'.$value.'###'] = 'selected="selected"';
					}
				} else {
					$markers['###checked_'.$k.'_'.$v.'###'] = 'checked="checked"';
					$markers['###selected_'.$k.'_'.$v.'###'] = 'selected="selected"';
				}
			}
			$this->template = $this->cObj->substituteMarkerArray($this->template, $markers);
		}
	}

	/**
	 * Substitutes default markers in $this->template.
	 *
	 * @return void
	 */
	protected function fillDefaultMarkers() {
		$settings = $this->parseSettings();
		$markers = array();
		$path = $this->pi_getPageLink($GLOBALS['TSFE']->id);
		$markers['###REL_URL###'] = $path;
		$markers['###ABS_URL###'] = t3lib_div::locationHeaderUrl('').$path;
		$name = "step-1";
		if($settings['formValuesPrefix']) {
			$name = $settings['formValuesPrefix']."[".$name."]";
		}
		$markers['###submit_reload###'] = ' name="'.$name.'" ';
		$this->fillCaptchaMarkers($markers);
		$this->fillFEUserMarkers($markers);
		$this->fillFileMarkers($markers);
		$this->template = $this->cObj->substituteMarkerArray($this->template, $markers);
	}

	/**
	 * Fills the markers for the supported captcha extensions.
	 *
	 * @param array &$markers Reference to the markers array
	 * @return void
	 */
	protected function fillCaptchaMarkers(&$markers) {
		global $LANG;
		if (t3lib_extMgm::isLoaded('captcha')){
			$markers["###CAPTCHA###"] = '<img src="'.t3lib_extMgm::siteRelPath('captcha').'captcha/captcha.php" alt="" />';
			$markers["###captcha###"] = $markers['###CAPTCHA###'];
		}
		if (t3lib_extMgm::isLoaded('simple_captcha')) {
			require_once(t3lib_extMgm::extPath('simple_captcha', 'class.tx_simplecaptcha.php'));
			$simpleCaptcha_className = t3lib_div::makeInstanceClassName('tx_simplecaptcha');
			$this->simpleCaptcha = new $simpleCaptcha_className();
			$captcha = $this->simpleCaptcha->getCaptcha();
			$markers["###simple_captcha###"] = $captcha;
			$markers["###SIMPLE_CAPTCHA###"] = $captcha;
		}
		if (t3lib_extMgm::isLoaded('sr_freecap')){
			require_once(t3lib_extMgm::extPath('sr_freecap').'pi2/class.tx_srfreecap_pi2.php');
			$this->freeCap = t3lib_div::makeInstance('tx_srfreecap_pi2');
			$markers = array_merge($markers,$this->freeCap->makeCaptcha());
		}
		if (t3lib_extMgm::isLoaded('jm_recaptcha')) {
			require_once(t3lib_extMgm::extPath('jm_recaptcha')."class.tx_jmrecaptcha.php");
			$this->recaptcha = new tx_jmrecaptcha();
			$markers["###RECAPTCHA###"] = $this->recaptcha->getReCaptcha();
			$markers["###recaptcha###"] = $markers['###RECAPTCHA###'];
		}

		if (t3lib_extMgm::isLoaded('wt_calculating_captcha')) {
			require_once(t3lib_extMgm::extPath('wt_calculating_captcha').'class.tx_wtcalculatingcaptcha.php');

			$captcha = t3lib_div::makeInstance('tx_wtcalculatingcaptcha');

			$markers["###WT_CALCULATING_CAPTCHA###"] = $captcha->generateCaptcha();
			$markers["###wt_calculating_captcha###"] = $markers['###WT_CALCULATING_CAPTCHA###'];
		}

		require_once(t3lib_extMgm::extPath('mailformplusplus')."Resources/PHP/mathguard/ClassMathGuard.php");
		$langFile = "EXT:mailformplusplus/Resources/Language/locallang.xml";
		$question = trim($GLOBALS['TSFE']->sL('LLL:'.$langFile.':mathguard_question'));
		$markers["###MATHGUARD###"] = MathGuard::returnQuestion($question,"red");
		$markers["###mathguard###"] = $markers["###MATHGUARD###"];
	}

	/**
	 * Fills the markers ###FEUSER_[property]### with the data from $GLOBALS["TSFE"]->fe_user->user.
	 *
	 * @param array &$markers Reference to the markers array
	 * @return void
	 */
	protected function fillFEUserMarkers(&$markers) {
		if (is_array($GLOBALS["TSFE"]->fe_user->user)) {
			foreach($GLOBALS["TSFE"]->fe_user->user as $k=>$v) {
				$markers['###FEUSER_'.strtoupper($k).'###'] = $v;
				$markers['###FEUSER_'.strtolower($k).'###'] = $v;
				$markers['###feuser_'.strtoupper($k).'###'] = $v;
				$markers['###feuser_'.strtolower($k).'###'] = $v;
			}
		}
	}

	/**
	 * Fills the file specific markers:
	 *
	 *  ###[fieldname]_minSize###
	 *  ###[fieldname]_maxSize###
	 *  ###[fieldname]_allowedTypes###
	 *  ###[fieldname]_maxCount###
	 *  ###[fieldname]_fileCount###
	 *  ###[fieldname]_remainingCount###
	 *
	 *  ###[fieldname]_uploadedFiles###
	 *  ###total_uploadedFiles###
	 *
	 * @param array &$markers Reference to the markers array
	 * @return void
	 */
	protected function fillFileMarkers(&$markers) {
		session_start();
		$settings = $this->parseSettings();

		//parse validation settings
		if(is_array($settings['validators.'])) {
			foreach($settings['validators.'] as $key=>$validatorSettings) {
				if(is_array($validatorSettings['config.']['fieldConf.'])) {
					foreach($validatorSettings['config.']['fieldConf.'] as $fieldname=>$fieldSettings) {
						if(is_array($fieldSettings['errorCheck.'])) {
							foreach($fieldSettings['errorCheck.'] as $key=>$check) {
								switch($check) {
									case "file_minSize":
										$minSize = $fieldSettings['errorCheck.'][$key."."]['minSize'];
										$markers["###".str_replace(".","",$fieldname)."_minSize###"] = t3lib_div::formatSize($minSize,' Bytes | KB | MB | GB');
										break;
									case "file_maxSize":
										$maxSize = $fieldSettings['errorCheck.'][$key."."]['maxSize'];
										$markers["###".str_replace(".","",$fieldname)."_maxSize###"] = t3lib_div::formatSize($maxSize,' Bytes | KB | MB | GB');
										break;
									case "file_allowedTypes":
										$types = $fieldSettings['errorCheck.'][$key."."]['allowedTypes'];
										$markers["###".str_replace(".","",$fieldname)."_allowedTypes###"] = $types;
										break;
									case "file_maxCount":
										$maxCount = $fieldSettings['errorCheck.'][$key."."]['maxCount'];
										$markers["###".str_replace(".","",$fieldname)."_maxCount###"] = $maxCount;
											
										$fileCount = count($_SESSION['mailformplusplusFiles'][str_replace(".","",$fieldname)]);
										$markers["###".str_replace(".","",$fieldname)."_fileCount###"] = $fileCount;
											
										$remaining = $maxCount - $fileCount;
										$markers["###".str_replace(".","",$fieldname)."_remainingCount###"] = $remaining;
										break;
									case "required":
										$markers['###required_'.str_replace(".","",$fieldname).'###'] = (isset($settings['requiredSign']))?$settings['requiredSign']:"*";
										break;
								}
							}
						}
					}
				}
			}
		}
		if(is_array($_SESSION['mailformplusplusFiles'])) {
			$singleWrap = $settings['singleFileMarkerTemplate.']['singleWrap'];
			$totalMarkerSingleWrap = $settings['totalFilesMarkerTemplate.']['singleWrap'];
			$totalWrap = $settings['singleFileMarkerTemplate.']['totalWrap'];
			$totalMarkersTotalWrap = $settings['totalFilesMarkerTemplate.']['totalWrap'];
			foreach($_SESSION['mailformplusplusFiles'] as $field=>$files) {
				foreach($files as $fileInfo) {
					$filename = $fileInfo['name'];
					$thumb = '';
					if($settings['singleFileMarkerTemplate.']['showThumbnails'] == '1') {
						$imgConf['image'] = 'IMAGE';
						$imgConf['image.']['altText'] = $filename;
						$imgConf['image.']['titleText'] = $filename;

						$relPath = substr($fileInfo['uploaded_folder'].$filename,1);
						$imgConf['image.']['file'] = $relPath;
						if($settings['singleFileMarkerTemplate.']['thumbnailWidth']) {
							$imgConf['image.']['file.']['width'] = $settings['singleFileMarkerTemplate.']['thumbnailWidth'];
						}
						if($settings['singleFileMarkerTemplate.']['thumbnailHeight']) {
							$imgConf['image.']['file.']['height'] = $settings['singleFileMarkerTemplate.']['thumbnailHeight'];
						}
						$thumb = $this->cObj->IMAGE($imgConf['image.']);
					}
					if(t3lib_extMgm::isLoaded('xajax') && $settings['files.']['enableAjaxFileRemoval']) {
						$filename .= '<a href="javascript:void" class="mailformplusplus_removelink" onclick="xajax_'.$this->prefixId.'_removeUploadedFile(\''.$field.'\',\''.$fileInfo['uploaded_name'].'\')">X</a>';
						$thumb .= '<a href="javascript:void" class="mailformplusplus_removelink" onclick="xajax_'.$this->prefixId.'_removeUploadedFile(\''.$field.'\',\''.$fileInfo['uploaded_name'].'\')">X</a>';
					}
					if(strlen($singleWrap) > 0 && strstr($singleWrap,"|")) {
						$wrappedFilename = str_replace("|",$filename,$singleWrap);
						$wrappedThumb = str_replace("|",$thumb,$singleWrap);
					} else {
						$wrappedFilename = $filename;
						$wrappedThumb = $thumb;
					}
					if($settings['singleFileMarkerTemplate.']['showThumbnails'] == '1') {
						$markers['###'.$field.'_uploadedFiles###'] .= $wrappedThumb;
					} else {
						$markers['###'.$field.'_uploadedFiles###'] .= $wrappedFilename;
					}
						
					if($settings['totalFilesMarkerTemplate.']['showThumbnails'] == '1') {
						$imgConf['image'] = 'IMAGE';
						$imgConf['image.']['altText'] = $filename;
						$imgConf['image.']['titleText'] = $filename;

						$relPath = substr($fileInfo['uploaded_folder'].$filename,1);
						$imgConf['image.']['file'] = $relPath;
						if($settings['totalFilesMarkerTemplate.']['thumbnailWidth']) {
							$imgConf['image.']['file.']['width'] = $settings['totalFilesMarkerTemplate.']['thumbnailWidth'];
						}
						if($settings['totalFilesMarkerTemplate.']['thumbnailHeight']) {
							$imgConf['image.']['file.']['height'] = $settings['totalFilesMarkerTemplate.']['thumbnailHeight'];
						}
						$thumb = $this->cObj->IMAGE($imgConf['image.']);
					}
						
					if(strlen($totalMarkerSingleWrap) > 0 && strstr($totalMarkerSingleWrap,"|")) {

						$wrappedFilename = str_replace("|",$filename,$totalMarkerSingleWrap);
						$wrappedThumb = str_replace("|",$thumb,$totalMarkerSingleWrap);
					} else {
						$wrappedFilename = $filename;
						$wrappedThumb = $thumb;
					}
						
					if($settings['totalFilesMarkerTemplate.']['showThumbnails'] == '1') {
						$markers['###total_uploadedFiles###'] .= $wrappedThumb;
					} else {
						$markers['###total_uploadedFiles###'] .= $wrappedFilename;
					}
						
						
				}
				if(strlen($totalWrap) > 0 && strstr($totalWrap,"|")) {
					$markers['###'.$field.'_uploadedFiles###'] = str_replace("|",$markers['###'.$field.'_uploadedFiles###'],$totalWrap);
				}
				$markers['###'.$field.'_uploadedFiles###'] = '<div id="F3_MailformPlusPlus_UploadedFiles_'.$field.'">'.$markers['###'.$field.'_uploadedFiles###'].'</div>';
			}
			if(strlen($totalMarkersTotalWrap) > 0 && strstr($totalMarkersTotalWrap,"|")) {
				$markers['###total_uploadedFiles###'] = str_replace("|",$markers['###total_uploadedFiles###'],$totalMarkersTotalWrap);
			}
			$markers['###TOTAL_UPLOADEDFILES###'] = $markers['###total_uploadedFiles###'];
		}
	}

	/**
	 * Substitutes markers
	 * 		###error_[fieldname]###
	 * 		###ERROR###
	 * in $this->template
	 *
	 * @return void
	 */
	protected function fillErrorMarkers(&$errors) {
		$markers = array();
		$singleWrap = $this->settings['singleErrorTemplate.']['singleWrap'];
		foreach($errors as $field=>$types) {
			$errorMessages = array();
			$clearErrorMessages = array();
			if(strlen(trim($GLOBALS['TSFE']->sL('LLL:'.$this->langFile.':error_'.$field))) > 0) {
				$errorMessage = trim($GLOBALS['TSFE']->sL('LLL:'.$this->langFile.':error_'.$field));
				if($errorMessage) {
					if(strlen($singleWrap) > 0 && strstr($singleWrap,"|")) {
						$errorMessage = str_replace("|",$errorMessage,$singleWrap);
					}
						
					$errorMessages[] = $errorMessage;
				}
			}
			if(!is_array($types)) {
				$types = array($types);
			}
			foreach($types as $type) {

				$temp = explode(";",$type);
				$type = array_shift($temp);
				foreach($temp as $item) {
					$item = explode("::",$item);
					$values[$item[0]] = $item[1];
				}

				//try to load specific error message with key like error_fieldname_integer
				$errorMessage = trim($GLOBALS['TSFE']->sL('LLL:'.$this->langFile.':error_'.$field.'_'.$type));
				if(strlen($errorMessage) == 0) {
					$type = strtolower($type);
					$errorMessage = trim($GLOBALS['TSFE']->sL('LLL:'.$this->langFile.':error_'.$field.'_'.$type));
				}
				if($errorMessage) {
					if(is_array($values)) {
						foreach($values as $key=>$value) {
							$errorMessage = str_replace("###".$key."###",$value,$errorMessage);
						}
					}
					if(strlen($singleWrap) > 0 && strstr($singleWrap,"|")) {
						$errorMessage = str_replace("|",$errorMessage,$singleWrap);
					}
					$errorMessages[] = $errorMessage;
				} else {
					F3_MailformPlusPlus_StaticFuncs::debugMessage('no_error_message','error_'.$field.'_'.$type);
				}
			}
			$errorMessage = implode("",$errorMessages);
			$totalWrap = $this->settings['singleErrorTemplate.']['totalWrap'];
			if(strlen($totalWrap) > 0 && strstr($totalWrap,"|")) {
				$errorMessage = str_replace("|",$errorMessage,$totalWrap);
			}
			$clearErrorMessage = $errorMessage;
			if($this->settings['addErrorAnchors']) {
				$errorMessage = '<a name="'.$field.'">'.$errorMessage.'</a>';

			}
			$langMarkers = F3_MailformPlusPlus_StaticFuncs::getFilledLangMarkers($errorMessage,$this->langFile);
			$errorMessage = $this->cObj->substituteMarkerArray($errorMessage, $langMarkers);
			$markers['###error_'.$field.'###'] = $errorMessage;
			$markers['###ERROR_'.strtoupper($field).'###'] = $errorMessage;
			$errorMessage = $clearErrorMessage;
			if($this->settings['addErrorAnchors']) {
				$errorMessage = '<a href="' . t3lib_div::getIndpEnv('REQUEST_URI') . '#'.$field.'">'.$errorMessage.'</a>';

			}
			//list settings
			$listSingleWrap = $this->settings['errorListTemplate.']['singleWrap'];
			if(strlen($listSingleWrap) > 0 && strstr($listSingleWrap,"|")) {
				$errorMessage = str_replace("|",$errorMessage,$listSingleWrap);
			}
				
			$markers['###ERROR###'] .= $errorMessage;
		}
		$totalWrap = $this->settings['errorListTemplate.']['totalWrap'];
		if(strlen($totalWrap) > 0 && strstr($totalWrap,"|")) {
			$markers['###ERROR###'] = str_replace("|",$markers['###ERROR###'],$totalWrap);
		}
		$langMarkers = F3_MailformPlusPlus_StaticFuncs::getFilledLangMarkers($markers['###ERROR###'],$this->langFile);
		$markers['###ERROR###'] = $this->cObj->substituteMarkerArray($markers['###ERROR###'], $langMarkers);
		$markers['###error###'] = $markers['###ERROR###'];
		$this->template = $this->cObj->substituteMarkerArray($this->template, $markers);
	}

	/**
	 * Substitutes markers defined in TypoScript in $this->template
	 *
	 * @return void
	 */
	protected function fillTypoScriptMarkers() {
		$markers = array();
		foreach($this->settings['markers.'] as $name=>$options) {
			
			if(!strstr($name,".")) {
				if(!strcmp($options,"USER") || !strcmp($options,"USER_INT")) {
					$this->settings['markers.'][$name.'.']['gp'] = $this->gp;
					//$markers['###'.$name.'###'] = t3lib_div::callUserFunction($this->settings['markers.'][$name.'.']['userFunc'],$this->settings['markers.'][$name.'.'],$this,"");
				} // else {
				$markers['###'.$name.'###'] = $this->cObj->cObjGetSingle($this->settings['markers.'][$name],$this->settings['markers.'][$name.'.']);
				//}
			}
		}
		
		$this->template = $this->cObj->substituteMarkerArray($this->template, $markers);
	}

	/**
	 * Substitutes markers
	 * 		###value_[fieldname]###
	 * 		###VALUE_[FIELDNAME]###
	 * 		###[fieldname]###
	 * 		###[FIELDNAME]###
	 * in $this->template
	 *
	 * @return void
	 */
	protected function fillValueMarkers() {
		$markers = array();
		if (is_array($this->gp)) {
			foreach($this->gp as $k=>$v) {
				if (!ereg('EMAIL_', $k)) {
					if (is_array($v)) {
						$v = implode(',', $v);
					}
					$v = trim($v);
					if ($v != "") {
						if(get_magic_quotes_gpc()) {
							$markers['###value_'.$k.'###'] = stripslashes(F3_MailformPlusPlus_StaticFuncs::reverse_htmlspecialchars($v));
						} else {
							$markers['###value_'.$k.'###'] = F3_MailformPlusPlus_StaticFuncs::reverse_htmlspecialchars($v);
						}
					} else {
						$markers['###value_'.$k.'###'] = '';
					}
					$markers['###'.$k.'###'] = $markers['###value_'.$k.'###'];
					$markers['###'.strtoupper($k).'###'] = $markers['###value_'.$k.'###'];
					$markers['###'.strtoupper("VALUE_".$k).'###'] = $markers['###value_'.$k.'###'];
				} //if end
			} // foreach end
		} // if end
		$this->template = $this->cObj->substituteMarkerArray($this->template, $markers);

		//remove remaining VALUE_-markers
		//needed for nested markers like ###LLL:tx_myextension_table.field1.i.###value_field1###### to avoid wrong marker removal if field1 isn't set
		$this->template = preg_replace('/###value_.*?###/i', '', $this->template);
	}

	/**
	 * Substitutes markers
	 * 		###LLL:[languageKey]###
	 * in $this->template
	 *
	 * @return void
	 */
	protected function fillLangMarkers() {
		global $LANG;
		$langMarkers = array();
		if ($this->langFile != '') {
			$aLLMarkerList = array();
			preg_match_all('/###LLL:.+?###/Ssm', $this->template, $aLLMarkerList);
			foreach($aLLMarkerList[0] as $LLMarker){
				$llKey = substr($LLMarker,7,strlen($LLMarker)-10);
				$marker = $llKey;
				$langMarkers['###LLL:'.$marker.'###'] = trim($GLOBALS['TSFE']->sL('LLL:'.$this->langFile.':'.$llKey));
			}
		}
		$this->template = $this->cObj->substituteMarkerArray($this->template, $langMarkers);
	}
}
?>
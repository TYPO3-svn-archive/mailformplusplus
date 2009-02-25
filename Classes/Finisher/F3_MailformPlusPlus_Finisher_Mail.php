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
 * Finisher to send mails after successful form submission.
 * 
 * A sample configuration looks like this:
 * 
 * <code>
 * finishers.2.class = F3_MailformPlusPlus_Finisher_Mail
 * finishers.2.config.limitMailsToUser = 5
 * finishers.2.config.checkBinaryCfLr = firstname,text,email
 * finishers.2.config.admin.header = 
 * finishers.2.config.admin.to_email = rf@typoheads.at
 * finishers.2.config.admin.to_name = Reinhard Führicht
 * finishers.2.config.admin.subject = SingleStep Request
 * finishers.2.config.admin.sender_email = email
 * finishers.2.config.admin.sender_name = lastname
 * finishers.2.config.admin.replyto_email = email
 * finishers.2.config.admin.replyto_name = lastname
 * finishers.2.config.admin.htmlEmailAsAttachment = 1
 * finishers.2.config.user.header = ...
 * finishers.2.config.user.to_email = email
 * finishers.2.config.user.to_name = lastname
 * finishers.2.config.user.subject = Your SingleStep request
 * finishers.2.config.user.sender_email = rf@typoheads.at
 * finishers.2.config.user.sender_name = Reinhard Führicht
 * finishers.2.config.user.replyto_email = rf@typoheads.at
 * finishers.2.config.user.replyto_name = TEXT
 * finishers.2.config.user.replyto_name.value = Reinhard Führicht
 * 
 * # sends only plain text mails and adds the HTML mail as attachment
 * finishers.2.config.user.htmlEmailAsAttachment = 1
 * 
 * # attaches static files or files uploaded via a form field
 * finishers.2.config.user.attachment = fileadmin/files/file.txt,picture
 * 
 * # attaches a PDF file with submitted values
 * finishers.2.config.user.attachPDF.class = F3_MailformPlusPlus_Generator_PDF
 * finishers.2.config.user.attachPDF.exportFields = firstname,lastname,email,interests,pid,submission_date,ip
 * </code>
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Finisher
 */
class F3_MailformPlusPlus_Finisher_Mail extends F3_MailformPlusPlus_AbstractFinisher {
	
	/**
     * The main method called by the controller
     * 
     * @return array The probably modified GET/POST parameters
     */
	public function process() {

		
		$this->init();
		
		//send emails
		$this->sendMail('admin');
		$this->sendMail('user');
		
		return $this->gp;
	}
	
	/**
     * Returns the final template code for given mode and suffix with substituted markers.
     * 
     * @param string $mode user/admin
     * @param string $suffix plain/html
     * @return string The template code
     */
	protected function parseTemplate($mode,$suffix) {
		$template = $this->getTemplate($mode,$suffix);
		
		$markers = F3_MailformPlusPlus_StaticFuncs::getFilledLangMarkers($template,$this->langFile);
		$valueMarkers = F3_MailformPlusPlus_StaticFuncs::getFilledValueMarkers($this->gp);
		$markers = array_merge($valueMarkers,$markers);
		$this->sanitizeMarkers($markers);
		$template = $this->cObj->substituteMarkerArray($template, $markers);
		$template = F3_MailformPlusPlus_StaticFuncs::removeUnfilledMarkers($template);
		return $template;
	}
	
	/**
     * Sanitizes E-mail markers by processing the 'checkBinaryCrLf' setting in TypoScript
     * 
     * @param array &$markers The E-mail markers
     * @return void
     */
	protected function sanitizeMarkers(&$markers) {
		$checkBinaryCrLf = $this->settings['checkBinaryCrLf'];
		if ($checkBinaryCrLf != '') {
			$markersToCheck = t3lib_div::trimExplode(',',$checkBinaryCrLf);
			foreach($markersToCheck as $idx=>$val) {
				if(substr($val,0,3) != '###') {
					$val = '###'.$markersToCheck[$idx];
				}
				
				if(substr($val,-3) != '###') {
					$val .= '###';
				}
				$iStr = $markers[$val];
				$iStr = str_replace (chr(13),'<br />', $iStr);
				$iStr = str_replace ('\\','', $iStr); 
				$markers[$val] = $iStr;
				
			}
		}
		foreach($markers as $field=>&$value) {
			$value = nl2br($value);
		}
	}
	
	/**
     * Parses the globally defined template file for E-mail template with given mode and suffix
     * 
     * @param string $mode user/admin
     * @param string $suffix plain/html
     * @return string The template code
     */
	protected function getTemplate($mode,$suffix) {
		$templateFile = $this->settings['templateFile'];
		if(isset($this->settings['templateFile.']) && is_array($this->settings['templateFile.'])) {
			$templateFile = $this->cObj->cObjGetSingle($this->settings['templateFile'],$this->settings['templateFile.']);
		} else {
			$templateFile = F3_MailformPlusPlus_StaticFuncs::resolvePath($templateFile);
		}
		$template = t3lib_div::getURL($templateFile);
		$template = $this->cObj->getSubpart($template,"###TEMPLATE_EMAIL_".strtoupper($mode)."_".strtoupper($suffix)."###");
		if(!$template) {
			$template = $this->cObj->getSubpart($template,"###template_email_".strtolower($mode)."_".strtolower($suffix)."###");
		}
		if(!$template) {
			throw new Exception("No template file to read E-Mail templates!");
		}
		return $template;
	}
	
	/**
     * Sends mail according to given type.
     * 
     * @param string $type (admin|user)
     * @return void
     */
	protected function sendMail($type) {
		$mailSettings = $this->settings[$type];
		
		$template['plain'] = $this->parseTemplate($type,"plain");
		$template['html'] = $this->parseTemplate($type,"html");
		//F3_MailformPlusPlus_StaticFuncs::debugMessage('E-Mail settings for '.$type);
		//F3_MailformPlusPlus_StaticFuncs::debugArray($mailSettings);
		
		//init mailer object
		require_once(PATH_t3lib.'class.t3lib_htmlmail.php');
	    $emailObj = t3lib_div::makeInstance('t3lib_htmlmail');
	    $emailObj->start();
		
		//set e-mail options
	    $emailObj->subject = $mailSettings['subject'];
	    
	    $sender = $mailSettings['sender_email'];
	    if(isset($mailSettings['sender_email']) && is_array($mailSettings['sender_email'])) {
	    	$sender = implode(",",$mailSettings['sender_email']);
	    }
	    $emailObj->from_email = $sender;
	    $emailObj->from_name = $mailSettings['sender_name'];
	    
		$replyto = $mailSettings['replyto_email'];
	    if(isset($mailSettings['replyto_email']) && is_array($mailSettings['replyto_email'])) {
	    	$replyto = implode(",",$mailSettings['replyto_email']);
	    }
	    $emailObj->replyto_email = $replyto;
	    $emailObj->replyto_name = $mailSettings['replyto_name'];
	    $emailObj->returnPath = '';
	    if($mailSettings['email_header']) {
	    	$emailObj->add_header($mailSettings['header']);
	    }
	    if($template['plain']) {
	    	$emailObj->setPlain($template['plain']);
	    }
		
		if($template['html']) {
			if($mailSettings['htmlEmailAsAttachment']) {
				$tmphtml=tempnam("typo3temp/","/mailformplusplus_").".html";
				$tmphandle=fopen($tmphtml,"wb");
				if ($tmphandle) {
					fwrite($tmphandle,$template['html']);
					fclose($tmphandle);
					$emailObj->addAttachment($tmphtml);
				}
			} else {
	    		$emailObj->setHtml($template['html']);
			}
	    }
		
		if(!is_array($mailSettings['attachment'])) {
			$mailSettings['attachment'] = array($mailSettings['attachment']);
		}
		foreach($mailSettings['attachment'] as $attachment) {
			if(strlen($attachment) > 0) {
				$emailObj->addAttachment($attachment);
			}
		}
		
		if($mailSettings['attachPDF']) {
			#print "adding pdf";
			$emailObj->addAttachment($mailSettings['attachPDF']);
		}
	    
		//parse max count of mails to send
	    $count = 0;
	    $max = $this->settings['limitMailsToUser'];
	    if(!$max) {
	    	$max = 2;
	    }
		if(!is_array($mailSettings['to_email'])) {
			$mailSettings['to_email'] = array($mailSettings['to_email']);
		}
		reset($mailSettings['to_email']);
		
		$markers = F3_MailformPlusPlus_StaticFuncs::substituteIssetSubparts($template['html']);
		$template['html'] = $this->cObj->substituteMarkerArray($template['html'], $markers);
		$markers = F3_MailformPlusPlus_StaticFuncs::substituteIssetSubparts($template['plain']);
		$template['plain'] = $this->cObj->substituteMarkerArray($template['plain'], $markers);
		
		//send e-mails
	    foreach($mailSettings['to_email'] as $mailto) {
	    	
	    	if($count < $max) {
	    		if (strstr($mailto, '@') && !eregi("\r",$mailto) && !eregi("\n",$mailto)) {
					$sent = $emailObj->send($mailto);
				}
	    		$count++;
	    	}
			if($sent) {
				F3_MailformPlusPlus_StaticFuncs::debugMessage("Mail sent to: ".$mailto);
				F3_MailformPlusPlus_StaticFuncs::debugMessage("Mail content:",false);
				F3_MailformPlusPlus_StaticFuncs::debugMessage("Sender: ".$emailObj->from_name." <".$emailObj->from_email.">",false);
				F3_MailformPlusPlus_StaticFuncs::debugMessage("Reply to: ".$emailObj->replyto_name." <".$emailObj->replyto_email.">",false);
				F3_MailformPlusPlus_StaticFuncs::debugMessage("Message Plain: ".$template['plain'],false);
				F3_MailformPlusPlus_StaticFuncs::debugMessage("Message HTML: ".$template['html'],false);
			} else {
				F3_MailformPlusPlus_StaticFuncs::debugMessage("Mail sending failed to: ".$mailto);
				F3_MailformPlusPlus_StaticFuncs::debugMessage("Mail content:",false);
				F3_MailformPlusPlus_StaticFuncs::debugMessage("Sender: ".$emailObj->from_name." <".$emailObj->from_email.">",false);
				F3_MailformPlusPlus_StaticFuncs::debugMessage("Reply to: ".$emailObj->replyto_name." <".$emailObj->replyto_email.">",false);
				F3_MailformPlusPlus_StaticFuncs::debugMessage("Message Plain: ".$template['plain'],false);
				F3_MailformPlusPlus_StaticFuncs::debugMessage("Message HTML: ".$template['html'],false);
			}
	    }
		if($tmphtml) {
			unlink($tmphtml);
		}
	}
	
	/**
     * Explodes the given list seperated by $sep. Substitutes values with according value in GET/POST, if set.
     * 
     * @param string $list
     * @param string $sep
     * @return array
     */
	private function explodeList($list,$sep = ",") {
		$items = t3lib_div::trimExplode($sep,$list);
		$splitArray = array();
		foreach($items as $item) {
			if(isset($this->gp[$item])) {
				 array_push($splitArray,$this->gp[$item]);
			} else {
				array_push($splitArray,$item);
			}
		}
		return $splitArray;
	}
	
	/**
     * Substitutes values with according value in GET/POST, if set.
     * 
     * @param string $value
     * @return string
     */
	private function parseSettingValue($value) {
		if(isset($this->gp[$value])) {
			$parsed = $this->gp[$value];
		} else {
			$parsed = $value;
		}
		return $parsed;
		
	}
	
	/**
     * Parses a setting in TypoScript and overrides it with setting in plugin record if set.
     * The settings contains a single value or a TS object.
     * 
     * @param array $settings The settings array containing the mail settings
     * @param string $type admin|user
     * @param string $key The key to parse in the settings array
     * @return string
     */
	private function parseValue($settings,$type,$key) {
		if(isset($this->emailSettings[$type][$key])) {
			$parsed = $this->parseSettingValue($this->emailSettings[$type][$key]);
		} else if(isset($settings[$key.'.']) && is_array($settings[$key.'.'])) {
			$parsed = $this->cObj->cObjGetSingle($settings[$key],$settings[$key.'.']);
		} else {
			$parsed = $this->parseSettingValue($settings[$key]);
		}
		return $parsed;
	}
	
	/**
     * Parses a setting in TypoScript and overrides it with setting in plugin record if set.
     * The settings contains a list of values or a TS object.
     * 
     * @param array $settings The settings array containing the mail settings
     * @param string $type admin|user
     * @param string $key The key to parse in the settings array
     * @return string|array
     */
	private function parseList($settings,$type,$key) {
		if(isset($this->emailSettings[$type][$key])) {
			$parsed = $this->explodeList($this->emailSettings[$type][$key]);
		} else if(isset($settings[$key.'.']) && is_array($settings[$key.'.'])) {
			$parsed = $this->cObj->cObjGetSingle($settings[$key],$settings[$key.'.']); 
		} else {
			$parsed = $this->explodeList($settings[$key]);
		}
		return $parsed;
	}
	
	/**
     * Parses a list of file names or field names set in TypoScript and overrides it with setting in plugin record if set.
     * 
     * @param array $settings The settings array containing the mail settings
     * @param string $type admin|user
     * @param string $key The key to parse in the settings array
     * @return string
     */
	private function parseFilesList($settings,$type,$key) {
		if(isset($settings[$key.'.']) && is_array($settings[$key.'.'])) {
			$parsed = $this->cObj->cObjGetSingle($settings[$key],$settings[$key.'.']);
		} elseif($settings[$key]) {
			$files = t3lib_div::trimExplode(",",$settings[$key]);
			$parsed = array();
			session_start();
			foreach($files as $file) {
				#print $file.":";
				#print_r($_SESSION['mailformplusplusFiles']);
				if(isset($_SESSION['mailformplusplusFiles'][$file])) {
					foreach($_SESSION['mailformplusplusFiles'][$file] as $uploadedFile) {
						array_push($parsed,$uploadedFile['uploaded_path'].$uploadedFile['uploaded_name']);
					}
				} else {
					array_push($parsed,$file);
				}
			}
		}
		return $parsed;
	}
	
	/**
     * Substitutes markers like ###LLL:langKey### in given TypoScript settings array.
     * 
     * @param array &$settings The E-Mail settings
     * @return void
     */
	protected function fillLangMarkersInSettings(&$settings) {
		foreach($settings as &$value) {
			if(isset($value) && is_array($value)) {
				$this->fillLangMarkersInSettings($value);
			} else {
				$langMarkers = F3_MailformPlusPlus_StaticFuncs::getFilledLangMarkers($value,$this->langFile);
				if(!empty($langMarkers)) {
					$value = $this->cObj->substituteMarkerArray($value, $langMarkers);
				}
			}
		}
	}
	
	/**
     * Fetches the global TypoScript settings of the MailformPlusPlus
     * 
     * @return void
     */
	protected function getSettings() {
		return $this->configuration->getSettings();
	}
	
	/**
     * Inits the finisher mapping settings values to internal attributes.
     * 
     * @return void
     */
	protected function init() {
		
		//set language file
		if(isset($this->settings['langFile.']) && is_array($this->settings['langFile.'])) {
			$this->langFile = $this->cObj->cObjGetSingle($this->settings['langFile'],$this->settings['langFile.']);
		} else {
			$this->langFile = F3_MailformPlusPlus_StaticFuncs::resolvePath($this->settings['langFile']);
		}
	}
	
	/**
     * Method to set GET/POST for this class and load the configuration
     * 
     * @param array The GET/POST values
     * @param array The TypoScript configuration
     * @return void
     */
	public function loadConfig($gp,$tsConfig) {
		$this->gp = $gp;
		$this->settings = $this->parseEmailSettings($tsConfig);
		#print_r($this->emailSettings);
		#$this->settings = $tsConfig;
		#$this->settings['admin'] = $this->parseMailSettings($this->settings['admin.'],'admin');
		#$this->settings['user'] = $this->parseMailSettings($this->settings['user.'],'user');
		unset($this->settings['admin.']);
		unset($this->settings['user.']);
		#print_r($this->settings);
	}
	
	/**
	 * Parses the email settings in flexform and stores them in an array.
	 *
	 * @param array The TypoScript configuration
	 * @return array The parsed email settings
	 */
	protected function parseEmailSettings($tsConfig) {
		$emailSettings = $tsConfig;
		$options = array (
			'to_email',
			'subject',
			'sender_email',
			'sender_name',
			'replyto_email',
			'replyto_name',
			'to_name',
			'attachment',
			'attachPDF',
			'htmlEmailAsAttachment'
		);
		
		//*************************
		//ADMIN settings
		//*************************
		$emailSettings['admin'] = $this->parseEmailSettingsByType($emailSettings['admin.'],'admin',$options);
		
		//*************************
		//USER settings
		//*************************
		$emailSettings['user'] = $this->parseEmailSettingsByType($emailSettings['user.'],'user',$options);
		
		return $emailSettings;
	}
	
	/**
	 * Parses the email settings in flexform of a specific type (admin|user]
	 * 
	 * @param array $currentSettings The current settings array containing the settings made via TypoScript
	 * @param string $type (admin|user)
	 * @param array $optionsToParse Array containing all option names to parse.
	 * @return array The parsed email settings
	 */
	private function parseEmailSettingsByType($currentSettings,$type,$optionsToParse = array()) {
		$typeLower = strtolower($type);
		$typeUpper = strtoupper($type);
		$section = 'sEMAIL'.$typeUpper;
		$emailSettings = $currentSettings;
		foreach($optionsToParse as $option) {
			$value = F3_MailformPlusPlus_StaticFuncs::pi_getFFvalue($this->cObj->data['pi_flexform'],$option,$section);
			if(strlen($value) > 0) {
				$emailSettings[$option] = $value;
				if(isset($this->gp[$value])) {
					$emailSettings[$option] = $this->gp[$value];
				}
				
			} else {
				switch($option) {
					case "to_email";
					case "to_name":
					case "sender_email":
					case "replyto_email":
						$emailSettings[$option] = $this->parseList($currentSettings,$type,$option);
					break;
					
					case "subject":
					case "sender_name":
					case "replyto_name":
						$emailSettings[$option] = $this->parseValue($currentSettings,$type,$option);
					break;
					
					case "attachment":
						$emailSettings[$option] = $this->parseFilesList($currentSettings,$type,$option);
					break;
					
					case "attachPDF":
						if(isset($currentSettings['attachPDF.']) && is_array($currentSettings['attachPDF.'])) {
							#print "call";
							$generatorClass = $currentSettings['attachPDF.']['class'];
							if(!$generatorClass) {
								$generatorClass = "F3_MailformPlusPlus_Generator_PDF";
							}
							$generator = $this->componentManager->getComponent($generatorClass);
							$exportFields = array();
							if($emailSettings['attachPDF.']['exportFields']) {
								$exportFields = t3lib_div::trimExplode(",",$currentSettings['attachPDF.']['exportFields']);
							}
							#print_r($exportFields);
							$file = tempnam("typo3temp/","/mailformplusplus_").".pdf";
							$generator->generateFrontendPDF($this->gp,$this->settings['langFile'],$exportFields,$file,true);
							$emailSettings['attachPDF'] = $file;
						} elseif ($currentSettings['attachPDF']) {
							$emailSettings['attachPDF'] = $currentSettings['attachPDF'];
						}
					break;
					
					case "":
						if(isset($currentSettings['htmlEmailAsAttachment']) && !strcmp($currentSettings['htmlEmailAsAttachment'],"1")) {
							$emailSettings['htmlEmailAsAttachment'] = 1;
						}
		
					break;
				}
			}
		}
		$this->fillLangMarkersInSettings($emailSettings);
		return $emailSettings;
	}
	
}
?>

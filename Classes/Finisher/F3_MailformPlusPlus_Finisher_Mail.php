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
     * The settings array passed to the finisher.
     * 
     * @access protected
     * @var array
     */
	protected $settings;
	
	
	
	/**
     * The main method called by the controller
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @param array $gp The GET/POST parameters
     * @param array $settings The defined TypoScript settings for the finisher
     * @return array The probably modified GET/POST parameters
     */
	public function process($gp,$settings) {
		
		//set GET/POST parameters
		$this->gp = $gp;
		
		//set settings
		$this->settings = $settings;
		$this->init();
		$mailSettings = $this->parseMailSettings($settings['admin.']);
		$this->mergeSettingsWithPluginData($mailSettings,"admin");
		$mailSettings = $this->parseMailSettings($mailSettings);
		$template['plain'] = $this->parseTemplate("admin","plain");
		$template['html'] = $this->parseTemplate("admin","html");
		$this->sendMail($mailSettings,$template);
		$mailSettings = $this->parseMailSettings($settings['user.']);
		$this->mergeSettingsWithPluginData($mailSettings,"user");
		$mailSettings = $this->parseMailSettings($mailSettings);
		$template['plain'] = $this->parseTemplate("user","plain");
		$template['html'] = $this->parseTemplate("user","html");
		$this->sendMail($mailSettings,$template);
		return $this->gp;
	}
	
	/**
     * Merges settings from TypoScript with settings made in plugin record.
     * The settings in plugin record override the TypoScript settings.
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @param array &$mailSettings The parsed TypoScript settings
     * @param string $type admin/user
     * @return void
     */
	public function mergeSettingsWithPluginData(&$mailSettings,$type) {
		foreach($this->emailSettings[$type] as $key=>$value) {
			$mailSettings[$key] = $value;
		}
	}
	
	/**
     * Sets the internal "emailSettings" attribute holding the settings made in plugin record.
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @param array $new The settings to set
     * @return void
     */
	public function setEmailSettings($new) {
		$this->emailSettings = $new;
	}
	
	/**
     * Returns the final template code for given mode and suffix with substituted markers.
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
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
     * @author Reinhard Führicht <rf@typoheads.at>
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
     * @author Reinhard Führicht <rf@typoheads.at>
     * @param string $mode user/admin
     * @param string $suffix plain/html
     * @return string The template code
     */
	protected function getTemplate($mode,$suffix) {
		$settings = $this->settings;
		$templateFile = $settings['templateFile'];
		if(is_array($settings['templateFile.'])) {
			$templateFile = $this->cObj->cObjGetSingle($settings['templateFile'],$settings['templateFile.']);
		} else {
			$templateFile = F3_MailformPlusPlus_StaticFuncs::resolvePath($templateFile);
		}
		$template = t3lib_div::getURL($templateFile);
		$template = $this->cObj->getSubpart($template,"###TEMPLATE_EMAIL_".strtoupper($mode)."_".strtoupper($suffix)."###");
		if(!$template) {
			$template = $this->cObj->getSubpart($template,"###template_email_".strtolower($mode)."_".strtolower($suffix)."###");
		}
		return $template;
	}
	
	/**
     * Parses the given TypoScript E-Mail settings array and builds a new array with parsed and processed values.
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @param array &$settings The E-Mail settings
     * @param array &$template Array holding the templates for plain text and html
     * @return void
     */
	protected function sendMail(&$settings,&$template) {
		
		//init mailer object
		require_once(PATH_t3lib.'class.t3lib_htmlmail.php');
	    $emailObj = t3lib_div::makeInstance('t3lib_htmlmail');
	    $emailObj->start();
		
		//set e-mail options
	    $emailObj->subject = $settings['subject'];
	    
	    $sender = $settings['sender_email'];
	    if(is_array($settings['sender_email'])) {
	    	$sender = implode(",",$settings['sender_email']);
	    }
	    $emailObj->from_email = $sender;
	    $emailObj->from_name = $settings['sender_name'];
	    
		$replyto = $settings['replyto_email'];
	    if(is_array($settings['replyto_email'])) {
	    	$replyto = implode(",",$settings['replyto_email']);
	    }
	    $emailObj->replyto_email = $replyto;
	    $emailObj->replyto_name = $settings['replyto_name'];
	    $emailObj->returnPath = '';
	    if($settings['email_header']) {
	    	$emailObj->add_header($settings['header']);
	    }
	    if($template['plain']) {
	    	$emailObj->setPlain($template['plain']);
	    }
		
		if($template['html']) {
			if($settings['htmlEmailAsAttachment']) {
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
		
		if(!is_array($settings['attachment'])) {
			$settings['attachment'] = array($settings['attachment']);
		}
		foreach($settings['attachment'] as $attachment) {
			if(strlen($attachment) > 0) {
				$emailObj->addAttachment($attachment);
			}
		}
		
		#print_r($settings);
		if($settings['attachPDF']) {
			#print "adding pdf";
			$emailObj->addAttachment($settings['attachPDF']);
		}
	    
		//parse max count of mails to send
	    $count = 0;
	    $max = $this->settings['limitMailsToUser'];
	    if(!$max) {
	    	$max = 2;
	    }
		if(!is_array($settings['to_email'])) {
			$settings['to_email'] = array($settings['to_email']);
		}
		reset($settings['to_email']);
		
		$markers = F3_MailformPlusPlus_StaticFuncs::substituteIssetSubparts($template['html']);
		$template['html'] = $this->cObj->substituteMarkerArray($template['html'], $markers);
		$markers = F3_MailformPlusPlus_StaticFuncs::substituteIssetSubparts($template['plain']);
		$template['plain'] = $this->cObj->substituteMarkerArray($template['plain'], $markers);
		
		//send e-mails
	    foreach($settings['to_email'] as $mailto) {
	    	
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
     * Parses the given TypoScript E-Mail settings array and builds a new array with parsed and processed values.
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @param array &$settings The E-Mail settings
     * @return array Array containing the processed values
     */
	protected function parseMailSettings(&$settings) {
		if(!is_array($settings)) {
			return array();
		}
		$options = array();
		
		//parse recipients
		$addresses = array();
		if(is_array($settings['to_email.'])) {
			$options['to_email'] = $this->cObj->cObjGetSingle($settings['to_email'],$settings['to_email.']); 
		} else {
			$addresses = t3lib_div::trimExplode(",",$settings['to_email']);
			$options['to_email'] = array();
			foreach($addresses as $address) {
				if(isset($this->gp[$address]) && strstr($this->gp[$address],"@")) {
					 array_push($options['to_email'],$this->gp[$address]);
				} else {
					array_push($options['to_email'],$address);
				}
			}
		}
		
		//parse subject
		if(is_array($settings['subject.'])) {
			$options['subject'] = $this->cObj->cObjGetSingle($settings['subject'],$settings['subject.']);
		} elseif(isset($this->gp[$settings['subject']])) {
			$options['subject'] = $this->gp[$settings['subject']];
		} else {
			$options['subject'] = $settings['subject'];
		}
		
		//parse sender
		if(is_array($settings['sender_email.'])) {
			$options['sender_email'] = $this->cObj->cObjGetSingle($settings['sender_email'],$settings['sender_email.']);
		} else {
			$addresses = t3lib_div::trimExplode(",",$settings['sender_email']);
			$options['sender_email'] = array();
			foreach($addresses as $address) {
				if(isset($this->gp[$address]) && strstr($this->gp[$address],"@")) {
					 array_push($options['sender_email'],$this->gp[$address]);
				} else {
					array_push($options['sender_email'],$address);
				}
			}
		}
		
		//parse sender name
		if(is_array($settings['sender_name.'])) {
			$options['sender_name'] = $this->cObj->cObjGetSingle($settings['sender_name'],$settings['sender_name.']);
		} else {
			if(isset($this->gp[$settings['sender_name']])) {
				 $options['sender_name'] = $this->gp[$settings['sender_name']];
			} else {
				$options['sender_name'] = $settings['sender_name'];
			}
		}
		
		//parse reply to
		if(is_array($settings['replyto_email.'])) {
			$options['replyto_email'] = $this->cObj->cObjGetSingle($settings['replyto_email'],$settings['replyto_email.']);
		} else {
			$addresses = t3lib_div::trimExplode(",",$settings['replyto_email']);
			$options['replyto_email'] = array();
			foreach($addresses as $address) {
				if(isset($this->gp[$address]) && strstr($this->gp[$address],"@")) {
					 array_push($options['replyto_email'],$this->gp[$address]);
				} else {
					array_push($options['replyto_email'],$address);
				}
			}
		}
		
		//parse reply to name
		if(is_array($settings['replyto_name.'])) {
			$options['replyto_name'] = $this->cObj->cObjGetSingle($settings['replyto_name'],$settings['replyto_name.']);
		} else {
			if(isset($this->gp[$settings['replyto_name']])) {
				 $options['replyto_name'] = $this->gp[$settings['replyto_name']];
			} else {
				$options['replyto_name'] = $settings['replyto_name'];
			}
		}
		
		//parse attachment
		if(is_array($settings['attachment.'])) {
			$options['attachment'] = $this->cObj->cObjGetSingle($settings['attachment'],$settings['attachment.']);
		} elseif($settings['attachment']) {
			$files = t3lib_div::trimExplode(",",$settings['attachment']);
			$options['attachment'] = array();
			session_start();
			foreach($files as $file) {
				#print $file.":";
				#print_r($_SESSION['mailformplusplusFiles']);
				if(isset($_SESSION['mailformplusplusFiles'][$file])) {
					foreach($_SESSION['mailformplusplusFiles'][$file] as $uploadedFile) {
						array_push($options['attachment'],$uploadedFile['uploaded_path'].$uploadedFile['uploaded_name']);
					}
				} else {
					array_push($options['attachment'],$file);
				}
			}
		}
		#print_r($settings);
		if(is_array($settings['attachPDF.'])) {
			#print "call";
			$generatorClass = $settings['attachPDF.']['class'];
			if(!$generatorClass) {
				$generatorClass = "F3_MailformPlusPlus_Generator_PDF";
			}
			$generator = $this->componentManager->getComponent($generatorClass);
			$exportFields = array();
			if($settings['attachPDF.']['exportFields']) {
				$exportFields = t3lib_div::trimExplode(",",$settings['attachPDF.']['exportFields']);
			}
			#print_r($exportFields);
			$file = tempnam("typo3temp/","/mailformplusplus_").".pdf";
			$generator->generateFrontendPDF($this->gp,$this->settings['langFile'],$exportFields,$file,true);
			$options['attachPDF'] = $file;
		} elseif ($settings['attachPDF']) {
			$options['attachPDF'] = $settings['attachPDF'];
		}
		if(isset($settings['htmlEmailAsAttachment']) && !strcmp($settings['htmlEmailAsAttachment'],"1")) {
			$options['htmlEmailAsAttachment'] = 1;
		}
		$this->fillLangMarkersInSettings($options);
		#print_r($options);
		return $options;
	}
	
	/**
     * Substitutes markers like ###LLL:langKey### in given TypoScript settings array.
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @param array &$settings The E-Mail settings
     * @return void
     */
	protected function fillLangMarkersInSettings(&$settings) {
		foreach($settings as &$value) {
			if(is_array($value)) {
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
     * @author Reinhard Führicht <rf@typoheads.at>
     * @return void
     */
	protected function getSettings() {
		return $this->configuration->getSettings();
	}
	
	/**
     * Inits the finisher mapping settings values to internal attributes.
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @return void
     */
	protected function init() {
		
		//fetch global settings
		$settings = $this->settings;
		
		//set language file
		if(is_array($settings['langFile.'])) {
			$this->langFile = $this->cObj->cObjGetSingle($settings['langFile'],$settings['langFile.']);
		} else {
			$this->langFile = F3_MailformPlusPlus_StaticFuncs::resolvePath($settings['langFile']);
		}
		
		//make cObj instance
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->cObj->setCurrentVal($GLOBALS['TSFE']->id);
	}
	
	
	
}
?>
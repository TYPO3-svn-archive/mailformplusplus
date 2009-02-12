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
 * Default controller for MailformPlusPlus
 *
 * @author	Reinhard F�hricht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Controller
 */
class F3_MailformPlusPlus_Controller_Default extends F3_MailformPlusPlus_AbstractController {
	
	/**
     * The GimmeFive component manager
     * 
     * @access protected
     * @var F3_GimmeFive_Component_Manager
     */
	protected $componentManager;
	
	/**
     * The global MailformPlusPlus configuration
     * 
     * @access protected
     * @var F3_MailformPlusPlus_Configuration
     */
	protected $configuration;
	
	/**
     * The template file to be used. Only if template file was defined via plugin record
     * 
     * @access protected
     * @var string
     */
	protected $templateFile;
	
	//not used
	protected $piVars;
	
	/**
     * The constructor for a finisher setting the component manager and the configuration.
     * 
     * @author Reinhard Führicht <rf@typoheads.at>
     * @param F3_GimmeFive_Component_Manager $componentManager
     * @param F3_MailformPlusPlus_Configuration $configuration
     * @return void
     */
	public function __construct(F3_GimmeFive_Component_Manager $componentManager, F3_MailformPlusPlus_Configuration $configuration) {
		$this->componentManager = $componentManager;
		$this->configuration = $configuration;
		$this->initializeController();
	}
	
	/**
	 * Deletes all files older than a specific time in a temporary upload folder.
	 * Settings for the threshold time and the folder are made in TypoScript.
	 * 
	 * Here is an example:
	 * <code>
	 * plugin.F3_MailformPlusPlus.settings.files.clearTempFilesOlderThanHours = 24
	 * plugin.F3_MailformPlusPlus.settings.files.tmpUploadFolder = uploads/mailformplusplus/tmp
	 * </code>
	 * 
	 * @param integer $olderThan Delete files older than $olderThan hours.
	 * @return void
	 * @author Reinhard F�hricht <rf@typoheads.at>
	 */
	protected function clearTempFiles($olderThan) {
		if(!$olderThan) {
			return;
		}
		
		//get temp upload folder
		$uploadFolder = $this->getTempUploadFolder();
		
		//build absolute path to upload folder
		$path = F3_MailformPlusPlus_StaticFuncs::getDocumentRoot().$uploadFolder;
		
		//read files in directory
		$tmpFiles = t3lib_div::getFilesInDir($path);
		
		F3_MailformPlusPlus_StaticFuncs::debugMessage("Cleaning temporary files in folder \"".$path."\"");
		
		//calculate threshold timestamp
		//hours * 60 * 60 = millseconds 
		$threshold = time() - $olderThan * 60 * 60;
		
		//for all files in temp upload folder
		foreach($tmpFiles as $file) {
			
			//if creation timestamp is lower than threshold timestamp
			//delete the file
			$creationTime = filemtime($path.$file);
			
			//fix for different timezones
			$creationTime += date("O")/100*60;
			if($creationTime < $threshold) {
				unlink($path.$file);
				F3_MailformPlusPlus_StaticFuncs::debugMessage("Deleted file \"".$file."\"",false);
			}
		}
	}
	
	/**
	 * Searches for upload folder settings in TypoScript setup. 
	 * If no settings is found, the default upload folder is set.
	 * 
	 * Here is an example:
	 * <code>
	 * plugin.F3_MailformPlusPlus.settings.files.tmpUploadFolder = uploads/mailformplusplus/tmp
	 * </code>
	 * 
	 * The default upload folder is: '/uploads/mailformplusplus/tmp/'
	 * 
	 * @return void
	 * @author Reinhard F�hricht <rf@typoheads.at>
	 */
	protected function getTempUploadFolder() {
		
		//set default upload folder
		$uploadFolder = '/uploads/mailformplusplus/tmp/';
		
		//if temp upload folder set in TypoScript, take that setting
		if($_SESSION['mailformplusplusSettings']['settings']['files.']['tmpUploadFolder']) {
			$uploadFolder = $_SESSION['mailformplusplusSettings']['settings']['files.']['tmpUploadFolder'];
			$uploadFolder = F3_MailformPlusPlus_StaticFuncs::sanitizePath($uploadFolder);
		}
		
		//if the set directory doesn't exist, print a message
		#if(!is_dir(F3_MailformPlusPlus_StaticFuncs::getDocumentRoot().$uploadFolder)) {
	#		F3_MailformPlusPlus_StaticFuncs::debugMessage("Folder: '".F3_MailformPlusPlus_StaticFuncs::getDocumentRoot().$uploadFolder."' doesn't exist!");
	#	}
	if(!is_dir(F3_MailformPlusPlus_StaticFuncs::getTYPO3Root().$uploadFolder)) {
			F3_MailformPlusPlus_StaticFuncs::debugMessage("Folder: '".F3_MailformPlusPlus_StaticFuncs::getTYPO3Root().$uploadFolder."' doesn't exist!");
		}
		return $uploadFolder;
	}
	
	
	/**
	 * Clears the mailformplusplus specific values from SESSION, which are:
	 * 
	 * <ul>
	 * 	<li>The TypoScript settings array</li>
	 *  <li>The uploaded files information</li>
	 *  <li>The step information</li>
	 * </ul>
	 * 
	 * The session only gets cleared, when the form is shown for the first time (submitted == 0).
	 *
	 * @return void
	 * @author Reinhard F�hricht <rf@typoheads.at>
	 */
	protected function clearSession() {
		session_start();
		unset($_SESSION['mailformplusplusValues']);
		unset($_SESSION['mailformplusplusFiles']);
		unset($_SESSION['mailformplusplusSettings']['lastStep']);
		unset($_SESSION['submitted_ok']);
		unset($_SESSION['mailformplusplusSettings']['usedSuffix']);
		unset($_SESSION['mailformplusplusSettings']['usedSettings']);
		F3_MailformPlusPlus_StaticFuncs::debugMessage("Form is called the first time, cleared params in $_SESSION");
	}
	
	/**
	 * Processes uploaded files, moves them to a temporary upload folder, renames them if they already exist and
	 * stores the information in $_SESSION['mailformplusplusFiles']
	 * 
	 * 
	 * @return void
	 * @author Reinhard F�hricht <rf@typoheads.at>
	 */
	protected function processFiles() {
		session_start();
		
		//if files were uploaded
		if(isset($_FILES) && is_array($_FILES)) {
			
			//get upload folder
			$uploadFolder = $this->getTempUploadFolder();
			
			//build absolute path to upload folder
			#$uploadPath = F3_MailformPlusPlus_StaticFuncs::getDocumentRoot().$uploadFolder;
			$uploadPath = F3_MailformPlusPlus_StaticFuncs::getTYPO3Root().$uploadFolder;
			if(!file_exists($uploadPath)) {
				F3_MailformPlusPlus_StaticFuncs::debugMessage("Folder: ".$uploadPath." doesn't exist!");
				return;
			}
			//for all file properties
			/*
			 * $_FILES looks like this:
			 * 
			 * Array (
    		 *	[mailformplusplus] => Array (
             *		[name] => Array (
             *      	[picture] => 
             *      	[picture2] => Wasserlilien.jpg
             *   	)
             *		[type] => Array (
             *      	[picture] => 
             *      	[picture2] => image/jpeg
             *   	)
             *		[tmp_name] => Array (
             *      	[picture] => 
             *      	[picture2] => /cluster/ispman/temp/phpbqqUEg
             *  	)
             *		[error] => Array (
             *      	[picture] => 4
             *      	[picture2] => 0
             *   	)
             *		[size] => Array (
             *      	[picture] => 0
             *      	[picture2] => 83794
             *   	)
        	 *	 )
			 *)
			 */
			foreach($_FILES as $sthg=>&$files) {
				
				//if a file was uploaded
				if(isset($files['name']) && is_array($files['name'])) {
					
					//for all file names
					foreach($files['name'] as $field=>$name) {
						$filename = substr($name, 0, strpos($name, '.'));
						if(strlen($filename) > 0) {
							$ext = substr($name, strpos($name, '.'));
							$suffix = 1;
							
							//build file name
							$uploadedFileName = $filename.$ext;
							
							//rename if exists
							while(file_exists($uploadPath.$uploadedFileName)) {
								$uploadedFileName = $filename."_".$suffix.$ext;
								$suffix++;
								
							}
							$files['name'][$field] = $uploadedFileName;
							
							//move from temp folder to temp upload folder
							#print $files['tmp_name'][$field];
							#print $uploadPath.$uploadedFileName;
							move_uploaded_file($files['tmp_name'][$field],$uploadPath.$uploadedFileName);
							$files['uploaded_name'][$field] = $uploadedFileName;
							
							//set values for $_SESSION
							$tmp['name'] = $name;
							$tmp['uploaded_name'] = $uploadedFileName;
							$tmp['uploaded_path'] = $uploadPath;
							$tmp['size'] = $files['size'][$field];
							$tmp['type'] = $files['type'][$field];
							if(!is_array($_SESSION['mailformplusplusFiles'][$field]) && strlen($field)) {
								$_SESSION['mailformplusplusFiles'][$field] = array();
							}
							array_push($_SESSION['mailformplusplusFiles'][$field],$tmp);
						}
					}
				}
			}
		}
		
		session_commit();
		session_start();
	}
	
	/**
     * Sets the template file attribute to $template
     * 
     * @author Reinhard F�hricht <rf@typoheads.at>
     * @param string $template
     * @return void
     */
	public function setTemplateFile($template) {
		$this->templateFile = $template;
	}
	
	protected function runClasses($classesArray) {
		foreach($classesArray as $tsConfig) {
			F3_MailformPlusPlus_StaticFuncs::debugMessage("Calling: ".$tsConfig['class']);
			$obj = $this->componentManager->getComponent($tsConfig['class']);
			$this->gp = $obj->process($this->gp,$tsConfig['config.']);
		}
	}
	
	/**
	 * Main method of the form handler.
	 * 
	 * @author Reinhard F�hricht <rf@typoheads.at>
	 * @return rendered view
	 * @author Reinhard F�hricht
	 */
	public function process() {
		session_start();
		
		//read settings
		$settings = $this->getSettings();
		$_SESSION['mailformplusplusSettings']['settings'] = $settings;
		
		//set debug mode
		$_SESSION['mailformplusplusSettings']['debugMode'] = ($settings['debug'] == "1")?TRUE:FALSE;
		
		F3_MailformPlusPlus_StaticFuncs::debugMessage("Using controller \"F3_MailformPlusPlus_Controller_Default\"");
		
		//set gp vars
		$this->gp = array_merge(t3lib_div::_GET(), t3lib_div::_POST());
		if($settings['formValuesPrefix']) {
			$this->gp = $this->gp[$settings['formValuesPrefix']];
		}
		
		//set submitted
		$submitted = $this->gp['submitted'];
		
		if($this->gp['step-1']) {
			$submitted = false;
			$submit_reload = true;
			$this->processFiles();
		}
		
		//read template file
		$this->readTemplateFile($settings);
		
		// set stylesheet file
		$this->setStyleSheet($settings);
		
		//add some JavaScript for fancy form stuff
		$this->addSpecialJS($settings);
		
		//init view
		if(!$settings['view']) {
			$settings['view'] = "F3_MailformPlusPlus_View_Default";
		}
		$viewClass = $settings['view'];
		$view = $this->componentManager->getComponent($viewClass);
		$view->setTemplate($this->templateFile, 'FORM');
		$view->setLangFile($this->langFile);
		$view->setPredefined($this->predefined);
		
		F3_MailformPlusPlus_StaticFuncs::debugMessage("Using view \"".$viewClass."\"");
		$errors = array();
		session_start();
		
		//if not submitted
		if(!$submitted) {
			
			if(!$submit_reload) {
			
				//clear session variables
				$this->clearSession();
			}
			
			//clear uploaded files in temp folder if configured
			$this->clearTempFiles($settings['files.']['clearTempFilesOlderThanHours']);
			
			unset($_SESSION['submitted_ok']);
			
			//run preProcessors
			if(isset($settings['preProcessors.']) && is_array($settings['preProcessors.'])) {
				$this->runClasses($settings['preProcessors.']);
			}
			
			//run init interceptors
			if(isset($settings['initInterceptors.']) && is_array($settings['initInterceptors.'])) {
				$this->runClasses($settings['initInterceptors.']);
			}
			
			//display form
			$content = $view->render($this->gp,$errors).$this->additionalJS;
			return $content;
			
		//if submitted
		} else {
			
			//run init interceptors
			if(isset($settings['initInterceptors.']) && is_array($settings['initInterceptors.']) && !$_SESSION['submitted_ok']) {
				$this->runClasses($settings['initInterceptors.']);
			}
			
			//run validation
			$valid = array(true);
			if(isset($settings['validators.']) && is_array($settings['validators.'])  && !$_SESSION['submitted_ok']) {
				foreach($settings['validators.'] as $tsConfig) {
					F3_MailformPlusPlus_StaticFuncs::debugMessage("Calling Validator: ".$tsConfig['class']);
					$validator = $this->componentManager->getComponent($tsConfig['class']);
					
					//add requiredFields settings from plugin record, if class is the default validator or a subclass.
					if((is_a($validator,"F3_MailformPlusPlus_Validator_Default") || is_subclass_of($validator,"F3_MailformPlusPlus_Validator_Default")) && is_array($this->requiredFields)) {
						$tsConfig['config.']['requiredFields'] = $this->requiredFields;
					}
					$res = $validator->validate($this->gp,$tsConfig['config.'],$errors);
					array_push($valid,$res);
				}
			}
			
			//if form is valid
			if($this->isValid($valid)) {
				if(!$_SESSION['submitted_ok']) {
					$this->processFiles();
				}
				
				//run save interceptors
				if(isset($settings['saveInterceptors.'])  && is_array($settings['saveInterceptors.'])  && !$_SESSION['submitted_ok']) {
					$this->runClasses($settings['saveInterceptors.']);
				}
				
				//run loggers
				if(isset($settings['loggers.']) && is_array($settings['loggers.']) && !$_SESSION['submitted_ok']) {
					foreach($settings['loggers.'] as $tsConfig) {
						F3_MailformPlusPlus_StaticFuncs::debugMessage("Calling Logger: ".$tsConfig['class']);
						$logger = $this->componentManager->getComponent($tsConfig['class']);
						$logger->log($this->gp,$tsConfig['config.']);
					}
				}
				
				//run finishers
				if(isset($settings['finishers.']) && is_array($settings['finishers.'])) {
					foreach($settings['finishers.'] as $tsConfig) {
						
						$finisher = $this->componentManager->getComponent($tsConfig['class']);
						//check if the form was finished before. This flag is set by the F3_Finisher_Confirmation
						if(!$_SESSION['submitted_ok']) {
							F3_MailformPlusPlus_StaticFuncs::debugMessage("Calling Finisher: ".$tsConfig['class']);
							$tsConfig['config.']['returns'] = $tsConfig['returns']; 
							$tsConfig['config.']['templateFile'] = $settings['templateFile'];
							$tsConfig['config.']['langFile'] = $settings['langFile'];
							$tsConfig['config.']['formValuesPrefix'] = $settings['formValuesPrefix'];
							
							//if the finisher returns HTML (e.g. F3_MailformPlusPlus_Finisher_Confirmation)
							if($tsConfig['config.']['returns']) {
								
								return $finisher->process($this->gp,$tsConfig['config.']);			
							} else {
								
								//add email settings from plugin record if the finisher is the mail finisher or a subclass
								if((is_a($finisher,"F3_MailformPlusPlus_Finisher_Mail") || is_subclass_of($finisher,"F3_MailformPlusPlus_Finisher_Mail"))) {
									$finisher->setEmailSettings($this->emailSettings);
									
								//add redirect settings from plugin record if the finisher is the recirect finisher or a subclass
								} elseif((is_a($finisher,"F3_MailformPlusPlus_Finisher_Redirect") || is_subclass_of($finisher,"F3_MailformPlusPlus_Finisher_Redirect"))) {
									if(strlen($this->redirectPage) > 0) {
										$tsConfig['config.']['redirect_page'] = $this->redirectPage; 
									}
								}
								
								$this->gp = $finisher->process($this->gp,$tsConfig['config.']);
							}
							
						//if the form was finished before, only show the output of the F3_MailformPlusPlus_Finisher_Confirmation
						} elseif((is_a($finisher,"F3_MailformPlusPlus_Finisher_Confirmation") || is_subclass_of($finisher,"F3_MailformPlusPlus_Finisher_Confirmation"))) {
							F3_MailformPlusPlus_StaticFuncs::debugMessage("Calling Finisher: ".$tsConfig['class']);
							$finisher = $this->componentManager->getComponent($tsConfig['class']);
							$tsConfig['config.']['templateFile'] = $settings['templateFile'];
							$tsConfig['config.']['langFile'] = $settings['langFile'];
							$tsConfig['config.']['formValuesPrefix'] = $settings['formValuesPrefix'];
							return $finisher->process($this->gp,$tsConfig['config.']);
						}
					}
				}
				
				//not returned, but finished. What to do?
				
			//if form is not valid
			} else {
				
				//show form with errors
				$content = $view->render($this->gp,$errors).$this->additionalJS;
				return $content;
			}
		}
		
		
	}
	
	/**
	 * Read stylesheet file set in TypoScript. If set add to header data
	 * 
	 * @param $settings The mailformplusplus settings
	 * @return void
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function setStyleSheet(&$settings) {
		$stylesheetFile = $settings['stylesheetFile'];
		if (strlen($stylesheetFile) > 0) {
			
			// set stylesheet
			$GLOBALS['TSFE']->additionalHeaderData['special_css'] .= 
				'<link rel="stylesheet" href="'.F3_MailformPlusPlus_StaticFuncs::resolveRelPathFromSiteRoot($stylesheetFile).'" type="text/css" media="screen" />';
		}
	}
	
	/**
	 * Read template file set in flexform or TypoScript, read the file's contents to $this->templateFile
	 * 
	 * @param $settings The mailformplusplus settings
	 * @return void
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	protected function readTemplateFile(&$settings) {
		
		//template file was not set in flexform, search TypoScript for setting
		if(!$this->templateFile) {
			
			$templateFile = $settings['templateFile'];
			if(isset($settings['templateFile.']) && is_array($settings['templateFile.'])) {
				$this->templateFile = $this->cObj->cObjGetSingle($settings['templateFile'],$settings['templateFile.']);
			} else {
				$this->templateFile = t3lib_div::getURL(F3_MailformPlusPlus_StaticFuncs::resolvePath($templateFile));
			}
		} else {
				$templateFile = $this->templateFile;
				$this->templateFile = t3lib_div::getURL(F3_MailformPlusPlus_StaticFuncs::resolvePath($templateFile));
		}
		
		if(!$this->templateFile) {
			F3_MailformPlusPlus_StaticFuncs::debugMessage("Could not find template file");
		}
		
	}
	
	/**
	 * Returns some JavaScript code used for fany form stuff.
	 * 
	 * Example code:
	 * 
	 * <code>
	 * 
	 * #enable fancy form
	 * plugin.F3_MailformPlusPlus.settings.fancyForm = 1
	 * 
	 * #the id of the parent element (e.g. the form element)
	 * plugin.F3_MailformPlusPlus.settings.fancyForm.parentId = mailformplusplus_contact_form
	 * 
	 * #the class name of the help text elements
	 * plugin.F3_MailformPlusPlus.settings.helpTexts.className = contexthelp
	 * 
	 * #the id of the parent element (e.g. the form element)
	 * plugin.F3_MailformPlusPlus.settings.helpTexts.parentId = mailformplusplus_contact_form
	 * 
	 * #how many times a parent() call has to be added to get from input element on the same level as the help text element again
	 * plugin.F3_MailformPlusPlus.settings.helpTexts.parentTimes = 2
	 * 
	 * #autoComplete takes a list of fieldId, values settings. The values option can be any TypoScript object or a comma seperated list of words.
	 * plugin.F3_MailformPlusPlus.settings.autoComplete.1.fieldId = some_field
	 * plugin.F3_MailformPlusPlus.settings.autoComplete.1.values = Typoheads, Typoheads Gmbh, TYPO3
	 * plugin.F3_MailformPlusPlus.settings.autoComplete.1.fieldId = some_other_field
	 * plugin.F3_MailformPlusPlus.settings.autoComplete.1.values = USER
	 * plugin.F3_MailformPlusPlus.settings.autoComplete.1.values.userFunc = user_myClass->user_myFunction
	 * </code>
	 *
	 * @param $settings The mailformplusplus settings
	 * @return string JavaScript
	 * @author Reinhard F�hricht <rf@typoheads.at>
	 */
	protected function addSpecialJS($settings) {
		
		/*
		 * do fancy form.
		 * Adds JavaScript to replace checkboxes and radio buttons with graphics
		 */
		if($settings['fancyForm'] == "1") {
			F3_MailformPlusPlus_StaticFuncs::debugMessage("Using fancy form stuff");
			$GLOBALS['TSFE']->additionalHeaderData['special_css'] .= '
				<link href="typo3conf/ext/mailformplusplus/Resources/JS/crir/crir.css" rel="stylesheet" type="text/css" media="screen"/>
			';
			if(!strstr($GLOBALS['TSFE']->additionalHeaderData['special_js'],"/jquery.js")) {
				$GLOBALS['TSFE']->additionalHeaderData['special_js'] .=
				'<script language="JavaScript" type="text/javascript" src="'.t3lib_extMgm::extRelPath('mailformplusplus').'Resources/JS/jquery/jquery.js"></script>';
			}
			$GLOBALS['TSFE']->additionalHeaderData['special_js'] .= '
				<script language="JavaScript" type="text/javascript" src="'.t3lib_extMgm::extRelPath('mailformplusplus').'Resources/JS/crir/crir.js"></script>
				';
			$parentId = $settings['fancyForm.']['parentId'];
			if($parentId) {
				$this->additionalJS .= '
				<script language="JavaScript" type="text/javascript">
					$("#'.$parentId.' input[@type=checkbox]").addClass("crirHiddenJS");
					$("#'.$parentId.' input[@type=radio]").addClass("crirHiddenJS");
				</script>';
			} else {
				$this->additionalJS .= '
				<script language="JavaScript" type="text/javascript">
					$("input[@type=checkbox]").addClass("crirHiddenJS");
					$("input[@type=radio]").addClass("crirHiddenJS");
				</script>';
			}
		}
		
		/*
		 * Adds JavaScript for help texts. This texts will fade in, when the input fields gets focus and fade out on blur.
		 */
		if(is_array($settings['helpTexts.'])) {
			F3_MailformPlusPlus_StaticFuncs::debugMessage("Enabling help texts");
			if(!strstr($GLOBALS['TSFE']->additionalHeaderData['special_js'],"/jquery.js")) {
				$GLOBALS['TSFE']->additionalHeaderData['special_js'] .=
				'<script language="JavaScript" type="text/javascript" src="'.t3lib_extMgm::extRelPath('mailformplusplus').'Resources/JS/jquery/jquery.js"></script>';
			}
			$class = $settings['helpTexts.']['className'];
			$parentId = $settings['helpTexts.']['parentId'];
			$parentTimes = $settings['helpTexts.']['parentTimes'];
			$parent = "";
			if(is_numeric($parentTimes)) {
				for($i = 0;$i < $parentTimes; $i++) {
					$parents .= 'parent().';
				}
			}
			$this->additionalJS .= '
			<script language="JavaScript" type="text/javascript">
				$("#'.$parentId.' .'.$class.'").hide();
				$("#'.$parentId.' input[@type=text]").focus(function(){
					$(this).'.$parents.'next().children(".'.$class.'").fadeIn("slow");
				});
				$("#'.$parentId.' input[@type=text]").blur(function(){
					$(this).'.$parents.'next().children(".'.$class.'").fadeOut("slow");
				});
			</script>';
		}
		
		/*
		 * Adds JavaScript for auto complete. If the user types some letters in a configured field, a list of suggestions will be shown.
		 */
		if(is_array($settings['autoComplete.'])) {
			F3_MailformPlusPlus_StaticFuncs::debugMessage("Enabling autoComplete");
			
			$GLOBALS['TSFE']->additionalHeaderData['special_css'] .= '
				<link href="typo3conf/ext/mailformplusplus/Resources/JS/autocomplete/jquery.autocomplete.css" rel="stylesheet" type="text/css" media="screen"/>
				<link href="typo3conf/ext/mailformplusplus/Resources/JS/autocomplete/lib/thickbox.css" rel="stylesheet" type="text/css" media="screen"/>
			';
			$GLOBALS['TSFE']->additionalHeaderData['special_js'] .= '
				<script language="JavaScript" type="text/javascript" src="'.t3lib_extMgm::extRelPath('mailformplusplus').'Resources/JS/autocomplete/lib/jquery.bgiframe.min.js"></script>
				<script language="JavaScript" type="text/javascript" src="'.t3lib_extMgm::extRelPath('mailformplusplus').'Resources/JS/autocomplete/lib/jquery.ajaxQueue.js"></script>
				<script language="JavaScript" type="text/javascript" src="'.t3lib_extMgm::extRelPath('mailformplusplus').'Resources/JS/autocomplete/lib/thickbox-compressed.js"></script>
				<script language="JavaScript" type="text/javascript" src="'.t3lib_extMgm::extRelPath('mailformplusplus').'Resources/JS/autocomplete/jquery.autocomplete.js"></script>
				';
			$this->additionalJS .= '
				<script language="JavaScript" type="text/javascript">';
			foreach($settings['autoComplete.'] as $key=>$options) {
				$values = t3lib_div::trimExplode(",",$options['values']);
				$this->additionalJS .= '
					$(document).ready(function(){
					    var valuesArray = new Array("'.implode('","',$values).'");
						$("#'.$options['fieldId'].'").autocomplete(valuesArray,{matchContains: true});
					  });
				';
			}
			$this->additionalJS .= '
				</script>
			';
		}
	}
	
	/**
	 * Find out if submitted form was valid. If one of the values in the given array $valid is false the submission was not valid.
	 * 
	 * @param $valid Array with the return values of each validator
	 * @return boolean
	 * @author Reinhard F�hricht <rf@typoheads.at>
	 */
	protected function isValid($valid) {
		foreach($valid as $item) {
			if(!$item) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Possibly unnecessary
	 *
	 * @return void
	 * @author Reinhard F�hricht <rf@typoheads.at>
	 */
	protected function initializeController($value='') {
		$this->piVars = t3lib_div::GParrayMerged($this->configuration->getPrefixedPackageKey());
	}

}
?>
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
 * Multistep forms controller for MailformPlusPlus
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Controller
 */
class F3_MailformPlusPlus_Controller_Multistep extends F3_MailformPlusPlus_Controller_Default {
	
	/**
	 * Main method of the form handler.
	 *
	 * @return rendered view
	 * @author	Reinhard Führicht <rf@typoheads.at>
	 */
	public function process() {
		
		//read global settings
		session_start();
		$settings = $this->getSettings();
		
		//set GET/POST parameters
		$this->gp = array_merge(t3lib_div::_GET(), t3lib_div::_POST());
		if($settings['formValuesPrefix']) {
			$this->gp = $this->gp[$settings['formValuesPrefix']];
			$_SESSION['mailformplusplusSettings']['formValuesPrefix'] = $settings['formValuesPrefix'];
		}
		
		$this->mergeGPWithSession();
		
		//set debug mode
		$this->debugMode = ($settings['debug'] == '1')?TRUE:FALSE;
		$_SESSION['mailformplusplusSettings']['debugMode'] = $this->debugMode;
		
		//find current step
		$this->findCurrentStep();
		
		//set last step
		$this->lastStep = $_SESSION['mailformplusplusSettings']['currentStep'];
		if(!$this->lastStep) {
			$this->lastStep = 1;
		}
		
		//find total step count
		$stepCount = 0;
		foreach($settings as $step=>$options) {
			if(is_numeric($step)) {
				$stepCount ++;
			}
		}
		$this->totalSteps = $stepCount;
		
		//merge settings with specific settings for current step
		if(isset($settings[$this->currentStep.'.']) && is_array($settings[$this->currentStep.'.'])) {
			$settings = array_merge($settings,$settings[$this->currentStep.'.']);
		}
		
		//set debug mode again. Maybe it is turned off for this step
		$this->debugMode = ($settings['debug'] == '1')?TRUE:FALSE;
		$_SESSION['mailformplusplusSettings']['debugMode'] = $this->debugMode;
		
		F3_MailformPlusPlus_StaticFuncs::debugMessage('Using controller "F3_MailformPlusPlus_Controller_Default"');
		
		//store step values in session
		$_SESSION['mailformplusplusSettings']['settings'] = $settings;
		$_SESSION['mailformplusplusSettings']['currentStep'] = $this->currentStep;
		$_SESSION['mailformplusplusSettings']['lastStep'] = $this->lastStep;
		$_SESSION['mailformplusplusSettings']['totalSteps'] = $this->totalSteps;
		
		$disableErrorChecks = false;
		if($this->currentStep == $this->lastStep) {
			$disableErrorChecks = true;
		}
		
		//add some JavaScript for fancy form stuff
		$this->addSpecialJS($settings);
		
		//set submitted
		$submitted = $this->gp['submitted'];
		
		//read template file
		$this->readTemplateFile($settings);
		
		// set stylesheet file
		$this->setStyleSheet($settings);
		
		//init view
		$viewClass = $settings['view'];
		if(!$viewClass) {
			$viewClass = 'F3_MailformPlusPlus_View_Multistep';
		}
		$view = $this->componentManager->getComponent($viewClass);
		$view->setLangFile($this->langFile);
		$this->setViewSubpart($view,$settings,$this->currentStep);
		
		if($this->finished) {
			F3_MailformPlusPlus_StaticFuncs::debugMessage('Form is finished!');
		}
		F3_MailformPlusPlus_StaticFuncs::debugMessage('Using view "'.$viewClass.'"');
		
		$errors = array();
		
		//if not submitted
		if(!$submitted) {
			
			//clear session variables
			$this->clearSession();
			
			$this->gp = array();
			
			//clear uploaded files in temp folder if configured
			$this->clearTempFiles($settings['files.']['clearTempFilesOlderThanHours']);
			
			//run preProcessors
			if(isset($settings['preProcessors.']) && is_array($settings['preProcessors.'])) {
				$this->runClasses($settings['preProcessors.']);
			}
			
			//run init interceptors
			if(isset($settings['initInterceptors.']) && is_array($settings['initInterceptors.'])) {
				$this->runClasses($settings['initInterceptors.']);
			}
			
			//debug GET/POST parameters
			if(is_array($this->gp) && $this->debugMode) {
				F3_MailformPlusPlus_StaticFuncs::debugMessage('The current GET/POST values:<br />',false);
				F3_MailformPlusPlus_StaticFuncs::debugArray($this->gp);
			}
				
			//display form
			return $view->render($this->gp,$errors);
			
		//if submitted
		} else {
			
			//save settings because they have to be changed for error validation
			$oldSettings = $settings;
			
			//run init interceptors
			if(isset($settings['initInterceptors.']) && is_array($settings['initInterceptors.'])  && !$_SESSION['submitted_ok']) {
				$this->runClasses($settings['initInterceptors.']);
			}
			
			//debug GET/POST parameters
			if(isset($this->gp) && is_array($this->gp) && $this->debugMode) {
				F3_MailformPlusPlus_StaticFuncs::debugMessage('The current GET/POST values:<br />',false);
				F3_MailformPlusPlus_StaticFuncs::debugArray($this->gp);
			}
			
			//load settings from right step for error checks, ...
			if($this->currentStep > $this->lastStep) {
				$settings = $this->getSettings();
				if(is_array($settings[($this->currentStep-1).'.'])) {
					$settings = array_merge($settings,$settings[($this->currentStep-1).'.']);
				}
				$_SESSION['mailformplusplusSettings']['settings'] = $settings;
			}
			
			//run validation
			$valid = array(true);
			if(isset($settings['validators.']) && is_array($settings['validators.'])  && !$_SESSION['submitted_ok']) {
				foreach($settings['validators.'] as $tsConfig) {
					F3_MailformPlusPlus_StaticFuncs::debugMessage("Calling Validator: ".$tsConfig['class']);
					$validator = $this->componentManager->getComponent($tsConfig['class']);

					$validator->loadConfig($this->gp,$tsConfig['config.']);
					$res = $validator->validate($errors);
					array_push($valid,$res);
				}
			}
			
			//set settings back to current step settings because validation is over
			$settings = $oldSettings;
			$_SESSION['mailformplusplusSettings']['settings'] = $settings;
			
			//if form is valid
			if($this->isValid($valid)) {
				if(!$_SESSION['submitted_ok']) {
					$this->processFiles();
				}
				
				//if no more steps
				if($this->finished) {
					
					if(!is_array($this->gp)) {
						$this->gp = array();
					}
					
					//run save interceptors
					if(isset($settings['saveInterceptors.']) && is_array($settings['saveInterceptors.']) && !$_SESSION['submitted_ok']) {
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
	
								$finisher->loadConfig($this->gp,$tsConfig['config.']);
								
								//if the finisher returns HTML (e.g. F3_MailformPlusPlus_Finisher_Confirmation)
								if($tsConfig['config.']['returns']) {
	
									return $finisher->process();
								} else {
									
									$this->gp = $finisher->process();
								}
	
								//if the form was finished before, only show the output of the F3_MailformPlusPlus_Finisher_Confirmation
							} elseif((is_a($finisher,"F3_MailformPlusPlus_Finisher_Confirmation") || is_subclass_of($finisher,"F3_MailformPlusPlus_Finisher_Confirmation"))) {
								F3_MailformPlusPlus_StaticFuncs::debugMessage("Calling Finisher: ".$tsConfig['class']);
								$finisher = $this->componentManager->getComponent($tsConfig['class']);
								$tsConfig['config.']['templateFile'] = $settings['templateFile'];
								$tsConfig['config.']['langFile'] = $settings['langFile'];
								$tsConfig['config.']['formValuesPrefix'] = $settings['formValuesPrefix'];
								$finisher->loadConfig($this->gp,$tsConfig['config.']);
								return $finisher->process();
							}
						}
					}
				
				//form is not finished, render next step
				} else {
					
					//if user clicked "submit"
					if($this->currentStep >= $this->lastStep) {
						$this->storeGPinSession($settings);
					}
					
					//display form
					return $view->render($this->gp,$errors);
				}
				
				//not returned, but finished. What to do?
				
			//if form is not valid
			} else {
				
				//stay on current step
				if($this->lastStep < $_SESSION['mailformplusplusSettings']['currentStep']) {
					$_SESSION['mailformplusplusSettings']['currentStep']--;
				}
				
				//load settings from last step again because an error occurred
				if($this->currentStep > $this->lastStep) {
					$settings = $this->getSettings();
					if(isset($settings[($this->currentStep-1)."."]) && is_array($settings[($this->currentStep-1)."."])) {
						$settings = array_merge($settings,$settings[($this->currentStep-1).'.']);
					}
					$_SESSION['mailformplusplusSettings']['settings'] = $settings;
				}
		
				
				//reset the template because step had probably been decreased
				$this->setViewSubpart($view,$settings,($this->currentStep-1));
				
				//show form with errors
				#print get_class($view);
				return $view->render($this->gp,$errors);
			}
		}
		
		
	}
	
	/**
	 * Searches for current step and sets $this->currentStep according
	 *
	 * @return void
	 * @author	Reinhard Führicht <rf@typoheads.at>
	 */
	protected function findCurrentStep() {
		if(isset($this->gp) && is_array($this->gp)) {
			$highest = 0;
			foreach (array_keys($this->gp) as $pname) {
				
				if (strstr($pname,'step-')) {
					$mpPage = substr($pname,strpos($pname,'step-'),6);
					
					if (strpos($mpPage, '-')) {
						$mpPage = substr($mpPage,strrpos ($mpPage, '-')+1);
					} //if end
					
					if(intVal($mpPage) > $highest) {
						$highest = intVal($mpPage);
					}
				} // if end
			} // foreach end
			$this->currentStep = $highest;
		}
		if(!$this->currentStep) {
			$this->currentStep = 1;
		}
	}
	
	
	
	/**
	 * Sets the template of the view.
	 *
	 * @param F3_MailformPlusPlus_AbstractView &$view Reference to view object
	 * @param array &$settings Reference to the settings array
	 * @param integer $step The current step
	 * @return void
	 * @author	Reinhard Führicht <rf@typoheads.at>
	 */
	protected function setViewSubpart(&$view,&$settings,$step) {
		$this->finished = 0;
		
		//search for ###TEMPLATE_FORM[step][suffix]###
		if(strstr($this->templateFile,"###TEMPLATE_FORM".$step.$settings['templateSuffix']."###")) {
			F3_MailformPlusPlus_StaticFuncs::debugMessage("Using subpart \"###TEMPLATE_FORM".$step.$settings['templateSuffix']."###\"");
			$view->setTemplate($this->templateFile, 'FORM'.$step.$settings['templateSuffix']);
			
		//search for ###TEMPLATE_FORM[step]###
		} elseif(strstr($this->templateFile,"###TEMPLATE_FORM".$step."###")) {
			F3_MailformPlusPlus_StaticFuncs::debugMessage("Using subpart \"###TEMPLATE_FORM".$step."###\"");
			$view->setTemplate($this->templateFile, 'FORM'.$step);
			
		//search for ###TEMPLATE_FORM###
		} elseif(strstr($this->templateFile,"###TEMPLATE_FORM###")) {
			F3_MailformPlusPlus_StaticFuncs::debugMessage("Using subpart \"###TEMPLATE_FORM###\"");
			$view->setTemplate($this->templateFile, 'FORM');
			
		//mark form as finished
		} else {
			
			$this->finished = 1;
		}
	}
	
	/**
	 * Merges the current GET/POST parameters with the stored ones in SESSION
	 *
	 * @return void
	 * @author	Reinhard Führicht <rf@typoheads.at>
	 */
	protected function mergeGPWithSession() {
		session_start();
		if(!is_array($this->gp)) {
			$this->gp = array();
		}
		if(!is_array($_SESSION['mailformplusplusValues'])) {
			$_SESSION['mailformplusplusValues'] = array();
		}
		
		foreach($_SESSION['mailformplusplusValues'] as $step=>&$params) {
			if(is_array($params)) {
				unset($params['submitted']);
				if($step != $this->currentStep) {
					foreach($params as $key=>$value) {
						$this->gp[$key] = $value;
					}
				}
			}
		}
	}
	
	/**
	 * Stores the current GET/POST parameters in SESSION
	 *
	 * @param array &$settings Reference to the settings array to get information about checkboxes and radiobuttons.
	 * @return void
	 * @author	Reinhard Führicht <rf@typoheads.at>
	 */
	protected function storeGPinSession(&$settings) {
		session_start();
		
		//merge GET/POST again to get a third version of submitted values.
		//the values in $this->gp are not reliable because they got merged with $_SESSION in initPreProcessor
		$newGP = array_merge(t3lib_div::_GET(), t3lib_div::_POST());
		if($_SESSION['mailformplusplusSettings']['settings']['formValuesPrefix']) {
			$newGP = $newGP[$_SESSION['mailformplusplusSettings']['settings']['formValuesPrefix']];
		}
		
		//set the variables in session
		foreach($newGP as $key=>$value) {
			if(!strstr($key,"step-") && !strstr($key,"submitted")) {
				#$_SESSION['mailformplusplusValues'][$key] = $value;
				$_SESSION['mailformplusplusValues'][$this->currentStep-1][$key] = $value;
			}
		}
		
		//check for checkbox and radiobutton fields using the values in $newGP
		if($settings['checkBoxFields']) {
			$fields = t3lib_div::trimExplode(",",$settings['checkBoxFields']);
			foreach($fields as $field) {
				if(!isset($newGP[$field]) && isset($this->gp[$field])) {
					#$_SESSION['mailformplusplusValues'][][$field] = array();
					$_SESSION['mailformplusplusValues'][$this->currentStep-1][$field] = array();
				}
			}
		}
		if($settings['radioButtonFields']) {
			$fields = t3lib_div::trimExplode(",",$settings['checkBoxFields']);
			foreach($fields as $field) {
				if(!isset($newGP[$field]) && isset($this->gp[$field])) {
					#$_SESSION['mailformplusplusValues'][$field] = "";
					$_SESSION['mailformplusplusValues'][$this->currentStep-1][$field] = array();
				}
			}
		}
	}
	
	
	
}
?>
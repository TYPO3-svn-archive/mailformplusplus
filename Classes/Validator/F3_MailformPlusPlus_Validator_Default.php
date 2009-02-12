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
 * A default validator for MailformPlusPlus providing basic validations.
 * 
 * Example configuration:
 * 
 * <code>
 * plugin.F3_MailformPlusPlus.settings.validators.1.class = F3_MailformPlusPlus_Validator_Default
 * 
 * # single error check
 * plugin.F3_MailformPlusPlus.settings.validators.1.config.fieldConf.firstname.errorCheck.1 = required
 * 
 * #multiple error checks for one field
 * plugin.F3_MailformPlusPlus.settings.validators.1.config.fieldConf.email.errorCheck.1 = required
 * plugin.F3_MailformPlusPlus.settings.validators.1.config.fieldConf.email.errorCheck.2 = email
 * 
 * #error checks with parameters
 * #since the parameter for the error check "minLength" is "value", you can use a marker ###value### in your error message.
 * #E.g. The lastname has to be at least ###value### characters long.
 * plugin.F3_MailformPlusPlus.settings.validators.1.config.fieldConf.lastname.errorCheck.1 = required
 * plugin.F3_MailformPlusPlus.settings.validators.1.config.fieldConf.lastname.errorCheck.2 = minLength
 * plugin.F3_MailformPlusPlus.settings.validators.1.config.fieldConf.lastname.errorCheck.2.value = 2
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Validator
 */
class F3_MailformPlusPlus_Validator_Default extends F3_MailformPlusPlus_AbstractValidator {
	
	/**
	 * Validates the submitted values using given settings
	 *
	 * @param array $gp The current GET/POST parameters
	 * @param array $settings The TypoScript settings for the validator
	 * @param array $errors Reference to the errors array to store the errors occurred
	 * @return boolean
	 */
	public function validate($gp,$settings,&$errors) {
		
		//set GET/POST parameters
		$this->gp = $gp;
		
		//no config? validation returns true
		if(!is_array($settings['fieldConf.'])) {
			return true;
		}
		
		//$disableErrorCheckFields = array();
		if(isset($settings['disableErrorCheckFields'])) {
			$disableErrorCheckFields = t3lib_div::trimExplode(",",$settings['disableErrorCheckFields']);
		}
		if(is_array($settings['requiredFields'])) {
			foreach($settings['requiredFields'] as $field) {
				$settings['fieldConf.'][$field."."]['errorCheck.'] = array();
				$settings['fieldConf.'][$field."."]['errorCheck.']['1'] = "required";
			}
		}
				
		//foreach configured form field
		foreach($settings['fieldConf.'] as $fieldName=>$fieldSettings) {
			$name = str_replace(".","",$fieldName);
			
			//parse error checks
			if(is_array($fieldSettings['errorCheck.'])) {
				$counter = 0;
				$errorChecks = array();
				//set required to first position if set
				foreach($fieldSettings['errorCheck.'] as $key=>$check) {
					if(!strstr($key,".")) {
						if(!strcmp($check,"required") || !strcmp($check,"file_required")) {
							$errorChecks[$counter]['check'] = $check;
							unset($fieldSettings['errorCheck.'][$key]);
							$counter++;
						}	
					}
				}
				
				//set other errorChecks
				foreach($fieldSettings['errorCheck.'] as $key=>$check) {
					if(!strstr($key,".")) {
						$errorChecks[$counter]['check'] = $check;
						if(is_array($fieldSettings['errorCheck.'][$key."."])) {
							$errorChecks[$counter]['params'] = $fieldSettings['errorCheck.'][$key."."];
						}
						$counter++;
					}
				}
				
				$checkFailed = "";
				if(!isset($disableErrorCheckFields) || !in_array($name,$disableErrorCheckFields)) {
					
					//foreach error checks
					foreach($errorChecks as $check) {
						switch($check['check']) {
							
							//uploaded file specific
							
							//file type matches one of the configured allowed types
							case "file_allowedTypes":
								$checkFailed = $this->validateAllowedFileTypes($check,$name);
							break;
							
							//only a configured number of files are allowed to be uploaded via this field
							case 'file_maxCount':
								$checkFailed = $this->validateMaxFileCount($check,$name);
							break;
							
							//uploaded files must have a minimum size
							case "file_minSize":
								$checkFailed = $this->validateMinFileSize($check,$name);
							break;
							
							//uploaded files must have a maximum size
							case "file_maxSize":
								$checkFailed = $this->validateMaxFileSize($check,$name);
							break;
							
							//the upload field is required
							case "file_required":
								$checkFailed = $this->validateFileRequired($check,$name);
							break;
							
							//value is required
							case "required":
								$checkFailed = $this->validateRequired($check,$name);
							break;
							
							//value should be a valid e-mail
							case "email":
								$checkFailed = $this->validateEmail($check,$name);
							break;
							
							//the value contains none of the configured words or phrases
							case "containsNone":
								$checkFailed = $this->validateContainsNone($check,$name);
							break;
							
							//the value contains at least one of the configured words or phrases
							case "containsOne":
								$checkFailed = $this->validateContainsOne($check,$name);
							break;
							
							//the value must contain all of the configured words or phrases
							case "containsAll":
								$checkFailed = $this->validateContainsAll($check,$name);
							break;
							
							//the value equals a configured word
							case "equals":
								$checkFailed = $this->validateEquals($check,$name);
							break;
							
							//value should not equal it's default value
							case "notDefaultValue":
								$checkFailed = $this->validateNotDefaultValue($check,$name);
							break;
							
							//value should be an integer
							case "integer":
								$checkFailed = $this->validateInteger($check,$name);
							break;
							
							//value should be a float
							case "float":
								$checkFailed = $this->validateFloat($check,$name);
							break;
							
							//value should be greater than X
							case "minValue":
								$checkFailed = $this->validateMinValue($check,$name);
							break;
							
							//value should be less than X
							case "maxValue":
								$checkFailed = $this->validateMaxValue($check,$name);
							break;
							
							//value should be between X and Y
							case "betweenValue":
								$checkFailed = $this->validateBetweenValue($check,$name);
							break;
							
							//value should be a string with a minimum length of X
							case "minLength":
								$checkFailed = $this->validateMinLength($check,$name);
							break;
							
							//value should be a string with a maximum length of X
							case "maxLength":
								$checkFailed = $this->validateMaxLength($check,$name);
							break;
							
							//value should be a string with a length between X and Y
							case "betweenLength":
								$checkFailed = $this->validateBetweenLength($check,$name);
							break;
							
							//value should be a an array with a minimum item count of X
							case "minItems":
								$checkFailed = $this->validateMinItems($check,$name);
							break;
							
							//value should be a an array with a maximum item count of X
							case "maxItems":
								$checkFailed = $this->validateMaxItems($check,$name);
							break;
							
							//value should be a an array with an item count between X and Y
							case "betweenItems":
								$checkFailed = $this->validateBetweenItems($check,$name);
							break;
							
							//value should exist in a db table
							case "isInDBTable":
								$checkFailed = $this->validateIsInDBTable($check,$name);
							break;
							
							//value should not exist in a db table
							case "isNotInDBTable":
								$checkFailed = $this->validateIsNotInDBTable($check,$name);
							break;
							
							//value should match a regular expression
							case "ereg":
								$checkFailed = $this->validateEreg($check,$name);
							break;
							
							//value should match a regular expression (case insensitive)
							case "eregi":
								$checkFailed = $this->validateEregi($check,$name);
							break;
							
							//value should match a captcha string generated by the extension 'captcha'
							case "captcha":
								$checkFailed = $this->validateCaptcha($check,$name);
							break;
							
							//value should match a captcha string generated by the extension 'sr_freecap'
							case "sr_freecap":
								$checkFailed = $this->validateSrFreecap($check,$name);
							break;
							
							
							case "simple_captcha":
								$checkFailed = $this->validateSimpleCaptcha($check,$name);
							break;
							
							//value should match a captcha string generated by the extension 'jm_recaptcha'
							case "jm_recaptcha":
								$checkFailed = $this->validateJmRecaptcha($check,$name);
							break;
							
							//value should match the expected result generated by MathGuard
							case "mathguard":
								$checkFailed = $this->validateMathGuard($check,$name);
							break;
							
							//the value is between a configured date range
							case "dateRange":
								$checkFailed = $this->validateDateRange($check,$name);
							break;
							
							//the value is a valid time
							case "time":
								$checkFailed = $this->validateTime($check,$name);
							break;
							
							//the value is a valid date
							case "date":
								$checkFailed = $this->validateDate($check,$name);
							break;
						}
						
						if(strlen($checkFailed) > 0) {
							if(!is_array($errors[$name])) {
								$errors[$name] = array();
							}
							array_push($errors[$name],$checkFailed);
						}
					}
				}
			}
		}
		return empty($errors);
		
	}
	
	/**
	 * Validates that an uploaded file has a minimum file size
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateMinFileSize(&$check,$name) {
		$checkFailed = "";
		$minSize = $check['params']['minSize'];
		foreach($_FILES as $sthg=>&$files) {
			if(	strlen($files['name'][$name]) > 0 &&
				$minSize &&
				$files['size'][$name] < $minSize) {
		
				unset($files);
				$checkFailed = $this->getCheckFailed($check);
			}
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that an uploaded file has a maximum file size
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateMaxFileSize(&$check,$name) {
		$checkFailed = "";
		$maxSize = $check['params']['maxSize'];
		foreach($_FILES as $sthg=>&$files) {
			if(	strlen($files['name'][$name]) > 0 &&
				$maxSize &&
				$files['size'][$name] > $maxSize) {
					
				unset($files);
				$checkFailed = $this->getCheckFailed($check);
			}
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that up to x files get uploaded via the spcified upload field
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateMaxFileCount(&$check,$name) {
		$checkFailed = "";
		
		session_start();
								
		$maxCount = $check['params']['maxCount'];
		if(	is_array($_SESSION['mailformplusplusFiles'][$name]) && 
			count($_SESSION['mailformplusplusFiles'][$name]) >= $maxCount &&
			$_SESSION['mailformplusplusSettings']['currentStep'] == $_SESSION['mailformplusplusSettings']['lastStep']) {
			
			$checkFailed = $this->getCheckFailed($check);
		} elseif (is_array($_SESSION['mailformplusplusFiles'][$name]) && 
			$_SESSION['mailformplusplusSettings']['currentStep'] > $_SESSION['mailformplusplusSettings']['lastStep']) {
			
			foreach($_FILES as $idx=>$info) {
				if(strlen($info['name'][$name]) > 0 && count($_SESSION['mailformplusplusFiles'][$name]) >= $maxCount) {
					$checkFailed = $this->getCheckFailed($check);
				}
			}
			
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a file gets uploaded via specified upload field
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateFileRequired(&$check,$name) {
		$checkFailed = "";
		session_start();
		$found = false;
		foreach($_FILES as $sthg=>&$files) {
			if(strlen($files['name'][$name]) > 0) {
				$found = true;
			}	
		}
		if(!$found && count($_SESSION['mailformplusplusFiles'][$name]) == 0) {
			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field is filled out
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateRequired(&$check,$name) {
		$checkFailed = "";
		if(strlen(trim($this->gp[$name])) == 0) {
			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field doesn't contain one of the specified words
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateContainsNone($check,$name) {
		$checkFailed = "";
		$formValue = trim($this->gp[$name]);
		$checkValue = $check['params']['words'];
		if(is_array($check['params']['words.'])) {
			if(!strcmp($check['params']['words'],"USER") || !strcmp($check['params']['words'],"USER_INT")) {
				$checkValue = t3lib_div::callUserFunction($check['params']['words.']['userFunc'],$check['params']['words.'],$this,"");
			} else {
				$checkValue = $this->cObj->cObjGetSingle($check['params']['words'],$check['params']['words.']);
			}
		}
		if(!is_array($checkValue)) {
			$checkValue = t3lib_div::trimExplode(",",$checkValue);
		}
		$found = false;
		foreach($checkValue as $word) {
			if(stristr($formValue,$word) && !$found) {
				
				//remove userfunc settings and only store comma seperated words
				$check['params']['words'] = implode(",",$checkValue);
				unset($check['params']['words.']);
				$checkFailed = $this->getCheckFailed($check);
				$found = true;
			}
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field equals a specified word
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateEquals($check,$name) {
		$checkFailed = "";
		$formValue = trim($this->gp[$name]);
		$checkValue = $check['params']['word'];
		if(is_array($check['params']['word.'])) {
			if(!strcmp($check['params']['word'],"USER") || !strcmp($check['params']['word'],"USER_INT")) {
				$checkValue = t3lib_div::callUserFunction($check['params']['word.']['userFunc'],$check['params']['word.'],$this,"");
			} else {
				$checkValue = $this->cObj->cObjGetSingle($check['params']['word'],$check['params']['word.']);
			}
		}
		if(strcasecmp($formValue,$checkValue)) {
			
			//remove userfunc settings
			unset($check['params']['word.']);
			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field contains at least one of the specified words
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateContainsOne($check,$name) {
		$checkFailed = "";
		$formValue = trim($this->gp[$name]);
		$checkValue = $check['params']['words'];
		if(is_array($check['params']['words.'])) {
			if(!strcmp($check['params']['words'],"USER") || !strcmp($check['params']['words'],"USER_INT")) {
				$checkValue = t3lib_div::callUserFunction($check['params']['words.']['userFunc'],$check['params']['words.'],$this,"");
			} else {
				$checkValue = $this->cObj->cObjGetSingle($check['params']['words'],$check['params']['words.']);
			}
		}
		if(!is_array($checkValue)) {
			$checkValue = t3lib_div::trimExplode(",",$checkValue);
		}
		$found = false;
		foreach($checkValue as $word) {
			if(stristr($formValue,$word) && !$found) {
				$found = true;
			}
		}
		if(!$found) {
			
			//remove userfunc settings and only store comma seperated words
			$check['params']['words'] = implode(",",$checkValue);
			unset($check['params']['words.']);
			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field contains all of the specified words
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateContainsAll($check,$name) {
		$checkFailed = "";
		$formValue = trim($this->gp[$name]);
		$checkValue = $check['params']['words'];
		if(is_array($check['params']['words.'])) {
			if(!strcmp($check['params']['words'],"USER") || !strcmp($check['params']['words'],"USER_INT")) {
				$checkValue = t3lib_div::callUserFunction($check['params']['words.']['userFunc'],$check['params']['words.'],$this,"");
			} else {
				$checkValue = $this->cObj->cObjGetSingle($check['params']['words'],$check['params']['words.']);
			}
		}
		if(!is_array($checkValue)) {
			$checkValue = t3lib_div::trimExplode(",",$checkValue);
		}
		foreach($checkValue as $word) {
			if(!stristr($formValue,$word)) {
				
				//remove userfunc settings and only store comma seperated words
				$check['params']['words'] = implode(",",$checkValue);
				unset($check['params']['words.']);
				$checkFailed = $this->getCheckFailed($check);
			}
		}

		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field has valid email syntax.
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateEmail(&$check,$name) {
		$checkFailed = "";
		$valid = t3lib_div::validEmail($this->gp[$name]);
		if(!$valid) {
			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field doesn't equal a specified default value.
	 * This default value could have been set via a PreProcessor.
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateNotDefaultValue(&$check,$name) {
		$checkFailed = "";
		$defaultValue = $check['params']['defaultValue'];
		if(is_array($check['params']['defaultValue.'])) {
			$defaultValue = $this->cObj->cObjGetSingle($check['params']['defaultValue'],$check['params']['defaultValue.']);
		}
		if ($defaultValue != '') {
			if (!strcmp($defaultValue,$this->gp[$name])) {
				$checkFailed = $this->getCheckFailed($check);
			}
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field is a valid integer.
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateInteger(&$check,$name) {
		$checkFailed = "";
		if(isset($this->gp[$name]) && !empty($this->gp[$name])) {
			$ereg = "^[0-9]+$";
			$valid = ereg($ereg, $this->gp[$name]);
			if(!$valid) {
				$checkFailed = $this->getCheckFailed($check);
			}
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field is a valid float
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateFloat(&$check,$name) {
		$checkFailed = "";
		$valid = is_float($this->gp[$name]);
		if(!$valid) {
			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field is an integer and greater than or equal a specified value
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateMinValue(&$check,$name) {
		$checkFailed = "";
		$min = $check['params']['value'];
		if(	isset($this->gp[$name]) && 
			!empty($this->gp[$name]) &&
			$min &&
			(!t3lib_div::testInt($this->gp[$name]) || intVal($this->gp[$name]) < $min)) {
			
			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field is an integer and lower than or equal a specified value
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateMaxValue(&$check,$name) {
		$checkFailed = "";
		$max = $check['params']['value'];
		if(	isset($this->gp[$name]) && 
			!empty($this->gp[$name]) &&
			$max &&
			(!t3lib_div::testInt($this->gp[$name]) || intVal($this->gp[$name]) > $max)) {
			
			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field is an integer between two specified values
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateBetweenValue(&$check,$name) {
		$checkFailed = "";
		$min = $check['params']['minValue'];
		$max = $check['params']['maxValue'];
		if(	isset($this->gp[$name]) && 
			!empty($this->gp[$name]) &&
			$min &&
			$max &&
			(!t3lib_div::testInt($this->gp[$name]) || intVal($this->gp[$name]) < $min || intVal($this->gp[$name]) > $max)) {
			
			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field is a string and at least a specified count of characters long
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateMinLength(&$check,$name) {
		$checkFailed = "";
		$min = $check['params']['value'];
		if(	isset($this->gp[$name]) && 
			!empty($this->gp[$name]) &&
			$min &&
			strlen(trim($this->gp[$name])) < $min) {

			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field is a string and shorter a specified count of characters
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateMaxLength(&$check,$name) {
		
		$checkFailed = "";
		$max = $check['params']['value'];
		if(	isset($this->gp[$name]) && 
			!empty($this->gp[$name]) &&
			$max &&
			strlen(trim($this->gp[$name])) > $max) {

			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field is a string and has a length between two specified values
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateBetweenLength(&$check,$name) {
		$checkFailed = "";
		$min = $check['params']['minValue'];
		$max = $check['params']['maxValue'];
		if(	isset($this->gp[$name]) && 
			!empty($this->gp[$name]) &&
			$min &&
			$max && 
			(strlen($this->gp[$name]) < $min || strlen($this->gp[$name]) > $max)) {
			
			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field is an array and has at least a specified amount of items
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateMinItems(&$check,$name) {
		$checkFailed = "";
		$value = $check['params']['value'];
		if(is_array($this->gp[$name])) {
			if(count($this->gp[$name]) < $value) {
				$checkFailed = $this->getCheckFailed($check);
			}
		} else {
			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field is an array and has less than or exactly a specified amount of items
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateMaxItems(&$check,$name) {
		$checkFailed = "";
		$value = $check['params']['value'];
		if(is_array($this->gp[$name])) {
			if(count($this->gp[$name]) > $value) {
				$checkFailed = $this->getCheckFailed($check);
			}
		} else {
			$checkFailed = $this->getCheckFailed($check);
		}
	}
	
	/**
	 * Validates that a specified field is an array and has an item count between two specified values
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateBetweenItems(&$check,$name) {
		$checkFailed = "";
		$min = $check['params']['minValue'];
		$max = $check['params']['maxValue'];
		if(	isset($this->gp[$name]) && 
			!empty($this->gp[$name]) && 
			is_array($this->gp[$name]) &&
			$min &&
			$max &&
			(count($this->gp[$name]) < $min || count($this->gp[$name]) > $max)) {

			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field's value is found in a specified db table
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateIsInDBTable(&$check,$name) {
		$checkFailed = "";
		$checkTable = $check['params']['table'];
		$checkField = $check['params']['field'];
		$where = $check['params']['additionalWhere'];
		if (!empty($checkTable) && !empty($checkField)) {
			$where = $checkField.'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->get_post[$fieldname],$checkTable).' '.$additionalWhere;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($checkField,$checkTable,$where);
			if ($res && !$GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
				$checkFailed = $this->getCheckFailed($check);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field's value is not found in a specified db table
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateIsNotInDBTable(&$check,$name) {
		$checkFailed = "";
		$checkTable = $check['params']['table'];
		$checkField = $check['params']['field'];
		$where = $check['params']['additionalWhere'];
		if (!empty($checkTable) && !empty($checkField)) {
			$where = $checkField.'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->get_post[$fieldname],$checkTable).' '.$additionalWhere;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($checkField,$checkTable,$where);
			if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
				$checkFailed = $this->getCheckFailed($check);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field's value matches a regular expression
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateEreg(&$check,$name) {
		$checkFailed = "";
		$ereg = $check['params']['value'];
		if($ereg && !ereg($ereg, $this->gp[$name])) {
			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field's value matches a regular expression case insensitive
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateEregi(&$check,$name) {
		$checkFailed = "";
		$eregi = $check['params']['value'];
		if($eregi && !eregi($eregi, $this->gp[$name])) {
			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field's value matches the generated word of the extension "captcha"
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateCaptcha(&$check,$name) {
		$checkFailed = "";
		
		// get captcha sting
		session_start();
		$captchaStr = $_SESSION['tx_captcha_string'];
		$_SESSION['tx_captcha_string'] = '';
		if ($captchaStr != $this->gp[$name]) {
			$checkFailed = $this->getCheckFailed($check);
		}	
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field's value matches the generated word of the extension "sr_freecap"
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateSrFreecap(&$check,$name) {
		$checkFailed = "";
		if(t3lib_extMgm::isLoaded('sr_freecap')) {
			require_once(t3lib_extMgm::extPath('sr_freecap').'pi2/class.tx_srfreecap_pi2.php');
			$this->freeCap = t3lib_div::makeInstance('tx_srfreecap_pi2');
			if(!$this->freeCap->checkWord($this->gp[$name])) {
				$checkFailed = $this->getCheckFailed($check);
			}
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that the correct image of possible images displayed by the extension "simple_captcha" got selected.
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateSimpleCaptcha(&$check,$name) {
		$checkFailed = "";
		if (t3lib_extMgm::isLoaded('simple_captcha')) {
			require_once(t3lib_extMgm::extPath('simple_captcha', 'class.tx_simplecaptcha.php'));
			$simpleCaptcha_className = t3lib_div::makeInstanceClassName('tx_simplecaptcha');
			$this->simpleCaptcha = new $simpleCaptcha_className();
			if (!$this->simpleCaptcha->checkCaptcha()) {
				$checkFailed = $this->getCheckFailed($check);
			}
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field's value matches the generated word of the extension "jm_recaptcha"
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateJmRecaptcha(&$check,$name) {
		$checkFailed = "";
		if(t3lib_extMgm::isLoaded('jm_recaptcha')) {
			require_once(t3lib_extMgm::extPath('jm_recaptcha')."class.tx_jmrecaptcha.php");
			$this->recaptcha = new tx_jmrecaptcha();
			$status = $this->recaptcha->validateReCaptcha();
			if (!$status['verified']) {
				$checkFailed = $this->getCheckFailed($check);
			}
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field's value matches the expected result of the MathGuard question
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateMathGuard(&$check,$name) {
		$checkFailed = "";
		require_once(t3lib_extMgm::extPath('mailformplusplus')."Resources/PHP/mathguard/ClassMathGuard.php");
		if (!MathGuard :: checkResult($_REQUEST['mathguard_answer'], $_REQUEST['mathguard_code'])) {
			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field's value is a valid time
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateTime(&$check,$name) {
		$checkFailed = "";
		$pattern = $check['params']['pattern'];
		eregi('^[h|m]*(.)[h|m]*', $pattern, $res);
		$sep = $res[1];			    
		$timeCheck = explode($sep, $this->get_post[$fieldname]);
		if (is_array($timeCheck)) {
			$hours = $tc[0];
			if (!is_numeric($hours) || $hours < 0 || $hours > 23) {
				$checkFailed = $this->getCheckFailed($check);
			}
			$minutes = $tc[1];
			if (!is_numeric($minutes) || $minutes < 0 || $minutes > 59) {
				$checkFailed = $this->getCheckFailed($check);
			}
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that an uploaded file via specified field matches one of the given file types
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateAllowedFileTypes(&$check,$name) {
		$checkFailed = "";
		$allowed = $check['params']['allowedTypes'];
		foreach($_FILES as $sthg=>&$files) {
			if(strlen($files['name'][$name]) > 0) {
				if($allowed) {
					$types = t3lib_div::trimExplode(",",$allowed);
					$fileext = substr($files['name'][$name], strrpos($files['name'][$name], '.') + 1);
					$fileext = strtolower($fileext);
					if(!in_array($fileext,$types)) {
						unset($files);
						$checkFailed = $this->getCheckFailed($check);
					}		
				}
			}
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field's value is a valid date
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateDate(&$check,$name) {
		$checkFailed = "";
		
		# find out separator
		$pattern = $check['params']['pattern'];
		eregi('^[d|m|y]*(.)[d|m|y]*', $pattern, $res);
		$sep = $res[1];
		
		# normalisation of format
		$pattern = $this->normalizeDatePattern($pattern,$sep);
		
		# find out correct positioins of "d","m","y"
		$pos1 = strpos($pattern, 'd');
		$pos2 = strpos($pattern, 'm');
		$pos3 = strpos($pattern, 'y');
		$dateCheck = explode($sep, $this->gp[$name]);
		if (sizeof($dateCheck) != 3) {
			$checkFailed = $this->getCheckFailed($check);
		} elseif (intval($dateCheck[0]) == 0 || intval($dateCheck[1]) == 0 || intval($dateCheck[2]) == 0) {
			$checkFailed = $this->getCheckFailed($check);
		} elseif (!checkdate($dateCheck[$pos2], $dateCheck[$pos1], $dateCheck[$pos3])) {
			$checkFailed = $this->getCheckFailed($check);
		} elseif (strlen($dateCheck[$pos3]) != 4) {
			$checkFailed = $this->getCheckFailed($check);
		}
		return $checkFailed;
	}
	
	/**
	 * Validates that a specified field's value is a valid date and between two specified dates
	 *
	 * @param array &$check The TypoScript settings for this error check
	 * @param string $name The field name
	 * @return string The error string
	 */
	protected function validateDateRange(&$check,$name) {
		
		$checkFailed = "";
		
		$min = $check['params']['min'];
		$max = $check['params']['max'];
		$pattern = $check['params']['pattern'];
		eregi('^[d|m|y]*(.)[d|m|y]*', $pattern, $res);
		$sep = $res[1];
		
		# normalisation of format
		$pattern = $this->normalizeDatePattern($pattern,$sep);
		
		# find out correct positioins of "d","m","y"
		$pos1 = strpos($pattern, 'd');
		$pos2 = strpos($pattern, 'm');
		$pos3 = strpos($pattern, 'y');
		$date = $this->gp[$name];
		$checkdate = explode($sep,$date);
		$check_day = $checkdate[$pos1];
		$check_month = $checkdate[$pos2];
		$check_year = $checkdate[$pos3];
		if($min != "") {
			$min_date = explode($sep,$min);
			$min_day = $min_date[$pos1];
			$min_month = $min_date[$pos2];
			$min_year = $min_date[$pos3];
			if($check_year<$min_year) {
				$checkFailed = $this->getCheckFailed($check);
			} elseif ($check_year == $min_year && $check_month < $min_month) {
				$checkFailed = $this->getCheckFailed($check);
			} elseif ($check_year == $min_year && $check_month == $min_month && $check_day < $min_day) {
				$checkFailed = $this->getCheckFailed($check);
			}
		}
		if($max != "") {
			$max_date = explode($sep,$max);
			$max_day = $max_date[$pos1];
			$max_month = $max_date[$pos2];
			$max_year = $max_date[$pos3];
			if($check_year > $max_year) {
				$checkFailed = $this->getCheckFailed($check);
			} elseif ($check_year == $max_year && $check_month > $max_month) {
				$checkFailed = $this->getCheckFailed($check);
			} elseif ($check_year == $max_year && $check_month == $max_month && $check_day > $max_day) {
				$checkFailed = $this->getCheckFailed($check);
			}
		}
		
		return $checkFailed;
	}
	
	/**
	 * Internal method to normalize a specified date pattern for internal use
	 *
	 * @param string $pattern The pattern
	 * @param string $sep The seperator character
	 * @return string The normalized pattern
	 */
	protected function normalizeDatePattern($pattern,$sep) {
		$pattern = strtoupper($pattern);
		$pattern = str_replace($sep, '', $pattern);
		$pattern = str_replace('DD', 'd', $pattern);
		$pattern = str_replace('D', 'd', $pattern);
		$pattern = str_replace('MM', 'm', $pattern);
		$pattern = str_replace('M', 'm', $pattern);
		$pattern = str_replace('YYYY', 'y', $pattern);
		$pattern = str_replace('YY', 'y', $pattern);
		return $pattern;
	}
	
	/**
	 * Sets the suitable string for the checkFailed message parsed in view.
	 *
	 * @param array $check The parsed check settings
	 * @return string The check failed string
	 */
	protected function getCheckFailed($check) {
		$checkFailed = $check['check'];
		if(is_array($check['params'])) {
			$checkFailed .= ";";
			foreach($check['params'] as $key=>$value) {
				$checkFailed .= $key."::".$value.";";
			}
			$checkFailed = substr($checkFailed,0,strlen($checkFailed)-1);
		}
		return $checkFailed;
	}
	
}
?>
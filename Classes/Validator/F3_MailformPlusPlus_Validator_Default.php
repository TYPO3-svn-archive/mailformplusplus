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
	 * Method to set GET/POST for this class and load the configuration
	 *
	 * @param array The GET/POST values
	 * @param array The TypoScript configuration
	 * @return void
	 */
	public function loadConfig($gp,$tsConfig) {
		$this->settings = $tsConfig;

		$flexformValue = F3_MailformPlusPlus_StaticFuncs::pi_getFFvalue($this->cObj->data['pi_flexform'],'required_fields','sMISC');
		if($flexformValue) {
			$fields = t3lib_div::trimExplode(',',$flexformValue);
			foreach($fields as $field) {
				$this->settings['fieldConf.'][$field."."]['errorCheck.'] = array();
				$this->settings['fieldConf.'][$field."."]['errorCheck.']['1'] = "required";
			}
		}

		$this->gp = $gp;
	}

	/**
	 * Validates the submitted values using given settings
	 *
	 * @param array $errors Reference to the errors array to store the errors occurred
	 * @return boolean
	 */
	public function validate(&$errors) {


		//no config? validation returns true
		if(!is_array($this->settings['fieldConf.'])) {
			return true;
		}

		//$disableErrorCheckFields = array();
		if(isset($this->settings['disableErrorCheckFields'])) {
			$disableErrorCheckFields = t3lib_div::trimExplode(",",$this->settings['disableErrorCheckFields']);
		}
		
		$restrictErrorChecks = array();
		if(isset($this->settings['restrictErrorChecks'])) {
			$restrictErrorChecks = t3lib_div::trimExplode(",",$this->settings['restrictErrorChecks']);
		}


		//foreach configured form field
		foreach($this->settings['fieldConf.'] as $fieldName=>$fieldSettings) {
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
						$classNameFix = ucfirst($check['check']);
						$errorCheckObject = $this->componentManager->getComponent('F3_MailformPlusPlus_ErrorCheck_'.$classNameFix);
						if(!$errorCheckObject) {
							F3_MailformPlusPlus_StaticFuncs::debugMessage('Error check "F3_MailformPlusPlus_ErrorCheck_'.$classNameFix.'" not found!');
						}
						if(empty($restrictErrorChecks) || in_array($check['check'],$restrictErrorChecks)) {
							$checkFailed = $errorCheckObject->check($check,$name,$this->gp);
							if(strlen($checkFailed) > 0) {
								if(!is_array($errors[$name])) {
									$errors[$name] = array();
								}
								array_push($errors[$name],$checkFailed);
							}
						} else {
							F3_MailformPlusPlus_StaticFuncs::debugMessage('Skipped error check "'.$check['check'].'"!');
						}
					}
				}
			}
		}
		return empty($errors);

	}

}
?>
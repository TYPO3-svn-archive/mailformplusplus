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
 * $Id:$
 *                                                                       
 */

/**
 * An interceptor parsing some GET/POST parameters
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Interceptor
 */
class F3_MailformPlusPlus_Interceptor_ParseValues extends F3_MailformPlusPlus_AbstractInterceptor {

	/**
	 * The main method called by the controller
	 *
	 * @param array $gp The GET/POST parameters
	 * @param array $settings The defined TypoScript settings for the interceptor
	 * @return array The probably modified GET/POST parameters
	 */
	public function process($gp, $settings) {
		$this->gp = $gp;

		//parse as float
		$fields = t3lib_div::trimExplode(',', $settings['parseFloatFields'], true);
		$this->parseFloats($fields);
		
		return $this->gp;
	}
	
	/**
	 * parses the given field values from strings to floats
	 * 
	 * @return void
	 * @param array $fields
	 */
	protected function parseFloats($fields){
		if (is_array($fields)) {
			foreach($fields as $field) {
				if(isset($this->gp[$field])) {
					$this->gp[$field] = $this->getFloat($this->gp[$field]);
				}
			}
		}
	}

	/**
	 * Parses the formated value as float. Needed for values like:
	 * x xxx,- / xx,xx / xx'xxx,xx / -xx.xxx,xx
	 * Caution: This pareses x.xxx.xxx to xxxxxxx (but xx.xx to xx.xx)
	 * 
	 * @return float
	 * @param string $value formated float
	 */
	protected function getFloat($value) {
     	return floatval(preg_replace('#^([-]*[0-9\.,\' ]+?)((\.|,){1}([0-9-]{1,2}))*$#e', "str_replace(array('.', ',', \"'\", ' '), '', '\\1') . '.\\4'", $value));
	} 

}
?>

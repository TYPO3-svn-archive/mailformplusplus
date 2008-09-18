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
 * Sample implementation of a Finisher Class used by MailformPlusPlus redirecting to another page.
 * This class needs a parameter "redirect_page" to be set in TS.
 *
 * Sample configuration:
 * 
 * <code>
 * finishers.4.class = F3_MailformPlusPlus_Finisher_Default
 * finishers.4.config.redirectPage = 65
 * </code>
 *
 * @author	Reinhard F�hricht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Finisher
 */
class F3_MailformPlusPlus_Finisher_Redirect extends F3_MailformPlusPlus_AbstractFinisher {
	

	
	
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
		$email_redirect = $settings['redirectPage'];
		
		//if redirect_page was page id
		if (is_numeric($email_redirect)) {
		
			// these parameters have to be added to the redirect url
			$addparams = array();
			if (t3lib_div::_GP("L")) {
				$addparams["L"] = t3lib_div::_GP("L");
			}
			
			$url = $this->cObj->getTypoLink_URL($email_redirect, '',$addparams);
			
		//else it may be a full URL
		} else {
			$url = $email_redirect;
		}
		
		//correct the URL by replacing &amp;
		if ($settings['correctRedirectUrl']) { 
			$url = str_replace('&amp;', '&', $url);
		}
		
		if($url) {
			header("Location: ".t3lib_div::locationHeaderUrl($url));
		}
		exit();
	}
	
}
?>
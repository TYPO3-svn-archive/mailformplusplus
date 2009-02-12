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
 * A view class for multistep forms
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	View
 * @link F3_MailformPlusPlus_View_Default
 * @see F3_MailformPlusPlus_View_Default
 */
class F3_MailformPlusPlus_View_Multistep extends F3_MailformPlusPlus_View_Default {

	/**
	 * Get the right settings for current step.
	 * 
	 * @return 	array The settings
	 */
	protected function parseSettings() {
		session_start();
		return $_SESSION['mailformplusplusSettings']['settings'];
	}
	
	
	
	/**
	 * Fill default markers of multistep forms
	 * 
	 * ###curStep###
	 * ###maxStep###
	 * ###lastStep###
	 * ###submit_nextStep###
	 * ###submit_prevStep###
	 * ###submit_reload###
	 * ###step_bar###
	 * 
	 * @param	integer	$currentStep current step (begins with 1)
	 * @param	integer	$lastStep last step
	 * @param	string	$buttonNameBack name attribute of the back button
	 * @param	string	$buttonNameFwd name attribute of the forward button
	 * @return 	void
	 */
	protected function fillDefaultMarkers() {
		parent::fillDefaultMarkers();
		$path = $this->pi_getPageLink($GLOBALS['TSFE']->id);
		$markers = array();
		$markers['###REL_URL###'] = $path;
		$markers['###ABS_URL###'] = t3lib_div::locationHeaderUrl('').$path;
		
		session_start();
		
		// current step
		$markers['###curStep###'] = $_SESSION['mailformplusplusSettings']['currentStep'];
		
		// maximum step/number of steps
		$markers['###maxStep###'] = $_SESSION['mailformplusplusSettings']['totalSteps'];
		
		// the last step shown
		$markers['###lastStep###'] = $_SESSION['mailformplusplusSettings']['lastStep'];
		
		$name = "step-";
		if($_SESSION['mailformplusplusSettings']['formValuesPrefix']) {
			$name = $_SESSION['mailformplusplusSettings']['formValuesPrefix']."[".$name."#step#]";
		} else {
			$name = "step-#step#";
		}
		
		// submit name for next page
		$markers['###submit_nextStep###'] = ' name="'.str_replace("#step#",($_SESSION['mailformplusplusSettings']['currentStep']+1),$name).'" ';

		// submit name for previous page
		$markers['###submit_prevStep###'] = ' name="'.str_replace("#step#",($_SESSION['mailformplusplusSettings']['currentStep']-1),$name).'" ';

		// submit name for reloading the same page/step
		$markers['###submit_reload###'] = ' name='.str_replace("#step#",($_SESSION['mailformplusplusSettings']['currentStep']),$name).'" ';
		
		// step bar
		$markers['###step_bar###'] = $this->createStepBar(
										$_SESSION['mailformplusplusSettings']['currentStep'],
										$_SESSION['mailformplusplusSettings']['totalSteps'],
										str_replace("#step#",($_SESSION['mailformplusplusSettings']['currentStep']-1),$name),
										str_replace("#step#",($_SESSION['mailformplusplusSettings']['currentStep']+1),$name)
									 );
		
		
		$this->addHiddenFields($markers);
		$this->template = $this->cObj->substituteMarkerArray($this->template, $markers);
		
	}
	
	/**
	 * Adds the values stored in $_SESSION as hidden fields in marker ###ADDITIONAL_MULTISTEP###.
	 * 
	 * Needed in conditional forms.
	 * 
	 * @param	array	&$markers The markers to put the new one into
	 * @return 	void
	 */
	protected function addHiddenFields(&$markers) {
		session_start();
		$hiddenFields = "";
		
		if(is_array($_SESSION['mailformplusplusValues'])) {
			foreach($_SESSION['mailformplusplusValues'] as $step=>$params) {
				if($step != $_SESSION['mailformplusplusSettings']['currentStep']) {
					foreach($params as $key=>$value) {
						$name = $key;
						if($_SESSION['mailformplusplusSettings']['formValuesPrefix']) {
							$name = $_SESSION['mailformplusplusSettings']['formValuesPrefix']."[".$key."]";
						}
						if(is_array($value)) {
							foreach($value as $k=>$v) {
								
								$hiddenFields .= '<input type="hidden" name="'.$name.'[]" value="'.$v.'" />';
							}
						} else {
							$hiddenFields .= '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
						}
					}
				}
			}
		}
		$markers['###ADDITIONAL_MULTISTEP###'] = $hiddenFields;
	}
	
	/**
	 * copied from dam_index
	 * 
	 * Returns HTML of a box with a step counter and "back" and "next" buttons
	 * 
	 * @param	integer	$currentStep current step (begins with 1)
	 * @param	integer	$lastStep last step
	 * @param	string	$buttonNameBack name attribute of the back button
	 * @param	string	$buttonNameFwd name attribute of the forward button
	 * @return 	string	HTML code
	 */
	protected function createStepBar($currentStep,$lastStep,$buttonNameBack ="",$buttonNameFwd ="") {
		global $LANG;
		
		$bgcolor = '#c1d5ba';
		if($this->errors) {
			$bgcolor = '#dd7777';
		}
		$nrcolor = t3lib_div::modifyHTMLcolor($bgcolor,30,30,30);

		$content='';
		$buttons='';

		for ($i = 1; $i <= $lastStep; $i++) {
			$color = $nrcolor;
			if($i == $currentStep) {
				$color = '#000';
			}
			$content.= '<span style="margin-left:5px; margin-right:5px; color:'.$color.';">'.$i.'</span>';
		}
		$content = '<span style="margin-left:50px; margin-right:25px; vertical-align:middle; font-family:Verdana,Arial,Helvetica; font-size:22px; font-weight:bold;">'.$content.'</span>';

		//if not the first step, show back button
		if($currentStep > 1) {
			$buttons .= '<input type="submit" name="'.$buttonNameBack.'" value="'.trim($GLOBALS['TSFE']->sL('LLL:'.$this->langFile.':'."back")).'" style="margin-right:10px;" />';
		}

		//if not the last step, show forward button
		if($currentStep < $lastStep) {
			$buttons .= '<input type="submit" name="'.$buttonNameFwd.'" value="'.trim($GLOBALS['TSFE']->sL('LLL:'.$this->langFile.':'."next")).'" />';
		}

		$content .= '<span id="stepsFormButtons" style="margin-left:25px;vertical-align:middle;">'.$buttons.'</span>';
		$content = '<div style="padding:4px; border-bottom:1px solid #eee; background:'.$bgcolor.';">'.$content.'</div>';

		return $content;
	}
	
	
}
?>

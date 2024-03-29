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
 * An abstract view for MailformPlusPlus
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	View
 */
abstract class F3_MailformPlusPlus_AbstractView extends tslib_pibase {

	/**
	 * The prefix id
	 *
	 * @access public
	 * @var string
	 */
	public $prefixId = 'F3_MailformPlusPlus';

	/**
	 * The extension key
	 *
	 * @access public
	 * @var string
	 */
	public $extKey = 'mailformplusplus';

	/**
	 * The cObj for link generation in FE
	 *
	 * @access public
	 * @var tslib_cObj
	 */
	public $cObj;

	/**
	 * The piVars
	 *
	 * @access public
	 * @var array
	 */
	public $piVars;

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
	 * The model of the view
	 *
	 * @access protected
	 * @var misc
	 */
	protected $model;

	/**
	 * The subparts array
	 *
	 * @access protected
	 * @var array
	 */
	protected $subparts;

	/**
	 * The Typolink configuration for this view
	 *
	 * @access protected
	 * @var array
	 */
	protected $typolinkConf;

	/**
	 * The template code
	 *
	 * @access protected
	 * @var string
	 */
	protected $template;

	/**
	 * The translation file name
	 *
	 * @access protected
	 * @var string
	 */
	protected $langFile;

	/**
	 * The get/post parameters
	 *
	 * @access protected
	 * @var array
	 */
	protected $gp;

	/**
	 * Currently not needed
	 *
	 * @access protected
	 * @var tx_xajax
	 */
	protected $xajax;

	/**
	 * The constructor for a view setting the component manager and the configuration.
	 *
	 * @param F3_GimmeFive_Component_Manager $componentManager
	 * @param F3_MailformPlusPlus_Configuration $configuration
	 * @return void
	 */
	public function __construct(F3_GimmeFive_Component_Manager $componentManager, F3_MailformPlusPlus_Configuration $configuration) {
		parent::__construct();
		$this->componentManager = $componentManager;
		$this->configuration = $configuration;
		$this->cObj = F3_MailformPlusPlus_StaticFuncs::$cObj;
		$this->typolinkConf['parameter.']['current'] = 1;
		$this->typolinkConf['additionalParams'] = $this->cObj->stdWrap($typolinkConf['additionalParams'], $typolinkConf['additionalParams.']);
		unset($this->typolinkConf['additionalParams.']);

		$this->pi_loadLL();
		$this->initializeView();
	}

	/**
	 * Sets the internal attribute "langFile"
	 *
	 * @param string $langFile The file path
	 * @return void
	 */
	public function setLangFile($langFile) {
		$this->langFile = $langFile;
	}

	/**
	 * Sets the settings
	 *
	 * @param string $settings The settings
	 * @return void
	 */
	public function setSettings($settings) {
		$this->settings = $settings;
	}

	/**
	 * Sets the key of the chosen predefined form
	 *
	 * @param string $key The key of the predefined form
	 * @return void
	 */
	public function setPredefined($key) {
		$this->predefined = $key;
	}

	/**
	 * Sets the model of the view
	 *
	 * @param misc $model
	 * @return void
	 */
	public function setModel($model) {
		$this->model = $model;
	}

	/**
	 * Returns the model of the view
	 *
	 * @return misc $model
	 */
	public function getModel() {
		return $model;
	}

	/**
	 * Sets the template of the view
	 *
	 * @param string $templateCode The whole template code of a template file
	 * @param string $templateName Name of a subpart containing the template code to work with
	 * @param boolean $forceTemplate Not needed
	 * @return void
	 */
	public function setTemplate($templateCode, $templateName, $forceTemplate = FALSE) {
		$this->subparts['template'] = $this->cObj->getSubpart($templateCode,'###TEMPLATE_' . $templateName . '###');
		$this->subparts['item'] = $this->cObj->getSubpart($this->subparts['template'], '###ITEM###');
	}
	
	/**
	 * Returns false if the view doesn't have template code.
	 *
	 * @return boolean
	 */
	public function hasTemplate() {
		return !empty($this->subparts['template']);
	}

	/**
	 * This method performs the rendering of the view
	 *
	 * @param array $gp The get/post parameters
	 * @param array $errors An array with errors occurred whilest validation
	 * @return rendered view
	 * @abstract
	 */
	abstract public function render($gp, $errors);

	/**
	 * Overwrite this method to extend the initialization of the View
	 *
	 * @return void
	 * @author Jochen Rau
	 */
	protected function initializeView() {
	}

	/**
	 * Fills markers in template
	 *
	 * @return void
	 * @author Jochen Rau
	 */
	protected function fillMarker($term, &$markerArray, &$wrappedSubpartArray) {
		$labelWrap['noTrimWrap'] = $this->configuration->offsetGet('labelWrap') ? $this->configuration->offsetGet('labelWrap') : NULL;
		foreach ($term as $property => $value) {
			// TODO Improve pre-processing of property-values
			if (is_array($value)) {
				$value = implode(',' , $value);
			}
			$propertyMarker = '###' . $this->getUpperCase($property) . '###';
			$markerArray[$propertyMarker] = $term[$property] ? $value : $this->pi_getLL('na');
			$labelMarker = '###' . $this->getUpperCase($property) . '_LABEL###';
			$markerArray[$labelMarker] = $this->cObj->stdWrap($this->pi_getLL($property), $labelWrap);
		}
	}

	/**
	 * Returns given string in uppercase
	 *
	 * @param string $camelCase The string to transform
	 * @return string Parsed string
	 * @author Jochen Rau
	 */
	protected function getUpperCase($camelCase) {
		return strtoupper(preg_replace('/\p{Lu}+(?!\p{Ll})|\p{Lu}/u', '_$0', $camelCase));
	}
}
?>

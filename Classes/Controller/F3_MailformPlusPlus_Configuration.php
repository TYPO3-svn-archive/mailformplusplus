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
 * The configuration of the MailformPlusPlus
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Controller
 */
class F3_MailformPlusPlus_Configuration implements ArrayAccess {

	/**
	 * The package key
	 *
	 * @var string
	 */
	const PACKAGE_KEY = 'MailformPlusPlus';

	/**
	 * The TS setup
	 *
	 * @access protected
	 * @var array
	 */
	protected $setup;

	/**
	 * The constructor reading the TS setup into the according attribute
	 *
	 * @author Reinhard Führicht <rf@typoheads.at>
	 * @return void
	 */
	public function __construct() {
		$this->setup = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->getPrefixedPackageKey() . '.'];
	}

	/**
	 * Merges the values of $setup with plugin.[xxx].settings
	 *
	 * @author Reinhard Führicht <rf@typoheads.at>
	 * @param array $setup
	 * @return void
	 */
	public function merge($setup) {
		if (isset($setup) && is_array($setup)) {
			$settings = $this->setup['settings.'];
			$settings = t3lib_div::array_merge_recursive_overrule($settings, $setup);
			$this->setup['settings.'] = $settings;
		}
	}

	public function offsetGet($offset) {
		return $this->setup['settings.'][$offset];
	}

	public function offsetSet($offset, $value) {
		$this->setup['settings.'][$offset] = $value;
	}

	public function offsetExists($offset) {
		if (isset($this->setup['settings.'][$offset])) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function offsetUnset($offset) {
		$this->setup['settings.'][$offset] = NULL;
	}

	/**
	 * Returns the TS settings for mailformplusplus.
	 *
	 * @author Reinhard Führicht <rf@typoheads.at>
	 * @return array The settings
	 */
	public function getSettings() {
		return $this->setup['settings.'];
	}

	/**
	 * Returns the sources config for mailformplusplus
	 *
	 * @author Reinhard Führicht <rf@typoheads.at>
	 * @return array The config
	 */
	public function getSourcesConfiguration() {
		return $this->setup['sources.'];
	}

	/**
	 * Returns the package key
	 *
	 * @author Reinhard Führicht <rf@typoheads.at>
	 * @return string
	 */
	public function getPackageKey() {
		return self::PACKAGE_KEY;
	}

	/**
	 * Returns the package key in lower case
	 *
	 * @author Reinhard Führicht <rf@typoheads.at>
	 * @return string
	 */
	public function getPackageKeyLowercase() {
		return strtolower(self::PACKAGE_KEY);
	}

	/**
	 * Returns the prefixed package key
	 *
	 * @author Reinhard Führicht <rf@typoheads.at>
	 * @return string
	 */
	public function getPrefixedPackageKey() {
		return F3_GimmeFive_Component_Manager::PACKAGE_PREFIX . '_' . self::PACKAGE_KEY;
	}

	/**
	 * Returns the prefixed package key in lower case
	 *
	 * @author Reinhard Führicht <rf@typoheads.at>
	 * @return string
	 */
	public function getPrefixedPackageKeyLowercase() {
		return strtolower(F3_GimmeFive_Component_Manager::PACKAGE_PREFIX . '_' . self::PACKAGE_KEY);
	}
}
?>

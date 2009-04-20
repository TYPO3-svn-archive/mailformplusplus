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
 * A class providing static helper functions for MailformPlusPlus
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Utils
 */
class F3_MailformPlusPlus_StaticFuncs {

	/**
	 * The cObj
	 *
	 * @access protected
	 * @var tslib_cObj
	 */
	static public $cObj;
	
	static public $predefined;

	/**
	 * Returns the absolute path to the document root
	 *
	 * @return string
	 */
	static public function getDocumentRoot() {
		return t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT');
	}

	/**
	 * Returns the absolute path to the TYPO3 root
	 *
	 * @return string
	 */
	static public function getTYPO3Root() {
		$path = t3lib_div::getIndpEnv('SCRIPT_FILENAME');
		$path = str_replace("/index.php","",$path);
		return $path;
	}

	
	/**
	 * Adds needed prefix to class name if not set in TS
	 *
	 * @return string
	 */
	static public function prepareClassName($className) {
		if(!preg_match('/^F3_/',$className)) {
			$className = 'F3_MailformPlusPlus_'.$className;
		}
		return $className;
	}

	

	/**
	 * copied from class tslib_content
	 *
	 * Substitutes markers in given template string with data of marker array
	 *
	 * @param 	string
	 * @param	array
	 * @return	string
	 */
	static public function substituteMarkerArray($content,$markContentArray) {
		if (is_array($markContentArray))	{
			reset($markContentArray);
			while(list($marker,$markContent) = each($markContentArray))	{
				$content = str_replace($marker,$markContent,$content);
			}
		}
		return $content;
	}

	/**
	 * copied from class t3lib_parsehtml
	 *
	 * Returns the first subpart encapsulated in the marker, $marker (possibly present in $content as a HTML comment)
	 *
	 * @param	string		Content with subpart wrapped in fx. "###CONTENT_PART###" inside.
	 * @param	string		Marker string, eg. "###CONTENT_PART###"
	 * @return	string
	 */
	static public function getSubpart($content,$marker)	{
		$start = strpos($content, $marker);
		if ($start===false)	{ return ''; }
		$start += strlen($marker);
		$stop = strpos($content, $marker, $start);
		// Q: What shall get returned if no stop marker is given /*everything till the end*/ or nothing
		if ($stop===false)	{ return /*substr($content, $start)*/ ''; }
		$content = substr($content, $start, $stop-$start);
		$matches = array();
		if (preg_match('/^([^\<]*\-\-\>)(.*)(\<\!\-\-[^\>]*)$/s', $content, $matches)===1)	{
			return $matches[2];
		}
		$matches = array();
		if (preg_match('/(.*)(\<\!\-\-[^\>]*)$/s', $content, $matches)===1)	{
			return $matches[1];
		}
		$matches = array();
		if (preg_match('/^([^\<]*\-\-\>)(.*)$/s', $content, $matches)===1)	{
			return $matches[2];
		}
		return $content;
	}

	/**
	 * Return value from somewhere inside a FlexForm structure
	 *
	 * @param	array		FlexForm data
	 * @param	string		Field name to extract. Can be given like "test/el/2/test/el/field_templateObject" where each part will dig a level deeper in the FlexForm data.
	 * @param	string		Sheet pointer, eg. "sDEF"
	 * @param	string		Language pointer, eg. "lDEF"
	 * @param	string		Value pointer, eg. "vDEF"
	 * @return	string		The content.
	 */
	static public function pi_getFFvalue($T3FlexForm_array,$fieldName,$sheet='sDEF',$lang='lDEF',$value='vDEF')	{
		$sheetArray = is_array($T3FlexForm_array) ? $T3FlexForm_array['data'][$sheet][$lang] : '';
		if (is_array($sheetArray))	{
			return F3_MailformPlusPlus_StaticFuncs::pi_getFFvalueFromSheetArray($sheetArray,explode('/',$fieldName),$value);
		}
	}

	/**
	 * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
	 *
	 * @param	array		Multidimensiona array, typically FlexForm contents
	 * @param	array		Array where each value points to a key in the FlexForms content - the input array will have the value returned pointed to by these keys. All integer keys will not take their integer counterparts, but rather traverse the current position in the array an return element number X (whether this is right behavior is not settled yet...)
	 * @param	string		Value for outermost key, typ. "vDEF" depending on language.
	 * @return	mixed		The value, typ. string.
	 * @access private
	 * @see pi_getFFvalue()
	 */
	static public function pi_getFFvalueFromSheetArray($sheetArray,$fieldNameArr,$value)	{

		$tempArr=$sheetArray;
		foreach($fieldNameArr as $k => $v)	{
			if (t3lib_div::testInt($v))	{
				if (is_array($tempArr))	{
					$c=0;
					foreach($tempArr as $values)	{
						if ($c==$v)	{
							#debug($values);
							$tempArr=$values;
							break;
						}
						$c++;
					}
				}
			} else {
				$tempArr = $tempArr[$v];
			}
		}
		return $tempArr[$value];
	}

	/**
	 * This function formats a date
	 *
	 * @param long $date The timestamp to format
	 * @param boolean $end Is end date or start date
	 * @return string formatted date
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	static public function dateToTimestamp($date,$end = false) {
		$dateArr = explode(".",$date);
		if($end) {
			return mktime(23,59,59,$dateArr[1],$dateArr[0],$dateArr[2]);
		}
		return mktime(0,0,0,$dateArr[1],$dateArr[0],$dateArr[2]);
	}

	/**
	 * Returns the http path to the site
	 *
	 * @return string
	 */
	static public function getHostname() {
		return t3lib_div::getIndpEnv('TYPO3_SITE_URL');
	}

	/**
	 * Ensures that a given path has a / as first and last character.
	 * This method only appends a / to the end of the path, if no filename is in path.
	 *
	 * Examples:
	 *
	 * uploads/temp				--> /uploads/temp/
	 * uploads/temp/file.ext	--> /uploads/temp/file.ext
	 *
	 * @param string $path
	 * @return string Sanitized path
	 */
	static public function sanitizePath($path) {
		if(substr($path,0,1) != "/") {
			$path = "/".$path;
		}
		if(substr($path,strlen($path)-1) != "/" && !strstr($path,".")) {
			$path = $path."/";
		}
		return $path;
	}

	/**
	 * Converts an absolute path into a relative path from TYPO3 root directory.
	 *
	 * Example:
	 *
	 * IN : C:/xampp/htdocs/typo3/fileadmin/file.html
	 * OUT : fileadmin/file.html
	 *
	 * @param string $template The template code
	 * @param string $langFile The path to the language file
	 * @return array The filled language markers
	 * @static
	 */
	static public function convertToRelativePath($absPath) {

		//C:/xampp/htdocs/typo3/index.php
		$scriptPath =  t3lib_div::getIndpEnv('SCRIPT_FILENAME');

		//C:/xampp/htdocs/typo3/
		$rootPath = str_replace('index.php','',$scriptPath);

		return str_replace($rootPath,'',$absPath);

	}

	/**
	 * Finds and fills language markers in given template code.
	 *
	 * @param string $template The template code
	 * @param string $langFile The path to the language file
	 * @return array The filled language markers
	 * @static
	 */
	static public function getFilledLangMarkers(&$template,$langFile) {
		$GLOBALS['TSFE']->readLLfile($langFile);
		$langMarkers = array();
		if ($langFile != '') {
			$aLLMarkerList = array();
			preg_match_all('/###LLL:.+?###/Ssm', $template, $aLLMarkerList);

			foreach($aLLMarkerList[0] as $LLMarker){
				$llKey =  substr($LLMarker,7,strlen($LLMarker)-10);
				$marker = $llKey;
				$langMarkers['###LLL:'.$marker.'###'] = trim($GLOBALS['TSFE']->sL('LLL:' . $langFile. ':' . $llKey));
			}
		}
		return $langMarkers;
	}

	/**
	 * Finds and fills value markers using given GET/POST parameters.
	 *
	 * @param array &$gp Reference to the GET/POST parameters
	 * @return array The filled value markers
	 * @static
	 */
	static public function getFilledValueMarkers(&$gp) {
		if (isset($gp) && is_array($gp)) {
			foreach($gp as $k=>$v) {
				if (!ereg('EMAIL_', $k)) {
					if (is_array($v)) {
						$v = implode(',', $v);
					}
					$v = trim($v);
					if ($v != "") {
						if(get_magic_quotes_gpc()) {
							$markers['###value_'.$k.'###'] = stripslashes(self::reverse_htmlspecialchars($v));
						} else {
							$markers['###value_'.$k.'###'] = self::reverse_htmlspecialchars($v);
						}
					} else {
						$markers['###value_'.$k.'###'] = '';
					}
				} //if end
			} // foreach end
		} // if end
		return $markers;
	}

	/**
	 * I have no idea
	 *
	 * @author	Peter Luser <pl@typoheads.at>
	 * @param string $mixed The value to process
	 * @return string The processed value
	 * @static
	 */
	static public function reverse_htmlspecialchars($mixed) {
		$htmltable = get_html_translation_table(HTML_ENTITIES);
		foreach($htmltable as $key => $value) {
			$mixed = ereg_replace(addslashes($value),$key,$mixed);
		}
		return $mixed;
	}

	/**
	 * Method to print a debug message to screen
	 *
	 * @param string $message The message to print
	 * @param boolean $extended Print a header style message or default output
	 * @return void
	 * @static
	 */
	static public function debugMessage($key) {
		session_start();
		if($_SESSION['mailformplusplusSettings']['debugMode']) {
			$message = F3_MailformPlusPlus_Messages::getDebugMessage($key);
			if(strlen($message) == 0) {
				print $key.'<br />';
			} else {
				if(func_num_args() > 1) {
					$args = func_get_args();
					array_shift($args);
					$message = vsprintf($message,$args);
				}
				print $message.'<br />';
			}
		}
	}

	/**
	 * Manages the exception throwing
	 *
	 * @param string $key Key in language file
	 * @return void
	 * @static
	 */
	static public function throwException($key) {
		$message = F3_MailformPlusPlus_Messages::getExceptionMessage($key);
		if(strlen($message) == 0) {
			throw new Exception($key);
		} else {
			if(func_num_args() > 1) {
				$args = func_get_args();
				array_shift($args);
				$message = vsprintf($message,$args);
			}
			throw new Exception($message);
		}

	}

	/**
	 * Method to print the contents of an array
	 *
	 * @param array $arr The array to print
	 * @return void
	 * @static
	 */
	static public function debugArray($arr) {
		if(!is_array($arr)) {
			return;
		}
		$fields = array();
		foreach($arr as $key=>$value) {
			if(is_array($value)) {
				$value = implode(",",$value);
			}
			array_push($fields,$key."=".$value);
		}
		print implode("<br />",$fields);
	}



	/**
	 * Removes unfilled markers from given template code.
	 *
	 * @param string $content The template code
	 * @return string The template code without markers
	 * @static
	 */
	static public function removeUnfilledMarkers($content) {
		return preg_replace('/###.*?###/', '', $content);
	}

	/**
	 * Substitutes EXT: with extension path in a file path
	 *
	 * @param string The path
	 * @return string The resolved path
	 * @static
	 */
	static public function resolvePath($path) {
		$path = explode("/",$path);
		if(strpos($path[0],"EXT") > -1) {
			$parts = explode(":",$path[0]);
			$path[0] = t3lib_extMgm::extPath($parts[1]);
		}
		$path = implode("/",$path);
		$path = str_replace("//","/",$path);
		return $path;
	}

	/**
	 * Substitutes EXT: with extension path in a file path and returns the relative path.
	 *
	 * @param string The path
	 * @return string The resolved path
	 * @static
	 */
	static public function resolveRelPath($path) {
		$path = explode("/",$path);
		if(strpos($path[0],"EXT") > -1) {
			$parts = explode(":",$path[0]);
			$path[0] = t3lib_extMgm::extRelPath($parts[1]);
		}
		$path = implode("/",$path);
		$path = str_replace("//","/",$path);
		return $path;
	}

	/**
	 * Substitutes EXT: with extension path in a file path and returns the relative path from site root.
	 *
	 * @param string The path
	 * @return string The resolved path
	 * @static
	 */
	static public function resolveRelPathFromSiteRoot($path) {
		$path = explode("/",$path);
		if(strpos($path[0],"EXT") > -1) {
			$parts = explode(":",$path[0]);
			$path[0] = t3lib_extMgm::extRelPath($parts[1]);
		}
		$path = implode("/",$path);
		$path = str_replace("//","/",$path);
		$path = str_replace("../","",$path);
		return $path;
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
	 * @static
	 * @author	Reinhard Führicht <rf@typoheads.at>
	 */
	static public function getTempUploadFolder() {

		//set default upload folder
		$uploadFolder = '/uploads/mailformplusplus/tmp/';

		//if temp upload folder set in TypoScript, take that setting
		if($_SESSION['mailformplusplusSettings']['settings']['files.']['uploadFolder']) {
			$uploadFolder = $_SESSION['mailformplusplusSettings']['settings']['files.']['uploadFolder'];
			$uploadFolder = F3_MailformPlusPlus_StaticFuncs::sanitizePath($uploadFolder);
		}

		//if the set directory doesn't exist, print a message
		#if(!is_dir(F3_MailformPlusPlus_StaticFuncs::getDocumentRoot().$uploadFolder)) {
		#		F3_MailformPlusPlus_StaticFuncs::debugMessage("Folder: '".F3_MailformPlusPlus_StaticFuncs::getDocumentRoot().$uploadFolder."' doesn't exist!");
		#	}
		if(!is_dir(F3_MailformPlusPlus_StaticFuncs::getTYPO3Root().$uploadFolder)) {
			F3_MailformPlusPlus_StaticFuncs::debugMessage('folder_doesnt_exist',F3_MailformPlusPlus_StaticFuncs::getTYPO3Root().$uploadFolder);
		}
		return $uploadFolder;
	}
	
	/**
	 * Parses given value and unit and creates a timestamp now-timebase.
	 *
	 * @param int Timebase value
	 * @param string Timebase unit (seconds|minutes|hours|days)
	 * @static
	 * @return long The timestamp
	 */
	static public function getTimestamp($value,$unit) {
		$now = time();
		$convertedValue = 0;
		switch($unit) {
			case "days":
				$convertedValue = $value * 24 * 60 * 60;
				break;
			case "hours":
				$convertedValue = $value * 60 * 60;
				break;
			case "minutes":
				$convertedValue = $value * 60;
				break;
		}
		return $now-$convertedValue;
	}
}

?>

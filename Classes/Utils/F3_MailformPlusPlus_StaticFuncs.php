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
	public static $cObj;

	/**
	 * Returns the absolute path to the document root
	 *
	 * @return string
	 */
	public static function getDocumentRoot() {
		return t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT');
	}

	/**
	 * Returns the absolute path to the TYPO3 root
	 *
	 * @return string
	 */
	public static function getTYPO3Root() {
		$path = t3lib_div::getIndpEnv('SCRIPT_FILENAME');
		$path = str_replace("/index.php","",$path);
		return $path;
	}

	/**
	 * Helper method used by substituteIssetSubparts()
	 *
	 * @see F3_MailformPlusPlus_StaticFuncs::substituteIssetSubparts()
	 * @author  Stephan Bauer <stephan_bauer(at)gmx.de>
	 * @return boolean
	 */
	protected static function markersCountAsSet($markers, $conditionValue) {

		// Find first || or && or !
		$pattern = '/(_*([A-Za-z0-9]+)_*(\|\||&&)_*([^_]+)_*)|(_*(!)_*([A-Za-z0-9]+))/';

		session_start();
		// recurse if there are more
		if( preg_match($pattern, $conditionValue, $matches) ){
			$isset = isset($markers['###' . $matches[2] . '###']);
			if($matches[3] == '||' && $isset) {
				$return = true;
			} elseif($matches[3] == '||' && !$isset) {
				$return = F3_MailformPlusPlus_StaticFuncs.php:: markersCountAsSet($markers, $matches[4]);
			} elseif($matches[3] == '&&' && $isset) {
				$return = F3_MailformPlusPlus_StaticFuncs.php:: markersCountAsSet($markers, $matches[4]);
			} elseif($matches[3] == '&&' && !$isset) {
				$return = false;
			} elseif($matches[6] == '!' && !$isset) {
				return !(isset($markers['###' . $matches[7] . '###']) && $markers['###' . $matches[7] . '###'] != '');
			} elseif($_SESSION['mailformplusplusSettings']['debugMode'] == 1) {
				F3_MailformPlusPlus_StaticFuncs::debugMessage('invalid_isset',$matches[2]);
			}
		} else {

			// remove underscores
			$pattern = '/_*/';
			$str = preg_replace($pattern, $str, '');

			// end of recursion
			$return = isset($markers['###' . $conditionValue . '###']) && ($markers['###' . $conditionValue . '###'] != '');
		}
		return $return;
	}



	/**
	 * Use or remove subparts with ISSET_[fieldname] patterns (thx to Stephan Bauer <stephan_bauer(at)gmx.de>)
	 *
	 * @param	string		$subpart: html content with markers
	 * @param	array		$markers: array with markername->substitution value
	 * @author  Stephan Bauer <stephan_bauer(at)gmx.de>
	 * @return	string		substituted HTML content
	 */
	public static function substituteIssetSubparts($subpart,$markers = array()){
		$flags = array();
		$nowrite = false;
		$out = array();
		foreach(split(chr(10), $subpart) as $line){

			// works only on it's own line
			$pattern = '/###ISSET_+([^#]*)_*###/';

			// set for odd ISSET_xyz, else reset
			if(preg_match($pattern, $line, $matches)) {
				if(!$flags[$matches[1]]) { // set
					$flags[$matches[1]] = true;

					// set nowrite flag if required until the next ISSET_xyz
					// (only if not already set by envelop)
					if((!F3_MailformPlusPlus::markersCountAsSet($markers, $matches[1])) && (!$nowrite)) {
						$nowrite = $matches[1];
					}
				} else { // close it
					$flags[$matches[1]] = false;
					if($nowrite == $matches[1]) {
						$nowrite = 0;
					}
				}
			} else { // It is no ISSET_line. Write if permission is given.
				if(!$nowrite) {
					$out[] = $line;
				}
			}
		}
		$out = implode(chr(10),$out);

		return $markers;
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
	public static function substituteMarkerArray($content,$markContentArray) {
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
	public static function getSubpart($content,$marker)	{
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
	public static function pi_getFFvalue($T3FlexForm_array,$fieldName,$sheet='sDEF',$lang='lDEF',$value='vDEF')	{
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
	private static function pi_getFFvalueFromSheetArray($sheetArray,$fieldNameArr,$value)	{

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
	public static function dateToTimestamp($date,$end = false) {
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
	public static function getHostname() {
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
	public static function sanitizePath($path) {
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
	public static function convertToRelativePath($absPath) {

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
	public static function getFilledLangMarkers(&$template,$langFile) {
		$GLOBALS['TSFE']->readLLfile($langFile);
		$langMarkers = array();
		if ($langFile != '') {
			$aLLMarkerList = array();
			preg_match_all('/###LLL:.+?###/Ssm', $template, $aLLMarkerList);

			foreach($aLLMarkerList[0] as $LLMarker){
				$llKey =  strtolower(substr($LLMarker,7,strlen($LLMarker)-10));
				$marker = $llKey;
				$langMarkers['###LLL:'.$marker.'###'] = trim($GLOBALS['TSFE']->sL('LLL:' . $langFile. ':' . $llKey));
				$langMarkers['###LLL:'.strtoupper($marker).'###'] = $langMarkers['###LLL:'.$marker.'###'];
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
	public static function getFilledValueMarkers(&$gp) {
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
	public static function reverse_htmlspecialchars($mixed) {
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
	public static function debugMessage($key) {
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
	public static function throwException($key) {
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
	public static function debugArray($arr) {
		if(!is_array($arr)) {
			return;
		}
		foreach($arr as $key=>$value) {
			if(is_array($value)) {
				$value = implode(",",$value);
			}
			$fields[] = $key."=".$value;
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
	public static function removeUnfilledMarkers($content) {
		return preg_replace('/###.*?###/', '', $content);
	}

	/**
	 * Substitutes EXT: with extension path in a file path
	 *
	 * @param string The path
	 * @return string The resolved path
	 * @static
	 */
	public static function resolvePath($path) {
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
	public static function resolveRelPath($path) {
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
	public static function resolveRelPathFromSiteRoot($path) {
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
}

?>

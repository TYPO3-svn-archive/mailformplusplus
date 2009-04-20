<?php
/**
 * Config for Backend Module of MailformPlusPlus
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Controller
 */

// DO NOT REMOVE OR CHANGE THESE 3 LINES:
define('TYPO3_MOD_PATH', '../typo3conf/ext/mailformplusplus/Classes/Controller/Module/');
$BACK_PATH = '../../../../../../typo3/';
$MCONF['name']  ='web_txmailformplusplusmoduleM1';


$MCONF['access'] = 'user,group';
$MCONF['script'] = 'index.php';

$MLANG['default']['tabs_images']['tab'] = 'moduleicon.gif';
$MLANG['default']['ll_ref'] = 'LLL:EXT:mailformplusplus/Resources/Language/locallang_mod.xml';
?>

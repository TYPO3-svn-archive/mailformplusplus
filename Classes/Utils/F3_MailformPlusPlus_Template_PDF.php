<?php

/* $Id$ */

if (TYPO3_MODE=="BE")   {
	require_once("../../../Resources/PHP/fpdf/fpdf.php");
} else {
	require_once("typo3conf/ext/mailformplusplus/Resources/PHP/tcpdf/tcpdf.php");
}

/**
 * A PDF Template class for MailformPlusPlus generated PDF files
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Utils
 */
class F3_MailformPlusPlus_Template_PDF extends TCPDF {

	protected $sysLangFile;
	
	public function __construct() {
		parent::__construct();
		$this->sysLangFile = 'EXT:mailformplusplus/Resources/Language/locallang.xml';
	}

	//Page header
	public function Header() {
	  
		//Arial bold 15
		$this->SetFont('Freesans','B',15);
	  
		
		//Title
		$this->Cell(0,10,trim($GLOBALS['TSFE']->sL('LLL:'.$this->sysLangFile.':submission_details')),'B',0,'C');
	  
		//Line break
		$this->Ln(20);
	}

	//Page footer
	public function Footer() {
	  
		//Position at 1.5 cm from bottom
		$this->SetY(-15);
	  
		//Arial italic 8
		$this->SetFont('Freesans','I',8);
	    
		$text = trim($GLOBALS['TSFE']->sL('LLL:'.$this->sysLangFile.':footer_text'));
		$text = sprintf($text,date("d.m.Y H:i:s",time()));
		$this->Cell(0,10,$text,'T',0,'C');
		$this->Cell(0,10,trim($GLOBALS['TSFE']->sL('LLL:'.$this->sysLangFile.':page')).' '.$this->PageNo().'/{nb}','T',0,'R');
	}

}
?>

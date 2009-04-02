<?php

/* $Id$ */

if (TYPO3_MODE=="BE")   {
	require_once("../../../Resources/PHP/fpdf/fpdf.php");
} else {
	require_once("typo3conf/ext/mailformplusplus/Resources/PHP/fpdf/fpdf.php");
}

/**
 * A PDF Template class for MailformPlusPlus generated PDF files
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Utils
 */
class F3_MailformPlusPlus_Template_PDF extends FPDF {

	protected $sysLangFile;
	
	public function __construct() {
		parent::__construct();
		$this->sysLangFile = 'EXT:mailformplusplus/Resources/Language/locallang.xml';
		
	}

	//Page header
	public function Header() {
		global $LANG;
		$LANG->includeLLFile($this->sysLangFile);
	  
		//Arial bold 15
		$this->SetFont('Arial','B',15);
	  
		
		//Title
		$this->Cell(0,10,trim($LANG->getLL('submission_details')),'B',0,'C');
	  
		//Line break
		$this->Ln(20);
	}

	//Page footer
	public function Footer() {
		global $LANG;
		$LANG->includeLLFile($this->sysLangFile);
	  
		//Position at 1.5 cm from bottom
		$this->SetY(-15);
	  
		//Arial italic 8
		$this->SetFont('Arial','I',8);
	    
		$text = trim($LANG->getLL('footer_text'));
		$text = sprintf($text,date("d.m.Y H:i:s",time()));
		$this->Cell(0,10,$text,'T',0,'C');
		$this->Cell(0,10,trim($LANG->getLL('page')).' '.$this->PageNo().'/{nb}','T',0,'R');
	}

}
?>

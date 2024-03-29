<?php

/* $Id: F3_MailformPlusPlus_Template_PDF.php 17657 2009-03-10 11:17:52Z reinhardfuehricht $ */

if (TYPO3_MODE=="BE")   {
	require_once("../../../Resources/PHP/tcpdf/tcpdf.php");
} else {
	require_once("typo3conf/ext/mailformplusplus/Resources/PHP/tcpdf/tcpdf.php");
}

/**
 * A PDF Template class for MailformPlusPlus generated PDF files for usage with Generator_TCPDF.
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Utils
 */
class F3_MailformPlusPlus_Template_TCPDF extends TCPDF {

	/**
	 * Path to language file
	 *
	 * @access protected
	 * @var string
	 */
	protected $sysLangFile;
	
	public function __construct() {
		parent::__construct();
		$this->sysLangFile = 'EXT:mailformplusplus/Resources/Language/locallang.xml';
	}
	
	/**
	 * Generates the header of the page
	 * 
	 * @return void
	 */
	public function Header() {
		
	}

	/**
	 * Generates the footer
	 * 
	 * @return void
	 */
	public function Footer() {
	  
		//Position at 1.5 cm from bottom
		$this->SetY(-15);
	  
		//Arial italic 8
		$this->SetFont('Freesans', 'I', 8);
	    
		$text = $this->getLL('footer_text');
		$text = sprintf($text,date('d.m.Y H:i:s', time()));
		$this->Cell(0, 10, $text, 'T', 0, 'C');
		$pageNumbers = $this->getLL('page') . ' ' . $this->PageNo() . '/' . $this->numpages;
		$this->Cell(0, 10, $pageNumbers, 'T', 0, 'R');
	}
	
	private function getLL($key) {
		global $LANG;
		if (TYPO3_MODE=="BE")   {
			$LANG->includeLLFile($this->sysLangFile);
			$text = trim($LANG->getLL($key));
		} else {
			$text = trim($GLOBALS['TSFE']->sL('LLL:' . $this->sysLangFile . ':' . $key));
		}
		return $text;
	}

}
?>

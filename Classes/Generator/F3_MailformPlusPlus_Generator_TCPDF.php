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
 * $Id: F3_MailformPlusPlus_Generator_PDF.php 18491 2009-03-30 09:23:57Z reinhardfuehricht $
 *                                                                        */

/**
 * Class to generate PDF files in Backend and Frontend
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	F3_MailformPlusPlus
 * @subpackage	Generator
 * @uses F3_MailformPlusPlus_Template_PDF
 */
class F3_MailformPlusPlus_Generator_TCPDF {

	/**
	 * The internal PDF object
	 *
	 * @access protected
	 * @var F3_MailformPlusPlus_Template_PDF
	 */
	protected $pdf;

	/**
	 * The GimmeFive component manager
	 *
	 * @access protected
	 * @var F3_GimmeFive_Component_Manager
	 */
	protected $componentManager;

	/**
	 * Default Constructor
	 *
	 * @param F3_GimmeFive_Component_Manager $componentManager The component manager of GimmeFive
	 * @return void
	 */
	public function __construct(F3_GimmeFive_Component_Manager $componentManager) {
		$this->componentManager = $componentManager;

	}

	/**
	 * Function to generate a PDF file from submitted form values. This function is called by F3_MailformPlusPlus_Controller_Backend
	 *
	 * @param array $records The records to export to PDF
	 * @param array $exportFields A list of fields to export. If not set all fields are exported
	 * @see F3_MailformPlusPlus_Controller_Backend::generatePDF()
	 * @return void
	 */
	function generateModulePDF($records, $exportFields = array()) {
		
		//init pdf object
		$this->pdf = $this->componentManager->getComponent('F3_MailformPlusPlus_Template_TCPDF');
		$addedOneRecord = false;

		//for all records,
		//check if the record is valid.
		//a valid record has at least one param to export
		//if no valid record is found render an error message in pdf file
		foreach($records as $data) {
			$valid = false;
			if(isset($data['params']) && is_array($data['params'])) {
				foreach($data['params'] as $key => $value) {
					if(count($exportFields) == 0 || in_array($key, $exportFields)) {
						$valid = true;
					}
				}
			}
			if($valid) {
				$addedOneRecord = true;
				$this->pdf->AliasNbPages();
				$this->pdf->AddPage();
				$this->pdf->SetFont('Freesans', '', 12);
				$standardWidth = 100;
				$nameWidth = 70;
				$valueWidth = 70;
				$feedWidth = 30;
				if(count($exportFields) == 0 || in_array('pid', $exportFields)) {
					$this->pdf->Cell($standardWidth, '15', 'Page-ID:', 0, 0);
					$this->pdf->Cell($standardWidth, '15', $data['pid'], 0, 1);
				}
				if(count($exportFields) == 0 || in_array('submission_date', $exportFields)) {
					$this->pdf->Cell($standardWidth, '15', 'Submission date:', 0, 0);
					$this->pdf->Cell($standardWidth, '15', date('d.m.Y H:i:s', $data['crdate']), 0, 1);
				}
				if(count($exportFields) == 0 || in_array('ip', $exportFields)) {
					$this->pdf->Cell($standardWidth, '15', 'IP address:', 0, 0);
					$this->pdf->Cell($standardWidth, '15', $data['ip'], 0, 1);
				}
					
				$this->pdf->Cell($standardWidth, '15', 'Submitted values:', 0, 1);
				$this->pdf->SetLineWidth(.3);
				$this->pdf->Cell($feedWidth);
				$this->pdf->SetFillColor(255, 255, 255);
				$this->pdf->Cell($nameWidth, '6', 'Name', 'B', 0, 'C', true);
				$this->pdf->Cell($valueWidth, '6', 'Value', 'B', 0, 'C', true);
				$this->pdf->Ln();
				$this->pdf->SetFillColor(200, 200, 200);
				$fill = false;
				
				foreach($exportFields as $key => $field) {
					
					if(	strcmp($field, 'pid') == FALSE ||
						strcmp($field, 'submission_date') == FALSE ||
						strcmp($field, 'ip') == FALSE) {
						
							
						unset($exportFields[$key]);
					}
				}
				if(count($exportFields) == 0) {
					$exportFields = array_keys($data['params']);
				}
				foreach($exportFields as $idx => $key) {
					if(isset($data['params'][$key])) {
						$value = $data['params'][$key];
						if(is_array($value)) {
							$this->pdf->Cell($feedWidth);
							$this->pdf->Cell($nameWidth, '6', $key, 0, 0, 'L', $fill);
							$this->pdf->Cell($valueWidth, '6', array_shift($value), 0, 0, 'L', $fill);
							$this->pdf->Ln();
							foreach($value as $v) {
								$this->pdf->Cell($feedWidth);
								$this->pdf->Cell($nameWidth, '6', '', 0, 0, 'L', $fill);
								$this->pdf->Cell($valueWidth, '6', $v, 0, 0, 'L', $fill);
								$this->pdf->Ln();
							}
							$fill = !$fill;
						} else {
							$this->pdf->Cell($feedWidth);
							$this->pdf->Cell($nameWidth, '6', $key, 0, 0, 'L', $fill);
							$this->pdf->Cell($valueWidth, '6', $value, 0, 0, 'L', $fill);
							$this->pdf->Ln();
							$fill = !$fill;
						}
				}
						
				}
			}
		}

		//if no valid record was found, render an error message
		if(!$addedOneRecord) {
			$this->pdf->AliasNbPages();
			$this->pdf->AddPage();
			$this->pdf->SetFont('Freesans', '', 12);
			$this->pdf->Cell(300, 100, 'No valid records found! Try to select more fields to export!', 0, 0, 'L');
		}
		
		$this->pdf->Output('mailformplusplus.pdf','D');
		

	}

	/**
	 * Function to generate a PDF file from submitted form values. This function is called by F3_MailformPlusPlus_Finisher_Confirmation and F3_MailformPlusPlus_Finisher_Mail
	 *
	 * @param array $gp The values to export
	 * @param string $langFile The translation file configured in TypoScript of MailformPlusPlus
	 * @param array $exportFields A list of fields to export. If not set all fields are exported
	 * @param string $file A filename to save the PDF in. If not set, the PDF will be rendered directly to screen
	 * @param boolean $returns If set, the PDF will be rendered into the given file, if not set, the PDF will be rendered into the file and afterwards directly to screen
	 * @see F3_MailformPlusPlus_Finisher_Confirmation::process()
	 * @see F3_MailformPlusPlus_Finisher_Mail::parseMailSettings()
	 * @return void|filename
	 */
	function generateFrontendPDF($gp, $langFile, $exportFields = array(), $file = '', $returns = false) {
		$this->pdf = $this->componentManager->getComponent('F3_MailformPlusPlus_Template_TCPDF');
		$this->pdf->AddPage();
		$this->pdf->SetFont('Freesans', '', 12);
		$view = $this->componentManager->getComponent('F3_MailformPlusPlus_View_PDF');
		$view->setTemplate($this->templateCode, 'PDF');
		$view->setPredefined(F3_MailformPlusPlus_StaticFuncs::$predefined);
		
		$content = $view->render($gp, array());
		
		$pdf = $this->componentManager->getComponent('F3_MailformPlusPlus_Template_TCPDF');
		
		$pdf->writeHTML(stripslashes($content), true, 0);

		if(strlen($file) > 0) {
			$pdf->Output($file, 'F');
			$pdf->Close();
			$downloadpath = $file;
			if($returns) {
				return $downloadpath;
			}
			
			header('Location: ' . $downloadpath);
		} else {
			$pdf->Output('mailformplusplus.pdf','D');
		}

	}
	
	public function setTemplateCode($templateCode) {
		$this->templateCode = $templateCode;
	}
}
?>

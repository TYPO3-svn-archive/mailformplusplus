<?php
/**
 * Font Courier for FPDF
 * 
 * @package	F3_MailformPlusPlus
 * @subpackage	Font
 */
for($i=0;$i<=255;$i++)
	$fpdf_charwidths['courier'][chr($i)]=600;
$fpdf_charwidths['courierB']=$fpdf_charwidths['courier'];
$fpdf_charwidths['courierI']=$fpdf_charwidths['courier'];
$fpdf_charwidths['courierBI']=$fpdf_charwidths['courier'];
?>

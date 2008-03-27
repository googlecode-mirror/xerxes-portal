<?php 

// inherits $objXML, $objRequest, $objPage

// type

$strType = $objRequest->getProperty("type");

// get file name supplied by user, or take default

$strFileName = $objRequest->getProperty("file_name");
if ( $strFileName == "" ) $strFileName = "records-" . time();

// strip out bad characters

$strFileName = str_replace("/", "-", $strFileName);
$strFileName = str_replace("\\", "-", $strFileName);
$strFileName = str_replace("?", "-", $strFileName);
$strFileName = str_replace("*", "-", $strFileName);
$strFileName = str_replace(":", "-", $strFileName);
$strFileName = str_replace("|", "-", $strFileName);
$strFileName = str_replace("\"", "-", $strFileName);
$strFileName = str_replace("<", "-", $strFileName);
$strFileName = str_replace(">", "-", $strFileName);


if ( $strType == "endnote" )
{
	header("Cache-Control: private");
	header("Content-type: application/x-research-info-systems");
	
	echo $objPage->transform($objXml, "xsl/citation/ris.xsl");
}
elseif ( $strType == "ris")
{
	header('Content-type: text/plain');
	header("Content-Disposition: attachment; filename=" . $strFileName);
	
	echo $objPage->transform($objXml, "xsl/citation/ris.xsl");
}
elseif ( $strType == "text")
{
	// add a .txt extension
	
	if (strpos($strFileName, ".txt") === false) $strFileName .= ".txt";

	header('Content-type: text/plain');
	header("Content-Disposition: attachment; filename=" . $strFileName);
	
	echo $objPage->transform($objXml, "xsl/citation/basic.xsl");
}

?>
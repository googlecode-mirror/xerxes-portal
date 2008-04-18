<?php 

// inherits $objRegistry, $objXml and $objRequest

$strBase = $objRegistry->getConfig("BASE_URL");
$strCategory = $objRequest->getProperty("category");
$strFolder = $objRequest->getProperty("folder");

if ( $strFolder == null )
{
	$strFolder = "categories";
}

$objDatabases = simplexml_import_dom($objXml->documentElement);

$normalized = $objDatabases->xpath("categories/category[old='$strCategory']/normalized");

if ( $normalized == false )
{
	header( "Location: " . $strBase );	
}
else
{
	header( "Location: $strBase/$strFolder/" . (string) $normalized[0]);	
}


?>
	
		



<?php 

// inherits $objRegistry and $objXml

$strBase = $objRegistry->getConfig("BASE_URL");
$iLimit = $objRegistry->getConfig("SEARCH_LIMIT");

$strUrl = "";
$x = 1;
$bolFound = false;

$objDatabases = simplexml_import_dom($objXml->documentElement);

$strUrl = $strBase . "/?base=metasearch&action=search";
$strUrl .= "&context=" . urlencode($objRequest->getProperty("context"));
$strUrl .= "&context_url=" . urlencode($objRequest->getProperty("context_url"));
$strUrl .= "&query=" . urlencode($objRequest->getProperty("query"));
$strUrl .= "&field=" . urlencode($objRequest->getProperty("field"));

foreach ($objDatabases->category->subcategory as $subcategory) 
{
	if ( ( $subcategory['name'] == $objRequest->getProperty("subcategory") ) ||  $objRequest->getProperty("subcategory") == null )
	{
		$bolFound = true;
		
		foreach ( $subcategory->database as $database  )
		{
			if ( (string) $database->searchable == "1" && $x <= $iLimit )
			{
				$strUrl .= "&database=" . urlencode($database->metalib_id);
			}
			
			$x++;
		}
	}
}

// found no databases?

if ( $bolFound == false )
{
	throw new Exception("no databases found for jump request");
}


header( "Location: " . $strUrl );

?>
	
		



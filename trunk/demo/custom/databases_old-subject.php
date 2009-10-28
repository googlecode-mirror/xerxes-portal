<?php 

/**
 * ancient file for compatibility of beta-version xerxes
 *
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

// inherits $objRegistry, $objXml and $objRequest

$strBase = $objRegistry->getConfig("BASE_URL");
$strCategory = $objRequest->getProperty("category");
$strFolder = $objRequest->getProperty("folder");

if ( $strFolder == null )
{
	$strFolder = "databases/subject";
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
	
		



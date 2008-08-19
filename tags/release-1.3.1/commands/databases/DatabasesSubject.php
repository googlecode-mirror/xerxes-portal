<?php

/**
 * Display a single 'subject' in Xerxes, which is an inlined display of a subcategories
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version 1.1
 * @package Xerxes
 */

class Xerxes_Command_DatabasesSubject extends Xerxes_Command_Databases
{
	/**
	 * Fetch a single top-level category and inline its subcategories as XML;
	 * Request param should be 'subject', the normalized name of the subject as
	 * created by PopulateDatabases
	 *
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 * @return int status
	 */
	
	public function doExecute(Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry)
	{
		$objXml = new DOMDOcument( );
		$objXml->loadXML( "<category />" );
		
		$strOld = $objRequest->getProperty( "category" );
		$strSubject = $objRequest->getProperty( "subject" );
		
		$objData = new Xerxes_DataMap( );
		$objCategoryData = $objData->getSubject( $strSubject, $strOld );
		
		$y = 1;
		
		if ( $objCategoryData != null )
		{
			$objXml->documentElement->setAttribute( "name", $objCategoryData->name );
			$objXml->documentElement->setAttribute( "normalized", $objCategoryData->normalized );
			
			// standard url for the category 
			       
			$arrParams = array ("base" => "databases", "action" => "subject", "subject" => $objCategoryData->normalized );
			$url = Xerxes_Parser::escapeXml( $objRequest->url_for( $arrParams ) );
			$objElement = $objXml->createElement( "url", $url );
			$objXml->documentElement->appendChild( $objElement );
			
			// the attributes of the subcategories
			$db_list_index = 1;
			
			foreach ( $objCategoryData->subcategories as $objSubData )
			{
				$objSubCategory = $objXml->createElement( "subcategory" );
				$objSubCategory->setAttribute( "name", $objSubData->name );
				$objSubCategory->setAttribute( "position", $y );
				$objSubCategory->setAttribute( "id", $objSubData->id );
				
				$y ++;
				
				// the database information

				foreach ( $objSubData->databases as $objDatabaseData )
				{
					$objDatabase = Xerxes_Helper::databaseToNodeset( $objDatabaseData, $objRequest, $objRegistry, $db_list_index );
					$objDatabase = $objXml->importNode( $objDatabase, true );
					$objSubCategory->appendChild( $objDatabase );
				}
				
				$objXml->documentElement->appendChild( $objSubCategory );
			}
		}
		
		$objRequest->addDocument( $objXml );
		
		return 1;
	}
}
?>
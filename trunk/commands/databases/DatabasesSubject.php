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
	public function doExecute()
	{
		$objXml = new DOMDOcument( );
		$objXml->loadXML( "<category />" );
		
		$strOld = $this->request->getProperty( "category" );
		$strSubject = $this->request->getProperty( "subject" );
		
		// look up home page default subject from config if no subject was specified, and we were 
		// instructed to look it up with use_categories_quicksearch=true
		
		if ( $strSubject == "" && $this->request->getProperty( "use_categories_quicksearch" ) == "true" )
		{
			$strSubject = $this->registry->getConfig( "categories_quicksearch", false, "quick-search" );
		}
		
		$objData = new Xerxes_DataMap( );
		$objCategoryData = $objData->getSubject( $strSubject, $strOld );
		
		$y = 1;
		
		if ( $objCategoryData != null )
		{
			$objXml->documentElement->setAttribute( "name", $objCategoryData->name );
			$objXml->documentElement->setAttribute( "normalized", $objCategoryData->normalized );
			
			// standard url for the category 

			$arrParams = array ("base" => "databases", "action" => "subject", "subject" => $objCategoryData->normalized );
			$url = Xerxes_Parser::escapeXml( $this->request->url_for( $arrParams ) );
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
					$objDatabase = Xerxes_Helper::databaseToNodeset( $objDatabaseData, $this->request, $this->registry, $db_list_index );
					$objDatabase = $objXml->importNode( $objDatabase, true );
					$objSubCategory->appendChild( $objDatabase );
				}
				
				$objXml->documentElement->appendChild( $objSubCategory );
			}
		}
		
		$this->request->addDocument( $objXml );
		
		return 1;
	}
}
?>
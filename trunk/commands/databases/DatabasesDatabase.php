<?php

/**
 * Search for databases in the Xerxes db, and put database
 * info in xml. Can be: a single database; all databases (for a-z list);
 * or a database query. 
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version 1.1
 * @package Xerxes
 */

class Xerxes_Command_DatabasesDatabase extends Xerxes_Command_Databases
{
	/**
	 * Display information from a single database, uses 'id' parama in request to
	 * identify the database
	 *
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 * @return unknown
	 */
	
	public function doExecute(Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry)
	{
		$objXml = new DOMDOcument( );
		$objXml->loadXML( "<databases />" );
		
		$strID = $objRequest->getProperty( "id" );
		$strQuery = $objRequest->getProperty( "query" );
		$objData = new Xerxes_DataMap( );
		$arrResults = array();
		
		if ( $strID )
		{
			$arrResults = $objData->getDatabases( $strID );
      if (count($arrResults) == 0) {
        throw new Xerxes_NotFoundException("Can not find database with id $strID");
      }
		}
		elseif ( $strQuery )
		{
			$arrResults = $objData->getDatabases( null, $strQuery );
		} 
		else
		{
			// all database.
			$arrResults = $objData->getDatabases();
		}
		
		foreach ( $arrResults as $objDatabaseData )
		{
			$objDatabase = Xerxes_Helper::databaseToNodeset( $objDatabaseData, $objRequest, $objRegistry );
			$objDatabase = $objXml->importNode( $objDatabase, true );
			$objXml->documentElement->appendChild( $objDatabase );
		}
		
		$objRequest->addDocument( $objXml );
		
		return 1;
	}
}
?>
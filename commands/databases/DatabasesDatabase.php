<?php

/**
 * Search for databases in the Xerxes db, and put database
 * info in xml. Can be: a single database; all databases (for a-z list);
 * or a database query. 
 * 
 * Normally, if no ID or query is supplied, this action will end up
 * getting ALL databases. But if the request has suppress_full_db_list=true,
 * then it will get none.  
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_DatabasesDatabase extends Xerxes_Command_Databases
{
	public function doExecute()
	{
		$objXml = new DOMDOcument( );
		$objXml->loadXML( "<databases />" );
		
		$strID = $this->request->getProperty( "id" );
		$strQuery = $this->request->getProperty( "query" );
		$alpha = $this->request->getProperty( "alpha" );
		
		$objData = new Xerxes_DataMap( );
		$arrResults = array ( );
		
		if ( $strID )
		{
			$arrResults = $objData->getDatabases( $strID );
			
			if ( count( $arrResults ) == 0 )
			{
				throw new Xerxes_Exception_NotFound( "Can not find database with id $strID" );
			}
		}
		elseif ($alpha != "")
		{
			// get just the letters
			
			$alpha_list = $this->request->getSession("alpha_list");
			
			if ( $alpha_list == "" )
			{
				$alpha_list_array = $objData->getDatabaseAlpha();
				$alpha_list = implode(',', $alpha_list_array);
				
				// cache it in session too!
				
				$this->request->setSession("alpha_list", $alpha_list);
			}	
			
			// add it to the interface
			
			$objAlpha = new DOMDocument();
			$objAlpha->loadXML("<alpha />");
			
			foreach ( explode(',', $alpha_list) as $letter )
			{
				$objLetter = $objAlpha->createElement("letter", $letter);
				$objAlpha->documentElement->appendChild($objLetter);
			}
			
			$this->request->addDocument( $objAlpha );
			
			$arrResults = $objData->getDatabasesStartingWith( $alpha );			
		}
		elseif ( $strQuery )
		{
			$arrResults = $objData->getDatabases( null, $strQuery );
		} 
		elseif ( $this->request->getProperty( "suppress_full_db_list" ) != "true" )
		{
			// all database.
			$arrResults = $objData->getDatabases();
		}
		
		foreach ( $arrResults as $objDatabaseData )
		{
			$objDatabase = Xerxes_Helper::databaseToNodeset( $objDatabaseData, $this->request, $this->registry );
			$objDatabase = $objXml->importNode( $objDatabase, true );
			$objXml->documentElement->appendChild( $objDatabase );
		}
		
		$this->request->addDocument( $objXml );
		
		return 1;
	}
}
?>
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
		
		$objDataMap = new Xerxes_DataMap( );
		$arrResults = array ( );
		
		if ( $strID )
		{
			$arrResults = $objDataMap->getDatabases( $strID );
			
			if ( count( $arrResults ) == 0 )
			{
				throw new Xerxes_Exception_NotFound( "Can not find database with id $strID" );
			}
		}
		elseif ($alpha != "")
		{
			$this->addAlphaList();			
			$arrResults = $objDataMap->getDatabasesStartingWith( $alpha );			
		}
		elseif ( $strQuery )
		{
			$arrResults = $objDataMap->getDatabases( null, $strQuery );
		} 
		elseif ( $this->request->getProperty( "suppress_full_db_list" ) != "true" )
		{
			$this->addAlphaList();
			
			// only show single letters, please

			if ( $this->registry->getConfig("DATABASE_LIST_SINGLE_LETTER_DISPAY", false, false) &&
			     $this->request->getProperty("action") == "alphabetical" )
			{
				$params = array(
					"base" => "databases",
					"action" => "alphabetical",
					"alpha" => "A" // assume we want to go to 'A' as the start
				);
			
				$link = $this->request->url_for($params);
				$this->request->setRedirect($link);
				return 0;
			}

			// all database
			
			$arrResults = $objDataMap->getDatabases();
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
	
	protected function addAlphaList()
	{
		// check for letters in session
		
		$alpha_list = $this->request->getSession("alpha_list");
		
		if ( $alpha_list == "" ) // not in session
		{
			$objDataMap = new Xerxes_DataMap( );
			
			// check database cache
			
			$arrCache = $objDataMap->getCache("dblist", "az");
			
			if ( count($arrCache) > 0 ) // yup
			{
				$objCache = $arrCache[0];
				$alpha_list = $objCache->data;
			}
			else // not here either
			{
				// so create it
				
				$alpha_list_array = $objDataMap->getDatabaseAlpha();
				$alpha_list = implode(',', $alpha_list_array);
				
				// and cache it in the database
				
				$objCache = new Xerxes_Data_Cache();
				$objCache->source = "dblist";
				$objCache->id = "az";
				$objCache->data = $alpha_list;
				$objCache->expiry = time() + ( 60 * 60 ); // for one hour
									
				$objDataMap->setCache($objCache);
			}
			
			// cache it in session too!
				
			$this->request->setSession("alpha_list", $alpha_list);
		}	
		
		// add it to the interface
		
		$objAlpha = new DOMDocument();
		$objAlpha->loadXML("<alpha />");
		
		foreach ( explode(',', $alpha_list) as $letter )
		{
			$objEntry = $objAlpha->createElement("entry");
			$objAlpha->documentElement->appendChild($objEntry);
			
			$objLetter = $objAlpha->createElement("letter", $letter);
			$objEntry->appendChild($objLetter);
			
			$params = array(
				"base" => "databases",
				"action" => "alphabetical",
				"alpha" => $letter
			);
			
			$link = $this->request->url_for($params);
			
			$objLink = $objAlpha->createElement("link", Xerxes_Framework_Parser::escapeXML($link) );
			$objEntry->appendChild($objLink);
		}
		
		$this->request->addDocument( $objAlpha );
	}
}
?>
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
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			$objXml = new DOMDOcument();
			$objXml->loadXML("<databases />");
			
			$strID = $objRequest->getProperty("id");
			$strQuery = $objRequest->getProperty("query");
			$objData = new Xerxes_DataMap();
      $arrResults;
      if ( $strID ) {
        $arrResults = $objData->getDatabases($strID);
      }
      elseif ( $strQuery ) {
        $arrResults = $objData->getDatabases(null, $strQuery);
      }
			else {
        # all database.
        $arrResults = $objData->getDatabases();
      }
      
			foreach ( $arrResults as $objDatabaseData )
			{
				$objDatabase = $objXml->createElement("database");
				
				// single value fields
				
				foreach ( $objDatabaseData->properties() as $key => $value )
				{
					if ( $value != null )
					{
						$objElement = $objXml->createElement($key, Xerxes_Parser::escapeXml($value));
						$objDatabase->appendChild($objElement);
					}
				}
				
				// multi-value fields
				
				$arrMulti = array("keywords", "languages", "notes", "alternate_titles", "alternate_publishers");
				
				foreach ($arrMulti as $multi )
				{
					foreach ( $objDatabaseData->$multi as $value )
					{
						// remove the trailing 's'
						
						$single = substr($multi, 0, strlen($multi) - 1);
						
						if ( $value != null )
						{
							$objElement = $objXml->createElement($single, Xerxes_Parser::escapeXml($value));
							$objDatabase->appendChild($objElement);
						}
					}
				}
				
				$properties = $objDatabaseData->properties();
        //Add an element for url to Xerxes detail page for this db
				$objElement = $objXml->createElement( "url", 
					$objRequest->url_for( array(
						"base" => "databases",
						"action" => "database",
						"id" => htmlentities($properties['metalib_id'])
					)));				
				$objDatabase->appendChild($objElement);
        
        //Add an element for url to Xerxes-mediated direct link
        //to db. 
        $objElement = $objXml->createElement("xerxes_native_link_url",
          $objRequest->url_for( array(
            "base" => "databases",
            "action" => "proxy",
            "database" => htmlentities($properties['metalib_id'])
          )));
        $objDatabase->appendChild($objElement);
        
				$objXml->documentElement->appendChild($objDatabase);
			}
			
			$objRequest->addDocument($objXml);
				
			return 1;
		}
	}
?>
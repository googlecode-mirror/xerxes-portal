<?php

class Xerxes_Helper {


  /**
	 *  Take a Xerxes data object representing a database, output
   *  a DOMDocument nodeset representing that database, for including
   *  in an XML response. Used by some Databases controllers. 
   *
   *  To actually include the returned value, you will need to import it into
   *  your DOMDocument of choice first. Example:
   *        $objDatabase = self::databaseToNodeset($objDatabaseData, $objRequest);
   *        $objDatabase = $objXml->importNode( $objDatabase, true );
   *        $objXml->documentElement->appendChild($objDatabase);
   *
	 *
	 * @param Xerxes_Data_Database $objDatabaseData
	 * @param Xerxes_Framework_Request $objRequest  need the Xerxes request object to create urls for us. 
   * @param Xerxes_Framework_Registry $objRegistry need a registry object too, sorry. 
	 * @param &$index = null  sometimes we want to append a count index to the xml. Pass in a counter variable, and it will be included AND incremented (passed by reference).
   * @return DOMNode
	 */
  public static function databaseToNodeset(Xerxes_Data_Database $objDatabaseData, Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry, &$index = null) {
    $objDom = new DOMDocument();
    $objDom->loadXML("<database/>");
    
    $objDatabase = $objDom->documentElement;
    
    // single value fields
      
    foreach ( $objDatabaseData->properties() as $key => $value )
    {
      if ( $value != null )
      {
        $objElement = $objDom->createElement($key, Xerxes_Parser::escapeXml($value));
        $objDatabase->appendChild($objElement);
        
        // Sometimes we're asked to track and record index. 
        if ( ! is_null($index) && $key == "searchable" && $value == "1") {
          $objElement->setAttribute("count", $index);
          $index++;
        }
      }
    }
      
    // multi-value fields
    
    $arrMulti = array("keywords", "languages", "notes", "alternate_titles", "alternate_publishers", "group_restrictions");
    
    foreach ($arrMulti as $multi )
    {
      foreach ( $objDatabaseData->$multi as $value )
      {
        // remove the trailing 's'
        
        $single = substr($multi, 0, strlen($multi) - 1);
        
        if ( $value != null )
        {
          $objElement = $objDom->createElement($single, Xerxes_Parser::escapeXml($value));
          
          //group restriction needs another attribute
          if ($multi == "group_restrictions") {
            $objElement->setAttribute("display_name", $objRegistry->getGroupDisplayName($value)); 
          }
          
          $objDatabase->appendChild($objElement);          
        }
      }
    }
    
    // Is the particular user allowed to search this?
    $objElement = $objDom->createElement("searchable_by_user", Xerxes_Framework_Restrict::dbSearchableForUser( $objDatabaseData, $objRequest, $objRegistry));
    $objDatabase->appendChild( $objElement );
    
    //Add an element for url to Xerxes detail page for this db
    $objElement = $objDom->createElement( "url", 
      $objRequest->url_for( array(
        "base" => "databases",
        "action" => "database",
        "id" => htmlentities($objDatabaseData->metalib_id)
      )));				
    $objDatabase->appendChild($objElement);
    
    //Add an element for url to Xerxes-mediated direct link
    //to db. 
    $objElement = $objDom->createElement("xerxes_native_link_url",
      $objRequest->url_for( array(
        "base" => "databases",
        "action" => "proxy",
        "database" => htmlentities($objDatabaseData->metalib_id)
      )));
    $objDatabase->appendChild($objElement);
    
    return $objDatabase;
  }  

}
?>

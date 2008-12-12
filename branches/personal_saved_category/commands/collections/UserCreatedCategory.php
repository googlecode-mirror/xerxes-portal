<?php

/**
 * Display a single 'subject' in Xerxes, which is an inlined display of a subcategories
 * 
 */

class Xerxes_Command_UserCreatedCategory extends Xerxes_Command_Collections
{
	/**
	 * Fetch a single top-level category and inline its subcategories as XML;
	 * Request param should be 'subject', the normalized name of the subject as
	 * saved for user-created db. The 'normalized' name is the one we will show in
   * the url. 
	 *
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 * @return int status
	 */
	
	public function doExecute(Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry)
	{
		$objXml = new DOMDOcument( );
		$objXml->loadXML( "<category />" );
		
    $strSubject = $objRequest->getProperty( "subject" );
    $strUser = $objRequest->getProperty("username");
    
		$objData = new Xerxes_DataMap( );
		$objCategoryData = null;
    /* Only fetch if we actually have params, avoid the fetch-everything phenomena */
    if ( $strSubject && $strUser ) { 
      $objCategoryData = $objData->getSubject( $strSubject, null, Xerxes_DataMap::userCreatedMode, $strUser );
    }
    
    //If there hasn't
    if (! $objCategoryData) {
      if ( $objRequest->getRedirect()) {
        //Nevermind, we're in the creation process, already redirected, just
        //end now. 
        return 1;
      }
      else {
        throw new Xerxes_NotFoundException("Personal collection not found.");
      }
    }
    
    // Make sure they have access
    if (! $objCategoryData->published) {
      Xerxes_Helper::ensureSpecifiedUser( $objCategoryData->owned_by_user, $objRequest, $objRegistry, "This is a private database set only accessible to the user who created it. Please log in if you are that user." );
    }          
		
		$y = 1;
		
		if ( $objCategoryData != null )
		{
			$objXml->documentElement->setAttribute( "name", $objCategoryData->name );
			$objXml->documentElement->setAttribute( "normalized", $objCategoryData->normalized );
      $objXml->documentElement->setAttribute("owned_by_user", $objCategoryData->owned_by_user);
      $objXml->documentElement->setAttribute("published", $objCategoryData->published);
			
			// standard url for the category 
			       
			$arrParams = array ("base" => "collections", "action" => "subject", "username" => $strUser, "subject" => $objCategoryData->normalized );
			$url = Xerxes_Parser::escapeXml( $objRequest->url_for( $arrParams ) );
			$objElement = $objXml->createElement( "url", $url );
			$objXml->documentElement->appendChild( $objElement );

      //edit url for the user-created category
      $arrParams = array ("base" => "collections", "action" => "edit_form", "username" => $strUser, "subject" => $objCategoryData->normalized );
			$url = Xerxes_Parser::escapeXml( $objRequest->url_for( $arrParams ) );
			$objElement = $objXml->createElement( "edit_url", $url );
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
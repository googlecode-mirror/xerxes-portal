<?php

/**
 * Can be used to remove a database from a user-created subcategory (if param
 *  "db" is present), or delete a subcategory (if just "subcategory" param). 
 *
 *  In all cases, you need a 'subject' and 'username' param, and 'return'. 
 */

class Xerxes_Command_RemoveDatabaseFromUserSubcategory extends Xerxes_Command_Collections
{
	/**
	 *
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 * @return int status
	 */
	
	public function doExecute(Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry)
	{
    
		$strNormalizedSubject = $objRequest->getProperty("subject");
    $strUsername = $objRequest->getProperty("username");
    $strDatabaseID = $objRequest->getProperty("id");
    
		$strSubcatID = $objRequest->getProperty( "subcategory" );
    
    
    // Make sure they are logged in as the user they are trying to save as. 
    Xerxes_Helper::ensureSpecifiedUser($strUsername, $objRequest, $objRegistry, "You must be logged in as $strUsername to save to a personal database collection owned by that user.");
    
    $objData = new Xerxes_DataMap();
    $subcategory = null;
    
    // Find the category
    
    $category = $objData->getSubject( $strNormalizedSubject, null, Xerxes_DataMap::userCreatedMode, $strUsername );
    $subcategory = $this->getSubcategory($category, $strSubcatID);    

    
   
    $objData->removeDatabaseFromUserCreatedSubcategory($strDatabaseID, $subcategory); 
        
    
    // Send them back where they came from, with a message. 
    $this->returnWithMessage("Removed database");
    
		return 1;
	}
}
?>
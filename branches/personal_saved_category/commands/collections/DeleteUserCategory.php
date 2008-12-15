<?php

/**
 *  Delete a user-created category 
 *
 *  In all cases, you need a 'subject' and 'username' param, and 'return'. 
 */

class Xerxes_Command_DeleteUserCategory extends Xerxes_Command_Collections
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
    
    
    // Make sure they are logged in as the user they are trying to save as. 
    Xerxes_Helper::ensureSpecifiedUser($strUsername, $objRequest, $objRegistry, "You must be logged in as $strUsername to save to a personal database collection owned by that user.");
    
    $objData = new Xerxes_DataMap();
    
    // Find the category
    
    $category = $objData->getSubject( $strNormalizedSubject, null, Xerxes_DataMap::userCreatedMode, $strUsername );

    
   
    $objData->deleteUserCreatedCategory($category); 
        
    
    // Send them back where they came from, with a message. 
    $this->returnWithMessage("Deleted Collection" ,array("base" => "databases", "action" => "categories"));
    
		return 1;
	}
}
?>
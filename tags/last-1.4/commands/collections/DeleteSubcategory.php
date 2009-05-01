<?php

/**
 * Can be used to remove a database from a user-created subcategory (if param
 *  "db" is present), or delete a subcategory (if just "subcategory" param). 
 *
 *  In all cases, you need a 'subject' and 'username' param, and 'return'. 
 */

class Xerxes_Command_DeleteSubcategory extends Xerxes_Command_Collections
{
	public function doExecute()
	{
		$strNormalizedSubject = $this->request->getProperty( "subject" );
		$strUsername = $this->request->getProperty( "username" );
		$strSubcatID = $this->request->getProperty( "subcategory" );
		
		// Make sure they are logged in as the user they are trying to save as. 
		Xerxes_Helper::ensureSpecifiedUser( $strUsername, $this->request, $this->registry, "You must be logged in as $strUsername to save to a personal database collection owned by that user." );
		
		$objData = new Xerxes_DataMap( );
		$subcategory = null;
		
		// Find the category
		

		$category = $objData->getSubject( $strNormalizedSubject, null, Xerxes_DataMap::userCreatedMode, $strUsername );
		$subcategory = $this->getSubcategory( $category, $strSubcatID );
		
		$objData->deleteUserCreatedSubcategory( $subcategory );
		
		// Send them back where they came from, with a message. 
		$this->returnWithMessage( "Deleted " . $subcategory->name, array ("base" => "collections", "action" => "edit_form", "subject" => $category->normalized, "username" => $strUsername ) );
		
		return 1;
	}
}
?>
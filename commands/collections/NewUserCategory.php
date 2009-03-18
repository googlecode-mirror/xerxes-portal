<?php

/**
 * Create a new user created category, name identified by request param
 * "new_subject_name".  username must also be a request param. 
 *
 * Will refuse to create a new subject with the same normalized name
 * as an existing one by that user, will just no-op. 
 *
 * Redirects to 'edit' page for newly created (or existing) subject
 * when done. 
 *
 * If new_subcategory_name is given, will create an initial empty subcat
 * in the new category--UNLESS action='save_complete', in which case
 * new subcat is never created.  This is used when this command is used by
 * the save_complete action, for AJAXy database saving in collection,
 * and the new subcat should not be created in that case. 
 */

class Xerxes_Command_NewUserCategory extends Xerxes_Command_Collections
{
	public function doExecute()
	{
		$strNewSubject = $this->request->getProperty( "new_subject_name" );
		
		if ( empty( $strNewSubject ) )
		{
			$strNewSubject = $this->registry->getConfig( "default_collection_name", false, "My Saved Databases" );
		}
		
		$strUsername = $this->request->getProperty( "username" );
		
		$strNewSubcategory = $this->request->getProperty( "new_subcategory_name" );
		
		if ( $this->request->getProperty( "action" ) == "save_complete" )
		{
			// Nevermind, don't do it. 
			$strNewSubcategory = null;
		}
		
		// Make sure they are logged in as the user they are trying to save as. 
		Xerxes_Helper::ensureSpecifiedUser( $strUsername, $this->request, $this->registry, "You must be logged in as $strUsername to save to a personal database collection owned by that user." );
		
		$objData = new Xerxes_DataMap( );
		$existingSubject = null;
		
		// Make sure it's truly new and has a unique normalized form, else
		// reuse existing. This takes care of browser-refresh, or typing in the identical
		// name of an already existing one.
		
		$strNormalizedSubject = Xerxes_Data_Category::normalize( $strNewSubject );
		
		$existingSubject = $objData->getSubject( $strNormalizedSubject, null, Xerxes_DataMap::userCreatedMode, $strUsername );
		
		// if we found a dupe, we'll use that, otherwise create one. 
		
		if ( ! $existingSubject )
		{
			$objDataCategory = new Xerxes_Data_Category( );
			$objDataCategory->name = $strNewSubject;
			$objDataCategory->username = $strUsername;
			$objDataCategory->normalized = $strNormalizedSubject;
			$objDataCategory->published = 0;
			$existingSubject = $objData->addUserCreatedCategory( $objDataCategory );
		}
		
		// and create an initial section, please.
		
		if ( $strNewSubcategory && ! $this->request->getProperty( "format" ) == "json" )
		{
			$subcategory = new Xerxes_Data_Subcategory( );
			$subcategory->name = $strNewSubcategory;
			$subcategory->category_id = $existingSubject->id;
			$subcategory->sequence = 1;
			$subcategory = $objData->addUserCreatedSubcategory( $subcategory );
		}
		
		// send them off to the edit_mode of their new category. 
		   
		$newUrl = $this->request->url_for( array (
			"base" => "collections", 
			"action" => "subject", 
			"username" => $this->request->getProperty( "username" ), 
			"subject" => $existingSubject->normalized ), true ); // force full url for redirect

		$this->request->setRedirect( $newUrl );
		
		return 1;
	}
}
?>
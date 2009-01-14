<?php

/**
 * 
 */

class Xerxes_Command_NewUserCategory extends Xerxes_Command_Collections
{
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
   *
   *
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 * @return int status
	 */
	
	public function doExecute(Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry)
	{
		
    $strNewSubject = $objRequest->getProperty("new_subject_name");
    if (empty($strNewSubject)) $strNewSubject = 'My Collection';

    $strUsername = $objRequest->getProperty("username");    
    
    $strNewSubcategory = $objRequest->getProperty("new_subcategory_name");
    if ( $objRequest->getProperty("action") == "save_complete") {
      // Nevermind, don't do it. 
      $strNewSubcategory = null;
    }

    
    // Make sure they are logged in as the user they are trying to save as. 
    Xerxes_Helper::ensureSpecifiedUser($strUsername, $objRequest, $objRegistry, "You must be logged in as $strUsername to save to a personal database collection owned by that user.");
    
    $objData = new Xerxes_DataMap();
    $existingSubject = null;
        
    
    
          
    // Make sure it's truly new and has a unique normalized form, else
    // reuse existing.
    // This takes care of browser-refresh, or typing in the identical
    // name of an already existing one. 
    $strNormalizedSubject = Xerxes_Data_Category::normalize($strNewSubject);

    $existingSubject = $objData->getSubject( $strNormalizedSubject, null, Xerxes_DataMap::userCreatedMode, $strUsername );
          
    // If we found a dupe, we'll use that, otherwise create one. 
    if (! $existingSubject) {
      
      $objDataCategory = new Xerxes_Data_Category();
      $objDataCategory->name = $strNewSubject;
      $objDataCategory->username = $strUsername;
      $objDataCategory->normalized = $strNormalizedSubject;
      $objDataCategory->published = 0;
      $existingSubject = $objData->addUserCreatedCategory($objDataCategory);        
    }      
    
    // And create an initial section, please.
    if ( $strNewSubcategory && ! $objRequest->getProperty("format") == "json") {
      $subcategory = new Xerxes_Data_Subcategory();
      $subcategory->name = $strNewSubcategory;
      $subcategory->category_id = $existingSubject->id;        
      $subcategory->sequence = 1;
      $subcategory = $objData->addUserCreatedSubcategory($subcategory);
    }
    

  
    // Send them off to the edit_mode of their new category.    
    $newUrl = $objRequest->url_for( array( "base" => "collections",
                                   "action" => "edit_form",
                                   "username" => $objRequest->getProperty("username"),
                                   "subject" => $existingSubject->normalized),                                     
                            true // force full url for redirect
                            );
    $objRequest->setRedirect(  $newUrl  );     
  

    
			
		return 1;
	}
}
?>
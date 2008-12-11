<?php

/**
 * 
 */

class Xerxes_Command_ChooseUserCategory extends Xerxes_Command_Collections
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
    //Cancel?
    if ($objRequest->getProperty("cancel")) {
      $this->returnWithMessage("Cancelled"); 
      return 1;
    }
		
		$strSubjectSelection = $objRequest->getProperty( "subject" );
    $strNewSubject = $objRequest->getProperty("new_subject_name");
    $strUsername = $objRequest->getProperty("username");
        

    
    // Make sure they are logged in as the user they are trying to save as. 
    Xerxes_Helper::ensureSpecifiedUser($strUsername, $objRequest, $objRegistry, "You must be logged in as $strUsername to save to a personal database collection owned by that user.");
    
    $objData = new Xerxes_DataMap();
    $existingSubject = null;
        
    
    // Were we directed to create a new one?
    if ( $strNewSubject || $strSubjectSelection == "NEW" ) {
      if (empty($strNewSubject)) $strNewSubject = "My Collection";
          
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
    }
    // Okay, we've created a new subject if we needed one, and possibly
    // found an existing dupe of directed new subject. In either case,
    // existingSubject will have a value. If so, we actually want to redirect
    // to put that identified subject in the URL, for proper display of choice
    // box, especially on redirects. 
    if ( $existingSubject ) {      
      $fixedUrl = $objRequest->url_for( array( "base" => "collections",
                                     "action" => "save_choose_subheading",
                                     "id" => $objRequest->getProperty("id"),
                                     "username" => $objRequest->getProperty("username"),
                                     "subject" => $existingSubject->normalized),
                              true // force full url
                              );
      $objRequest->setRedirect(  $fixedUrl  );
      
    }
    
			
		return 1;
	}
}
?>
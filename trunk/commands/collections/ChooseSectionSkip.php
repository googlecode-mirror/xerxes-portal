<?php

/**
 * 
 */

class Xerxes_Command_ChooseSectionSkip extends Xerxes_Command_Collections
{
	/**
	 * Prior to displaying a section choice dialog, do we even have any sections
   * to choose from? If just one, skip that choice dialog. if 0, create one,
   * and skip dialog. 
   *
   *
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 * @return int status
	 */
	
	public function doExecute(Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry)
	{
		
		$strSubjectSelection = $objRequest->getProperty( "subject" );
    $strUsername = $objRequest->getProperty("username");
    

    
    // Make sure they are logged in as the user they are trying to save as. 
    Xerxes_Helper::ensureSpecifiedUser($strUsername, $objRequest, $objRegistry, "You must be logged in as $strUsername to save to a personal database collection owned by that user.");
    
    $objData = new Xerxes_DataMap();
          
    $existingSubject = $objData->getSubject( $strSubjectSelection, null, Xerxes_DataMap::userCreatedMode, $strUsername );
    
    
    $subcats = $existingSubject->subcategories;
    
    if ( count($subcats) <= 1 ) {
      if (count($subcats) == 1) {
       $subcat_id = $subcats[0]->id;
      }
      else {
        //create one
        $new_subcat =  new Xerxes_Data_Subcategory();
        $new_subcat->sequence = 1;
        $new_subcat->category_id = $existingSubject->id;
        $new_subcat->name = $objRegistry->getConfig("default_collection_section_name", false, "Databases");
        
        $new_subcat = $objData->addUserCreatedSubcategory($new_subcat);
        $subcat_id = $new_subcat->id;
      }
    
      $fixedUrl = $objRequest->url_for( array( "base" => "collections",
                               "action" => "save_complete",
                               "subject" => $existingSubject->normalized,
                               "subcategory" => $subcat_id,
                               "id" => $objRequest->getProperty("id"),
                               "username" => $objRequest->getProperty("username"),
                               "return" => $objRequest->getProperty("return")
                               ),
                              true // force full url for redirect
                              );
      $objRequest->setRedirect(  $fixedUrl  );      
    }

    
			
		return 1;
	}
}
?>
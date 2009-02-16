<?php

/**
 * 
 */

class Xerxes_Command_SaveDatabaseToUserCategory extends Xerxes_Command_Collections
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
    // If that was set to "NEW" then, we have a newly created subject,
    // already created by a prior command in execution chain, but we
    // need to make sure to look it up properly. 
    if ( $strNormalizedSubject == "NEW" ) {
      $strNormalizedSubject =  Xerxes_Data_Category::normalize ($objRequest->getProperty("new_subject_name"));
    }
    
    
    $strUsername = $objRequest->getProperty("username");
    $strDatabaseID = $objRequest->getProperty("id");
    
		$strSubcatSelection = $objRequest->getProperty( "subcategory" );
    $strNewSubcat = $objRequest->getProperty("new_subcategory_name");
    
    
    // Make sure they are logged in as the user they are trying to save as. 
    Xerxes_Helper::ensureSpecifiedUser($strUsername, $objRequest, $objRegistry, "You must be logged in as $strUsername to save to a personal database collection owned by that user.");
    
    $objData = new Xerxes_DataMap();
    $subcategory = null;
    
    // Find the category
    
    $category = $objData->getSubject( $strNormalizedSubject, null, Xerxes_DataMap::userCreatedMode, $strUsername );
      
    

    
    // To do. Create silently if not found?
    if (! $category) throw new Exception("Selected category not found in database.");
    
    // Were we directed to create a new one?
    if ( $strNewSubcat || ($strSubcatSelection == "NEW" )) {
      
      if (empty($strNewSubcat)) $strNewSubcat = "Databases";
      
      $subcategory = new Xerxes_Data_Subcategory();
      $subcategory->name = $strNewSubcat;
      $subcategory->category_id = $category->id;
      // just put it at the end
      $last_one = $category->subcategories[ count($category->subcategories) -1 ];
      $subcategory->sequence = $last_one->sequence + 1;
      
      $subcategory = $objData->addUserCreatedSubcategory($subcategory);
    }
    
    //If no db id was provided, all we needed to do was create a subcategory.
    if ( ! $strDatabaseID ) {
      $this->returnWithMessage("New section created");
      return 1;
    }
    
    
    // If we don't have a subcategory object from having just created one, find it from categories children. 
    if ( ! $subcategory ) {
      foreach( $category->subcategories as $s ) {
        if ($s->id == $strSubcatSelection) $subcategory = $s; 
      }
    }
    // Now we better have one. 
    if (! $subcategory) throw new Exception("Selected section not found.");


      
    
    // And add the db to it, unless it already is there. 
    foreach ($subcategory->databases as $db) {
      if ($db->metalib_id == $strDatabaseID) {
        $this->returnWithMessage("Database was already in section  ".  $subcategory->name);
        return 1;
      }
    }
    $objData->addDatabaseToUserCreatedSubcategory($strDatabaseID, $subcategory);    
    
    // Send them back where they came from, with a message. 
    $this->returnWithMessage("Saved database in " . $category->name );
    
		return 1;
	}
}
?>
<?php

/**
 * 
 */

class Xerxes_Command_RenameUserCategory extends Xerxes_Command_Collections
{
	/**
	 * Rename a user-created category or subcategory. Expects request param
   * 'new_name', subject=>normalized-name, and possibly subcategory=>id. If 
   *  subcategory is present, subcategory will be renamed. 
   *
   *  Redirects back to 'return' request param, or user created category
   *  edit page. 
	 *
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 * @return int status
	 */
	
	public function doExecute(Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry)
	{
    //Cancel?
    $arrDefaultReturn = array("base" => "collections", "action" => "edit_form", "subject" => $objRequest->getProperty("subject"), "subcategory" => $objRequest->getProperty("subcategory"), "username" => $objRequest->getProperty("username")); 
    if ($objRequest->getProperty("cancel")) {      
      $this->returnWithMessage("Cancelled", $arrDefaultReturn );
      return 1;
    }
		
		$strSubject = $objRequest->getProperty( "subject" );
    $strSubcatID = $objRequest->getProperty("subcategory");
    $strPublished = $objRequest->getProperty("published");
    $strUsername = $objRequest->getProperty("username");
    $strNewName = $objRequest->getProperty("new_name");
    
    // Make sure they are logged in as the user they are trying to save as. 
    Xerxes_Helper::ensureSpecifiedUser($strUsername, $objRequest, $objRegistry, "You must be logged in as $strUsername to save to a personal database collection owned by that user.");
    
    $objData = new Xerxes_DataMap();
    
    $category = $objData->getSubject( $strSubject, null, Xerxes_DataMap::userCreatedMode, $strUsername );

    $message = "";
    
    if (! empty($strSubcatID)) {
      //Edit a subcategory, rename
      $subcat = $this->getSubcategory( $category, $strSubcatID);
      if (! empty($strNewName) ) {
        $message .= "Section name changed. ";
        $subcat->name = $strNewName;
        $objData->updateUserSubcategoryProperties($subcat);
      }
    }
    else {
      //Edit a category, rename/publish
      if (! empty($strNewName)) {
        $category->name = $strNewName;
        $message .= "Collection name changed. ";
      }
      if (! empty($strPublished)) {
        $category->published = (boolean) ($strPublished == "true");
        $message .= " Collection publication status changed. ";
      }
      $objData->updateUserCategoryProperties($category);
    }
    // New name if it's been changed!
    $arrDefaultReturn["subject"] = $category->normalized;
    $this->returnWithMessage($message, $arrDefaultReturn);
    
		return 1;
	}
}
?>
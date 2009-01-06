<?php

/**
 * 
 */

class Xerxes_Command_ReorderSubcats extends Xerxes_Command_Collections
{
	/**
	 * Reorder subcategories. 
	 *
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 * @return int status
	 */
	
	public function doExecute(Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry)
	{
    $arrDefaultReturn = array("base" => "collections", "action" => "edit_form", "subject" => $objRequest->getProperty("subject"), "subcategory" => $objRequest->getProperty("subcategory"), "username" => $objRequest->getProperty("username")); 
    
		$strSubject = $objRequest->getProperty( "subject" );
    $strUsername = $objRequest->getProperty("username");
    

    
    
    // Make sure they are logged in as the user they are trying to save as. 
    Xerxes_Helper::ensureSpecifiedUser($strUsername, $objRequest, $objRegistry, "You must be logged in as $strUsername to save to a personal database collection owned by that user.");
    
    $objData = new Xerxes_DataMap();
    
    $category = $objData->getSubject( $strSubject, null, Xerxes_DataMap::userCreatedMode, $strUsername );

    
    // Find any new assigned numbers, and reorder. 
    $orderedSubcats = $category->subcategories;
    // We need to through the assignments in sorted order by sequence choice,
    // for this to work right. 
    $sortedProperties = $objRequest->getAllProperties();
    asort($sortedProperties);
    
    foreach ($sortedProperties as $name => $new_sequence) {
      if (! empty($new_sequence) &&  preg_match('/^subcat_seq_(\d+)$/', $name, $matches) ) {
        $subcatID = $matches[1];
        $old_index = null;
        $subcategory = null;
        for ($i = 0; $i < count($orderedSubcats) ; $i++) {
          $candidate = $orderedSubcats[$i];
          if ($candidate->id == $subcatID) {
            $old_index = $i;
            $subcategory = $candidate;
          }
        }
        // If we found it. 
        if ( $subcategory ) {        
          // remove it from the array, then add it back in
          array_splice( $orderedSubcats, $old_index, 1);
          array_splice( $orderedSubcats, $new_sequence - 1, 0, array($subcategory));                     
        }
      }
    }
    
    
    // Okay, we've re-ordered $orderedSubcats, now update the sequence #s
    for ($i = 0; $i < count($orderedSubcats) ; $i++) {
      $subcategory = $orderedSubcats[$i];
      $subcategory->sequence = $i+1;
      $objData->updateUserSubcategoryProperties( $subcategory );
    }
    
    $this->returnWithMessage("Section order changed", $arrDefaultReturn);
    
		return 1;
	}
}
?>
<?php

/**
 * Base class for 'collection' commands, personal db collections
 *
 */

abstract class Xerxes_Command_Collections extends Xerxes_Framework_Command
{

  public function returnWithMessage($strMessage, $arrDefaultUrl = null) {
    $return = $this->request->getProperty("return");
    
    if ( $return ) {
      $url = "http://" . $this->request->getServer('SERVER_NAME') .  $this->request->getProperty("return");
    }
    elseif ($arrDefaultUrl) {
      $url = $this->request->url_for($arrDefaultUrl);
    }
    else {
      $url = $this->registry->getConfig("BASE_WEB_PATH");
    }
    
    $this->request->setSession("flash_message", $strMessage);
    $this->request->setRedirect( $url );
  }
  
  // Find the subcategory with the given id, from the Xerxes_Data_Category object passed in. 
  public function getSubcategory($objCategory, $subcatId) {
    $subcategory = null;
    foreach( $objCategory->subcategories as $s ) {
        if ($s->id == $subcatId) $subcategory = $s; 
    }
    return $subcategory;
  }
  
  // Is the collection the default one? We tell by seeing if it's name matches
  // the default name. 
  public function isDefaultCollection($objCategoryData) {
    return ( $objCategoryData->normalized == Xerxes_Data_Category::normalize($this->registry->getConfig("default_collection_name", false, "My Saved Databases")));
  }
}

?>
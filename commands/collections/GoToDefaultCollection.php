<?php	
	
	/**
	 * Display the top-level categories from the Metalib KB
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_GoToDefaultCollection extends Xerxes_Command_Collections
	{
		/**
		 * Redirects to oldest created collection by this user, or
     * if user has no collections, creates one using default
     * names, and redirects there. 
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
      // If supplied in URL, use that (for future API use). 
      $username = $objRequest->getProperty("username");
      if ( ! $username ) {
        // default to logged in user
        $username = $objRequest->getSession("username");
      }
      // Default names for if we have to create a new category.
      // Can be sent with HTTP request, otherwise we have hard coded defaults.
      $strNewSubject = $objRegistry->getConfig("default_collection_name", false, "My Saved Databases");
      $strNormalizedSubject = Xerxes_Data_Category::normalize($strNewSubject);
            

      
      // We can only do this if we have a real user (not temp user)
      if ($username == null || ! Xerxes_Framework_Restrict::isAuthenticatedUser( $objRequest )) {
          throw new Xerxes_AccessDeniedException("You must be logged in to use this function.");
      }
      
			
			$objData = new Xerxes_DataMap();
			//$arrResults = $objData->getUserCreatedCategories($username, "id");
      $arrResults = $objData->getUserCreatedCategories($username);
      
      // find the default one, if present. 
      $redirectCategory = null;
      for($i = 0 ; $i < count($arrResults); $i++) {
        $iCat = $arrResults[$i];
        if ( $iCat->normalized == $strNormalizedSubject ) {
          $redirectCategory = $iCat;
          break;
        }
      }
      
      // Couldn't find it? Have to make one. 
      if ( empty($redirectCategory)) {
        //Create one
        $redirectCategory = $this->addDefaultCollection($objData, $username);

      }
      /*  This doesn't work right yet, although it's a nice idea. 
      else {
        // Okay, let's make sure our default category has at least one
        // section, which it always ought to, but data corruption sometimes,
        // and we can fix it up. Got to refetch it to get it's subcategories.
        
        $redirectCategory = $objData->getSubject( $redirectCategory->normalized, null, Xerxes_DataMap::userCreatedMode, $redirectCategory->username);
        
        if ( count($redirectCategory->subcategories) == 0) {
          // add the default one 
          $this->addDefaultSubcategory($objData, $redirectCategory);
        }
      }*/
       
            

      
      // And redirect
      $url = $objRequest->url_for( array( 'base' => 'collections',
                                          'action' => 'subject',
                                          'username' => $username,
                                          'subject' => $redirectCategory->normalized));
      $objRequest->setRedirect( $url );

        
			return 1;
		}
    
    protected function addDefaultCollection($objData, $username) {
        $strNewSubject = $this->registry->getConfig("default_collection_name", false, "My Saved Databases");
        $strNormalizedSubject = Xerxes_Data_Category::normalize($strNewSubject);

        

        
      
      
        $redirectCategory = new Xerxes_Data_Category();
        $redirectCategory->name = $strNewSubject;
        $redirectCategory->username = $username;
        $redirectCategory->normalized = $strNormalizedSubject;
        $redirectCategory->published = 0;
        $newCollection = $objData->addUserCreatedCategory($redirectCategory);
        
        //And give it a section
        $this->addDefaultSubcategory($objData, $newCollection);
        
        return $newCollection;
    }

    protected function addDefaultSubcategory($objData, $objCategory) {
        $strNewSubcategory = $this->registry->getConfig("default_collection_section_name", false, "Databases");
      
        $subcategory = new Xerxes_Data_Subcategory();
        $subcategory->name = $strNewSubcategory;
        $subcategory->category_id = $objCategory->id;        
        $subcategory->sequence = 1;
        $subcategory = $objData->addUserCreatedSubcategory($subcategory);
        
        return $subcategory;
    }
    
	}	
?>
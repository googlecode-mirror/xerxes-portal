<?php	
	
	/**
	 * Extra action you can add on the end to DatabasesDatabases (when used in a
   * search type action) that will redirect to /databases/database when
   * there's only one hit. 
   * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_DatabasesRedirectOneResult extends Xerxes_Command_Databases
	{
		/**
     *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int	status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
      // Sorry, unneccesary conversion to xml. 
			$xml = simplexml_import_dom($objRequest->toXML());
      
      $databases = $xml->databases->database;
      
      if ( count( $databases) == 1 ) {
        $id = $databases[0]->metalib_id;
        $url = $objRequest->url_for( array( "base" => "databases",
                                              "action" => "database",
                                              "id" => $id));
        $objRequest->setRedirect( $url );
      }
      

      
      /*$dbs =  $xml->getElementsByTagname("database");
      if ( $dbs->length == 1) {
        // redirect
        $id = $dbs->item(0)->getElementsByTagname("metalib_id")->item(0);
        var_dump($id);
        $url = $objRequest->url_for( array( "base" => "databases",
                                            "action" => "database",
                                            "id" => "foo"));
      }*/
		}
	}	
?>
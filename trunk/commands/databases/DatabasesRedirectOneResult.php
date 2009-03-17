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
	public function doExecute()
	{
		// Sorry, unneccesary conversion to xml. 
		
		$xml = simplexml_import_dom( $this->request->toXML() );
		
		$databases = $xml->databases->database;
		
		if ( count( $databases ) == 1 )
		{
			$id = $databases[0]->metalib_id;
			$url = $this->request->url_for( array ("base" => "databases", "action" => "database", "id" => $id ) );
			$this->request->setRedirect( $url );
		}
	}
}
?>
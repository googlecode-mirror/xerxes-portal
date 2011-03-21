<?php

/**
 * Framework Router
 * 
 * @author David Walker
 * @copyright 2011 California State University
 * @version $Id$
 * @package Xerxes
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 */

class Xerxes_Framework_Router
{
	public function getCommands( Xerxes_Framework_ControllerMap $controller_map )
	{
		// the data will be built-up by calling one or more command classes
		// which will fetch their data based on other parameters supplied in
		// the request; returning that data as xml to a master xml dom document
		// inside the Xerxes_Framework_Request class, or in some cases specififying 
		// a url to redirect the user out
		
		$commands = $controller_map->getCommands();
		
		foreach ( $commands as $arrCommand )
		{
			$strDirectory = $arrCommand[0]; // directory where the command class is located
			$strNamespace = $arrCommand[1]; // prefix namespace of the command class
			$strClassFile = $arrCommand[2]; // suffix name of the command class
						
			// directory where commands live
			
			$command_path = ( XERXES_APPLICATION_PATH . "commands/$strDirectory" );
			
			// allow for a local override, even
			
			$local_command_path = "commands/$strDirectory";
			
			// echo "<h3>$strClassFile</h3>";

			// if the specified command class exists in the distro or local commands folder, then
			// instantiate an object and execute it

			$strClass = $strNamespace . "_Command_" . $strClassFile;
			
			$local_command = file_exists( "$local_command_path/$strClassFile.php" );
			
			if ( file_exists( "$command_path/$strClassFile.php" ) || $local_command )
			{
				// if the instance has a local version, take it!
				
				if ( $local_command )
				{
					require_once ("$local_command_path/$strClassFile.php");
				}
				else
				{
					require_once ("$command_path/$strClassFile.php");
				}
				
				// instantiate the command class and execute it, but only
				// if it extends xerxes_framework_command

				$objCommand = new $strClass( );
				
				if ( $objCommand instanceof Xerxes_Framework_Command )
				{
					$objCommand->execute();
				} 
				else
				{
					throw new Exception( "command classes must be instance of Xerxes_Framework_Command" );
				}
			} 
		}		
	}
}

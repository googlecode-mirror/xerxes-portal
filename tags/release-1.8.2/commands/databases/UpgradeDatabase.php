<?php

	/**
	 * Upgrade KB tables
	 * 
	 * @author David Walker
	 * @copyright 2010 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id$
	 * @package Xerxes
	 * @uses Xerxes_Framework_Parser
	 * @uses lib/xslt/marc-to-database.xsl
	 */

	class Xerxes_Command_UpgradeDatabase extends Xerxes_Command_Databases
	{
		public function doExecute()
		{
			$username = $this->request->getProperty("username");
			$password = $this->request->getProperty("password");
			
			if ( $username == null ) throw new Exception("you must supply username paramater");
			if ( $password == null ) throw new Exception("you must supply a password paramater");
			
			echo "\n\nUPGRADE DATABASE \n\n";
			
			echo "  Executing KB upgrade . . . ";
			
			$data = new Xerxes_DataMap(null, $username, $password);
			$data->upgradeKB();
			
			echo "done!\n";
		}
	}

?>
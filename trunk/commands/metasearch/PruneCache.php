<?php

	/**
	 * Remove old items from the cache
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */

	class Xerxes_Command_PruneCache extends Xerxes_Command_Metasearch
	{
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			$objDataMap = new Xerxes_DataMap();
			$status = $objDataMap->clearCache();

			if ( $status != 1 )
			{
				throw new Exception("could not prune the cache");
			}
	   	}
	}

?>
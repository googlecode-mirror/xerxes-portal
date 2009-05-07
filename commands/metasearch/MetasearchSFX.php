<?php	
	
	/**
	 * Redirects the user to SFX with OpenURL for record
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_MetasearchSFX extends Xerxes_Command_Metasearch
	{
		public function doExecute()
		{
			$configLinkResolver = $this->registry->getConfig("LINK_RESOLVER_ADDRESS", true);
			$configSID = $this->registry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
			
			$objXerxesRecord = new Xerxes_Record();
			$objXerxesRecord->loadXML($this->getRecord());
      
			$this->request->setRedirect($objXerxesRecord->getOpenURL($configLinkResolver, $configSID));
			
			return 1;
		}
	}

?>
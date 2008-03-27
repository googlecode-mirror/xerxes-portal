<?php	
	
	/**
	 * Pull down the SFX institutional holdings file and populate the local 
	 * database with its information
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_PopulateFullText extends Xerxes_Command_Availability
	{
		/**
		 * Populate the local database with data from SFX
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int		status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{			
			echo "\n\nSFX INSTITUTIONAL HOLDINGS POPULATION \n\n";
			
			
			// You can define the export file on sfx as having an instance extension, so
			// give the client the opportunity to define that here
			
			$strInstance = $objRequest->getProperty("instance");
			if ( $strInstance != "" ) $strInstance = "-" . $strInstance;
			
			// construct the address to Google Scholar institutional 
			// holdings file on SFX
			
			$configSfx = $objRegistry->getConfig("LINK_RESOLVER_ADDRESS", true);
			
			$strUrl = $configSfx . "/cgi/public/get_file.cgi?file=institutional_holding" . $strInstance . ".xml";
			
			// fire-up a transaction with the database
			
			$objData = new Xerxes_DataMap();
			$objData->beginTransaction();
			
			
			echo "  Flushing SFX fulltext table . . . ";
			
			$objData->clearFullText();
			
			echo "done.\n";
			
			echo "  Pulling down SFX inst holdings file . . . ";
			
			try
			{
				$objXml = new SimpleXMLElement($strUrl, null, true);
			}
			catch (Exception $e)
			{
				throw new Exception("cannot get institutional holding file from sfx: '$strUrl'");
			}
			
			echo "done.\n";
			echo "  Processing file . . . ";
			
			$objItems = $objXml->xpath("//item");
			
			if ( $objItems == false ) throw new Exception("could not find items in inst holding file.");
			
			echo "done.\n";
			echo "  Adding to database . . . ";
			
			foreach ( $objItems as $objItem )
			{
				foreach ( $objItem->coverage as $objCoverage )
				{
					$objFullText = new Xerxes_Data_Fulltext();
					
					$objFullText->issn = (string) $objItem->issn;
					$objFullText->issn = str_replace("-","",$objFullText->issn);
					
					$objFullText->title = (string) $objItem->title;
					$objFullText->title = preg_replace("/\W/","",$objFullText->title);
					$objFullText->title = substr(strtolower($objFullText->title),0, 100);
					
					$objFullText->startdate = (int) $objCoverage->from->year;
					
					$objFullText->enddate = (int) $objCoverage->to->year;
					if ( $objFullText->enddate == 0 ) $objFullText->enddate = 9999;
					
					$objFullText->embargo = (int) $objCoverage->embargo->days_not_available;
					$objFullText->updated = date("YmdHis");
					
					// add it to the database
					
					$objData->addFulltext($objFullText);
				}
			}
			
			echo "done.\n";
			echo "  Commiting changes . . . ";
			
			$objData->commit();
			
			echo "done.\n";
			
			return 1;
		}
	}	
?>
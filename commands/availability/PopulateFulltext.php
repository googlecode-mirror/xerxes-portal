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
		public function doExecute()
		{
      		$configMemory = $this->registry->getConfig("HARVEST_MEMORY_LIMIT", false, "500M");
			ini_set("memory_limit",$configMemory);
			
			echo "\n\nSFX INSTITUTIONAL HOLDINGS POPULATION \n\n";
			
			// You can define the export file on sfx as having an instance extension, so
			// give the client the opportunity to define that here
			
			$strInstance = $this->request->getProperty("instance");
			if ( $strInstance != "" ) $strInstance = "-" . $strInstance;
			
			// construct the address to Google Scholar institutional 
			// holdings file on SFX. Either SFX specific config, or
			// general link resolver config. 
			
			$configSfx = $this->registry->getConfig( "ALTERNATE_FULLTEXT_HARVEST_ADDRESS", false, $this->registry->getConfig( "LINK_RESOLVER_ADDRESS", false ) );

			if ( ! $configSfx )
			{
				throw new Exception( "Can not run populate action, no link resolver address configured. " .
					"Need config ALTERNATE_FULLTEXT_HARVEST_ADDRESS or LINK_RESOLVER_ADDRESS." );
			}
			
			$strUrl = $configSfx . "/cgi/public/get_file.cgi?file=institutional_holding" . $strInstance . ".xml";
			
			// fire-up a transaction with the database
			
			$objData = new Xerxes_DataMap();
			$objData->beginTransaction();
			
			echo "  Flushing SFX fulltext table . . . ";
			
			$objData->clearFullText();
			
			echo "done.\n";
			
			echo "  Pulling down SFX inst holdings file . . . ";
			
			// try to get the data from sfx
			
			try
			{
				$strResponse = Xerxes_Parser::request($strUrl);
				$objXml = new SimpleXMLElement($strResponse);
			}
			catch (Exception $e)
			{
				throw new Exception("cannot get institutional holding file from sfx: '$strUrl'. " .
					"If this is the correct SFX server address, make sure your SFX allows access to " .
					"institutional holding file from this IP address in config/get_file_restriction.config " .
					"on SFX server.");
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
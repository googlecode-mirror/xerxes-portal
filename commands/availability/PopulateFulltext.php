<?php	
	
	/**
	 * Pull down the SFX institutional holdings file and populate the local 
	 * database with its information
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id$
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
			
			
			// fire-up a transaction with the database
				
			$objData = new Xerxes_DataMap();
			$objData->beginTransaction();
			
			// clear old data
			
			echo "  Flushing SFX fulltext table . . . ";
				
			$objData->clearFullText();
				
			echo "done.\n";			
			
			
			// try to get the data from sfx
			
			
			
			$done = false;
			$x = 0;
			
			while ( $done == false )
			{
				$x++;
				
				$strUrl = $configSfx . "/cgi/public/get_file.cgi?file=institutional_holding" . 
					$strInstance . '-.prt' . str_pad($x, 2, '0', STR_PAD_LEFT) . ".xml";
				
	
				
				echo "  Pulling down SFX inst holding file ($x) . . . ";
				
				try
				{
					$strResponse = Xerxes_Framework_Parser::request($strUrl);
					$objXml = new SimpleXMLElement($strResponse);
				}
				catch (Exception $e)
				{
					if ( $x == 1) // first time, must be error
					{
						throw new Exception("cannot get institutional holding file from sfx: '$strUrl'. " .
							"If this is the correct SFX server address, make sure your SFX allows access to " .
							"institutional holding file from this IP address in config/get_file_restriction.config " .
							"on SFX server.");
					}
					
					$done = true;
				}
				
				echo "done.\n";
				
				if ( ! $done )
				{
					echo "  Processing file . . . ";
					
					$objItems = $objXml->xpath("//item[@type != 'other']");
					
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
							$objFullText->title = urlencode($objFullText->title);
							$objFullText->title = substr(Xerxes_Framework_Parser::strtolower($objFullText->title),0, 100);
							
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
				}
			}
			
			echo "  Commiting changes . . . ";
			
			$objData->commit();
			
			echo "done.\n";
			
			return 1;
		}
	}	
?>
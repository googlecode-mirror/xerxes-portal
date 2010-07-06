<?php	
	
	/**
	 * Import peer reviewed data
	 * 
	 * @author David Walker
	 * @copyright 2010 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id$
	 * @package Xerxes
	 */
	
	class Xerxes_Command_PopulateRefereed extends Xerxes_Command_Availability
	{
		public function doExecute()
		{
      		$configMemory = $this->registry->getConfig("HARVEST_MEMORY_LIMIT", false, "500M");
			ini_set("memory_limit",$configMemory);
			
			echo "\n\nPEER REVIEWED DATA POPULATION \n\n";

			$objData = new Xerxes_DataMap();	

			$file = $this->registry->getConfig("PATH_PARENT_DIRECTORY") . "/lib/data/refereed.txt";
			
			
			### dump
			
			// this is a dump, really only used by david to 
			// generate the file to begin with
			
			if ( $this->request->getProperty("dump") != null )
			{
				echo "Dumping peer reviewed data . . . ";
				
				$data = $objData->getAllRefereed();
				$output = "";
				
				foreach ( $data as $title )
				{
					$output .= "\n" . $title->issn . "\t" . $title->title . "\t" . $title->timestamp;
				}
				
				file_put_contents($file, $output);
				
				echo "done.\n";
				
				return 1;
			}
			
			
			### load
			
			echo "Getting data from refereed file . . . ";
			
			$file_data = file_get_contents($file);
						
			if ( $file_data === false )
			{
				throw new Exception("Could not find a refereed data file at $file");
			}

			echo "done.\n";
			

			$objData->beginTransaction();
			
			if ( $this->request->getProperty("no-flush") == null )
			{
				echo "Flushing old refereed data . . . ";
				$objData->flushRefereed();
				echo "done.\n";
			}
			
			echo "Adding new refereed data . . . ";
			
			$titles = explode("\n", $file_data);
			
			$x = 0;
			
			foreach ( $titles as $title )
			{
				$arrTitle = explode("\t", $title);
				
				if ( count($arrTitle) == 3 )
				{
					$object = new Xerxes_Data_Refereed();
					$object->issn = trim($arrTitle[0]);
					$object->title = trim($arrTitle[1]);
					$object->timestamp = trim($arrTitle[2]);
					
					$x++;
					$objData->addRefereed($object);
				}
			}
			
			echo "done.\n";
			
			echo "Added $x titles.\n";
			
			echo "Committing changes . . . ";
			
			$objData->commit();
			
			echo "done.\n";
			
			return 1;
		}
	}	
?>
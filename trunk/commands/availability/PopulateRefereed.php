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
			
			echo "Getting data from refereed file . . . ";
			
			$file = file_get_contents("refereed.txt");
						
			if ( $file === false )
			{
				throw new Exception("no refereed file, yo!");
			}

			echo "done.\n";
			
			$objData = new Xerxes_DataMap();
			$objData->beginTransaction();
			
			if ( $this->request->getProperty("no-flush") == null )
			{
				echo "Flushing old refereed data . . . ";
				$objData->flushRefereed();
			}
			
			echo "done.\n";
			
			echo "Adding new refereed data . . . ";
			
			$titles = explode("\n", $file);
			
			foreach ( $titles as $title )
			{
				$arrTitle = explode("\t", $title);
				
				if ( count($arrTitle) == 2 )
				{
					$object = new Xerxes_Data_Refereed();
					$object->issn = trim($arrTitle[0]);
					$object->title = trim($arrTitle[1]);
					
					$objData->addRefereed($object);
				}
			}
			
			echo "done.\n";
			
			echo "Committing changes . . . ";
			
			$objData->commit();
			
			echo "done.\n";
			
			return 1;
		}
	}	
?>
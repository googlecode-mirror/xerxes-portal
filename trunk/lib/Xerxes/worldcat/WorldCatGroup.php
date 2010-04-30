<?php

/**
 * Worldcat group processor
 *
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_WorldCatGroup
{
	public $source;
	public $type;
	public $libraries_include;
	public $libraries_exclude;
	public $lookup_address;
	public $limit_material_types;
	public $exclude_material_types;
	public $show_holdings = false;
	public $query_limit;

	public function __construct($strSource, $xml)
	{
		$this->source = $strSource;
		
		$groups = $xml->xpath("//worldcat_groups/group");
		
		if ( $groups != false )
		{
			foreach ( $groups as $group )
			{
				if ( $group["id"] == $strSource )
				{
					$this->libraries_include = (string) $group->libraries;
					$this->limit_material_types = (string) $group->limit_material_types;
					$this->exclude_material_types = (string) $group->exclude_material_types;
					$this->frbr = (string) $group->frbr;
					
					if ( (string) $group->show_holdings == "true" )
					{
						$this->show_holdings = true;
					}
	
					// exclude certain libraries?
					
					$id = (string) $group->exclude;
					$this->query_limit = (string) $group->query_limit;
				
					if ( $id != "" )
					{
						$arrID = explode(",", $id);
						
						foreach ( $arrID as $strID )
						{
							foreach ( $xml->xpath("//worldcat_groups/group[@id='$strID']/libraries") as $exclude )
							{
								if ( $this->libraries_exclude != null )
								{
									$this->libraries_exclude .= "," . (string) $exclude;
								}
								else
								{
									$this->libraries_exclude = (string) $exclude;
								}
							}
						}
					}
					
					if ( $group->lookup )
					{
						$this->type = (string) $group->lookup["type"];
						$this->lookup_address = (string) $group->lookup->address;
						$this->availability_messages = (string) $group->lookup->availability_messages;
						$this->locations_excluded = (string) $group->lookup->locations_excluded;
					}
				}
			}
		}
	}
}

?>
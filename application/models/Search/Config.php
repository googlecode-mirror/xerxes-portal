<?php

/**
 * Search Config
 *
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

abstract class Xerxes_Model_Search_Config extends Xerxes_Framework_Registry
{
	private $facets = array();
	private $fields = array();
	
	public function init()
	{
		parent::init();
		
		// facets
		
		$facets = $this->xml->xpath("//config[@name='facet_fields']/facet");
		
		if ( $facets !== false )
		{
			foreach ( $facets as $facet )
			{
				$this->facets[(string) $facet["internal"]] = $facet;
			}
		}
		
		// fields
		
		$fields = $this->xml->xpath("//config[@name='basic_search_fields']/field");
		
		if ( $fields !== false )
		{
			foreach ( $fields as $field )
			{
				$this->fields[(string) $field["internal"]] = (string) $field["public"];
			}
		}
	}
	
	public function getFacetPublicName($internal)
	{
		if ( array_key_exists($internal, $this->facets) )
		{
			$facet = $this->facets[$internal];
			
			return (string) $facet["public"]; 
		}
		else
		{
			return null;
		}
	}

	public function getValuePublicName($internal_group, $internal_field)
	{
		if ( strstr($internal_field, "'") || strstr($internal_field, " ") )
		{
			return $internal_field;
		}
		
		$query = "//config[@name='facet_fields']/facet[@internal='$internal_group']/value[@internal='$internal_field']";
		
		$values = $this->xml->xpath($query);
		
		if ( count($values) > 0 )
		{
			return (string) $values[0]["public"];
		}
		else
		{
			return $internal_field;
		}
	}	
	
	public function getFacetType($internal)
	{
		$facet = $this->getFacet($internal);
		return (string) $facet["type"];
	}
	
	public function isDateType($internal)
	{
		if ( $this->getFacetType($internal) == "date" )
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function getFacet($internal)
	{
		if ( array_key_exists($internal, $this->facets) )
		{
			return $this->facets[$internal];
		}
		else
		{
			return null;
		}
	}	
	
	public function getFacets()
	{
		return $this->facets;
	}
	
	public function getFields()
	{
		return $this->fields;
	}
	
	public function getFieldAttribute($field,$attribute)
	{
		$values = $this->xml->xpath("//config[@name='basic_search_fields']/field[@internal='$field']/@$attribute");
		
		if ( count($values) > 0 )
		{
			return (string) $values[0];
		}
		else
		{
			return null;
		}
	}

	/**
	 * Swap the sort id for the internal sort option
	 * 
	 * @param string $id 	public id
	 * @return string 		the internal sort option
	 */
	
	public function swapForInternalSort($id)
	{
		$config = $this->getConfig("sort_options");
		
		if ( $config != null )
		{
			foreach ( $config->option as $option )
			{
				if ( (string) $option["id"] == $id )
				{
					return (string) $option["internal"];
				}
			}			
		}
		
		// if we got this far no mapping, so return original
		
		return $id; 
	}

	/**
	 * Swap the field id for the internal field index
	 * 
	 * @param string $id 	public id
	 * @return string 		the internal field
	 */	
	
	public function swapForInternalField($id)
	{
		$config = $this->getConfig("basic_search_fields");
		
		if ( $config != null )
		{
			foreach ( $config->field as $field )
			{
				$field_id = (string) $field["id"];
				
				if ( $field_id == "")
				{
					continue;
				}
				
				// if $id was blank, then we take the first
				// one in the list, otherwise, we're looking 
				// to match
				
				elseif ( $field_id == $id || $id == "")
				{
					return (string) $field["internal"];
				}
			}			
		}
		
		// if we got this far no mapping, so return original
		
		return $id; 
	}

	/**
	 * The options for the sorting mechanism
	 * 
	 * @return array
	 */
	
	protected function sortOptions()
	{
		$options = array();
		
		$config = $this->config->getConfig("sort_options");
		
		if ( $config != null )
		{
			foreach ( $config->option as $option )
			{
				$options[(string)$option["id"]] = (string) $option["public"];
			}
		}
		
		return $options;
	}	
}

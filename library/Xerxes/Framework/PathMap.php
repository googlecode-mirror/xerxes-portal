<?php

/**
 * Keeps track of mapping path components to query properties on an
 * action by action basis. Used only when pretty uris are turned on.
 * Usually used by ControllerMap, and not accessed directly by any 
 * other code. Gets mappings from actions.xml.
 * 
 * Caches answer for length of life of ControllerMap/PathMap, but that's
 * currently just life of a request. This works well enough it looks like. 
 * 
 * @author Jonathan Rochkind
 * @copyright 2008 Johns Hopkins University
 * @version $Id: PathMap.php 1768 2011-03-05 21:23:20Z dwalker@calstate.edu $
 * @package  Xerxes_Framework
 * @license http://www.gnu.org/licenses/
 *
 */

class Xerxes_Framework_PathMap
{
	private $actions_xml = null; // simplexml object containing instructions for the actions
	private $mapsByProperty = array(); // array keyed by section name or "section/action"
	                                   // value is an array mapping properties (key) to path indexes (value) 
	private $mapsByIndex = array();	 // array keyed by section name or "section/action"
	                                 // value is an array mapping path indexes (key) to properties (value) 
	
	/**
	 * Constructor
	 * 
	 * @param SimpleXML $actions_xml_arg		SimpleXML object of actions.xml directives
	 */
	
	public function __construct(SimpleXMLElement $actions_xml_arg)
	{ 
		$this->actions_xml = $actions_xml_arg;
	}
	
	/**
	 * Retrieve an array of paramater-name-to-path-index mappings for a given action
	 *
	 * @param string $section		the section to find the action
	 * @param string $action		the specific action being called
	 * @return array				array in form of [paramater_name] => position_in_path
	 */
	
	public function propertyToIndexMap($section, $action)
	{
		$key_name = "$section/$action";
		
		if (! array_key_exists($key_name, $this->mapsByProperty) )
		{
			$this->buildMapForAction( $section, $action );
		}
		
		return $this->mapsByProperty[$key_name];
	}

	/**
	 * Retrieve an individual path-index for a given action's paramater-name
	 *
	 * @param string $section			the section to find the action
	 * @param string $action			the specific action being called
	 * @param string $property_name		property name
	 * @return mixed					[int] path index number or [null] if no mapping exists
	 */
	
	public function indexForProperty($section, $action, $property_name)
	{
		$map = $this->propertyToIndexMap($section, $action);
				 
		if ( array_key_exists($property_name, $map) )
		{
			return $map[$property_name];
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * Retrieve an array of path-index-to-parameter-name mappings for a given action
	 *
	 * @param string $section		the section to find the action
	 * @param string $action		the specific action being called
	 * @return array				array in form of [position_in_path] => paramater_name
	 */
	
	public function indexToPropertyMap($section, $action)
	{
		$key_name = "$section/$action";
		
		if (! array_key_exists($key_name, $this->mapsByIndex ) )
		{
			$this->buildMapForAction( $section, $action );
		}
		
		return $this->mapsByIndex[$key_name];
	}
	
	/**
	 * Retrieve an individual paramater-name for a given action's path-index
	 *
	 * @param string $section		the section to find the action
	 * @param string $action		the specific action being called
	 * @param int $path_index		the 0-based numbered index of the path
	 * @return mixed				[string] paramater name or [null] if no mapping exists
	 */
	
	public function propertyForIndex($section, $action, $path_index)
	{
		$map = $this->indexToPropertyMap($section, $action);

		if ( array_key_exists($path_index, $map) )
		{
			return $map[$path_index];
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * Does the actual work of extracting the pathIndex mappings
	 *
	 * @param string $section		the section to find the action
	 * @param string $action		the specific action being called
	 * @return null					extracts the values to member variables
	 */
	
	private function buildMapForAction($section, $action)
	{
		$map_xml = null;
		$key_name = "$section/$action";

		// if no configed path param, empty array will be stored, good.
		
		$this->mapsByProperty[$key_name] = array();
		$this->mapsByIndex[$key_name] = array();
				 
		// section may supply a default param-map for the section. 

		$section_paths = $this->actions_xml->xpath("//commands/section[@name='$section']/pathParamMap");
		
		// action may provide a param-map as well
		
		$action_paths = $this->actions_xml->xpath("//commands/section[@name='$section']/action[@name='$action']/pathParamMap");
		
		// assign the default section map if present
		
		if ( $section_paths != false )
		{
			$map_xml = $section_paths[0];
			
			foreach ($map_xml->mapEntry as $map_entry)
			{
				$iIndex = (integer) $map_entry['pathIndex'];
				$strProperty = (string) $map_entry['property'];
				
				$this->mapsByProperty[$key_name][$strProperty] = $iIndex;
				$this->mapsByIndex[$key_name][$iIndex] = $strProperty;
			}
		}
		
		// and add the local map on top if also present. Important that
		// action overrides section, not replaces! 
		
		if ( $action_paths != false )
		{
			$map_xml = $action_paths[0];
			
			foreach ($map_xml->mapEntry as $map_entry)
			{
				$iIndex = (integer) $map_entry['pathIndex'];
				$strProperty = (string) $map_entry['property'];
				
				// remove conflicting session declerations
				
				$oldProperty = $this->propertyForIndex($section, $action, $iIndex);
				
				if ($oldProperty) unset($this->mapsByProperty[$key_name][$oldProperty]);
				$oldIndex = $this->indexForProperty($section, $action, $strProperty);
				if ($oldIndex) unset($this->mapsByIndex[$key_name][$oldIndex]);
				
				// and add new ones
				
				$this->mapsByProperty[$key_name][$strProperty] = $iIndex;
				$this->mapsByIndex[$key_name][$iIndex] = $strProperty;
			}
		}
	}
}

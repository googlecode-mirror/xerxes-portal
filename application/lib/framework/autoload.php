<?php

set_include_path(get_include_path() . PATH_SEPARATOR . realpath("../")); // path to application root

$xerxes_namespaces = array();

/**
 * Import a namespace into the application
 *
 * @param string $namespace	namespace prefix		
 * @param string $location	path from application root to the files
 */

function import($namespace, $location)
{
	global $xerxes_namespaces;
	$xerxes_namespaces[$location] = $namespace;
	uasort($xerxes_namespaces,'xerxes_namespace_sort');
}

/**
 * Sort array by size of values
 */

function xerxes_namespace_sort($a,$b)
{
    return strlen($b)-strlen($a);
}

/**
 * Autoloader
 *
 * @param string $class_name	the name of the class
 */

function __autoload($class_name)
{
	global $xerxes_namespaces;
	
	$file_location = "";
	$found = false;
	
	foreach ( $xerxes_namespaces as $location => $namespace  )
	{
		// if the first part of the class name matches the first part
		// of one of our registerd namespaces, take that location
		
		$length = strlen($namespace);
		
		if ( strlen($class_name) >= $length )
		{
			if ( substr($class_name,0,$length) == $namespace )
			{
				// set the location in place of the namespace
				
				$file_location = $location . substr($class_name,$length);
				$found = true;
				break;
			}
		}
	}
	
	if ( $found == false )
	{
		throw new Exception("The class '$class_name' does not appear to be part of any registered namespaces.");
	}
	
	// now everthing after the prefix should map to the file system
	
	$pieces = explode("_", $file_location);
	$file_location = implode("/", $pieces);
	
	// and add .php if not specifically defined in location
	
	if ( ! strpos($file_location, '.php') )
	{
		$file_location .= '.php';
	}
	
	// try to include it
	
	@include_once $file_location;
	
	// nope, so try the stub
	
	if ( ! class_exists($class_name) )
	{
		// could be a secondary class in the file, so check the stub
		// form of the name
		
		array_pop($pieces);
		$stub_file_location = implode("/", $pieces) . ".php";
		
		// now try that
		
		@include_once $stub_file_location;
		
		// double-nopes!!
		
		if ( ! class_exists($class_name) )
		{
			throw new Exception("Could not find a file for the class '$class_name'");
		}
	}
}


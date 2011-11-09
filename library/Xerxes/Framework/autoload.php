<?php

// register the autoload function 

spl_autoload_register('xerxes_autoload');

// paths

$xerxes_root = realpath(dirname(__FILE__));

$xerxes_root = explode(DIRECTORY_SEPARATOR, $xerxes_root);
array_pop($xerxes_root);
array_pop($xerxes_root);
array_pop($xerxes_root);
$xerxes_root = implode(DIRECTORY_SEPARATOR, $xerxes_root);

define('XERXES_APPLICATION_PATH', $xerxes_root . DIRECTORY_SEPARATOR . "application" . DIRECTORY_SEPARATOR);
define('XERXES_LIBRARY_PATH', $xerxes_root . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR);

set_include_path( XERXES_LIBRARY_PATH . PATH_SEPARATOR . XERXES_APPLICATION_PATH . PATH_SEPARATOR . get_include_path() );

/**
 * The global namespace array
 */

global $xerxes_namespaces;
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
	$xerxes_namespaces[$namespace] = $location;
	uksort($xerxes_namespaces,'xerxes_namespace_sort');
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

function xerxes_autoload($class_name)
{
	global $xerxes_namespaces;
	
	$file_location = "";
	$found = false;
	
	foreach ( $xerxes_namespaces as $namespace => $location )
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
		$file_location = $class_name;
	}
	
	// now everthing after the prefix should map to the file system
	
	require_once( str_replace('_', '/', "$file_location.php") );
}

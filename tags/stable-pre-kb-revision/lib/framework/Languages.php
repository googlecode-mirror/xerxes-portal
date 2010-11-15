<?php

/**
 * Parses and holds information about language codes and names
 *
 * @author Ivan Masar
 * @copyright 2010 Ivan Masar
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package  Xerxes_Framework
 */

class Xerxes_Framework_Languages
{
	protected $xpath = "";		// language data we can query
	protected $gettext = false; // whether gettext is installed
	protected $languages_file_system = "/usr/share/xml/iso-codes/iso_639.xml";
	protected $languages_file_xerxes = "../data/iso_639.xml"; // local version
	protected $locale = "C";	// default locale
	protected $domain = "iso_639";	// gettext domain
	private static $instance;	// singleton pattern
	
	protected function __construct()
	{
	}
	
	/**
	 * Get an instance of the file; Singleton to ensure correct data
	 *
	 * @return Xerxes_Framework_Languages
	 */
	
	public static function getInstance()
	{
		if ( empty( self::$instance ) )
		{
			self::$instance = new Xerxes_Framework_Languages( );
		}
		
		return self::$instance;
	}
	
	/**
	 * Initialize the object by picking up and processing the ISO 639 xml file
	 * 
	 * @exception 	will throw exception if no file can be found
	 */
	
	public function init()
	{
		// first, see if Getttext functions are installed
		
		if ( function_exists( 'bindtextdomain' ) )
		{
			$this->gettext = true;
			
			// windows hack
			
			if ( ! defined("LC_MESSAGES") )
			{
				define("LC_MESSAGES", 6);
			}
		}
			
		$objRegistry = Xerxes_Framework_Registry::getInstance();
				
		// set full path to local copy
		
		$this->languages_file_xerxes = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . $this->languages_file_xerxes);
		
		// if the iso-codes is not installed, use our copy
		
		$file = "";
		
		if ( file_exists( $this->languages_file_system ) )
		{
			$file = $this->languages_file_system;
		}
		elseif ( file_exists( $this->languages_file_xerxes) )
		{
			$file = $this->languages_file_xerxes;
		}
		else
		{
			throw new Exception( "could not find file with the ISO 639 language list" );
		}
		
		// load the languages file
		
		$xml = new DOMDocument();
		$xml->load( $file );
		
		$this->xpath = new DOMXPath( $xml );
		
		unset($xml);
		
		// which language shall we display?
		
		$this->locale = $objRegistry->getConfig( 'XERXES_LOCALE', false, 'C' );
		
		// bindings
		
		if ( $this->gettext == true )
		{
			bindtextdomain( $this->domain, '/usr/share/locale' ); // this works on windows too?
			bind_textdomain_codeset( $this->domain, 'UTF-8' );	// assume UTF-8, all the .po files in iso_639 use it
			textdomain( $this->domain );
		}
	}
	
	/**
	 * Get localized language name of provided ISO 639 code
	 *
	 * @param string $type			the standard according to which the code will be interpreted;
	 * 					one of: iso_639_1_code, iso_639_2B_code
	 * @param string $code			the 2-letter language code
	 * @return mixed  A string with the loaclized language name or NULL if the code is not valid
	 */
	
	public function getNameFromCode( $type, $code )
	{
		if ($type != 'name')
		{
			$code = Xerxes_Framework_Parser::strtolower( $code );
		}
		
		$elements = $this->xpath->query( "//iso_639_entry[@$type='$code']" ); 
		
		if ( ! is_null( $elements ) )
		{
			foreach ($elements as $element)
			{
				$name = $element->getAttribute( 'name' );
				
				if ( $this->gettext == false )
				{
					return $name;
				}
				
				$originalLocale = $this->getXerxesLocale();
				
				$this->setXerxesLocale( $this->locale );
				
				$languageName = dgettext( $this->domain, $name );
				
				$this->setXerxesLocale( $originalLocale );
				
				return $languageName;
			}
		}
		else
		{
			return null;
		}
	}
	
	public function getXML()
	{
		return $this->xml;
	}
	
	private function getXerxesLocale()
	{
		return setlocale( LC_MESSAGES, null );
	}
	
	private function setXerxesLocale( $locale )
	{
		$result = setlocale( LC_MESSAGES, $locale );
		
		return $result;
	}
}
?>

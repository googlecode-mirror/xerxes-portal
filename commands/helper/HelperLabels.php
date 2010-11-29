<?php

/**
 * Labels to JS
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_HelperLabels extends Xerxes_Command_Helper
{
	public function doExecute()
	{
		$parent = $this->registry->getConfig("PATH_PARENT_DIRECTORY");
		
		$xml = new DOMDocument();
		$xml->load("$parent/lib/xsl/labels/eng.xsl");
		
		if ( file_exists("xsl/labels/eng.xsl") )
		{
			$local_xml = new DOMDocument();
			$local_xml->load("xsl/labels/eng.xsl");
			$import = $xml->importNode($local_xml->documentElement, true);
			$xml->documentElement->appendChild($import);			
		}
		
		// if language is set to something other than english
		// then include that file to override the english labels

		$language = $this->registry->getConfig("XERXES_LANGUAGE");		
		
		if ( $language != "" )
		{
			$language_xml = new DOMDocument();
			$language_xml->load("$parent/lib/xsl/labels/$language.xsl");
			
			$import = $xml->importNode($language_xml->documentElement, true);
			$xml->documentElement->appendChild($import);

			if ( file_exists("xsl/labels/$language.xsl") )
			{
				$local_xml = new DOMDocument();
				$local_xml->load("xsl/labels/$language.xsl");
				$import = $xml->importNode($local_xml->documentElement, true);
				$xml->documentElement->appendChild($import);			
			}		
		}	
		
		$this->request->addDocument($xml);
	}
}
?>

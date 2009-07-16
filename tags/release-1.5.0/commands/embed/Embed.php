<?php

/**
 * Base class for embed commands, mostlyl used for embed generator. 
 *
 */

abstract class Xerxes_Command_Embed extends Xerxes_Framework_Command
{
	/**
	 * This static method has most of the actual logic for a snippet generator page.
	 * This mainly entails constructing and adding some URLs in an <embed> block
	 * used for pointing to various types of snippet inclusion.  Subclass defines 
	 * the starting url properties (base, action, anything else neccesary for a 
	 * snippet display), and calls this.
	 *
	 * @param array $url_params
	 * @param array $direct_url_params
	 * @return unknown
	 */
	
	protected function doExecuteHelper(Array $url_params, Array $direct_url_params = null)
	{
		// default embed css to false, because it's awful.
		
		if ( $this->request->getProperty( "disp_embed_css" ) == "" )
		{
			$this->request->setProperty( "disp_embed_css", "false" );
		}
		
		$objXml = new DOMDOcument( );
		$objXml->loadXML( "<embed_info />" );
		
		$properties = array_keys( $this->request->getAllProperties() );
		$display_properties = array ( );
		
		foreach ( $properties as $p )
		{
			if ( substr( $p, 0, 5 ) == 'disp_' )
			{
				array_push( $display_properties, $p );
			}
		}
		
		// direct to resource non-embedded action url
		
		if ( $direct_url_params )
		{
			$direct_url = $this->request->url_for( $direct_url_params, true );
			$objXml->documentElement->appendChild( $objXml->createElement( "direct_url", $direct_url ) );
		}
		
		// embedded urls

		$url_params["gen_full_urls"] = "true";
		
		// base embedded action url

		$raw_embedded_action_url = $this->request->url_for( $url_params, true );
		
		if ( strpos( $raw_embedded_action_url, '?' ) == 0 )
		{
			$raw_embedded_action_url .= "?";
		}
		$objXml->documentElement->appendChild( $objXml->createElement( "raw_embedded_action_url", $raw_embedded_action_url ) );
		
		// direct embed url
		
		$url_params["disp_embed"] = "true";
		
		foreach ( $display_properties as $p )
		{
			$url_params[$p] = $this->request->getProperty( $p );
		}
		
		$embed_ssi_url = $this->request->url_for( $url_params, true );
		
		$objXml->documentElement->appendChild( $objXml->createElement( "embed_direct_url", $embed_ssi_url ) );
		
		// now the js snippet url
		
		$url_params["format"] = "embed_html_js";
		$embed_js_call_url = $this->request->url_for( $url_params, true );
		$objXml->documentElement->appendChild( $objXml->createElement( "embed_js_call_url", $embed_js_call_url ) );
		$this->request->addDocument( $objXml );
		
		return 1;
	
	}
	
	/**
	 *  Call from within the XSLT view, to load  the embedable content as a sample. 
	 */
	
	public static function getEmbedContent($url)
	{
		$output = '';
		$handle = fopen( $url, 'r' );
		
		if ( ! $handle )
		{
			return "Error: Could not open content at $url";
		}
		
		while ( ! feof( $handle ) )
		{
			//read file line by line into variable
			$output = $output . fgets( $handle, 4096 );
		}
		fclose( $handle );
		return $output;
	}
	

}

?>
<?php

	/**
	 * Authenticates users and downloads data from the Innovative Patron API;
	 * based on the functions originally developed by John Blyberg
	 * 
	 * @author David Walker
	 * @author John Blyberg
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */

	class Xerxes_InnovativePatron
	{
		private $strServer = null;
		
		/**
		* Constructor
		*
		* @param string $strServer	server address of parton api, including port number and trailing slash
		*/
		
		public function __construct ( $strServer )
		{				
			$this->strServer = $strServer;
		}
		
		/**
		* Returns patron data from the API as array
		*
		* @param string $id 		barcode
		* @return array 			data returned by the api as associative array
		* @exception 				throws exception when iii patron api reports error
		*/
		
		public function getData( $id )
		{
			// normalize the barcode
			
			$id = str_replace(" ", "", $id);
			
			// fetch data from the api
			
			$url = $this->strServer . "PATRONAPI/$id/dump";
			$arrData = $this->getContent($url);
			
			// if something went wrong
			
			if ( array_key_exists("ERRMSG", $arrData ) )
			{
				throw new Exception($arrData["ERRMSG"]);	
			}

			return $arrData;
		}
	
		/**
		* Checks tha validity of a barcode / pin combo, essentially a login test
		*
		* @param string $id 	barcode
		* @param string $pin 	the pin to use with $id
		* @return bool			true if valid, false if not
		*/
		
		public function authenticate ( $id, $pin )
		{
			// normalize the barcode and pin
			
			$id = str_replace(" ", "", $id);
			$pin = str_replace(" ", "", $pin);
			
			// fetch data from the api

			$pin = urlencode($pin);
			$url = $this->strServer . "PATRONAPI/$id/$pin/pintest";
			$arrData = $this->getContent($url);
			
			// check pin test for error message, indicating
			// failure
			
			if ( array_key_exists("ERRMSG", $arrData ) )
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		
		/**
		* Fetches and normalize the API data
		*
		* @param string $url 	url of patron dump or pint test
		* @return array			patron data
		*/
		
		private function getContent( $url )
		{
			$arrRawData = array();
			$arrData = array();
			
			// get the data and strip out html tags
			
			$strResponse = Xerxes_Parser::request($url);
			$strResponse = trim(strip_tags($strResponse));
			
			if ( $strResponse == "" )
			{
				throw new Exception("Could not connect to Innovative Patron API");			
			}
			else
			{
				// cycle thru each line in the response, splitting each
				// on the equal sign into an associative array
				
				$arrRawData = explode("\n", $strResponse);
				
				foreach ($arrRawData as $strLine)
				{
					$arrLine = explode("=", $strLine);
					
					// strip out the code, leaving just the attribute name
					
					$arrLine[0] = preg_replace("/\[[^\]]{1,}\]/", "", $arrLine[0]);
					$arrData[trim($arrLine[0])] = trim( $arrLine[1] );
				}
			}
			
			return $arrData;
		}
	}

?>
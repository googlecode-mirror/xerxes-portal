<?php

	/**
	 * Utility class for displaying common page elements in XML
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package  Xerxes_Framework
	 * @uses Xerxes_Parser
	 */

	class Xerxes_Framework_Page
	{	
		/**
		 * Displays paged information (e.g., 11-20 of 34 results)
		 *
		 * @param int $iTotal 		total # of hits for query
		 * @param int $iStart 		start value for the page
		 * @param int $iMaximum 	maximum number of results to show
		 * @return DOMDocument 		summary of page results
		 */
		
		public function summary( $iTotal, $iStart, $iMaximum ) 
		{		
			$objXml = new DOMDocument();
			
			if ( $iStart == 0 ) $iStart = 1;
			
			// set end point
			
			$iStop = null;
			$iStop = $iStart + ( $iMaximum - 1 );
		
			// if end value of group of 10 exceeds total number of hits,
			// take total number of hits as end value 
	
			if ( $iStop > $iTotal ) 
			{
				$iStop = $iTotal;
			}
				
			if ( $iTotal > 0 )
			{	
				$objXml->loadXML("<summary><range>$iStart-$iStop</range><total>" . number_format($iTotal) . "</total></summary>");
			}
			
			return $objXml;
		}

		/**
		 * Creates a paging navigation for the results sets in XML
		 *
		 * @param string $strPage
		 * @param string $strStartAttribute
		 * @param int $iStartRecord
		 * @param string $strTotalHitsAttribute
		 * @param int $iTotalHits
		 * @param int $iRecordsPerPage
		 * @param string $strAdditional
		 * @return DOMDocument formatted paging navigation
		 */
		
		public function pager( $strPage, $strStartAttribute, $iStartRecord, $strTotalHitsAttribute, $iTotalHits, 
			$iRecordsPerPage, $strAdditional)
		{
			$objXml = new DOMDocument();
			$objXml->loadXML("<pager />");
				
			$iBaseRecord = 1;			// starting record in any result set
			$iPageNumber = 1;			// starting page number in any result set
			$bolShowFirst = false;		// show the first page when you get past page 10
	
			$iCurrentPage = null;		// calculates the current selected page
			$iTotalPages = null;		// calculates the total number of pages
			$iBottomRange = null;		// used to show a range of pages
			$iTopRange = null;			// used to show a range of pages
	
			$iCurrentPage = null;
			$iBottomRange = null;
			$iTopRange = null;
			
			if ( $iStartRecord == 0 ) $iStartRecord = 1;
			
			$iCurrentPage = (( $iStartRecord - 1 ) / $iRecordsPerPage ) + 1;
			$iBottomRange = $iCurrentPage - 5;
			$iTopRange = $iCurrentPage + 5;
				
			$iTotalPages = ceil( $iTotalHits / $iRecordsPerPage );
	
			// for pages 1-10 show just 1-10 (or whatever records per page)
	
			if ( $iBottomRange < 5 )
			{
				$iBottomRange = 0;
			}
			if ( $iCurrentPage < $iRecordsPerPage )
			{
				$iTopRange = 10;
			}
			else
			{
				$bolShowFirst = true;
			}
				
			// chop the top pages as we reach the end range
	
			if ( $iTopRange > $iTotalPages )
			{
				$iTopRange = $iTotalPages;
			}
	
			// see if we even need a pager
			
			if ( $iTotalHits > $iRecordsPerPage )
			{	
				// show first page
				
				if ( $bolShowFirst == true )
				{
					$objPage = $objXml->createElement("page", "1");

					$strLink =  $strPage . "?" . $strStartAttribute . "=1";
						
					if ( $strTotalHitsAttribute != "" )
					{
						$strLink .= "&" . $strTotalHitsAttribute . "=" . $iTotalHits;
					}
	
					$strLink .=  $strAdditional;
					
					$objPage->setAttribute("link", Xerxes_Parser::escapeXml($strLink));
					$objPage->setAttribute("type", "first");
					$objXml->documentElement->appendChild($objPage);
				}
	
				// create pages and links
	
				while ( $iBaseRecord <= $iTotalHits )
				{
					if ( $iPageNumber >= $iBottomRange && $iPageNumber <= $iTopRange )
					{
						if ( $iCurrentPage == $iPageNumber )
						{
							$objPage = $objXml->createElement("page", $iPageNumber);
							$objPage->setAttribute("here", "true");
							$objXml->documentElement->appendChild($objPage);
						}
						else
						{
							$objPage = $objXml->createElement("page", $iPageNumber);
							
							$strLink =  $strPage . "?" . $strStartAttribute . "=" . $iBaseRecord;
								
							if ( $strTotalHitsAttribute != "" )
							{
								$strLink .= "&" . $strTotalHitsAttribute . "=" . $iTotalHits;
							}
	
							$strLink .=  $strAdditional;

							$objPage->setAttribute("link", Xerxes_Parser::escapeXml($strLink));
							$objXml->documentElement->appendChild($objPage);

						}
					}
	
					$iPageNumber++;
					$iBaseRecord += $iRecordsPerPage;
				}
	
				$iNext = $iStartRecord + $iRecordsPerPage;
	
					
				if ($iNext <= $iTotalHits )
				{
					$objPage = $objXml->createElement("page", "Next");
					
					$strLink = $strPage . "?" . $strStartAttribute . "=" . $iNext;
	
					if ( $strTotalHitsAttribute != "" )
					{
						$strLink .= "&" . $strTotalHitsAttribute . "=" . $iTotalHits;
					}
						
					$strLink .= $strAdditional;
					
					$objPage->setAttribute("link", Xerxes_Parser::escapeXml($strLink));
					$objPage->setAttribute("type", "next");
					$objXml->documentElement->appendChild($objPage);
				}	
			}
			
			return $objXml;

		}
		
		/**
		 * Creates a sorting page element
		 *
		 * @param string $strSortQuery	initial page and querystring values
		 * @param string $strSortKeys	selected sort value
		 * @param array $arrOptions		list of sort options and values
		 * @return DOMDocument 			paging navigation
		 */
		
		function sortDisplay( $strSortQuery, $strSortKeys, $arrOptions)
		{
		
			$objXml = new DOMDocument();
			$objXml->loadXML("<sort_display />");
			
			$x = 1;
			
			foreach ( $arrOptions as $key => $value )
			{
				if ( $key == $strSortKeys )
				{
					$objHere = $objXml->createElement("option", $value);
					$objHere->setAttribute("active", "true");
					$objXml->documentElement->appendChild($objHere);
				}
				else
				{
					$objHere = $objXml->createElement("option", $value);
					$objHere->setAttribute("active", "false");
					$objHere->setAttribute("link", Xerxes_Parser::escapeXml("$strSortQuery&sortKeys=$key"));
					$objXml->documentElement->appendChild($objHere);			
				}
				
				$x++;
			}
			
			return $objXml;
		}
		
		/**
		 * Simple XSLT transformation function
		 * 
		 * @param mixed $xml			DOMDocument or string containing xml
		 * @param string $strXslt		physical path to xslt document 
		 * @param array $arrParams		[optional] array of parameters to pass to stylesheet
		 * @param bool $bolAmersand		[optional] whether to unescape ampersands, true will convert &amp; => &
		 * @return string				newly formatted document
		 */ 
					
		public function transform ( $xml, $strXslt, $arrParams = null, $bolAmersand = false )
		{
			$strHtml = Xerxes_Parser::transform ( $xml, $strXslt, $arrParams );
			
			if ( $bolAmersand == true )
			{
				$strHtml = str_replace("&amp;", "&", $strHtml);
			}
			
			return $strHtml;
		}	
	}
?>
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

    
    protected function build_link($strBaseUrl, $params, $extraParams, $objRequest)
    {
        
        # $extraParams can be array, or already formatted string.
        # array preferred.				
        if (is_array($extraParams)) {
          $params = array_merge($params, $extraParams);
        }
        
        $strLink;
        if ( $objRequest == null ) {
          #old Style, deprecated.
          $strLink = $strBaseUrl . '?' . http_build_query($params, "", "&amp;");
        }
        else {
          # new style!
          $strLink = $objRequest->url_for($params);
        }
        
        # Do we need to add on a string $extraParams? Bah, deprecated!
        if (! is_array($extraParams) ) {
          # deprecated. 
          $strLink .=  $extraParams;
        }
        
        return $strLink;
        
    }
   
    
		public function pager_dom($arrParams, $strStartAttribute, $iStartRecord, $strTotalHitsAttribute, $iTotalHits, 
			$iRecordsPerPage, $objRequest)
		{
      return $this->pager(null, $strStartAttribute, $iStartRecord, $strTotalHitsAttribute, $iTotalHits, 
			$iRecordsPerPage, $arrParams, $objRequest);
		}
    
		/**
		 * Deprecated. Call pager_dom instead with more sensible params
     * for new style with request url_for url gen. 
     * Creates a paging navigation for the results sets in XML
     * This method has to create a URL. Legacy code may pass in parts
     * or url directly. This is deprecated. Preferred way is to pass
     * in an $objRequest, and url_for will be used. Pass in an array
     * of additional params if neccesary. 
		 *
		 * @param string $strPage. For new style (url_for), please leave null! 
		 * @param string $strStartAttribute
		 * @param int $iStartRecord
		 * @param string $strTotalHitsAttribute
		 * @param int $iTotalHits
		 * @param int $iRecordsPerPage
		 * @param string $strAdditional. For new style, should be an array of params. 
     * @param Xerxes_Framework_Request $objRequest. For new style, _required_. 
		 * @return DOMDocument formatted paging navigation
		 */
		
		public function pager( $strPage, $strStartAttribute, $iStartRecord, $strTotalHitsAttribute, $iTotalHits, 
			$iRecordsPerPage, $strAdditional, $objRequest = null)
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

          $params = array($strStartAttribute => 1);
          if ( $strTotalHitsAttribute != "") {
            $params[$strTotalHitsAttribute] = $iTotalHits;
          }
          
          $strLink = $this->build_link($strPage, $params, $strAdditional, $objRequest);
            
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
							
              $params = array( $strStartAttribute => $iBaseRecord );
              if ( $strTotalHitsAttribute != "" )
							{
                $params[$strTotalHitsAttribute] = $iTotalHits;
							}
	
              $strLink = $this->build_link( $strPage, $params, $strAdditional, $objRequest);
              
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
          
          $params = array( $strStartAttribute => $iNext );										
					if ( $strTotalHitsAttribute != "" )
					{
            $params[$strTotalHitsAttribute] = $iTotalHits;
					}
						
					$strLink = $this->build_link( $strPage, $params, $strAdditional, $objRequest);
					
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
			
			$strBase = "";
			
			if ( strstr($strSortQuery, "?") )
			{
				$strBase="$strSortQuery&";
			}
			else
			{
				$strBase="$strSortQuery?sortKeys";
			}
			
			
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
					$objHere->setAttribute("link", Xerxes_Parser::escapeXml("$strBase=$key"));
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
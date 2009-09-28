<?php

/**
 * Extract properties for books, articles, and dissertations from MARC-XML record 
 * with special handling for Metalib X-Server response
 * 
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version 1.6
 * @package Xerxes
 */

class Xerxes_Metalib_Record extends Xerxes_Record
{
	private $metalib_id;
	private $result_set;
	private $record_number;
	private $database_name;
	
	public function map()
	{
		// test to see what profile the context object is using; set namespace accordingly
		

		if ($this->document->getElementsByTagNameNS ( "info:ofi/fmt:xml:xsd:book", "book" )->item ( 0 ) != null)
		{
			$this->xpath->registerNamespace ( "rft", "info:ofi/fmt:xml:xsd:book" );
		} elseif ($this->document->getElementsByTagNameNS ( "info:ofi/fmt:xml:xsd:dissertation", "dissertation" )->item ( 0 ) != null)
		{
			$this->xpath->registerNamespace ( "rft", "info:ofi/fmt:xml:xsd:dissertation" );
		} elseif ($this->document->getElementsByTagNameNS ( "info:ofi/fmt:xml:xsd", "journal" )->item ( 0 ) != null)
		{
			// this is not an actual namespace reference, but a bug in the metalib
			// x-server that causes it to send back a mislabelled namespace (2007-02-19)
			

			$this->xpath->registerNamespace ( "rft", "info:ofi/fmt:xml:xsd" );
		} else
		{
			$this->xpath->registerNamespace ( "rft", "info:ofi/fmt:xml:xsd:journal" );
		}
		
		// TODO put this in parse?
		

		$strLeaderMetalib = $this->controlfield ( "LDR" );
		$strYear = $this->datafield ( "YR" )->subfield ( "a" );
		
		if (strstr ( $strSource, "EBSCO" ))
		{
			$chrLeader7 = null;
		}
		
		if (strstr ( $strSource, 'ERIC' ) && strstr ( $strEric, 'ED' ) && ! stristr ( $strTitle, "proceeding" ))
			$strReturn = "Report";
		elseif (strstr ( $strSource, 'ERIC' ) && ! strstr ( $strEric, 'ED' ))
			$strReturn = "Article";
		elseif (strstr ( $strSource, 'OCLC_PAPERS' ))
			$strReturn = "Conference Paper";
		elseif (strstr ( $strSource, 'PCF1' ))
			$strReturn = "Conference Proceeding";
			
		// various places ebsco shoves format information
		

		$strEbscoPsycFormat = $this->datafield ( "656" )->subfield ( "a" );
		$strEbscoFormat = $this->datafield ( "514" )->subfield ( "a" );
		$strEbscoType = $this->datafield ( "072" )->subfield ( "a" );
		
		// psycinfo and related dbs are an exception to the ebsco format type
		

		if (strstr ( $this->source, "EBSCO_PSY" ) || strstr ( $this->source, "EBSCO_PDH" ))
		{
			$strEbscoType = "";
		}
		
		if ($strEbscoPsycFormat != null)
			array_push ( $this->format_array, $strEbscoPsycFormat );
		if ($strEbscoFormat != null)
			array_push ( $this->format_array, $strEbscoFormat );
		if ($strEbscoType != null)
			array_push ( $this->format_array, $strEbscoType );
			
		// oclc dissertation abstracts
		//
		// (HACK) 10/1/2007 this assumes that the diss abs record includes the 904, which means
		// there needs to be a local search config that performs an 'add new' action rather than
		// the  'remove' action that the parser uses by default
		

		if (strstr ( $this->strSource, "OCLC_DABS" ))
		{
			$this->degree = $this->datafield ( "904" )->subfield ( "j" );
			$this->institution = $this->datafield ( "904" )->subfield ( "h" );
			$this->journal_title = $this->datafield ( "904" )->subfield ( "c" );
			
			$this->journal = $this->journal_title . " " . $this->journal;
			
			if ($this->journal_title == "MAI")
			{
				array_push ( $this->format_array, "Thesis" );
			} else
			{
				array_push ( $this->format_array, "Dissertation" );
			}
			
			$strThesis = "";
		}
		
		// gale puts issn in 773b
		

		if (strstr ( $this->strSource, 'GALE' ))
		{
			$strGaleIssn = $this->datafield ( "773" )->subfield ( "b" );
			
			if ($strGaleIssn != null)
			{
				array_push ( $this->issns, $strGaleIssn );
			}
		}
		
		// ebsco book chapter
		

		$strEbscoBookTitle = $this->datafield ( "771" )->subfield ( "a" );
		
		if ($strEbscoBookTitle != "")
		{
			array_push ( $this->format_array, "Book Chapter" );
		}
		
		// JSTOR book review correction: title is meaningless, but subjects
		// contain the title of the books, so we'll swap them to the title here
		

		if (strstr ( $this->strSource, 'JSTOR' ) && $this->strTitle == "Review: [untitled]")
		{
			$this->strTitle = "";
			
			foreach ( $this->arrSubjects as $strSubject )
			{
				$this->strTitle .= " " . $strSubject;
			}
			
			$this->strTitle = trim ( $this->strTitle );
			$this->arrSubjects = null;
			
			array_push ( $this->format_array, "Book Review" );
		}
		
		// gale title clean-up, because for some reason unknown to man 
		// they put weird notes and junk at the end of the title. so remove them 
		// here and add them to notes.
		

		if (strstr ( $this->strSource, 'GALE_' ))
		{
			$iEndPoint = strlen ( $this->strTitle ) - 1;
			$arrMatches = array ();
			$strGaleRegExp = "/\(([^)]*)\)/";
			
			if (preg_match_all ( $strGaleRegExp, $this->strTitle, $arrMatches ) != 0)
			{
				$this->strTitle = preg_replace ( $strGaleRegExp, "", $this->strTitle );
			}
			
			foreach ( $arrMatches [1] as $strMatch )
			{
				array_push ( $this->arrNotes, "From title: " . $strMatch );
			}
			
			// subtitle only appears to be one of these notes
			

			if ($this->strSubTitle != "")
			{
				array_push ( $this->arrNotes, "From title: " . $this->strSubTitle );
				$this->strSubTitle = "";
			}
		}
		
		// google books: nothing indicates that this is actually a book
		

		if ($this->strSource == "GOOGLE_B")
		{
			array_push ( $this->format_array, "Book" );
		}
		
		// encyclopedia britannica, full text is in summary field, swap them. 
		

		if ($this->strSource == "BRITANNICA_ENCY")
		{
			if (count ( $this->getEmbeddedText () ) == 0 && $arrAbstract)
			{
				$text = join ( " ", $this->extractMarcArray ( $objXPath, 520, "a" ) );
				$text = str_replace ( '^', '', $text );
				
				$this->arrEmbeddedText = array ($text );
				$arrAbstract = array ();
			
			}
		}
		
		$this->strIssue = $this->extractMarcDataField ( $objXPath, "ISS", "a" );
		$this->strVolume = $this->extractMarcDataField ( $objXPath, "VOL", "a" );
		
		parent::map ();
		
		// source database
		

		$sid = $this->datafield ( "SID" );
		
		$this->metalib_id = ( string ) $sid->subfield ( "d" );
		$this->record_number = ( string ) $sid->subfield ( "j" );
		$this->result_set = ( string ) $sid->subfield ( "s" );
		$this->database_name = ( string ) $sid->subfield ( "t" );
		
		// source may have been explicitly set in the calling code, so make sure
		// there is no value here before we extract it from the marc record
		

		if ($this->source == "")
		{
			$this->source = ( string ) $sid->subfield ( "SID", "b" );
		}
		
		// eric doc number
		

		$this->eric_number = $this->datafield ( "ERI" )->subfield ( "a" );
		
		### openurl context object: journal title, volume, issue, pages from context object
		

		$objSTitle = $objXPath->query ( "//rft:stitle" )->item ( 0 );
		$objTitle = $objXPath->query ( "//rft:title" )->item ( 0 );
		$objVolume = $objXPath->query ( "//rft:volume" )->item ( 0 );
		$objIssue = $objXPath->query ( "//rft:issue" )->item ( 0 );
		$objStartPage = $objXPath->query ( "//rft:spage" )->item ( 0 );
		$objEndPage = $objXPath->query ( "//rft:epage" )->item ( 0 );
		$objISSN = $objXPath->query ( "//rft:issn" )->item ( 0 );
		$objISBN = $objXPath->query ( "//rft:isbn" )->item ( 0 );
		
		if ($objSTitle != null)
			$strStitle = $objSTitle->nodeValue;
		if ($objVolume != null)
			$this->strVolume = $objVolume->nodeValue;
		if ($objIssue != null)
			$this->strIssue = $objIssue->nodeValue;
		if ($objStartPage != null)
			$this->strStartPage = $objStartPage->nodeValue;
		if ($objEndPage != null)
			$this->strEndPage = $objEndPage->nodeValue;
		if ($this->strJournalTitle == "" && $objTitle != null)
			$this->strJournalTitle = $objTitle->nodeValue;
		
		$strAltIsbn = "";
		if ($objISBN != null)
			$strAltIsbn = $objISBN->nodeValue;
		$strAltIssn = "";
		if ($objISSN != null)
			$strAltIssn = $objISSN->nodeValue;
			
		// some databases have full-text but no 856
		// will capture these here and add to links array
		

		// pychcritiques -- no indicator of full-text either, assume all to be (9/5/07)
		// no unique metalib config either, using psycinfo, so make determination based on name. yikes!
		

		if (stristr ( $this->database_name, "psycCRITIQUES" ))
		{
			array_push ( $this->links, array ("Full-Text in HTML", array ("001" => ( string ) $this->controlfield ( "001" ) ), "html" ) );
		}
		
		// factiva -- no indicator of full-text either, assume all to be (9/5/07)
		

		if (stristr ( $this->source, "FACTIVA" ))
		{
			array_push ( $this->links, array ("Full-Text Available", array ("035_a" => ( string ) $this->datafield ( "035" )->subfield ( "a" ) ), "online" ) );
		}
		
		// eric -- document is recent enough to likely contain full-text;
		// 340000 being a rough approximation of the document number after which they 
		// started digitizing
		

		if (strstr ( $this->source, "ERIC" ) && strlen ( $this->eric_number ) > 3)
		{
			$strEricType = substr ( $this->strEric, 0, 2 );
			$strEricNumber = ( int ) substr ( $this->strEric, 2 );
			
			if ($strEricType == "ED" && $strEricNumber >= 340000)
			{
				$strFullTextPdf = "http://www.eric.ed.gov/ERICWebPortal/contentdelivery/servlet/ERICServlet?accno=" . $this->strEric;
				
				array_push ( $this->arrLinks, array ("Full-text at ERIC.gov", $strFullTextPdf, "pdf" ) );
			}
		}
		
		// 7 Apr 09, jrochkind. Gale Biography Resource Center
		// No 856 is included at all, but a full text link can be
		// constructed from the 001 record id.
		

		if ($this->strSource == "GALE_ZBRC")
		{
			$url = "http://galenet.galegroup.com/servlet/BioRC?docNum=" . $this->strControlNumber;
			array_push ( $this->arrLinks, array ("Full-Text in HTML", $url, "html" ) );
		}
		
		### full-text 856
		

		// examine the 856s present in the record to see if they are in
		// fact to full-text, and not to a table of contents or something
		// stupid like that, by checking for existence of subfield code 3
		

		foreach ( $this->datafield ( "856" ) as $link )
		{
			$strUrl = $link->subfield ( "u" );
			
			// empty link, skip to next foreach entry
			

			if ($strUrl == "")
			{
				continue;
			}
			
			// bad links
			// records that have 856s, but are not always for full-text; in that case, specify them
			// as being TOCs, which makes the 'none' links
			//
			// springer (metapress): does not distinguish between things in your subscription or not (9/16/08) 
			// cinahl (bzh): not only is 856 bad, but link missing http://  bah! thanks greg at upacific! (9/10/08)
			// wilson: if it has '$3' in the URL not full-text, since these are improperly parsed out (3/26/07) 
			// cabi: just point back to site (10/30/07)
			// google scholar: just point back to site (3/26/07) 
			// amazon: just point back to site (3/20/08)
			// abc-clio: just point back to site (7/30/07)
			// engineering village (evii): has unreliable full-text links in a consortium environment (4/1/08)
			// wiley interscience: wiley does not limit full-text links only to your subscription (4/29/08)
			// oxford: only include the links that are free, otherwise just a link to abstract (5/7/08)
			// gale: only has full-text if 'text available' note in 500 field (9/7/07) BUT: Not true of Gale virtual reference library (GALE_GVRL). 10/14/08 jrochkind. 
			// catalog: any catalog record that links to loc toc without $3, bad catalogers! (7/18/08)
			// ieee xplore: does not distinguish between things in your subscription or not (2/13/09) 
			

			if (stristr ( $strUrl, "$3" ) || stristr ( $this->strSource, "METAPRESS_XML" ) || stristr ( $this->strSource, "EBSCO_RZH" ) || stristr ( $this->strSource, "CABI" ) || stristr ( $this->strSource, "GOOGLE_SCH" ) || stristr ( $this->strSource, "AMAZON" ) || stristr ( $this->strSource, "ABCCLIO" ) || stristr ( $this->strSource, "EVII" ) || stristr ( $this->strSource, "WILEY_IS" ) || (stristr ( $this->strSource, "OXFORD_JOU" ) && ! strstr ( $strUrl, "content/full/" )) || (strstr ( $this->strSource, "GALE" ) && $this->strSource != "GALE_GVRL" && ! in_array ( "Text available", $this->arrNotes )) || stristr ( $strUrl, "www.loc.gov/catdir" ) || stristr ( $this->strSource, "IEEE_XPLORE" ))
			{
				$bolToc = true;
			}
			
			// TODO figure out what this is
			// mark Scopus as original_record, not full text
			

			if ($this->strSource == "ELSEVIER_SCOPUS")
			{
				$strLinkFormat = "original_record";
			}
			
			if ($bolToc == false)
			{
				// ebsco html
				// there is (a) an indicator from ebsco that the record has full-text, or 
				// (b) an abberant 856 link that doesn't work, but the construct link will work, 
				// so we take that as something of a full-text indicator
				

				if (strstr ( $this->strSource, "EBSCO" ) && (strstr ( $strEbscoFullText, "T" ) || strstr ( $strDisplay, "View Full Text" )))
				{
					array_push ( $this->arrLinks, array ($strDisplay, array ("001" => $str001 ), "html" ) );
				} 

				else
				{
					// look for the letters PDF in the label or the url, or HTML
					// in the label to see if we can pin-down format, otherwise
					// map it to the generic full-text property
					

					if (empty ( $strLinkFormat ))
						$strLinkFormat = "online";
					
					if (stristr ( $strDisplay, "PDF" ) || stristr ( $strUrl, "PDF" ))
					{
						$strLinkFormat = "pdf";
					} elseif (stristr ( $strDisplay, "HTML" ))
					{
						$strLinkFormat = "html";
					}
					
					array_push ( $this->arrLinks, array ($strDisplay, $strUrl, $strLinkFormat ) );
				
				}
			
			} else
			{
				array_push ( $this->arrLinks, array ($strDisplay, $strUrl, "none" ) );
			}
		}
	}
	
	### PROPERTIES ###
	

	public function getMetalibID()
	{
		return $this->metalib_id;
	}
	
	public function getResultSet()
	{
		return $this->result_set;
	}
	
	public function setResultSet($data)
	{
		$this->result_set = $data;
	}
	
	public function getRecordNumber()
	{
		return $this->record_number;
	}
	
	public function setRecordNumber($data)
	{
		$this->record_number = $data;
	}
	
	public function getDatabaseName()
	{
		return $this->database_name;
	}
}
?>
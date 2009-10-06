<?php

class Xerxes_MetalibRecord_Document extends Xerxes_Marc_Document 
{
	protected $record_type = "Xerxes_MetalibRecord";
}

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

class Xerxes_MetalibRecord extends Xerxes_Record
{
	private $metalib_id;
	private $result_set;
	private $record_number;
	private $database_name;
	
	public function map()
	{
		$leader = $this->leader();

		// source database
		
		$sid = $this->datafield ( "SID" );
		
		$this->metalib_id = ( string ) $sid->subfield( "d" );
		$this->record_number = ( string ) $sid->subfield( "j" );
		$this->result_set = ( string ) $sid->subfield( "s" );
		$this->database_name = ( string ) $sid->subfield( "t" );
		$this->source = ( string ) $sid->subfield( "b" );
		
		## metalib weirdness
		
		// puts leader in control field
		
		$strLeaderMetalib = (string) $this->controlfield( "LDR" );
		
		if ( $strLeaderMetalib != "" )
		{
			$leader->value = $strLeaderMetalib;
		}

		// this is not an actual openurl namespace, but a bug in the metalib
		// x-server that causes it to send back a mislabelled namespace (2007-02-19)
		
		if ($this->document->getElementsByTagNameNS ( "info:ofi/fmt:xml:xsd", "journal" )->item ( 0 ) != null)
		{
			$this->xpath->registerNamespace ( "rft", "info:ofi/fmt:xml:xsd" );
		}
		
		// ebsco and some screen-scrapers have multiple authors in repeating 100 fields; 
		// invalid marc, so switch all but first to 700
		
		$authors = $this->datafield("100");
		
		if ( $authors->length() > 1 )
		{
			for ( $x = 1; $x < $authors->length(); $x++ )
			{
				$author = $authors->item($x);
				$author->tag = "700";
			}
		}
		
		## ebsco format
		
		if (strstr ( $this->source, "EBSCO" ))
		{
			// leader appears to be hard-wired; useless
			
			$leader->value = "";

			// format
			
			array_push($this->format_array, (string) $this->datafield( "656" )->subfield( "a" ));
			array_push($this->format_array, (string) $this->datafield( "514" )->subfield( "a" ));
			
			$strEbscoType =  (string) $this->datafield( "072" )->subfield( "a" );
			
			if (strstr ( $this->source, "EBSCO_PSY" ) || strstr ( $this->source, "EBSCO_PDH" ))
			{
				$strEbscoType = "";
			}
			
			array_push($this->format_array, (string) $strEbscoType);

			// ebsco book chapter
			
			$strEbscoBookTitle = (string) $this->datafield ( "771" )->subfield ( "a" );
			
			if ($strEbscoBookTitle != "")
			{
				array_push ( $this->format_array, "Book Chapter" );
			}
		}
		
		// gale puts issn in 773b

		if (strstr ( $this->source, 'GALE' ))
		{
			$strGaleIssn = (string) $this->datafield("773")->subfield("b");
			
			if ($strGaleIssn != null)
			{
				array_push ( $this->issns, $strGaleIssn );
			}
		}
		
		// eric doc number

		$this->eric_number = (string) $this->datafield( "ERI" )->subfield( "a" );

		# full-text
			
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
			$strEricType = substr ( $this->eric_number, 0, 2 );
			$strEricNumber = ( int ) substr ( $this->eric_number, 2 );
			
			if ($strEricType == "ED" && $strEricNumber >= 340000)
			{
				$strFullTextPdf = "http://www.eric.ed.gov/ERICWebPortal/contentdelivery/servlet/ERICServlet?accno=" . $this->eric_number;
				
				array_push ( $this->links, array ("Full-text at ERIC.gov", $strFullTextPdf, "pdf" ) );
			}
		}
		
		// 7 Apr 09, jrochkind. Gale Biography Resource Center
		// No 856 is included at all, but a full text link can be
		// constructed from the 001 record id.

		if ($this->source == "GALE_ZBRC")
		{
			$url = "http://galenet.galegroup.com/servlet/BioRC?docNum=" . $this->control_number;
			array_push ( $this->links, array ("Full-Text in HTML", $url, "html" ) );
		}
		
		// special handling of 856
		
		$notes = $this->fieldArray("500", "a"); // needed for gale

		foreach ( $this->datafield( "856" ) as $link )
		{
			$strDisplay = (string) $link->subfield("z");
			$strUrl = (string) $link->subfield( "u" );
			$strEbscoFullText = (string) $link->subfield( "i" );
			
			// bad links
			
			// records that have 856s, but are not always for full-text; in that case, specify them
			// here as original records, and remove 856 so parent code doesn't process them as full-text links
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
			// ieee xplore: does not distinguish between things in your subscription or not (2/13/09) 

			if ( stristr ( $this->source, "METAPRESS_XML" ) || 
				stristr ( $this->source, "EBSCO_RZH" ) || 
				stristr ( $this->source, "CABI" ) || 
				stristr ( $this->source, "GOOGLE_SCH" ) || 
				stristr ( $this->source, "AMAZON" ) || 
				stristr ( $this->source, "ABCCLIO" ) || 
				stristr ( $this->source, "EVII" ) || 
				stristr ( $this->source, "WILEY_IS" ) || 
				(stristr ( $this->source, "OXFORD_JOU" ) && ! strstr ( $strUrl, "content/full/" )) || 
				(strstr ( $this->source, "GALE" ) && $this->source != "GALE_GVRL" && ! in_array ( "Text available", $notes )) || 
				stristr ( $this->source, "IEEE_XPLORE" ) || 
				$this->source == "ELSEVIER_SCOPUS" )
			{
				// take it out so the parent class doesn't treat it as full-text
				
				$link->tag = "XXX";
				array_push ( $this->links, array ($strDisplay, $strUrl, "original_record" ) );
			}
			
			// ebsco html
			
			// there is (a) an indicator from ebsco that the record has full-text, or 
			// (b) an abberant 856 link that doesn't work, but the construct link will work, 
			// so we take that as something of a full-text indicator

			elseif (strstr ( $this->source, "EBSCO" ) && (strstr ( $strEbscoFullText, "T" ) || strstr ( $strDisplay, "View Full Text" )))
			{
				$str001 = (string) $this->controlfield("001");
				array_push ( $this->links, array ($strDisplay, array ("001" => $str001 ), "html" ) );
				unset($link);
			} 
		}

		// Gale title clean-up, because for some reason unknown to man 
		// they put weird notes and junk at the end of the title. so remove them 
		// here and add them to notes.

		if (strstr ( $this->source, 'GALE_' ))
		{
			$arrMatches = array ();
			$strGaleRegExp = "/\(([^)]*)\)/";
			
			$title = $this->datafield("245");
			$title_main = $title->subfield("a");
			$title_sub = $title->subfield("b");
			
			if (preg_match_all ( $strGaleRegExp, $title_main->value, $arrMatches ) != 0)
			{
				$title_main->value = preg_replace ( $strGaleRegExp, "", $title_main->value );
			}
			
			$note_field = new Xerxes_Marc_DataField();
			$note_field->tag = "500";
			
			foreach ( $arrMatches [1] as $strMatch )
			{				
				$subfield = new Xerxes_Marc_Subfield();
				$subfield->code = "a";
				$subfield->value = "From title: " . $strMatch;
				$note_field->addSubField($subfield);
			}
			
			// sub title is only these wacky notes

			if ($title_sub->value != "")
			{
				$subfield = new Xerxes_Marc_Subfield();
				$subfield->code = "a";
				$subfield->value = "From title: " . $title_sub->value;	
				$note_field->addSubField($subfield);			
				
				$title_sub->value = "";
			}
			
			$this->addDataField($note_field);
		}		
		
		
		
		
		######## PARENT MAPPING ###########
		
		parent::map ();	
		
		###################################
		
		
		
		
		
		// metalib's own year, issue, volume fields
		
		if ( $this->year == "" )
		{
			$this->year = (string) $this->datafield("YR")->subfield("a");
		}

		if ( $this->issue == "" )
		{
			$this->issue = (string) $this->datafield("ISS")->subfield("a");
		}

		if ( $this->volume == "" )
		{
			$this->issue = (string) $this->datafield("VOL")->subfield("a");
		}			
		
		## oclc dissertation abstracts

		// (HACK) 10/1/2007 this assumes that the diss abs record includes the 904, which means
		// there needs to be a local search config that performs an 'add new' action rather than
		// the  'remove' action that the parser uses by default

		if (strstr ( $this->source, "OCLC_DABS" ))
		{
			$this->degree = (string) $this->datafield( "904" )->subfield( "j" );
			$this->institution = (string) $this->datafield( "904" )->subfield( "h" );
			$this->journal_title = (string) $this->datafield( "904" )->subfield( "c" );
			
			$this->journal = $this->journal_title . " " . $this->journal;
			
			if ($this->journal_title == "MAI")
			{
				$this->format =  "Thesis";
			} 
			else
			{
				$this->format =  "Dissertation";
			}
		}		
		
		// random format related changes
		
		if ( strstr ( $this->source, 'ERIC' ) && strstr ( $this->eric_number, 'ED' ) && ! stristr ( $this->title, "proceeding" ))
		{
			$this->format = "Report";
		}
		elseif (strstr ( $this->source, 'ERIC' ) && ! strstr ( $this->eric_number, 'ED' ) )
		{
			$this->format = "Article";
		}
		elseif (strstr ( $this->source, 'OCLC_PAPERS' ))
		{
			$this->format = "Conference Paper";
		}
		elseif (strstr ( $this->source, 'PCF1' ))
		{
			$this->format = "Conference Proceeding";
		}
		elseif ($this->source == "GOOGLE_B")
		{
			$this->format = "Book";
		}	

		// JSTOR book review correction: title is meaningless, but subjects
		// contain the title of the books, so we'll swap them to the title here

		if (strstr ( $this->source, 'JSTOR' ) && $this->title == "Review")
		{
			$this->title = "";
			$this->sub_title = "";
			
			foreach ( $this->subjects as $strSubject )
			{
				$this->title .= " " . $strSubject;
			}
			
			$this->title = trim ( $this->title );
			
			$this->subjects = null;
			
			$this->format = "Book Review";
		}

		// jstor links are all pdfs
		
		if (strstr ( $this->source, 'JSTOR' ))
		{
			for( $x = 0; $x < count($this->links); $x++ )
			{
				$link = $this->links[$x];
				$link[2] = "pdf";
				$this->links[$x] = $link;
			}
		}		
		
		// CSA subject term clean-up, 
		// since they put an asterick in front of each term (2009-09-30)
		
		if (strstr ( $this->source, 'CSA_' ))
		{
			for ( $x = 0; $x < count($this->subjects); $x++ )
			{
				$this->subjects[$x] = str_replace("*", "", $this->subjects[$x]);
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
<?php

class Xerxes_Record_Document extends Xerxes_Marc_Document 
{
	protected $record_type = "Xerxes_Record";
}

/**
 * Extract properties for books, articles, and dissertations from MARC-XML
 * 
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id: Record.php 1852 2011-03-17 18:15:54Z dwalker@calstate.edu $
 * @package Xerxes
 */

class Xerxes_Record extends Xerxes_Marc_Record
{
	protected $source = "";	// source database id
	protected $database_name; // source database name
	protected $record_id; // canonical record id
	protected $score; // relevenace score

	protected $format = ""; // format
	protected $format_array = array(); // possible formats
	protected $technology = ""; // technology/system format

	protected $control_number = ""; // the 001 basically, OCLC or otherwise
	protected $oclc_number = ""; // oclc number
	protected $govdoc_number = ""; // gov doc number
	protected $gpo_number = ""; // gov't printing office (gpo) number
	protected $eric_number = ""; // eric document number
	protected $isbns = array(); // isbn
	protected $issns = array(); // issn
	protected $call_number = ""; // lc call number
	protected $doi = ""; // doi

	protected $authors = array(); // authors
	protected $author_from_title = ""; // author from title statement
	protected $editor = false; // whether primary author is an editor
	
	protected $non_sort = ""; // non-sort portion of title
	protected $title = ""; // main title
	protected $sub_title = ""; // subtitle	
	protected $series_title = ""; // series title
	protected $trans_title = false; // whether title is translated
	protected $uniform_title = ""; // uniform title
	protected $additional_titles = array(); // related titles
	
	protected $place = ""; // place of publication	
	protected $publisher = ""; // publisher	
	protected $year = ""; // date of publication

	protected $edition = ""; // edition
	protected $extent = ""; // total pages
	protected $price = ""; // price

	protected $book_title = ""; // book title (for book chapters)
	protected $journal_title = ""; // journal title
	protected $journal = ""; // journal source information
	protected $short_title = ""; // journal short title
	protected $volume = ""; // volume
	protected $issue = ""; // issue
	protected $start_page = ""; // start page
	protected $end_page = ""; // end page

	protected $degree = ""; // thesis degree conferred
	protected $institution = ""; // thesis granting institution

	protected $description = ""; // physical description
	protected $abstract = ""; // abstract
	protected $summary = ""; // summary
	protected $snippet = ""; // snippet
	protected $summary_type = ""; // the type of summary
	protected $language = ""; // primary language of the record
	protected $notes = array (); // notes that are not the abstract, language, or table of contents
	protected $subjects = array (); // subjects
	protected $toc = ""; // table of contents note
	protected $series = array();
	
	protected $refereed = false; // whether the item is peer-reviewed
	protected $subscription = false; // whether the item is available in library subscription
	
	protected $links = array (); // all supplied links in the record both full text and non
	
	protected $alt_scripts = array (); // alternate character-scripts like cjk or hebrew, taken from 880s
	protected $alt_script_name = ""; // the name of the alternate character-script; we'll just assume one for now, I guess
	
	protected $serialized; // for serializing the object
	protected $physical_holdings = true;
	
	protected $context_object; // openurl context object
	
	public function __sleep()
	{
		// save only the xml
		
		$this->serialized = $this->document->saveXML();
		return array("serialized");
	}
	
	public function __wakeup()
	{
		// and then we recreate the object (with any new changes we've made)
		// by just loading the saved xml back into the object
		
		$this->loadXML($this->serialized);
	}
	
	protected function preload()
	{
		// the source can contain an openurl context object buried in it as well as marc-xml
		
		$this->context_object = new Xerxes_Record_ContextObject($this->document);	
	}

	/**
	 * Maps the marc data to the object's properties
	 */
	
	protected function map()
	{
		// control numbers
		
		$this->parseControlNumber();
		$this->parseOCLC();
		$this->parseISSN();
		$this->parseISBN();
		$this->parseDOI();
		$this->parseGovernmentNumbers();
		$this->parseCallNumber();
		
		// author
		
		$this->parseAuthors();
		
		// title
		
		$this->parseTitle();
		$this->parseSeriesTitle();
		$this->parseAdditionalTitles();
		
		// book data
		
		$this->parsePublisher();
		$this->parseBookInfo();
		
		// date

		$this->parseYear();
		
		 // notes
		
		$this->parseNotes();
		$this->parseAbstract();
		$this->parseLanguage();
		
		// thesis degree, institution, date awarded
				
		$this->parseThesis();
		
		// subjects
		
		$this->parseSubjects();

		// series
		
		$this->parseSeries();
		
		$this->parseJournal();

		// alt script
		
		$this->parseAltScript();
		
		// links
		
		$this->parseLinks();
	}
		
	protected function parseControlNumber()
	{
		$this->control_number = (string) $this->controlfield("001");
		$this->record_id = $this->control_number;		
	}
	
	protected function parseOCLC()
	{
		### oclc number
		
		// oclc number can be either in the 001 or in the 035$a
		// make sure 003 says 001 is oclc number or 001 includes an oclc prefix, 
		
		$str001 = (string) $this->controlfield("001");
		$str003 = (string) $this->controlfield("003");
		$str035 = (string) $this->datafield("035")->subfield("a");

		if ( $str001 != "" && (( $str003 == "" && preg_match('/^\(?([Oo][Cc])/', $str001) ) || 
			$str003 == "OCoLC" ))
		{
			$this->oclc_number = $str001;
		} 
		elseif ( strpos( $str035, "OCoLC" ) !== false )
		{
			$this->oclc_number = $str035;
		}
		
		// get just the number
		
		$arrOclc = array();
		
		if ( preg_match( "/[0-9]{1,}/", $this->oclc_number, $arrOclc ) != 0 )
		{
			$just_oclc_number = $arrOclc[0];
			
			// strip out leading 0s

			$just_oclc_number = preg_replace( "/^0{1,8}/", "", $just_oclc_number );
			
			$this->oclc_number = $just_oclc_number;
		}		
	}
	
	protected function parseISSN()
	{
		$issns = $this->fieldArray("022", "a" );
		$journal_issn = (string) $this->datafield("773")->subfield("x");
		
		if ( $journal_issn != null )
		{
			array_push( $issns, $journal_issn );
		}
		
		// clean-up and push
		
		foreach ( $issns as $issn )
		{
			if ( strpos( $issn, "^" ) === false )
			{
				array_push( $this->issns, $issn );
			}
		}
	}
	
	protected function parseISBN()
	{
		$isbns = $this->fieldArray("020", "az" );
		
		// clean-up and push

		foreach ( $isbns as $isbn )
		{
			if ( strpos( $isbn, "^" ) === false )
			{
				array_push( $this->isbns, $isbn );
			}
		}	
	}
	
	protected function parseDOI()
	{
		// this is kind of iffy since the 024 is not _really_ a DOI field; but this
		// is the most likely marc field; however need to see if the number follows the very loose
		// pattern of the DOI of 'prefix/suffix', where prefix and suffix can be nearly anything
		
		$field_024 = $this->fieldArray("024", "a");
		
		foreach ( $field_024 as $doi )
		{
			// strip any doi: prefix
			
			$doi = str_ireplace( "doi:", "", $doi );
			$doi = str_ireplace( "doi", "", $doi );
			
			// got it!
			
			if ( preg_match('/.*\/.*/', $doi) )
			{
				$this->doi = $doi;
				break;
			}
		}		
	}
	
	protected function parseCallNumber()
	{
		$call_number = (string) $this->datafield("050");
		$call_number_local = (string) $this->datafield("090");
		
		if ( $call_number != null )
		{
			$this->call_number = $call_number;
		} 
		elseif ( $call_number_local != null )
		{
			$this->call_number = $call_number_local;
		}		
	}
	
	protected function parseGovernmentNumbers()
	{
		$this->govdoc_number = (string) $this->datafield("086")->subfield("a");		
		$this->gpo_number = (string) $this->datafield("074")->subfield("a");
	}

	protected function parseThesis()
	{
		$thesis =  (string) $this->datafield("502")->subfield("a");
		
		### thesis

		// most 502 fields follow the following pattern, which we will use to
		// match and extract individual elements:
		// Thesis (M.F.A.)--University of California, San Diego, 2005
		// Thesis (Ph. D.)--Queen's University, Kingston, Ont., 1977.

		if ( $thesis != "" )
		{
			// extract degree conferred

			$arrDegree = array();
			
			if ( preg_match( '/\(([^\(]*)\)/', $thesis, $arrDegree ) != 0 )
			{
				$this->degree = $arrDegree[1];
			}
			
			// extract institution

			$iInstPos = strpos( $thesis, "--" );
			
			if ( $iInstPos !== false )
			{
				$institution = "";
				
				// get everything after the --
				$institution = substr( $thesis, $iInstPos + 2, strlen( $thesis ) - 1 );
				
				// find last comma in remaining text
				$iEndPosition = strrpos( $institution, "," );
				
				if ( $iEndPosition !== false )
				{
					$institution = substr( $institution, 0, $iEndPosition );
				}
				
				$this->institution = $institution;
			
			}
			
			// extract year conferred

			$this->year = $this->extractYear( $thesis );
		}		
	}
	
	protected function parseAuthors()
	{
		// authors

		$this->author_from_title = (string) $this->datafield("245")->subfield("c" );
		
		$objConfName =  $this->datafield("111"); // "anc"
		$objAddAuthor = $this->datafield("700"); // "a"
		$objAddCorp = $this->datafield("710"); //, "ab"
		$objAddConf = $this->datafield("711"); // "acn"
		
		// conference and corporate names from title ?

		$objConferenceTitle = $this->datafield("811"); // all
		
		if ( $objAddConf->length() == 0 && $objConferenceTitle->length() > 0 )
		{
			$objAddConf = $objConferenceTitle;
		}
		
		$objCorporateTitle = $this->datafield("810"); // all
		
		if ( $objAddCorp->length() == 0 && $objCorporateTitle->length() > 0 )
		{
			$objAddCorp = $objCorporateTitle;
		}
		
		// personal primary author
		
		if ( $this->datafield("100")->length() > 0 )
		{
			$objXerxesAuthor = $this->makeAuthorMarc( $this->datafield("100"), "a", "personal" );
			array_push( $this->authors, $objXerxesAuthor );
		} 
		elseif ( $objAddAuthor->length() > 0 )
		{
			// editor

			$objXerxesAuthor = $this->makeAuthorMarc( $objAddAuthor->item(0), "a", "personal", true);
			array_push( $this->authors, $objXerxesAuthor );
			$this->editor = true;
		}
		
		// additional personal authors

		if ( $objAddAuthor->length() > 0  )
		{
			// if there is an editor it has already been included in the array
			// so we need to skip the first author in the list
			
			if ( $this->editor == true )
			{
				$objAddAuthor->next();
			}
			
			foreach ( $objAddAuthor as $obj700 )
			{
				$objXerxesAuthor = $this->makeAuthorMarc( $obj700, "a", "personal", true );
				array_push( $this->authors, $objXerxesAuthor );
			}
		}
		
		// corporate author
		
		if ( (string) $this->datafield("110")->subfield("ab") != "" )
		{
			$objXerxesAuthor = $this->makeAuthorMarc( $this->datafield("110"), "ab", "corporate" );
			array_push( $this->authors, $objXerxesAuthor );
		}
		
		// additional corporate authors

		if ( $objAddCorp->length() > 0 )
		{
			foreach ( $objAddCorp as $objCorp )
			{
				$objXerxesAuthor = $this->makeAuthorMarc( $objCorp, "ab", "corporate", true );
				array_push( $this->authors, $objXerxesAuthor );
			}
		}
		
		// conference name

		if ( $objConfName->length() > 0)
		{
			$objXerxesAuthor = $this->makeAuthorMarc( $objConfName, "anc", "conference" );
			array_push( $this->authors, $objXerxesAuthor );
		}
		
		// additional conference names

		if ( $objAddConf->length() > 0 )
		{
			foreach ( $objAddConf as $objConf )
			{
				$objXerxesAuthor = $this->makeAuthorMarc( $objConf, "acn", "conference", true );
				array_push( $this->authors, $objXerxesAuthor );
			}
		}
		
		// last-chance from context-object
		
		if ( count($this->authors) == 0 )
		{
			foreach ( $this->context_object->authors as $objAuthor )
			{
				$objXerxesAuthor = new Xerxes_Record_Author();
				
				foreach ( $objAuthor->childNodes as $objAuthAttr )
				{					
					switch ( $objAuthAttr->localName )
					{
						case "aulast":
							$objXerxesAuthor->last_name = $objAuthAttr->nodeValue;
							$objXerxesAuthor->type = "personal";
							break;

						case "aufirst":
							$objXerxesAuthor->first_name = $objAuthAttr->nodeValue;
							break;
							
						case "auinit":
							$objXerxesAuthor->init = $objAuthAttr->nodeValue;
							break;
							
						case "aucorp":
							$objXerxesAuthor->name = $objAuthAttr->nodeValue;
							$objXerxesAuthor->type = "corporate";
							break;							
					}
				}
				
				array_push($this->authors, $objXerxesAuthor);
			}
		}		
	}
	
	protected function makeAuthorMarc($author, $subfields, $strType, $bolAdditional = false)
	{
		$author_string = "";
		$author_display = "";		
		
		// author can be string or data field
		
		if ($author instanceof Xerxes_Marc_DataField || $author instanceof Xerxes_Marc_DataFieldList)
		{
			$author_string = (string) $author->subfield($subfields);
			$author_display = (string) $author;
		}
		else
		{
			$author_string = $author;
		}
		
		return $this->makeAuthor($author_string, $author_display, $strType, $bolAdditional);
	}

	protected function makeAuthor($author, $author_display = null, $strType, $bolAdditional = false)
	{
		$objAuthor = new Xerxes_Record_Author();
		
		$objAuthor->type = $strType;
		$objAuthor->additional = $bolAdditional;
		
		$iComma = strpos( $author, "," );
		$iLastSpace = strripos( $author, " " );
		
		// for personal authors:

		// if there is a comma, we will assume the names are in 'last, first' order
		// otherwise in 'first last' order -- the second one here obviously being
		// something of a guess, assuming the person has a single word for last name
		// rather than 'van der Kamp', but better than the alternative?

		if ( $strType == "personal" )
		{
			$arrMatch = array();
			$strLast = "";
			$strFirst = "";
			$strInit = "";
			
			if ( $iComma !== false )
			{
				$strLast = trim( substr( $author, 0, $iComma ) );
				$strFirst = trim( substr( $author, $iComma + 1 ) );
			} 

			// some databases like CINAHL put names as 'last first' but first 
			// is just initials 'Walker DS' so we can catch this scenario?
			
			elseif ( preg_match( "/ ([A-Z]{1,3})$/", $author, $arrMatch ) != 0 )
			{
				$strFirst = $arrMatch[1];
				$strLast = str_replace( $arrMatch[0], "", $author );
			} 
			else
			{
				$strLast = trim( substr( $author, $iLastSpace ) );
				$strFirst = trim( substr( $author, 0, $iLastSpace ) );
			}
			
			if ( preg_match( '/ ([a-zA-Z]{1})\.$/', $strFirst, $arrMatch ) != 0 )
			{
				$strInit = $arrMatch[1];
				$strFirst = str_replace( $arrMatch[0], "", $strFirst );
			}
			
			$objAuthor->last_name = $strLast;
			$objAuthor->first_name = $strFirst;
			$objAuthor->init = $strInit;
		
		} 
		else
		{
			$objAuthor->name = trim( $author );
		}
		
		// display is different
		
		if ( $author_display != "" )
		{
			$objAuthor->display = $author_display;
		}
		
		return $objAuthor;
	}	
	
	protected function parseTitle()
	{
		$this->title = (string) $this->datafield("245")->subfield("anp");
		$this->sub_title = (string) $this->datafield("245")->subfield("b");
		$this->uniform_title = (string) $this->datafield("130|240");
		
		// sometimes title is solely in subfield p
		
		$title_part =  (string) $this->datafield("245")->subfield("p" );
		
		if ( $this->title == "" && $title_part != "" )
		{
			$this->title = $title_part;
		}
		
		// sometimes the title appears in a 242 or even a 246 if it is translated from another
		// language, although the latter is probably bad practice.  We will only take these
		// if the title in the 245 is blank, and take a 242 over the 246

		$translated_title = (string) $this->datafield("242")->subfield("a");
		$translated_subtitle = (string) $this->datafield("242")->subfield("b");
		
		$varying_title = (string) $this->datafield("246")->subfield("a");
		$varying_subtitle = (string) $this->datafield("246")->subfield("b");
		
		if ( $this->title == "" && $translated_title != "" )
		{
			$this->title = $translated_title;
			$this->trans_title = true;
		} 
		elseif ( $this->title == "" && $varying_title != "" )
		{
			$this->title = $varying_title;
			$this->trans_title = true;
		}
		
		if ( $this->sub_title == "" && $translated_subtitle != "" )
		{
			$this->sub_title = $translated_title;
			$this->trans_title = true;
		} 
		elseif ( $this->sub_title == "" && $varying_subtitle != "" )
		{
			$this->sub_title = $varying_subtitle;
			$this->trans_title = true;
		}
		
		// last chance, check the context object
		
		if ( $this->title == "" && $this->context_object->atitle != null )
		{
			$this->title = $this->context_object->atitle;
		}
		elseif ( $this->title == "" && $this->context_object->btitle != null )
		{
			$this->title = $this->context_object->btitle;
		}		
	}

	protected function parsePublisher()
	{
		$this->place = (string) $this->datafield("260")->subfield("a");
		$this->publisher = (string) $this->datafield("260")->subfield("b");		
	}
	
	protected function parseBookInfo()
	{
		$this->edition = (string) $this->datafield("250")->subfield("a");
		$this->extent = (string) $this->datafield("300")->subfield("a");
		$this->description = (string) $this->datafield("300");
		$this->price = (string) $this->datafield("365");
	}
	
	protected function parseYear()
	{
		$date = (string) $this->datafield("260")->subfield("c");
		
		### year

		if ( $date != "" )
		{
			$this->year = $this->extractYear( $date );
		} 
		elseif ( $this->extractYear( $this->publisher ) )
		{
			// off chance that the date is hanging out in the publisher field;
			// might as well strip it out here as well

			$this->year = $this->extractYear( $this->publisher );
			$this->publisher = str_replace( $this->year, "", $this->publisher );
		} 
		elseif ( $this->extractYear( $this->journal ) )
		{
			// perhaps somewhere in the 773$g

			$this->year = $this->extractYear( $this->journal );
		}
		
		// last chance grab from context object
		
		if ( $this->year == "" && $this->context_object->date != null )
		{
			$this->year = $this->extractYear($this->context_object->date);
		}		
	}	
	
	protected function parseSeriesTitle()
	{
		$this->series_title = (string) $this->datafield("440")->subfield("a" );
	}
	
	protected function parseAdditionalTitles()
	{
		// additional titles for display
		
		foreach ( $this->datafield('730|740') as $additional_titles )
		{
			$subfields = (string) $additional_titles->subfield();
			array_push($this->additional_titles, $subfields);
		}		
	}

	protected function parseNotes()
	{
		$tocs = $this->fieldArray("505", "agrt");

		foreach ( $tocs as $toc )
		{
			$this->toc .=  $toc;
		}
		
		// other notes
		
		$note_object = $this->xpath("//marc:datafield[@tag >= 500 and @tag < 600 and " .
			"@tag != 505 and @tag != 520 and @tag != 546]" );
		
		foreach ( $note_object as $note )
		{
			array_push($this->notes, $note->nodeValue);
		}		
	}
	
	protected function parseAbstract()
	{
		$abstracts = $this->fieldArray("520", "a");		
		
		foreach ( $abstracts as $abstract )
		{
			$this->abstract .= " " . $abstract;
		}
		
		$this->abstract = trim( strip_tags( $this->abstract ) );		
	}
	
	protected function parseFormat()
	{
		$this->format = $this->extractFormat();
	}
	
	/**
	 * Determines the format/genre of the item, broken out here for clarity
	 */
	
	protected function extractFormat()
	{
		$this->technology = (string) $this->datafield("538")->subfield("a");
		
		
		### data fields
		
		$format_array = $this->fieldArray("513", "a");
		
		if ( $this->datafield("111")->length() > 0 || $this->datafield("711")->length() > 0 )
		{
			array_push( $format_array, "conference paper" );
		}
		
		if ( $this->context_object->genre != null )
		{
			array_push($format_array, $this->context_object->genre );
		}

		$title_format = (string) $this->datafield("245")->subfield("k");
		
		if ( $title_format != null )
		{
			array_push( $this->format_array, $title_format );
		}
		
		// we'll combine all of the datafields that explicitly declare the
		// format of the record into a single string

		$data_fields = "";
		
		foreach ( $format_array as $strFormat )
		{
			$data_fields .= " " . Xerxes_Framework_Parser::strtolower( $strFormat );
		}
		
		
		### control fields
		
		$chrLeader6 = "";
		$chrLeader7 = "";
		$chrLeader8 = "";
		
		if ( strlen( (string) $this->leader() ) >= 8 )
		{
			$chrLeader6 = substr( (string) $this->leader(), 6, 1 );
			$chrLeader7 = substr( (string) $this->leader(), 7, 1 );
			$chrLeader8 = substr( (string) $this->leader(), 8, 1 );
		}
		
		// grab the 008 & 006 for handling
		
		$obj008 = $this->controlfield("008");
		
		// newspaper
		
		if ( $obj008 instanceof Xerxes_Marc_ControlField )
		{
			if ( $chrLeader7 == 's' && $obj008->position("21") == 'n' )
			{
				 return "Newspaper";
			}
		}
		
		// format made explicit

		if ( strstr( $data_fields, 'dissertation' ) ) return  "Dissertation"; 
		if ( (string) $this->datafield("502") != "" ) return  "Thesis"; 
		if ( (string) $this->controlfield("002") == "DS" ) return  "Thesis";
		if ( strstr( $data_fields, 'proceeding' ) ) return  "Conference Proceeding"; 
		if ( strstr( $data_fields, 'conference' ) ) return  "Conference Paper"; 
		if ( strstr( $data_fields, 'hearing' ) ) return  "Hearing"; 
		if ( strstr( $data_fields, 'working' ) ) return  "Working Paper"; 
		if ( strstr( $data_fields, 'book review' ) || strstr( $data_fields, 'review-book' ) ) return  "Book Review"; 
		if ( strstr( $data_fields, 'film review' ) || strstr( $data_fields, 'film-book' ) ) return  "Film Review";
		if ( strstr( "$data_fields ", 'review ' ) ) return  "Review";
		if ( strstr( $data_fields, 'book art' ) || strstr( $data_fields, 'book ch' ) || strstr( $data_fields, 'chapter' ) ) return  "Book Chapter"; 
		if ( strstr( $data_fields, 'journal' ) ) return  "Article"; 
		if ( strstr( $data_fields, 'periodical' ) || strstr( $data_fields, 'serial' ) ) return  "Article"; 
		if ( strstr( $data_fields, 'book' ) ) return  "Book";
        if ( strstr( $data_fields, 'pamphlet' ) ) return  "Pamphlet";  
        if ( strstr( $data_fields, 'essay' ) ) return  "Essay";
		if ( strstr( $data_fields, 'article' ) ) return  "Article";

		// format from other sources

		if ( $this->journal != "" ) return  "Article"; 
		if ( $chrLeader6 == 'a' && $chrLeader7 == 'a' ) return  "Book Chapter"; 
		if ( $chrLeader6 == 'a' && $chrLeader7 == 'm' )
		{
			$strReturn = "Book"; 
			
			if ( $obj008 instanceof Xerxes_Marc_ControlField  )
			{
				switch( $obj008->position("23") )
				{
					case "a": $strReturn = "Microfilm"; break;
					case "b": $strReturn = "Microfiche"; break;
					case "c": $strReturn = "Microopaque"; break;
					case "d": $strReturn = "Book--Large print"; break;
					case "e": $strReturn = "Book--Braille"; break;
					case "s": $strReturn = "eBook"; break;
				}
			}
			
			return $strReturn;
		}
		
		if ( $chrLeader8 == 'a' ) return "Archive"; 
		if ( $chrLeader6 == 'e' || $chrLeader6 == 'f' ) return "Map"; 
		if ( $chrLeader6 == 'c' || $chrLeader6 == 'd' ) return "Printed Music"; 
		if ( $chrLeader6 == 'i' ) return "Audio Book"; 
		if ( $chrLeader6 == 'j' ) return "Sound Recording"; 
		if ( $chrLeader6 == 'k' ) return "Photograph or Slide"; 
		if ( $chrLeader6 == 'g' ) return "Video"; 
		if ( $chrLeader6 == 'm' && $chrLeader7 == 'i' ) return "Website"; 
		if ( $chrLeader6 == 'm' ) return "Electronic Resource"; 
		if ( $chrLeader6 == 'a' && $chrLeader7 == 'b' ) return "Article"; 
		if ( $chrLeader6 == 'a' && $chrLeader7 == 's' ) return "Journal"; 
		if ( $chrLeader6 == 'a' && $chrLeader7 == 'i' ) return "Website"; 

		if ( count( $this->isbns ) > 0 ) return "Book"; 
		if ( count( $this->issns ) > 0 ) return "Article";
		
		// if we got this far, just return unknown
		
		return "Unknown";
	}	
	
	protected function parseSubjects()
	{
		// we'll exclude the numeric subfields since they contain information about the
		// source of the subject terms, which are probably not needed for display?

		foreach ( $this->datafield("6XX") as $subject )
		{
			$subfields = $subject->subfield("abcdefghijklmnopqrstuvwxyz");
			$subfields_array = array();
			
			foreach ( $subfields as $subfield )
			{
				array_push($subfields_array, (string) $subfield);
			}
			
			$subject_object = new Xerxes_Record_Subject();
			
			$subject_object->display = implode(" -- ", $subfields_array );
			$subject_object->value = (string) $subfields;
			
			array_push($this->subjects, $subject_object);
		}		
	}
	
	protected function parseSeries()
	{
		// series information

		foreach ( $this->datafield('4XX|800|810|811|830') as $subject )
		{
			array_push($this->series, (string) $subject);
		}			
	}
	
	protected function parseJournal()
	{
		### all journal data
		
		$this->journal = (string) $this->datafield("773")->subfield("atgbcdefhijklmnopqrsuvwxyz1234567890", true);		
		
		
		### journal title
		
		// specify the order of the subfields in 773 for journal as $a $t $g and then everything else
		// in case they are out of order 

		$this->journal_title = (string) $this->datafield("773")->subfield("t");
		$this->short_title = (string) $this->datafield("773")->subfield("p");

		// we'll take the journal title form the 773$t as the best option,

		if ( $this->journal_title == "" )
		{
			// otherwise see if context object has one
					
			if ( $this->context_object->jtitle != null )
			{
				$this->journal_title = $this->context_object->jtitle;
			}
			elseif ( $this->context_object->title != null )
			{
				$this->journal_title = $this->context_object->jtitle;
			}
			
			// or see if a short title exists
			
			elseif ( $this->short_title != "" && 
				($this->format == "Article" || $this->format == "Journal" || $this->format == "Newspaper")  )
			{
				$this->journal_title = $this->short_title;
			}
		}
		
		
		### volume, issue, pagination
		
		// context object is the best
		
		$this->volume = $this->context_object->volume;
		$this->issue = $this->context_object->issue;
		$this->start_page = $this->context_object->spage;
		$this->end_page = $this->context_object->epage;
		
		$strExtentHost = (string) $this->datafield("773")->subfield("h");
		$strJournal = (string) $this->datafield("773")->subfield("agpqt");
		
		// a best guess extraction of volume, issue, pages from 773

		$arrRegExJournal = $this->extractJournalData( $strJournal );
		
		// some sources include ^ as a filler character in issn/isbn, these people should be shot!


		### volume

		if ( $this->volume == "" )
		{
			if ( array_key_exists( "volume", $arrRegExJournal ) )
			{
				$this->volume = $arrRegExJournal["volume"];
			}
		}
		
		### issue
		
		if ( $this->issue == "" )
		{
			if ( array_key_exists( "issue", $arrRegExJournal ) )
			{
				$this->issue = $arrRegExJournal["issue"];
			}
		}
		
		### pages

		// start page

		if ( $this->start_page == "" )
		{
			if ( array_key_exists( "spage", $arrRegExJournal ) )
			{
				$this->start_page = $arrRegExJournal["spage"];
			}
		}
		
		// end page
		
		if ( $this->end_page == "" )
		{
			if ( array_key_exists( "epage", $arrRegExJournal ) )
			{
				// found an end page from our generic regular expression parser

				$this->end_page = $arrRegExJournal["epage"];
			} 
			elseif ( $strExtentHost != "" && $this->start_page != "" )
			{
				// there is an extent note, indicating the number of pages,
				// calculate end page based on that

				$arrExtent = array();
				
				if ( preg_match( '/([0-9]{1})\/([0-9]{1})/', $strExtentHost, $arrExtent ) != 0 )
				{
					// if extent expressed as a fraction of a page, just take
					// the start page as the end page
					
					$this->end_page = $this->start_page;
				} 
				elseif ( preg_match( "/[0-9]{1,}/", $strExtentHost, $arrExtent ) != 0 )
				{
					// otherwise take whole number

					$iStart = ( int ) $this->start_page;
					$iEnd = ( int ) $arrExtent[0];
					
					$this->end_page = $iStart + ($iEnd - 1);
				}
			}
		
		}
		
		// page normalization
		
		if ( $this->end_page != "" && $this->start_page != "" )
		{
			// pages were input as 197-8 or 197-82, or similar, so convert
			// the last number to the actual page number
			
			if ( strlen( $this->end_page ) < strlen( $this->start_page ) )
			{
				$strMissing = substr( $this->start_page, 0, strlen( $this->start_page ) - strlen( $this->end_page ) );
				$this->end_page = $strMissing . $this->end_page;
			}
		}		
	}

	/**
	 * Best-guess regular expression for extracting volume, issue, pagination,
	 * broken out here for clarity 
	 *
	 * @param string $strJournalInfo		any journal info, usually from 773
	 * @return array
	 */
	
	private function extractJournalData($strJournalInfo)
	{
		$arrFinal = array();
		$arrCapture = array();
		
		// we'll drop the whole thing to lower case and padd it
		// with spaces to make parsing easier
		
		$strJournalInfo = " " . Xerxes_Framework_Parser::strtolower( $strJournalInfo ) . " ";
		
		// volume

		if ( preg_match( '/ v[a-z]{0,5}[\.]{0,1}[ ]{0,3}([0-9]{1,})/', $strJournalInfo, $arrCapture ) != 0 )
		{
			$arrFinal["volume"] = $arrCapture[1];
			$strJournalInfo = str_replace( $arrCapture[0], "", $strJournalInfo );
		}
		
		// issue

		if ( preg_match( '/ i[a-z]{0,4}[\.]{0,1}[ ]{0,3}([0-9]{1,})/', $strJournalInfo, $arrCapture ) != 0 )
		{
			$arrFinal["issue"] = $arrCapture[1];
			$strJournalInfo = str_replace( $arrCapture[0], "", $strJournalInfo );
		} 
		elseif ( preg_match( '/ n[a-z]{0,5}[\.]{0,1}[ ]{0,3}([0-9]{1,})/', $strJournalInfo, $arrCapture ) != 0 )
		{
			$arrFinal["issue"] = $arrCapture[1];
			$strJournalInfo = str_replace( $arrCapture[0], "", $strJournalInfo );
		}
		
		// pages

		if ( preg_match( "/([0-9]{1,})-([0-9]{1,})/", $strJournalInfo, $arrCapture ) != 0 )
		{
			$arrFinal["spage"] = $arrCapture[1];
			$arrFinal["epage"] = $arrCapture[2];
			
			$strJournalInfo = str_replace( $arrCapture[0], "", $strJournalInfo );
		} 
		elseif ( preg_match( '/ p[a-z]{0,3}[\.]{0,1}[ ]{0,3}([0-9]{1,})/', $strJournalInfo, $arrCapture ) != 0 )
		{
			$arrFinal["spage"] = $arrCapture[1];
			$strJournalInfo = str_replace( $arrCapture[0], "", $strJournalInfo );
		}
		
		return $arrFinal;
	}	
	
	protected function parseAltScript()
	{	
		// the 880 represents an alternative character-script, like Hebrew or CJK;
		// for simplicity's sake, we just dump them all here in an array, with the 
		// intent of displaying them in paragraphs together in the interface or something?
		
		// we get every field except for the $6 which is a linking field
		
		$this->alt_scripts = $this->fieldArray("880", "abcdefghijklmnopqrstuvwxyz12345789" );
		
		// now use the $6 to figure out which character-script this is
		// assume just one for now

		$alt_script = (string) $this->datafield("880")->subfield("6");
		
		if ( $alt_script != null )
		{
			$match_codes_array = array();
			
			$script_code_array = array (
				"(3" => "Arabic", 
				"(B" => "Latin", 
				'$1' => "CJK", 
				"(N" => "Cyrillic", 
				"(S" => "Greek", 
				"(2" => "Hebrew"
			);
			
			if ( preg_match( '/[0-9]{3}-[0-9]{2}\/([^\/]*)/', $alt_script, $match_codes_array ) )
			{
				if ( array_key_exists( $match_codes_array[1], $script_code_array ) )
				{
					$this->alt_script_name = $script_code_array[$match_codes_array[1]];
				}
			}
		}		
	}
	
	protected function parseLanguage()
	{
		// take an explicit language note over 008 if available

		$language_note = (string) $this->datafield("546")->subfield("a");
		
		if ( $language_note != null )
		{
			$language_note = $this->stripEndPunctuation( $language_note, "." );
			
			if ( ! stristr( $language_note, "Undetermined" ) )
			{
				$this->language = str_ireplace( "In ", "", $language_note );
				$this->language = ucfirst( $this->language );
			}
		} 
		else
		{
			// get the language code from the 008
			
			$language_object = (string) $this->controlfield("008");
			
			if ( $language_object instanceof Xerxes_Marc_ControlField )
			{
				$lang_code = $language_object->position("35-37");

				if ( $lang_code != "")
				{
					$this->language = $lang_code;
				}			
			}
		}		
	}
	
	protected function parseLinks()
	{
		// examine the 856s present in the record to see if they are in
		// fact to full-text, and not to a table of contents or something
		// stupid like that

		foreach ( $this->datafield("856") as $link )
		{
			$resource_type = $link->ind2;
			$part = (string) $link->subfield("3");
			
			$url = (string) $link->subfield("u");
			$host_name = (string) $link->subfield("a");
			$display = (string) $link->subfield("z");
			$link_format_type = (string) $link->subfield("q");
			$link_text = (string) $link->subfield("y");			
			
			if ( $display == "" )
			{
				if ( $link_text != "" )
				{
					$display = $link_text;
				}
				elseif ( $host_name != "")
				{
					$display = $host_name;
				}
			}

			if ( $part != "" )
			{
				$display = $part . " " . $display;
			}
			
			// no link supplied
			
			if ( (string) $link->subfield("u") == "" )
			{
				continue;
			}
			
			// link includes loc url (bad catalogers!)
			
			if ( stristr($url, "catdir") || $resource_type == 2 )
			{
				array_push( $this->links, array (null, (string) $link->subfield("u"), "none" ) );
			}
			else
			{
				$link_format = "online";
					
				if ( stristr( $display, "PDF" ) || 
					stristr( $url, "PDF" ) || 
					stristr($link_format_type, "PDF" ) || 
					stristr($link_text, "PDF" ) )
				{
					$link_format = "pdf";
				} 
				elseif ( stristr( $display, "HTML" ) || 
					stristr($link_format_type, "HTML" ) ||  
					stristr($link_text, "HTML" ) )
				{
					$link_format = "html";
				}
				
				array_push( $this->links, array ($display, $url, $link_format ) );
			}
		}		
	}
	
	/**
	 * Property clean-up
	 */
	
	protected function cleanup()
	{
		### title

		$this->non_sort = strip_tags( $this->non_sort );
		$this->title = strip_tags( $this->title );
		$this->sub_title = strip_tags( $this->sub_title );
		
		// make sure subtitle is properly parsed out

		$iColon = strpos( $this->title, ":" );
		
		if ( $this->sub_title == "" && $iColon !== false )
		{
			$this->sub_title = trim( substr( $this->title, $iColon + 1 ) );
			$this->title = trim( substr( $this->title, 0, $iColon ) );
		}
		
		// make sure nonSort portion of the title is extracted

		// punctuation; we'll also *add* the definite/indefinite article below should 
		// the quote be followed by one of those -- this is all in english, yo!

		if ( strlen( $this->title ) > 0 )
		{
			if ( substr( $this->title, 0, 1 ) == "\"" || substr( $this->title, 0, 1 ) == "'" )
			{
				$this->non_sort = substr( $this->title, 0, 1 );
				$this->title = substr( $this->title, 1 );
			}
		}
		
		// common definite and indefinite articles

		if ( strlen( $this->title ) > 4 )
		{
			if ( Xerxes_Framework_Parser::strtolower( substr( $this->title, 0, 4 ) ) == "the " )
			{
				$this->non_sort .= substr( $this->title, 0, 4 );
				$this->title = substr( $this->title, 4 );
			} 
			elseif ( Xerxes_Framework_Parser::strtolower( substr( $this->title, 0, 2 ) ) == "a " )
			{
				$this->non_sort .= substr( $this->title, 0, 2 );
				$this->title = substr( $this->title, 2 );
			} 
			elseif ( Xerxes_Framework_Parser::strtolower( substr( $this->title, 0, 3 ) ) == "an " )
			{
				$this->non_sort .= substr( $this->title, 0, 3 );
				$this->title = substr( $this->title, 3 );
			}
		}

		### isbn
		
		// get just the isbn minus format notes

		for ( $x = 0 ; $x < count( $this->isbns ) ; $x ++ )
		{
			$arrIsbnExtract = array();
			
			$this->isbns[$x] = str_replace( "-", "", $this->isbns[$x] );
			
			if ( preg_match( "/[0-9]{12,13}X{0,1}/", $this->isbns[$x], $arrIsbnExtract ) != 0 )
			{
				$this->isbns[$x] = $arrIsbnExtract[0];
			} 
			elseif ( preg_match( "/[0-9]{9,10}X{0,1}/", $this->isbns[$x], $arrIsbnExtract ) != 0 )
			{
				$this->isbns[$x] = $arrIsbnExtract[0];
			}
		}

		## summary
		
		if ( $this->abstract != "" )
		{
			$this->summary = $this->abstract;
			$this->summary_type = "abstract";
		}
		elseif ( $this->snippet != "" )
		{
			$this->summary = $this->snippet;
			$this->summary_type = "snippet";
		} 
		elseif ( $this->toc != "" )
		{
			$this->summary = $this->toc;
			$this->summary_type = "toc";
		} 
		elseif ( count( $this->subjects ) > 0 )
		{
			$this->summary_type = "subjects";
			
			for ( $x = 0 ; $x < count( $this->subjects ) ; $x ++ )
			{
				$subject_object = $this->subjects[$x];
				$this->summary .= $subject_object->value;
				
				if ( $x < count( $this->subjects ) - 1 )
				{
					$this->summary .= "; ";
				}
			}
		}		
		
		## journals
		
		// construct a readable journal field if none supplied
		
		if ( $this->journal == "" )
		{
			if ( $this->journal_title != "" )
			{
				$this->journal = $this->toTitleCase($this->journal_title);

				if ( $this->volume != "" ) 
				{
					$this->journal .= " vol. " . $this->volume;
				}
				
				if ( $this->issue != "" )
				{
					$this->journal .= " iss. " . $this->issue;
				}
				
				if ( $this->year != "" )
				{
					$this->journal .= " (" . $this->year . ")";
				}
			}
		}

		### language
		
		// normalize and translate language names
		
		$langConverter = Xerxes_Framework_Languages::getInstance();
		
		if ( strlen( $this->language ) == 2 )
		{
			$this->language = $langConverter->getNameFromCode( 'iso_639_1_code', $this->language );
		} 
		elseif ( strlen( $this->language ) == 3 )
		{
			$this->language = $langConverter->getNameFromCode( 'iso_639_2B_code', $this->language );
		} 
		else
		{
			$language = $langConverter->getNameFromCode( 'name', $this->language );
			
			if ( $language != "" )
			{
				$this->language = $language;
			}
		}
		
		## de-duping
		
		// make sure no dupes in author array
		
		$author_original = $this->authors;
		$author_other = $this->authors;
		
		for ( $x = 0; $x < count($author_original); $x++ )
		{
			$objXerxesAuthor = $author_original[$x];
			
			if ( $objXerxesAuthor instanceof Xerxes_Record_Author  ) // skip those set to null (i.e., was a dupe)
			{
				$this_author = $objXerxesAuthor->allFields();
				
				for ( $a = 0; $a < count($author_other); $a++ )
				{
					if ( $a != $x ) // compare all other authors in the array
					{
						$objThatAuthor = $author_other[$a];
						
						if ( $objThatAuthor instanceof Xerxes_Record_Author ) // just in case
						{
							$that_author = $objThatAuthor->allFields();
							
							if ( $this_author == $that_author)
							{
								// remove the dupe
								
								$author_original[$a] = null;
							}
						}
					}
				}
			}
		}
		
		$this->authors = array(); // reset author array
		
		foreach ( $author_original as $author )
		{
			if ( $author instanceof Xerxes_Record_Author )
			{
				array_push($this->authors, $author);
			}
		}
		
		// make sure no dupes and no blanks in standard numbers
		
		$arrISSN = $this->issns;
		$arrISBN = $this->isbns;
		
		$this->issns = array();
		$this->isbns = array();
		
		foreach ( $arrISSN as $strISSN )
		{
			$strISSN = trim($strISSN);
			
			if ( $strISSN != "" )
			{
				$strISSN = str_replace( "-", "", $strISSN);
				
				//extract the issn number leaving behind extra chars and comments
				
				$match = array();
				
				if ( preg_match("/[0-9]{8,8}/", $strISSN, $match) )
				{
					$strISSN = $match[0];
				}
				
				array_push($this->issns, $strISSN);
			}
		}

		foreach ( $arrISBN as $strISBN )
		{
			$strISBN = trim($strISBN);
			
			if ( $strISBN != "" )
			{
				$strISBN = str_replace( "-", "", $strISBN);
				array_push($this->isbns, $strISBN);
			}
		}		
		
		
		$this->issns = array_unique( $this->issns ); 
		$this->isbns = array_unique( $this->isbns );
		
		
		### punctuation clean-up

		$this->book_title = $this->stripEndPunctuation( $this->book_title, "./;,:" );
		$this->title = $this->stripEndPunctuation( $this->title, "./;,:" );
		$this->sub_title = $this->stripEndPunctuation( $this->sub_title, "./;,:" );
		$this->short_title = $this->stripEndPunctuation( $this->short_title, "./;,:" );
		$this->journal_title = $this->stripEndPunctuation( $this->journal_title, "./;,:" );
		$this->series_title = $this->stripEndPunctuation( $this->series_title, "./;,:" );
		$this->technology = $this->stripEndPunctuation( $this->technology, "./;,:" );
		
		$this->place = $this->stripEndPunctuation( $this->place, "./;,:" );
		$this->publisher = $this->stripEndPunctuation( $this->publisher, "./;,:" );
		$this->edition = $this->stripEndPunctuation( $this->edition, "./;,:" );
		
		for ( $x = 0 ; $x < count( $this->authors ) ; $x ++ )
		{
			foreach ( $this->authors[$x] as $key => $value )
			{
				$objXerxesAuthor = $this->authors[$x];
				
				foreach ( $objXerxesAuthor as $key => $value )
				{
					$objXerxesAuthor->$key = $this->stripEndPunctuation( $value, "./;,:" );
				}
				
				$this->authors[$x] = $objXerxesAuthor;
			}
		}
		
		for ( $s = 0 ; $s < count( $this->subjects ) ; $s ++ )
		{
			$subject_object = $this->subjects[$s];
			$subject_object->value = $this->stripEndPunctuation( $subject_object->value, "./;,:" );
			$this->subjects[$s] = $subject_object;
		}
	}
	
	/**
	 * Get an OpenURL 1.0 formatted URL
	 *
	 * @param string $strResolver	base url of the link resolver
	 * @param string $strReferer	referrer (unique identifier)
	 * @return string
	 */
	
	public function getOpenURL($strResolver, $strReferer = null, $param_delimiter = "&")
	{
		$arrReferant = array(); // referrant values, minus author
		$strBaseUrl = ""; // base url of openurl request
		$strKev = ""; // key encoded values

		// set base url and referrer with database name

		$strKev = "url_ver=Z39.88-2004";
		
		if ( $strResolver != "" )
		{
			$strBaseUrl = $strResolver . "?";
		}
		if ( $strReferer != "" )
		{
			$strKev .= $param_delimiter . "rfr_id=info:sid/" . urlencode( $strReferer );
		}
		if ( $this->database_name != "" )
		{
			$strKev .= urlencode( " ( " . $this->database_name . ")" );
		}
		
		// add rft_id's
		
		$arrReferentId = $this->referentIdentifierArray();
		
		foreach ($arrReferentId as $id) 
		{
			$strKev .= $param_delimiter . "rft_id=" . urlencode($id); 
		}
			
		// add simple referrant values
		
		$arrReferant = $this->referantArray();
		
		foreach ( $arrReferant as $key => $value )
		{
			if ( $value != "" )
			{
				$strKev .= $param_delimiter . $key . "=" . urlencode( $value );
			}
		}
		
		// add primary author

		if ( count( $this->authors ) > 0 )
		{
			$objXerxesAuthor = $this->authors[0];
			
			if ( $objXerxesAuthor->type == "personal" )
			{
				if ( $objXerxesAuthor->last_name != "" )
				{
					$strKev .= $param_delimiter . "rft.aulast=" . urlencode( $objXerxesAuthor->last_name );
					
					if ( $this->editor == true )
					{
						$strKev .= urlencode( ", ed." );
					}
				}
				if ( $objXerxesAuthor->first_name != "" )
				{
					$strKev .= $param_delimiter. "rft.aufirst=" . urlencode( $objXerxesAuthor->first_name );
				}
				if ( $objXerxesAuthor->init != "" )
				{
					$strKev .= $param_delimiter . "rft.auinit=" . urlencode( $objXerxesAuthor->init );
				}
			} 
			else
			{
				$strKev .= $param_delimiter . "rft.aucorp=" . urlencode( $objXerxesAuthor->name );
			}
		}
		
		return $strBaseUrl . $strKev;
	}
	
	/**
	 * Convert record to OpenURL 1.0 formatted XML Context Object
	 *
	 * @return DOMDocument
	 */
	
	public function getContextObject()
	{
		$ns_context = "info:ofi/fmt:xml:xsd:ctx";

		$ns_referrant = "";
		
		$arrReferant = $this->referantArray();
		$arrReferantIds = $this->referentIdentifierArray();
		
		$objXml = new DOMDocument( );
		$objXml->loadXML( "<context-objects />" );
		
		$objContextObject = $objXml->createElementNS($ns_context, "context-object" );
		$objContextObject->setAttribute( "version", "Z39.88-2004" );
		$objContextObject->setAttribute( "timestamp", date( "c" ) );
		
		$objReferrent = $objXml->createElementNS($ns_context, "referent" );
		$objMetadataByVal = $objXml->createElementNS($ns_context, "metadata-by-val" );
		$objMetadata = $objXml->createElementNS($ns_context,"metadata" );
		
		// set data container

		if ( $arrReferant["rft.genre"] == "book" || 
			$arrReferant["rft.genre"] == "bookitem" || 
			$arrReferant["rft.genre"] == "report" )
		{
			$ns_referrant = "info:ofi/fmt:xml:xsd:book";
			$objItem = $objXml->createElementNS($ns_referrant, "book" );
		} 
		elseif ( $arrReferant["rft.genre"] == "dissertation" )
		{
			$ns_referrant = "info:ofi/fmt:xml:xsd:dissertation";
			$objItem = $objXml->createElementNS($ns_referrant, "dissertation" );
		} 
		else
		{
			$ns_referrant = "info:ofi/fmt:xml:xsd:journal";
			$objItem = $objXml->createElementNS($ns_referrant, "journal" );
		}
		
		$objAuthors = $objXml->createElementNS($ns_referrant, "authors" );
		
		// add authors

		$x = 1;
		
		foreach ( $this->authors as $objXerxesAuthor )
		{
			$objAuthor = $objXml->createElementNS($ns_referrant, "author" );
			
			if ( $objXerxesAuthor->last_name != "" )
			{
				$objAuthorLast = $objXml->createElementNS($ns_referrant, "aulast", Xerxes_Framework_Parser::escapeXml( $objXerxesAuthor->last_name ) );
				$objAuthor->appendChild( $objAuthorLast );
			}
			
			if ( $objXerxesAuthor->first_name != "" )
			{
				$objAuthorFirst = $objXml->createElementNS($ns_referrant, "aufirst", Xerxes_Framework_Parser::escapeXml( $objXerxesAuthor->first_name ) );
				$objAuthor->appendChild( $objAuthorFirst );
			}
			
			if ( $objXerxesAuthor->init != "" )
			{
				$objAuthorInit = $objXml->createElementNS($ns_referrant, "auinit", Xerxes_Framework_Parser::escapeXml( $objXerxesAuthor->init ) );
				$objAuthor->appendChild( $objAuthorInit );
			}
			
			if ( $objXerxesAuthor->name != "" )
			{
				$objAuthorCorp = $objXml->createElementNS($ns_referrant, "aucorp", Xerxes_Framework_Parser::escapeXml( $objXerxesAuthor->name ) );
				$objAuthor->appendChild( $objAuthorCorp );
			}
			
			$objAuthor->setAttribute( "rank", $x );
			
			if ( $x == 1 && $this->editor == true )
			{
				$objAuthor->setAttribute( "editor", "true" );
			}
			
			$objAuthors->appendChild( $objAuthor );
			
			$x ++;
		
		}
		
		$objItem->appendChild( $objAuthors );
			
		// add rft_id's. 
		
		foreach ( $arrReferantIds as $id )
		{
			// rft_id goes in the <referent> element directly, as a <ctx:identifier>
			
			$objNode = $objXml->createElementNS($ns_context, "identifier", Xerxes_Framework_Parser::escapeXml ( $id ) );
			$objReferrent->appendChild ( $objNode );
		}
		
		// add simple referrant values

		foreach ( $arrReferant as $key => $value )
		{
			if ( is_array( $value ) )
			{
				if ( count( $value ) > 0 )
				{
					foreach ( $value as $element )
					{
						$objNode = $objXml->createElementNS($ns_referrant, $key, Xerxes_Framework_Parser::escapeXml( $element ) );
						$objItem->appendChild( $objNode );
					}
				}
			} 
			elseif ( $value != "" )
			{
				$objNode = $objXml->createElementNS($ns_referrant, $key, Xerxes_Framework_Parser::escapeXml( $value ) );
				$objItem->appendChild( $objNode );
			}
		}
		
		$objMetadata->appendChild( $objItem );
		$objMetadataByVal->appendChild( $objMetadata );
		$objReferrent->appendChild( $objMetadataByVal );
		$objContextObject->appendChild( $objReferrent );
		$objXml->documentElement->appendChild( $objContextObject );
		
		return $objXml;
	}
	
	/**
	 * Serialize to XML
	 * 
	 * @return DOMDocument
	 */
	
	public function toXML()
	{
		$objXml = new DOMDocument( );
		$objXml->loadXML( "<xerxes_record />" );

		
		#### special handling
		
		// normalized title
		
		$strTitle = $this->getTitle(true);
		
		if ( $strTitle != "" )
		{
			$objTitle = $objXml->createElement("title_normalized",  Xerxes_Framework_Parser::escapeXML($strTitle));
			$objXml->documentElement->appendChild($objTitle);
		}
		
		// journal title
		
		$strJournalTitle = $this->getJournalTitle(true);
		
		if ( $strJournalTitle != "" )
		{
			$objJTitle = $objXml->createElement("journal_title",  Xerxes_Framework_Parser::escapeXML($strJournalTitle));
			$objXml->documentElement->appendChild($objJTitle);
		}		
		
		// primary author
		
		$strPrimaryAuthor = $this->getPrimaryAuthor(true);
		
		if ( $strPrimaryAuthor != "")
		{
			$objPrimaryAuthor= $objXml->createElement("primary_author", Xerxes_Framework_Parser::escapeXML($strPrimaryAuthor));
			$objXml->documentElement->appendChild($objPrimaryAuthor);
		}
		
		// full-text indicator
		
		if ($this->hasFullText())
		{
			$objFull= $objXml->createElement("full_text_bool", 1);
			$objXml->documentElement->appendChild($objFull);
		}
		
		// authors
			
		if ( count($this->authors) > 0 )
		{
			$objAuthors = $objXml->createElement("authors");
			$x = 1;
			
			foreach ( $this->authors as $objXerxesAuthor )
			{
				$objAuthor =  $objXml->createElement("author");
				$objAuthor->setAttribute("type", $objXerxesAuthor->type);
				
				if ( $objXerxesAuthor->additional == true )
				{
					$objAuthor->setAttribute("additional", "true");
				}

				if ( $objXerxesAuthor->last_name != "" )
				{					
					$objAuthorLast =  $objXml->createElement("aulast", Xerxes_Framework_Parser::escapeXml( $objXerxesAuthor->last_name ) );
					$objAuthor->appendChild($objAuthorLast);
				}
				
				if ( $objXerxesAuthor->first_name != "" )
				{
					$objAuthorFirst =  $objXml->createElement("aufirst", Xerxes_Framework_Parser::escapeXml( $objXerxesAuthor->first_name ) );
					$objAuthor->appendChild($objAuthorFirst);
				}
				
				if ( $objXerxesAuthor->init != "" )
				{
					$objAuthorInit =  $objXml->createElement("auinit", Xerxes_Framework_Parser::escapeXml( $objXerxesAuthor->init) );
					$objAuthor->appendChild($objAuthorInit);
				}

				if ( $objXerxesAuthor->name != "" )
				{
					$objAuthorCorp =  $objXml->createElement("aucorp", Xerxes_Framework_Parser::escapeXml( $objXerxesAuthor->name) );
					$objAuthor->appendChild($objAuthorCorp);
				}

				if ( $objXerxesAuthor->display != "" )
				{
					$objAuthorDisplay = $objXml->createElement("display", Xerxes_Framework_Parser::escapeXml( $objXerxesAuthor->display) );
					$objAuthor->appendChild($objAuthorDisplay);
				}				
				
				$objAuthor->setAttribute("rank", $x);
				
				if ( $x == 1 && $this->editor == true )
				{
					$objAuthor->setAttribute("editor", "true");
				}
				
				$objAuthors->appendChild($objAuthor);
				
				$x++;
			}
			
			$objXml->documentElement->appendChild($objAuthors);
		}		
	
		// standard numbers
			
		if ( count($this->issns) > 0 || count($this->isbns) > 0 || $this->govdoc_number != "" || $this->gpo_number != "" || $this->oclc_number != "")
		{
			$objStandard = $objXml->createElement("standard_numbers");
			
			if ( count($this->issns) > 0 )
			{
				foreach ( $this->issns as $strIssn )
				{
					$objIssn = $objXml->createElement("issn", Xerxes_Framework_Parser::escapeXml($strIssn));
					$objStandard->appendChild($objIssn);
				}
			}
			
			if ( count($this->isbns) > 0 )
			{
				foreach ( $this->isbns as $strIsbn )
				{
					$objIssn = $objXml->createElement("isbn", Xerxes_Framework_Parser::escapeXml($strIsbn));
					$objStandard->appendChild($objIssn);
				}
			}
			
			if ( $this->govdoc_number != "" )
			{
				$objGovDoc = $objXml->createElement("gpo", Xerxes_Framework_Parser::escapeXml($this->govdoc_number));
				$objStandard->appendChild($objGovDoc);
			}
			
			if ( $this->gpo_number != "" )
			{
				$objGPO = $objXml->createElement("govdoc", Xerxes_Framework_Parser::escapeXml($this->gpo_number));
				$objStandard->appendChild($objGPO);
			}
				
			if ( $this->oclc_number != "" )
			{
				$objOCLC = $objXml->createElement("oclc", Xerxes_Framework_Parser::escapeXml($this->oclc_number));
				$objStandard->appendChild($objOCLC);					
			}
				
			$objXml->documentElement->appendChild($objStandard);
		}		
		
		// table of contents
		
		if ($this->toc != null )
		{
			$objTOC = $objXml->createElement("toc");
				
			$arrChapterTitles = explode("--",$this->toc);
				
			foreach ( $arrChapterTitles as $strTitleStatement )
			{
				$objChapter = $objXml->createElement("chapter");
				
				if ( strpos($strTitleStatement, "/") !== false )
				{
					$arrChapterTitleAuth = explode("/", $strTitleStatement);
					
					$objChapterTitle = $objXml->createElement("title",  Xerxes_Framework_Parser::escapeXml(trim($arrChapterTitleAuth[0])));
					$objChapterAuthor = $objXml->createElement("author",  Xerxes_Framework_Parser::escapeXml(trim($arrChapterTitleAuth[1])));
					
					$objChapter->appendChild($objChapterTitle);
					$objChapter->appendChild($objChapterAuthor);
				}
				else 
				{
					$objStatement = $objXml->createElement("statement", Xerxes_Framework_Parser::escapeXml(trim($strTitleStatement)));
					$objChapter->appendChild($objStatement);
				}
				
				$objTOC->appendChild($objChapter);
			}
			
			$objXml->documentElement->appendChild($objTOC);
		}

		// links
			
		if ( $this->links != null )
		{
			$objLinks = $objXml->createElement("links");
		
			foreach ( $this->links as $arrLink )
			{
				$objLink = $objXml->createElement("link");
				
				if ( $this->isFullText($arrLink) )
				{
					$objLink->setAttribute("type", "full");
					$objLink->setAttribute("format", $arrLink[2]);
				}
				else
				{
					$objLink->setAttribute("type", $arrLink[2]);
				}
				
				$objDisplay = $objXml->createElement("display", Xerxes_Framework_Parser::escapeXml($arrLink[0]));
				$objLink->appendChild($objDisplay);
				
				// if this is a "construct" link, then the second element is an associative 
				// array of marc fields and their values for constructing a link based on
				// the metalib IRD record linking syntax
				
				if ( is_array($arrLink[1]) )
				{
					foreach ( $arrLink[1] as $strField => $strValue )
					{
						$objParam = $objXml->createElement("param", Xerxes_Framework_Parser::escapeXml($strValue));
						$objParam->setAttribute("field", $strField);
						$objLink->appendChild($objParam);
					}
				}
				else
				{
					$objURL = $objXml->createElement("url", Xerxes_Framework_Parser::escapeXml($arrLink[1]));
					$objLink->appendChild($objURL);
				}
				
				$objLinks->appendChild($objLink);
			}
			
			$objXml->documentElement->appendChild($objLinks);
		}
		
		// subjects
		
		if ( count($this->subjects) > 0 )
		{
			$objSubjects = $objXml->createElement("subjects");
			$objXml->documentElement->appendChild($objSubjects);
		
			foreach ( $this->subjects as $subject_object )
			{
				$objSubject = $objXml->createElement("subject", Xerxes_Framework_Parser::escapeXml($subject_object->display));
				$objSubject->setAttribute("value", $subject_object->value);
				$objSubjects->appendChild($objSubject);
			}
		}
		
		## basic elements
		
		foreach ( $this as $key => $value )
		{
			// these we handled above
			
			if ($key == "authors" || 
				$key == "isbns" ||
				$key == "issns" ||
				$key == "govdoc_number" ||
				$key == "gpo_number" ||
				$key == "oclc_number" ||
				$key == "toc" ||
				$key == "links" || 
				$key == "journal_title" ||
				$key == "subjects" ||
				
				// these are utility variables, not to be output
				
				$key == "document" ||
				$key == "xpath" || 
				$key == "node" ||
				$key == "context_object" ||
				$key == "serialized")
			{
				continue;
			}
			
			if ( is_array($value) )
			{
				if ( count($value) == 0 )
				{
					continue;
				}
			}
			
			if ( $value == "" )
			{
				continue;	
			}
			
			$this->createNode($key, $value, $objXml, $objXml->documentElement);
		}
		
		return $objXml;
	}
	
	/**
	 * Return record in CSL array
	 * 
	 * @return array
	 */
	
	public function toCSL()
	{
		$citation = array();
		
		// title

		$citation["title"] = $this->getTitle(true);
		
		// format
		
		if ( $this->format == "Book" )
		{
			$citation["type"] = "book";
			$citation["publisher"] = $this->getPublisher(); 
			$citation["publisher-place"] = $this->getPlace();
		}
		else
		{
			// journal info
			
			$citation["type"] = "article-journal";
			$citation["container-title"] = $this->getJournalTitle(true);
			$citation["volume"] = $this->getVolume(); 
			$citation["issue"] = $this->getIssue(); 
			$citation["page"] = $this->getPages(); 
		}
			
		// authors
		
		if ( count($this->authors) > 0 )
		{
			$citation["author"] = array();
			
			foreach ( $this->authors as $author )
			{
				$author_array = array(
					"family" => $author->last_name, 
					"given" => $author->first_name, 
				);
				
				array_push($citation["author"], $author_array);
			}
		}
		
		 // year
		
		if ( $this->getYear() != "" )
		{
			$citation["issued"]["date-parts"] = array(array($this->getYear()));
		}
		
		return $citation;
	}
	
	private function createNode($key, $value, $objDocument, $objParent)
	{
		if ( is_array($value) )
		{
			$objNode = $objDocument->createElement($key);
			$objParent->appendChild($objNode);
			
			foreach ( $value as $child_key => $child )
			{
				// assumes key is plural form with 's', so individual is minus 's'
				
				$name = substr($key, 0, -1);
				
				// unless it has a specific name
				
				if ( ! is_int($child_key) )
				{
					$name = $child_key;
				}
				
				// recursive
				
				$this->createNode($name, $child, $objDocument, $objNode);
			}
		}
		else
		{
			$objNode = $objDocument->createElement($key, Xerxes_Framework_Parser::escapeXML($value));
			$objParent->appendChild($objNode);
		}
	}
	
	/**
	 * Returns the object's properties that correspond to the OpenURL standard
	 * as an easy to use associative array
	 *
	 * @return array
	 */
	
	private function referantArray()
	{
		$arrReferant = array();
		$strTitle = "";
		
		### simple values

		$arrReferant["rft.genre"] = $this->convertGenreOpenURL( $this->format );
		
		switch($arrReferant["rft.genre"])
		{
			case "dissertation":
				
				$arrReferant["rft_val_fmt"] = "info:ofi/fmt:kev:mtx:dissertation";
				break;				
			
			case "book":
			case "bookitem":
			case "conference":
			case "proceeding":
			case "report":
			case "document":
				
				$arrReferant["rft_val_fmt"] = "info:ofi/fmt:kev:mtx:book";
				break;

			case "journal":
			case "issue":
			case "article":
			case "proceeding":
			case "conference":
			case "preprint":
			case "unknown":
				$arrReferant["rft_val_fmt"] = "info:ofi/fmt:kev:mtx:journal";
				break;					
		}
		
		
		if ( count( $this->isbns ) > 0 )
		{
			$arrReferant["rft.isbn"] = $this->isbns[0];
		}
		
		if ( count( $this->issns ) > 0 )
		{
			$arrReferant["rft.issn"] = $this->issns[0];
		}
			
		// rft.ed_number not an actual openurl 1.0 standard element, 
		// but sfx recognizes it. But only add if the eric type
		// is ED, adding an EJ or other as an ED just confuses SFX. 

		if ( $this->eric_number)
		{
			$strEricType = substr( $this->eric_number, 0, 2 );
			
			if ( $strEricType == "ED" )
			{
				$arrReferant["rft.ed_number"] = $this->eric_number;
			}
		}
		
		$arrReferant["rft.series"] = $this->series_title;
		$arrReferant["rft.place"] = $this->place;
		$arrReferant["rft.pub"] = $this->publisher;
		$arrReferant["rft.date"] = $this->year;
		$arrReferant["rft.edition"] = $this->edition;
		$arrReferant["rft.tpages"] = $this->extent;
		$arrReferant["rft.jtitle"] = $this->journal_title;
		$arrReferant["rft.stitle"] = $this->short_title;
		$arrReferant["rft.volume"] = $this->volume;
		$arrReferant["rft.issue"] = $this->issue;
		$arrReferant["rft.spage"] = $this->start_page;
		$arrReferant["rft.epage"] = $this->end_page;
		$arrReferant["rft.degree"] = $this->degree;
		$arrReferant["rft.inst"] = $this->institution;
		
		### title

		if ( $this->non_sort != "" )
		{
			$strTitle = $this->non_sort . " ";
		}
		if ( $this->title != "" )
		{
			$strTitle .= $this->title . " ";
		}
		if ( $this->sub_title != "" )
		{
			$strTitle .= ": " . $this->sub_title . " ";
		}
			
		// map title to appropriate element based on genre
		
		if ( $arrReferant["rft.genre"] == "book" || 
			$arrReferant["rft.genre"] == "conference" || 
			$arrReferant["rft.genre"] == "proceeding" || 
			$arrReferant["rft.genre"] == "report" )
		{
			$arrReferant["rft.btitle"] = $strTitle;
		} 
		elseif ( $arrReferant["rft.genre"] == "bookitem" )
		{
			$arrReferant["rft.atitle"] = $strTitle;
			$arrReferant["rft.btitle"] = $this->book_title;
		} 
		elseif ( $arrReferant["rft.genre"] == "dissertation" )
		{
			$arrReferant["rft.title"] = $strTitle;
			
			// since this is sometimes divined from diss abs, we'll drop all
			// the journal stuff that is still in the openurl but messes up sfx

			$arrReferant["rft.jtitle"] = null;
			$arrReferant["rft.issn"] = null;
			$arrReferant["rft.volume"] = null;
			$arrReferant["rft.issue"] = null;
			$arrReferant["rft.spage"] = null;
			$arrReferant["rft.epage"] = null;
		} 
		elseif ( $arrReferant["rft.genre"] == "journal" )
		{
			$arrReferant["rft.title"] = $strTitle;
			
			// remove these elements from a journal, since they produce
			// some erroneous info, especially date!

			$arrReferant["rft.date"] = null;
			$arrReferant["rft.pub"] = null;
			$arrReferant["rft.place"] = null;
		} 
		else
		{
			$arrReferant["rft.atitle"] = $strTitle;
		}
		
		return $arrReferant;
	}

	/**
	 * Returns the object's properties that correspond to OpenURL standard
	 * rft_id URIs as a simple list array. 
	 *
	 * @return array
	 */
	
	private function referentIdentifierArray()
	{
		$results = array ();
		
		if ($this->oclc_number != "")
		{
			array_push ( $results, "info:oclcnum/" . $this->oclc_number );
		}
	
		// doi
		
		if ($this->doi != "")
		{
			array_push ( $results, "info:doi/" . $this->doi );
		}
			
		// sudoc, using rsinger's convention, http://dilettantes.code4lib.org/2009/03/a-uri-scheme-for-sudocs/
		
		if ($this->govdoc_number != "")
		{
			array_push ( $results, "http://purl.org/NET/sudoc/" . urlencode ( $this->govdoc_number ) );
		}
		
		return $results;
	}	
	
	/**
	 * Crosswalk the internal identified genre to one available in OpenURL 1.0
	 *
	 * @param string $strFormat		original internal genre/format
	 * @return string				OpenURL genre value
	 */
	
	private function convertGenreOpenURL($strFormat)
	{
		switch ( $strFormat )
		{
			case "Journal" :
			case "Newspaper" :
				
				return "journal";
				break;
			
			case "Issue" :
				
				return "issue";
				break;
			
			case "Tests & Measures":
			case "Book Review" :
			case "Film Review" :
			case "Review" :
			case "Article" :
				
				return "article";
				break;
			
			case "Conference Proceeding" :
				
				// take this over 'conference' ?
				return "proceeding";
				break;
			
			case "Preprint" :
				
				return "preprint";
				break;
			
			case "Book" :
			case "Pamphlet":

                                //take this over 'Pamphlet'?
				return "book";
				break;

			case "Book Chapter" :
			case "Essay" :

				//take this over 'Essay'?
				return "bookitem";
				break;
			
			case "Report" :
				
				return "report";
				break;
			
			case "Dissertation" :
			case "Thesis" :
				
				// not an actual openurl genre
				return "dissertation";
				break;
			
			default :
				
				// take this over 'document'?
				return "unknown";
		}
	}
	
	protected function stripEndPunctuation($strInput, $strPunct)
	{
		$bolDone = false;
		$arrPunct = str_split( $strPunct );
		
		if ( strlen( $strInput ) == 0 )
		{
			return $strInput;
		}
		
		// check if the input ends in a character entity
		// reference, in which case, leave it alone, yo!
		
		if ( preg_match('/\&\#[0-9a-zA-Z]{1,5}\;$/', $strInput) )
		{
			return $strInput;
		}
		
		while ( $bolDone == false )
		{
			$iEnd = strlen( $strInput ) - 1;
			
			foreach ( $arrPunct as $strPunct )
			{
				if ( substr( $strInput, $iEnd ) == $strPunct )
				{
					$strInput = substr( $strInput, 0, $iEnd );
					$strInput = trim( $strInput );
				}
			}
			
			$bolDone = true;
			
			foreach ( $arrPunct as $strPunct )
			{
				if ( substr( $strInput, $iEnd ) == $strPunct )
				{
					$bolDone = false;
				}
			}
		}
		
		return $strInput;
	}
	
	protected function extractYear($strYear)
	{
		$arrYear = array();
		
		if ( preg_match( "/[0-9]{4}/", $strYear, $arrYear ) != 0 )
		{
			return $arrYear[0];
		} 
		else
		{
			return null;
		}
	}
	
	protected function toTitleCase($strInput)
	{
		$arrMatches = ""; // matches from regular expression
		$arrSmallWords = ""; // words that shouldn't be capitalized if they aren't the first word.
		$arrWords = ""; // individual words in input
		$strFinal = ""; // final string to return
		$strLetter = ""; // first letter of subtitle, if any

		// if there are no lowercase letters (and its sufficiently long a title to 
		// not just be an aconym or something) then this is likely a title stupdily
		// entered into a database in ALL CAPS, so drop it entirely to 
		// lower-case first

		$iMatch = preg_match( "/[a-z]/", $strInput );
		
		if ( $iMatch == 0 && strlen( $strInput ) > 10 )
		{
			$strInput = Xerxes_Framework_Parser::strtolower( $strInput );
		}
		
		// array of small words
		
		$arrSmallWords = array ('of', 'a', 'the', 'and', 'an', 'or', 'nor', 'but', 'is', 'if', 'then', 
		'else', 'when', 'at', 'from', 'by', 'on', 'off', 'for', 'in', 'out', 'over', 'to', 'into', 'with', 'as' );
		
		// split the string into separate words

		$arrWords = explode( ' ', $strInput );
		
		foreach ( $arrWords as $key => $word )
		{
			// if this word is the first, or it's not one of our small words, capitalise it 
			
			if ( $key == 0 || ! in_array( Xerxes_Framework_Parser::strtolower( $word ), $arrSmallWords ) )
			{
				// make sure first character is not a quote or something
				
				if ( preg_match("/^[^a-zA-Z0-9]/", $word ) )
				{
					$first = substr($word,0,1);
					$rest = substr($word,1);
					
					$arrWords[$key] = $first . ucwords( $rest );
				}
				else
				{
					$arrWords[$key] = ucwords( $word );
				}
			} 
			elseif ( in_array( Xerxes_Framework_Parser::strtolower( $word ), $arrSmallWords ) )
			{
				$arrWords[$key] = Xerxes_Framework_Parser::strtolower( $word );
			}
		}
		
		// join the words back into a string

		$strFinal = implode( ' ', $arrWords );
		
		// catch subtitles

		if ( preg_match( "/: ([a-z])/", $strFinal, $arrMatches ) )
		{
			$strLetter = ucwords( $arrMatches[1] );
			$strFinal = preg_replace( "/: ([a-z])/", ": " . $strLetter, $strFinal );
		}
		
		// catch words that start with double quotes

		if ( preg_match( "/\"([a-z])/", $strFinal, $arrMatches ) )
		{
			$strLetter = ucwords( $arrMatches[1] );
			$strFinal = preg_replace( "/\"[a-z]/", "\"" . $strLetter, $strFinal );
		}
		
		// catch words that start with a single quote
		// need to be a little more cautious here and make sure there is a space before the quote when
		// inside the title to ensure this isn't a quote for a contraction or for possisive; separate
		// case to handle when the quote is the first word

		if ( preg_match( "/ '([a-z])/", $strFinal, $arrMatches ) )
		{
			$strLetter = ucwords( $arrMatches[1] );
			$strFinal = preg_replace( "/ '[a-z]/", " '" . $strLetter, $strFinal );
		}
		
		if ( preg_match( "/^'([a-z])/", $strFinal, $arrMatches ) )
		{
			$strLetter = ucwords( $arrMatches[1] );
			$strFinal = preg_replace( "/^'[a-z]/", "'" . $strLetter, $strFinal );
		}
		
		return $strFinal;
	}
	
	protected function ordinal($value)
	{
		if ( is_numeric( $value ) )
		{
			if ( substr( $value, - 2, 2 ) == 11 || substr( $value, - 2, 2 ) == 12 || substr( $value, - 2, 2 ) == 13 )
			{
				$suffix = "th";
			} 
			elseif ( substr( $value, - 1, 1 ) == 1 )
			{
				$suffix = "st";
			} 
			elseif ( substr( $value, - 1, 1 ) == 2 )
			{
				$suffix = "nd";
			} 
			elseif ( substr( $value, - 1, 1 ) == 3 )
			{
				$suffix = "rd";
			} 
			else
			{
				$suffix = "th";
			}
			
			return $value . $suffix;
		} 
		else
		{
			return $value;
		}
	}
	
	protected function isFullText($arrLink)
	{
		if ( $arrLink[2] == "pdf" || $arrLink[2] == "html" || $arrLink[2] == "online" )
		{
			return true; 
		}
		else
		{
			return false;
		}
	}
	
	### PROPERTIES ###
	
	public function hasFullText()
	{
		$bolFullText = false;
		
		foreach ( $this->links as $arrLink )
		{
			if ( $this->isFullText($arrLink) == true )
			{
				$bolFullText = true;
			}
		}
		
		return $bolFullText;
	}
	
	public function getFullText($bolFullText = false)
	{
		// limit to only full-text links

		if ( $bolFullText == true )
		{
			$arrFinal = array();
			
			foreach ( $this->links as $arrLink )
			{
				if ( $this->isFullText($arrLink) == true )
				{
					array_push( $arrFinal, $arrLink );
				}
			}
			
			return $arrFinal;
		} 
		else
		{
			// all the links

			return $this->links;
		}
	}
	
	public function getPrimaryAuthor($bolReverse = false)
	{
		$arrPrimaryAuthor = $this->getAuthors( true, true, $bolReverse );
		
		if ( count( $arrPrimaryAuthor ) > 0 )
		{
			return $arrPrimaryAuthor[0];
		} 
		elseif ( $this->author_from_title != "" )
		{
			return trim( $this->author_from_title );
		} 
		else
		{
			return null;
		}
	}
	
	/**
	 * Return authors.  Authors will return as array, with each author name optionally formatted
	 * as a string ('first last' or 'last, first') or as an associative array in parts, based on
	 * paramaters listed below.
	 *
	 * @param bool $bolPrimary		[optional] return just the primary author, default false
	 * @param bool $bolFormat		[optional] return the author names as strings (otherwise as objects), default false
	 * @param bool $bolReverse		[optional] return author names as strings, last name first
	 * @return array
	 */
	
	public function getAuthors($bolPrimary = false, $bolFormat = false, $bolReverse = false)
	{
		$arrFinal = array();
		
		foreach ( $this->authors as $objXerxesAuthor )
		{
			// author as string
			
			if ( $bolFormat == true )
			{
				$strAuthor = ""; // author name formatted

				$strFirst = $objXerxesAuthor->first_name;
				$strLast = $objXerxesAuthor->last_name;
				$strInit = $objXerxesAuthor->init;
				$strName = $objXerxesAuthor->name;
				
				if ( $strName != "" )
				{
					$strAuthor = $strName;
				} 
				else
				{
					if ( $bolReverse == false )
					{
						$strAuthor = $strFirst . " ";
						
						if ( $strInit != "" )
						{
							$strAuthor .= $strInit . " ";
						}
						
						$strAuthor .= $strLast;
					} 
					else
					{
						$strAuthor = $strLast . ", " . $strFirst . " " . $strInit;
					}
				}
				
				array_push( $arrFinal, $strAuthor );
			} 
			else
			{
				// author objects
				
				array_push( $arrFinal, $objXerxesAuthor );
			}
			
			// we're only asking for the primary author
			
			if ( $bolPrimary == true )
			{
				// sorry, only additional authors (7XX), so return empty
				
				if ( $objXerxesAuthor->additional == true )
				{
					return array();
				}
				else
				{
					// exit loop, we've got the author we need
					break;
				}
			}
		}
		
		return $arrFinal;
	}
	
	public function getTitle($bolTitleCase = false)
	{
		$strTitle = "";
		
		if ( $this->non_sort != "" )
		{
			$strTitle = $this->non_sort;
		}
		
		$strTitle .= $this->title;
		
		if ( $this->sub_title != "" )
		{
			$strTitle .= ": " . $this->sub_title;
		}
		
		if ( $bolTitleCase == true )
		{
			$strTitle = $this->toTitleCase( $strTitle );
		}
		
		return $strTitle;
	}
	
	public function getBookTitle($bolTitleCase = false)
	{
		if ( $bolTitleCase == true )
		{
			return $this->toTitleCase( $this->book_title );
		} 
		else
		{
			return $this->book_title;
		}
	}
	
	public function getJournalTitle($bolTitleCase = false)
	{
		if ( $bolTitleCase == true )
		{
			return $this->toTitleCase( $this->journal_title );
		} 
		else
		{
			return $this->journal_title;
		}
	}
	
	public function getISSN()
	{
		if ( count( $this->issns ) > 0 )
		{
			return $this->issns[0];
		} 
		else
		{
			return null;
		}
	}
	
	public function getISBN()
	{
		if ( count( $this->isbns ) > 0 )
		{
			return $this->isbns[0];
		} 
		else
		{
			return null;
		}
	}
	
	public function getAllISSN()
	{
		return $this->issns;
	}
	
	public function getAllISBN()
	{
		return $this->isbns;
	}
	
	public function getMainTitle()
	{
		return $this->title;
	}
	
	public function getEdition()
	{
		return $this->edition;
	}
	
	public function getControlNumber()
	{
		return $this->control_number;
	}
	
	public function isEditor()
	{
		return $this->editor;
	}
	
	public function getFormat()
	{
		return $this->format;
	}
	
	public function setFormat($format)
	{
		$this->format = $format;
	}
	
	public function getTechnology()
	{
		return $this->technology;
	}
	
	public function getNonSort()
	{
		return $this->non_sort;
	}
	
	public function getSubTitle()
	{
		return $this->sub_title;
	}
	
	public function getSeriesTitle()
	{
		return $this->series_title;
	}
	
	public function getAbstract()
	{
		return $this->abstract;
	}
	
	public function getSummary()
	{
		return $this->summary;
	}
	
	public function getDescription()
	{
		return $this->description;
	}
	
	public function getLanguage()
	{
		return $this->language;
	}
	
	public function getTOC()
	{
		return $this->toc;
	}
	
	public function getPlace()
	{
		return $this->place;
	}
	
	public function getPublisher()
	{
		return $this->publisher;
	}
	
	public function getYear()
	{
		return $this->year;
	}
	
	public function getJournal()
	{
		return $this->journal;
	}
	
	public function getVolume()
	{
		return $this->volume;
	}
	
	public function getIssue()
	{
		return $this->issue;
	}
	
	public function getStartPage()
	{
		return $this->start_page;
	}
	
	public function getEndPage()
	{
		return $this->end_page;
	}
	
	public function getPages()
	{
		$pages = $this->start_page;
		
		if ( $this->getEndPage() != "" )
		{
			$pages .= "-" . $this->getEndPage();
		}
		
		return $pages;
	}
	
	public function getExtent()
	{
		return $this->extent;
	}
	
	public function getPrice()
	{
		return $this->price;
	}
		
	public function getNotes()
	{
		return $this->notes;
	}
		
	public function getSubjects() 
	{
		return $this->subjects;
	}
		
	public function getInstitution()
	{
		return $this->institution;
	}
		
	public function getDegree()
	{
		return $this->degree;
	}
		
	public function getCallNumber()
	{
		return $this->call_number;
	}
		
	public function getOCLCNumber()
	{
		return $this->oclc_number;
	}
		
	public function getDOI()
	{
		return $this->doi;
	}
	
	public function getSource()
	{
		return $this->source;
	}

	public function setSource($source)
	{
		$this->source = $source;
	}	
	
	public function setRefereed($bool)
	{
		$this->refereed = (bool) $bool;
	}
	
	public function getRefereed()
	{
		return $this->refereed;
	}
	
	public function setSubscription($bool)
	{
		$this->subscription = (bool) $bool;
	}
	
	public function getSubscription()
	{
		return $this->subscription;
	}
	
	public function getOriginalXML($bolString = false)
	{
		if ( $bolString == true )
		{
			return $this->document->saveXML();
		}
		else
		{
			return $this->document;
		}
	}
	
	public function getRecordID()
	{
		return $this->record_id;
	}
	
	public function setRecordID($id)
	{
		return $this->record_id = $id;
	}
	
	public function hasPhysicalHoldings()
	{
		return $this->physical_holdings;
	}
	
	public function setScore($score)
	{
		$this->score = $score;
	}
}

class Xerxes_Record_Subject
{
	public $value;
	public $display;
}

class Xerxes_Record_Author
{
	public $first_name;
	public $last_name;
	public $init;
	public $name;
	public $type;
	public $additional;
	public $display;
	
	public function allFields()
	{
		$values = "";
		
		foreach ( $this as $key => $value )
		{
			if ( $key == "additional" || $key == "display")
			{
				continue;
			}
			
			$values .= $value . " ";
		}
		
		return trim($values);
	}
}

class Xerxes_Record_ContextObject
{
	private $values = array();
	private $referrant = array();
	public $authors;
	
	public function __get($name)
	{
		if ( array_key_exists($name, $this->values))
		{
			return $this->values[$name];
		}
		elseif ( ! in_array($name, $this->referrant) )
		{
			throw new Exception("'$name' is not a valid Xerxes_Record_ContextObject property");
		}
		else
		{
			return null;
		}
	}
	
	public function __construct($document = null)
	{
		if ( $document instanceof DOMDocument )
		{
			$this->referrant = array("atitle","genre","date","title","stitle", "jtitle","volume","issue","spage",
				"epage","issn","isbn");
			
			$xpath = new DOMXPath($document);
			
			// test to see what profile the context object is using
			// set namespace accordingly
	
			if ($document->getElementsByTagNameNS ( "info:ofi/fmt:xml:xsd:book", "book" )->item ( 0 ) != null)
			{
				$xpath->registerNamespace ( "rft", "info:ofi/fmt:xml:xsd:book" );
			} 
			elseif ($document->getElementsByTagNameNS ( "info:ofi/fmt:xml:xsd:dissertation", "dissertation" )->item ( 0 ) != null)
			{
				$xpath->registerNamespace ( "rft", "info:ofi/fmt:xml:xsd:dissertation" );
			} 		
			elseif ($document->getElementsByTagNameNS ( "info:ofi/fmt:xml:xsd", "journal" )->item ( 0 ) != null)
			{
				$xpath->registerNamespace ( "rft", "info:ofi/fmt:xml:xsd" );
			}
			else
			{
				$xpath->registerNamespace ( "rft", "info:ofi/fmt:xml:xsd:journal" );
			}
			
			// extract values
			
			foreach ( $this->referrant as $ref )
			{
				$node = $xpath->query( "//rft:$ref" )->item ( 0 );
				
				if ( $node != null )
				{
					$this->values[$ref] = $node->nodeValue;
				}
			}
	
			$this->authors = $xpath->query( "//rft:author[rft:aulast != '' or rft:aucorp != '']" );
		}
	}
}

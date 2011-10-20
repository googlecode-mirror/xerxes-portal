<?php

class Xerxes_Record_Bibliographic_Document extends Xerxes_Marc_Document 
{
	protected $record_type = "Xerxes_Record_Bibliographic";
}

/**
 * Extract properties for books, articles, and dissertations from MARC-XML & OpenURL context object
 * 
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Record_Bibliographic extends Xerxes_Record
{
	protected $marc; // marc object	
	protected $context_object; // openurl context object
	
	public function __construct()
	{
		parent::__construct();
		$this->utility[] = "marc";
		$this->utility[] = "context_object";
	}	
	
	public function loadXML($xml)
	{
		$this->marc = new Xerxes_Marc_Record();
		$this->marc->loadXML($xml);

		$this->context_object = new Xerxes_Record_ContextObject();
		$this->context_object->loadXML($xml);		
		
		parent::loadXML($xml);
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
		
		// thesis: degree, institution, date awarded
				
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
		$this->control_number = (string) $this->marc->controlfield("001");
		$this->record_id = $this->control_number;		
	}
	
	protected function parseOCLC()
	{
		### oclc number
		
		// oclc number can be either in the 001 or in the 035$a
		// make sure 003 says 001 is oclc number or 001 includes an oclc prefix, 
		
		$str001 = (string) $this->marc->controlfield("001");
		$str003 = (string) $this->marc->controlfield("003");
		$str035 = (string) $this->marc->datafield("035")->subfield("a");

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
		$issns = $this->marc->fieldArray("022", "a" );
		$journal_issn = (string) $this->marc->datafield("773")->subfield("x");
		
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
		$isbns = $this->marc->fieldArray("020", "az" );
		
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
		
		$field_024 = $this->marc->fieldArray("024", "a");
		
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
		$call_number = (string) $this->marc->datafield("050");
		$call_number_local = (string) $this->marc->datafield("090");
		
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
		$this->govdoc_number = (string) $this->marc->datafield("086")->subfield("a");		
		$this->gpo_number = (string) $this->marc->datafield("074")->subfield("a");
	}

	protected function parseThesis()
	{
		$thesis = (string) $this->marc->datafield("502")->subfield("a");
		
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

		$this->author_from_title = (string) $this->marc->datafield("245")->subfield("c" );
		
		$objConfName =  $this->marc->datafield("111"); // "anc"
		$objAddAuthor = $this->marc->datafield("700"); // "a"
		$objAddCorp = $this->marc->datafield("710"); // "ab"
		$objAddConf = $this->marc->datafield("711"); // "acn"
		
		// conference and corporate names from title ?

		$objConferenceTitle = $this->marc->datafield("811"); // all
		
		if ( $objAddConf->length() == 0 && $objConferenceTitle->length() > 0 )
		{
			$objAddConf = $objConferenceTitle;
		}
		
		$objCorporateTitle = $this->marc->datafield("810"); // all
		
		if ( $objAddCorp->length() == 0 && $objCorporateTitle->length() > 0 )
		{
			$objAddCorp = $objCorporateTitle;
		}
		
		// personal primary author
		
		if ( $this->marc->datafield("100")->length() > 0 )
		{
			$objXerxesAuthor = $this->makeAuthor( $this->marc->datafield("100"), "a", "personal" );
			array_push( $this->authors, $objXerxesAuthor );
		} 
		elseif ( $objAddAuthor->length() > 0 )
		{
			// editor

			$objXerxesAuthor = $this->makeAuthor( $objAddAuthor->item(0), "a", "personal", true);
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
				$objXerxesAuthor = $this->makeAuthor( $obj700, "a", "personal", true );
				array_push( $this->authors, $objXerxesAuthor );
			}
		}
		
		// corporate author
		
		if ( (string) $this->marc->datafield("110")->subfield("ab") != "" )
		{
			$objXerxesAuthor = $this->makeAuthor( $this->marc->datafield("110"), "ab", "corporate" );
			array_push( $this->authors, $objXerxesAuthor );
		}
		
		// additional corporate authors

		if ( $objAddCorp->length() > 0 )
		{
			foreach ( $objAddCorp as $objCorp )
			{
				$objXerxesAuthor = $this->makeAuthor( $objCorp, "ab", "corporate", true );
				array_push( $this->authors, $objXerxesAuthor );
			}
		}
		
		// conference name

		if ( $objConfName->length() > 0)
		{
			$objXerxesAuthor = $this->makeAuthor( $objConfName, "anc", "conference" );
			array_push( $this->authors, $objXerxesAuthor );
		}
		
		// additional conference names

		if ( $objAddConf->length() > 0 )
		{
			foreach ( $objAddConf as $objConf )
			{
				$objXerxesAuthor = $this->makeAuthor( $objConf, "acn", "conference", true );
				array_push( $this->authors, $objXerxesAuthor );
			}
		}
		
		// last-chance from context-object
		
		if ( count($this->authors) == 0 )
		{
			foreach ( $this->context_object->getAuthors() as $objAuthor )
			{
				array_push($this->authors, $objAuthor);
			}
		}		
	}
	
	protected function makeAuthor($author, $subfields, $strType, $bolAdditional = false)
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
		
		return new Xerxes_Record_Author($author_string, $author_display, $strType, $bolAdditional);
	}

	protected function parseTitle()
	{
		$this->title = (string) $this->marc->datafield("245")->subfield("anp");
		$this->sub_title = (string) $this->marc->datafield("245")->subfield("b");
		$this->uniform_title = (string) $this->marc->datafield("130|240");
		
		// sometimes title is solely in subfield p
		
		$title_part =  (string) $this->marc->datafield("245")->subfield("p" );
		
		if ( $this->title == "" && $title_part != "" )
		{
			$this->title = $title_part;
		}
		
		// sometimes the title appears in a 242 or even a 246 if it is translated from another
		// language, although the latter is probably bad practice.  We will only take these
		// if the title in the 245 is blank, and take a 242 over the 246

		$translated_title = (string) $this->marc->datafield("242")->subfield("a");
		$translated_subtitle = (string) $this->marc->datafield("242")->subfield("b");
		
		$varying_title = (string) $this->marc->datafield("246")->subfield("a");
		$varying_subtitle = (string) $this->marc->datafield("246")->subfield("b");
		
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
		$this->place = (string) $this->marc->datafield("260")->subfield("a");
		$this->publisher = (string) $this->marc->datafield("260")->subfield("b");		
	}
	
	protected function parseBookInfo()
	{
		$this->edition = (string) $this->marc->datafield("250")->subfield("a");
		$this->extent = (string) $this->marc->datafield("300")->subfield("a");
		$this->description = (string) $this->marc->datafield("300");
		$this->price = (string) $this->marc->datafield("365");
	}
	
	protected function parseYear()
	{
		$date = (string) $this->marc->datafield("260")->subfield("c");
		
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
		$this->series_title = (string) $this->marc->datafield("440")->subfield("a" );
	}
	
	protected function parseAdditionalTitles()
	{
		// additional titles for display
		
		foreach ( $this->marc->datafield('730|740') as $additional_titles )
		{
			$subfields = (string) $additional_titles->subfield();
			array_push($this->additional_titles, $subfields);
		}		
	}

	protected function parseNotes()
	{
		$tocs = $this->marc->fieldArray("505", "agrt");

		foreach ( $tocs as $toc )
		{
			$this->toc .=  $toc;
		}
		
		// other notes
		
		$note_object = $this->marc->xpath("//marc:datafield[@tag >= 500 and @tag < 600 and " .
			"@tag != 505 and @tag != 520 and @tag != 546]" );
		
		foreach ( $note_object as $note )
		{
			array_push($this->notes, $note->nodeValue);
		}		
	}
	
	protected function parseAbstract()
	{
		$abstracts = $this->marc->fieldArray("520", "a");		
		
		foreach ( $abstracts as $abstract )
		{
			$this->abstract .= " " . $abstract;
		}
		
		$this->abstract = trim( strip_tags( $this->abstract ) );		
	}
	
	protected function parseFormat()
	{
		$this->technology = (string) $this->marc->datafield("538")->subfield("a");
		$this->format = $this->extractFormat();
	}
	
	/**
	 * Determines the format/genre of the item, broken out here for clarity
	 */
	
	protected function extractFormat()
	{
		// thesis
		
		if ( (string) $this->marc->datafield("502") != "" || (string) $this->marc->controlfield("002") == "DS" )
		{
			return  Xerxes_Record_Format::Thesis;
		}
		elseif ( $this->marc->datafield("111")->length() > 0 || $this->marc->datafield("711")->length() > 0 )
		{
			return Xerxes_Record_Format::ConferencePaper;
		}
		
		
		else
		{
			$format_array = $this->marc->fieldArray("513", "a");
		
			if ( $this->context_object->genre != null )
			{
				array_push($format_array, $this->context_object->genre );
			}
	
			$title_format = (string) $this->marc->datafield("245")->subfield("k");
			
			if ( $title_format != null )
			{
				array_push( $this->format_array, $title_format );
			}

		}
		
		
		if ( $this->journal != "" )
		{
			return  Xerxes_Record_Format::Article; 
		}
		else
		{
			// high-level format from leader
	
			$chrLeader6 = "";
			$chrLeader7 = "";
			$obj008 = $this->marc->controlfield("008");
			
			if ( strlen( (string) $this->leader() ) >= 8 )
			{
				$chrLeader6 = substr( (string) $this->leader(), 6, 1 );
				$chrLeader7 = substr( (string) $this->leader(), 7, 1 );
			}		
			
			if ( $chrLeader6 == 'a' && $chrLeader7 == 'm' ) return Xerxes_Record_Format::Book;
			if ( $chrLeader6 == 'a' && $chrLeader7 == 's' && $obj008->position("21") == 'n' ) return Xerxes_Record_Format::Newspaper;
			if ( $chrLeader6 == 'a' && $chrLeader7 == 's' ) return Xerxes_Record_Format::Serial; 
			if ( $chrLeader6 == 'a' && $chrLeader7 == 'i' ) return Xerxes_Record_Format::Website; 
			if ( $chrLeader6 == 'c' || $chrLeader6 == 'd' ) return Xerxes_Record_Format::MusicalScore; 
			if ( $chrLeader6 == 'e' || $chrLeader6 == 'f' ) return Xerxes_Record_Format::Map;
			if ( $chrLeader6 == 'g' ) return Xerxes_Record_Format::Video; 
			if ( $chrLeader6 == 'i' || $chrLeader6 == 'j' ) return Xerxes_Record_Format::SoundRecording; 
			if ( $chrLeader6 == 'k' ) return Xerxes_Record_Format::Image; 
			if ( $chrLeader6 == 'm' && $chrLeader7 == 'i' ) return Xerxes_Record_Format::Website; 
			if ( $chrLeader6 == 'm' ) return Xerxes_Record_Format::Unknown; 
			if ( $chrLeader6 == 'o' ) return Xerxes_Record_Format::Kit; 
			if ( $chrLeader6 == 'p' ) return Xerxes_Record_Format::MixedMaterial; 
			if ( $chrLeader6 == 'r' ) return Xerxes_Record_Format::PhysicalObject;
			if ( $chrLeader6 == 't' ) return Xerxes_Record_Format::Manuscript;
	
			if ( count( $this->isbns ) > 0 ) return Xerxes_Record_Format::Book; 
			if ( count( $this->issns ) > 0 ) return Xerxes_Record_Format::Article;
		}

		// if we got this far, just return unknown
		return Xerxes_Record_Format::Unknown;	
	}	
	
	protected function parseSubjects()
	{
		// we'll exclude the numeric subfields since they contain information about the
		// source of the subject terms, which are probably not needed for display?

		foreach ( $this->marc->datafield("6XX") as $subject )
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

		foreach ( $this->marc->datafield('4XX|800|810|811|830') as $subject )
		{
			array_push($this->series, (string) $subject);
		}			
	}
	
	protected function parseJournal()
	{
		### all journal data
		
		$this->journal = (string) $this->marc->datafield("773")->subfield("atgbcdefhijklmnopqrsuvwxyz1234567890", true);		
		
		
		### journal title
		
		// specify the order of the subfields in 773 for journal as $a $t $g and then everything else
		// in case they are out of order 

		$this->journal_title = (string) $this->marc->datafield("773")->subfield("t");
		$this->short_title = (string) $this->marc->datafield("773")->subfield("p");

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
		
		$strExtentHost = (string) $this->marc->datafield("773")->subfield("h");
		$strJournal = (string) $this->marc->datafield("773")->subfield("agpqt");
		
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
		
		$this->alt_scripts = $this->marc->fieldArray("880", "abcdefghijklmnopqrstuvwxyz12345789" );
		
		// now use the $6 to figure out which character-script this is
		// assume just one for now

		$alt_script = (string) $this->marc->datafield("880")->subfield("6");
		
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

		$language_note = (string) $this->marc->datafield("546")->subfield("a");
		
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
			
			$language_object = (string) $this->marc->controlfield("008");
			
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

		foreach ( $this->marc->datafield("856") as $link )
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
			
			// no url supplied
			
			if ( (string) $link->subfield("u") == "" )
			{
				continue;
			}
			
			// link includes loc url (bad catalogers!)
			
			if ( stristr($url, "catdir") || $resource_type == 2 )
			{
				$this->links[] = new Xerxes_Record_Link($url, Xerxes_Record_Link::INFORMATIONAL);
			}
			else
			{
				$link_object = new Xerxes_Record_Link($url, null, $display);
				
				// we check these a bit differently, since we don't want the presence of .html in the 
				// URL alone to determine if the format is html, only if other subfields say so
				// but we will take .pdf in the link as indicating the file is PDF
				
				$link_html_check = $display . "" . $link_format_type . " " . $link_text;
				$link_pdf_check = $link_html_check . " " . $url;
				
				if ( $link_object->extractFormat($link_pdf_check) == Xerxes_Record_Link::PDF )
				{
					$link_object->setFormat(Xerxes_Record_Link::PDF);
				}
				elseif ( $link_object->extractFormat($link_html_check) == Xerxes_Record_Link::HTML )
				{
					$link_object->setFormat(Xerxes_Record_Link::HTML);
				}
				
				$this->links[] = $link_object;
			}
		}		
	}
}
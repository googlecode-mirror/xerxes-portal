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
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Record extends Xerxes_Marc_Record
{
	protected $source = "";	// source database id
	protected $database_name; // source database name

	protected $format = ""; // format
	protected $format_array = array(); // possible formats
	protected $technology = ""; // technology/system format

	protected $control_number = ""; // the 001 basically, OCLC or otherwise
	protected $oclc_number = ""; // oclc number
	protected $govdoc_number = ""; // gov doc number
	protected $gpo_number = ""; // gov't printing office (gpo) number
	protected $eric_number = ""; // eric document number
	protected $isbns = array ( ); // isbn
	protected $issns = array ( ); // issn
	protected $call_number = ""; // lc call number
	protected $doi = ""; // doi

	protected $authors = array ( ); // authors
	protected $author_from_title = ""; // author from title statement
	protected $editor = false; // whether primary author is an editor
	
	protected $non_sort = ""; // non-sort portion of title
	protected $title = ""; // main title
	protected $sub_title = ""; // subtitle	
	protected $series_title = ""; // series title
	protected $trans_title = false; // whether title is translated
	
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
	protected $language = ""; // primary language of the record
	protected $notes = array ( ); // notes that are not the abstract, language, or table of contents
	protected $subjects = array ( ); // subjects
	protected $toc = ""; // table of contents note
	
	protected $links = array ( ); // all supplied links in the record both full text and non
	protected $embedded_text = array ( ); // full text embedded in document
	
	protected $alt_script = array ( ); // alternate character-scripts like cjk or hebrew, taken from 880s
	protected $alt_script_name = ""; // the name of the alternate character-script; we'll just assume one for now, I guess
	

	### PUBLIC FUNCTIONS ###
	
	/**
	 * Maps the marc data to the object's properties
	 */
	
	protected function map()
	{
		## openurl
		
		// the source can contain an openurl context object buried in it as well as marc-xml
		
		// test to see what profile the context object is using; set namespace accordingly

		if ($this->document->getElementsByTagNameNS ( "info:ofi/fmt:xml:xsd:book", "book" )->item ( 0 ) != null)
		{
			$this->xpath->registerNamespace ( "rft", "info:ofi/fmt:xml:xsd:book" );
		} 
		elseif ($this->document->getElementsByTagNameNS ( "info:ofi/fmt:xml:xsd:dissertation", "dissertation" )->item ( 0 ) != null)
		{
			$this->xpath->registerNamespace ( "rft", "info:ofi/fmt:xml:xsd:dissertation" );
		} 		
		elseif ($this->document->getElementsByTagNameNS ( "info:ofi/fmt:xml:xsd", "journal" )->item ( 0 ) != null)
		{
			$this->xpath->registerNamespace ( "rft", "info:ofi/fmt:xml:xsd" );
		}

		else
		{
			$this->xpath->registerNamespace ( "rft", "info:ofi/fmt:xml:xsd:journal" );
		}
		
		// context object: 

		// these just in case
		
		$objATitle = $this->xpath->query( "//rft:atitle" )->item ( 0 );
		$objBTitle = $this->xpath->query( "//rft:atitle" )->item ( 0 );
		$objAuthors = $this->xpath->query( "//rft:author[rft:aulast != '' or rft:aucorp != '']" );
		$objGenre = $this->xpath->query( "//rft:genre" )->item ( 0 );
		$objDate = $this->xpath->query( "//rft:date" )->item ( 0 );
		
		// journal title, volume, issue, pages from context object
		
		$objTitle = $this->xpath->query( "//rft:title" )->item ( 0 );
		$objSTitle = $this->xpath->query( "//rft:stitle" )->item ( 0 );
		$objJTitle = $this->xpath->query( "//rft:jtitle" )->item ( 0 );
		$objVolume = $this->xpath->query( "//rft:volume" )->item ( 0 );
		$objIssue = $this->xpath->query( "//rft:issue" )->item ( 0 );
		$objStartPage = $this->xpath->query( "//rft:spage" )->item ( 0 );
		$objEndPage = $this->xpath->query( "//rft:epage" )->item ( 0 );
		$objISSN = $this->xpath->query( "//rft:issn" )->item ( 0 );
		$objISBN = $this->xpath->query( "//rft:isbn" )->item ( 0 );
		
		
		if ($objSTitle != null) $this->short_title = $objSTitle->nodeValue;
		if ($objVolume != null)	$this->volume = $objVolume->nodeValue;
		if ($objIssue != null) $this->issue = $objIssue->nodeValue;
		if ($objStartPage != null) $this->start_page = $objStartPage->nodeValue;
		if ($objEndPage != null) $this->end_page = $objEndPage->nodeValue;
		if ($objISBN != null) array_push($this->isbns, $objISBN->nodeValue);
		if ($objISSN != null) array_push($this->issns, $objISSN->nodeValue);
		if ($objGenre != null) array_push($this->format_array, $objGenre->nodeValue);
		
		// control and standard numbers
		
		$this->control_number = (string) $this->controlfield("001");
		
		$arrIssn = $this->fieldArray("022", "a" );
		$arrIsbn = $this->fieldArray("020", "a" );

		$this->govdoc_number = (string) $this->datafield("086")->subfield("a");
		$this->gpo_number = (string) $this->datafield("074")->subfield("a");
		
		// doi
				
		$this->doi = (string) $this->datafield("024")->subfield("a");
		
		// this is kind of iffy since the 024 is not _really_ a DOI field; but this
		// is the most likely marc field; however need to see if the number follows the very loose
		// pattern of the DOI of 'prefix/suffix', where prefix and suffix can be nearly anything
		
		if ( $this->doi != "" )
		{
			// strip any doi: prefix
			
			$this->doi = str_replace( "doi:", "", $this->doi );
			
			if ( ! preg_match("/.*\/.*/", $this->doi) )
			{
				$this->doi = "";
			}
		}
		
		$strJournalIssn = (string) $this->datafield("773")->subfield("x");
		
		if ( $strJournalIssn != null )
		{
			array_push( $arrIssn, $strJournalIssn );
		}
			
		// call number

		$strCallNumber = (string) $this->datafield("050");
		$strCallNumberLocal = (string) $this->datafield("090");
		
		if ( $strCallNumber != null )
		{
			$this->call_number = $strCallNumber;
		} 
		elseif ( $strCallNumberLocal != null )
		{
			$this->call_number = $strCallNumberLocal;
		}
		
		// format
		
		$this->technology = (string) $this->datafield("538")->subfield("a");
		
		$arrFormat = $this->fieldArray("513", "a");

		foreach ( $arrFormat as $format )
		{
			array_push($this->format_array, (string) $format);
		}
		
		$strTitleFormat = (string) $this->datafield("245")->subfield("k");
		
		if ( $strTitleFormat != null )
		{
			array_push( $this->format_array, $strTitleFormat );
		}
			
		// thesis degree, institution, date awarded
		
		$strThesis = (string) $this->datafield("502")->subfield("a");
		
		// authors

		$strPrimaryAuthor = (string) $this->datafield("100")->subfield("a");

		$strCorpName = (string) $this->datafield("110")->subfield("ab");
		
		$strConfName = (string) $this->datafield("111")->subfield("anc");
		$this->author_from_title = (string) $this->datafield("245")->subfield("c" );
		
		$arrAltAuthors = $this->fieldArray("700", "a" );
		$arrAddCorp = $this->fieldArray("710", "ab" );
		$arrAddConf = $this->fieldArray("711", "acn" );
		
		// conference and corporate names from title ?

		$arrConferenceTitle = $this->fieldArray("811");
		
		if ( $arrAddConf == null && $arrConferenceTitle != null )
		{
			$arrAddConf = $arrConferenceTitle;
		}
		
		$arrCorporateTitle = $this->fieldArray("810");
		
		if ( $arrAddCorp == null && $arrCorporateTitle != null )
		{
			$arrAddCorp = $arrCorporateTitle;
		}
		
		if ( $strConfName != null || $arrAddConf != null )
		{
			array_push( $this->format_array, "conference paper" );
		}
			
		
		### title
		
		$this->title = (string) $this->datafield("245")->subfield("a");
		$this->sub_title = (string) $this->datafield("245")->subfield("b");
		$this->series_title = (string) $this->datafield("440")->subfield("a" );
		
		// sometimes title is in subfield p
		
		$title_part = (string) $this->datafield("245")->subfield("p" );
		
		if ( $this->title == "" && $title_part != "" )
		{
			$this->title = $title_part;
		}
		
		// sometimes the title appears in a 242 or even a 246 if it is translated from another
		// language, although the latter is probably bad practice.  We will only take these
		// if the title in the 245 is blank, and take a 242 over the 246

		$strTransTitle = (string) $this->datafield("242")->subfield("a");
		$strTransSubTitle = (string) $this->datafield("242")->subfield("b");
		
		$strVaryingTitle = (string) $this->datafield("246")->subfield("a" );
		$strVaryingSubTitle = (string) $this->datafield("246")->subfield("b");
		
		if ( $this->title == "" && $strTransTitle != "" )
		{
			$this->title = $strTransTitle;
			$this->trans_title = true;
		} 
		elseif ( $this->title == "" && $strVaryingTitle != "" )
		{
			$this->title = $strVaryingTitle;
			$this->trans_title = true;
		}
		
		if ( $this->sub_title == "" && $strTransSubTitle != "" )
		{
			$this->sub_title = $strTransTitle;
			$this->trans_title = true;
		} 
		elseif ( $this->sub_title == "" && $strVaryingSubTitle != "" )
		{
			$this->sub_title = $strVaryingSubTitle;
			$this->trans_title = true;
		}
		
		// last chance, check the context object
		
		if ( $this->title == "" && $objATitle != null )
		{
			$this->title = $objATitle->nodeValue;
		}
		elseif ( $this->title == "" && $objBTitle != null )
		{
			$this->title = $objBTitle->nodeValue;
		}		
		
		
		// edition, extent, description

		$this->edition = (string) $this->datafield("250")->subfield("a" );
		$this->extent = (string) $this->datafield("300")->subfield("a" );
		$this->description = (string) $this->datafield("300");
		$this->price = (string) $this->datafield("365");
		
		// publisher
		
		$this->place = (string) $this->datafield("260")->subfield("a");
		$this->publisher = (string) $this->datafield("260")->subfield("b");
		
		// date

		$strDate = (string) $this->datafield("260")->subfield("c");
		
		// notes
		
		$arrToc = $this->fieldArray("505", "agrt");

		foreach (  $arrToc as $toc )
		{
			$this->toc .= (string) $toc;
		}
		
		$arrAbstract = $this->fieldArray("520", "a");
		$strLanguageNote = (string) $this->datafield("546")->subfield("a");
		
		// other notes
		
		$objNotes = $this->xpath("//marc:datafield[@tag >= 500 and @tag < 600 and @tag != 505 and @tag != 520 and @tag != 546]" );
		
		foreach ( $objNotes as $objNote )
		{
			array_push($this->notes, $objNote->nodeValue);
		}
		
		// subjects

		// we'll exclude the numeric subfields since they contain information about the
		// source of the subject terms, which are probably not needed for display?

		foreach ( $this->datafield("6XX") as $subject )
		{
			$subfields = (string) $subject->subfield("abcdefghijklmnopqrstuvwxyz");
			array_push($this->subjects, $subfields);
		}
		
		// journal
		
		// specify the order of the subfields in 773 for journal as $a $t $g and then everything else
		//  in case they are out of order 
		
		$this->journal = (string) $this->datafield("773")->subfield("atgbcdefhijklmnopqrsuvwxyz1234567890");
		$strJournal = (string) $this->datafield("773")->subfield("agpt");
		$this->journal_title = (string) $this->datafield("773")->subfield("t");
		$this->short_title = (string) $this->datafield("773")->subfield("p");
		$strExtentHost = (string) $this->datafield("773")->subfield("h");
		
		// alternate character-scripts
		
		// the 880 represents an alternative character-script, like Hebrew or CJK;
		// for simplicity's sake, we just dump them all here in an array, with the 
		// intent of displaying them in paragraphs together in the interface or something?
		
		// we get every field except for the $6 which is a linking field

		$this->alt_script = $this->fieldArray("880", "abcdefghijklmnopqrstuvwxyz12345789" );
		
		// now use the $6 to figure out which character-script this is
		// assume just one for now

		$strAltScript = (string) $this->datafield("880")->subfield("6");
		
		if ( $strAltScript != null )
		{
			$arrMatchCodes = array ( );
			
			$arrScriptCodes = array ("(3" => "Arabic", "(B" => "Latin", '$1' => "CJK", "(N" => "Cyrillic", "(S" => "Greek", "(2" => "Hebrew" );
			
			if ( preg_match( "/[0-9]{3}-[0-9]{2}\/(.*)/", $strAltScript, $arrMatchCodes ) )
			{
				if ( array_key_exists( $arrMatchCodes[1], $arrScriptCodes ) )
				{
					$this->alt_script_name = $arrScriptCodes[$arrMatchCodes[1]];
				}
			}
		}
		
		### volume, issue, pagination
		
		// a best guess extraction of volume, issue, pages from 773

		$arrRegExJournal = $this->parseJournalData( $strJournal );
		
		// some sources include ^ as a filler character in issn/isbn, these people should be shot!

		foreach ( $arrIssn as $strIssn )
		{
			if ( strpos( $strIssn, "^" ) === false )
			{
				array_push( $this->issns, $strIssn);
			}
		}
		
		foreach ( $arrIsbn as $strIsbn )
		{
			if ( strpos( $strIsbn, "^" ) === false )
			{
				array_push( $this->isbns, $strIsbn );
			}
		}
		
		### language

		// take an explicit lanugage note over 008 if available

		if ( $strLanguageNote != null )
		{
			$strLanguageNote = $this->stripEndPunctuation( $strLanguageNote, "." );
			
			if ( strlen( $strLanguageNote ) == 2 )
			{
				$this->language = $this->convertLanguageCode( $strLanguageNote, true );
			} 
			elseif ( strlen( $strLanguageNote ) == 3 )
			{
				$this->language = $this->convertLanguageCode( $strLanguageNote );
			} 
			elseif ( ! stristr( $strLanguageNote, "Undetermined" ) )
			{
				$this->language = str_ireplace( "In ", "", $strLanguageNote );
			}
		} 
		else
		{
			// get the language code from the 008
			
			$objLang = $this->controlfield("008");
			
			if ( $objLang != null )
			{
				$strLangCode = $objLang->position("35-37");

				if ( $strLangCode != "")
				{
					$this->language = $this->convertLanguageCode($strLangCode);
				}			
			}
		}
		
		### format

		$this->format = $this->parseFormat( $this->format_array );
		

		### full-text
		
		// examine the 856s present in the record to see if they are in
		// fact to full-text, and not to a table of contents or something
		// stupid like that, by checking for existence of subfield code 3

		foreach ( $this->datafield("856") as $link )
		{
			$strUrl = (string) $link->subfield("u");
			
			$strDisplay = (string) $link->subfield("z");
			
			if ( $strDisplay == "" )
			{
				$strDisplay = (string) $link->subfield("a");
			}
			
			// no link supplied
			
			if ( (string) $link->subfield("u") == "" )
			{
				continue;
			}
			
			// has subfield 3 or the link includes loc url but was not specified (bad catalogers!)
			
			if ( $link->subfield("3") != "" || stristr($strUrl, "www.loc.gov/catdir") )
			{
				array_push( $this->links, array (null, $link->subfield("u"), "none" ) );
			}
			else
			{
				$strLinkFormat = "online";
					
				if ( stristr( $strDisplay, "PDF" ) || stristr( $strUrl, "PDF" ) )
				{
					$strLinkFormat = "pdf";
				} 
				elseif ( stristr( $strDisplay, "HTML" ) )
				{
					$strLinkFormat = "html";
				}
				
				array_push( $this->links, array ($strDisplay, $strUrl, $strLinkFormat ) );
			}
		}
		
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
		
		$arrOclc = array ( );
		
		if ( preg_match( "/[0-9]{1,}/", $this->oclc_number, $arrOclc ) != 0 )
		{
			$strJustOclcNumber = $arrOclc[0];
			
			// strip out leading 0s

			$strJustOclcNumber = preg_replace( "/^0{1,8}/", "", $strJustOclcNumber );
			
			$this->oclc_number = $strJustOclcNumber;
		}
		
		### summary
		
		// abstract
		
		foreach ( $arrAbstract as $strAbstract )
		{
			$this->abstract .= " " . $strAbstract;
		}
		
		$this->abstract = trim( strip_tags( $this->abstract ) );
		
		// summary
		
		if ( $this->abstract != "" )
		{
			$this->summary = $this->abstract;
		} 
		elseif ( $this->toc != "" )
		{
			$this->summary = "Includes chapters on: " . $this->toc;
		} 
		elseif ( count( $this->subjects ) > 0 )
		{
			$this->summary = "Covers the topics: ";
			
			for ( $x = 0 ; $x < count( $this->subjects ) ; $x ++ )
			{
				$this->summary .= $this->subjects[$x];
				
				if ( $x < count( $this->subjects ) - 1 )
				{
					$this->summary .= "; ";
				}
			}
		}
		
		### journal title

		// we'll take the journal title form the 773$t as the best option,

		if ( $this->journal_title == "" )
		{
			// otherwise see if context object has one
					
			if ( $objJTitle != null )
			{
				$this->journal_title = $objJTitle->nodeValue;
			}
			elseif ( $objTitle != null )
			{
				$this->journal_title = $objTitle->nodeValue;
			}
			
			// or see if a short title exists
			
			elseif ( $this->short_title != "" && ($this->format == "Article" || $this->format == "Journal or Newspaper") )
			{
				$this->journal_title = $this->short_title;
			}
		}

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

				$arrExtent = array ( );
				
				if ( preg_match( "/([0-9]{1})\/([0-9]{1})/", $strExtentHost, $arrExtent ) != 0 )
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
		
		### isbn
		
		// get just the isbn minus format notes

		for ( $x = 0 ; $x < count( $this->isbns ) ; $x ++ )
		{
			$arrIsbnExtract = array ( );
			
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
		
		### thesis

		// most 502 fields follow the following pattern, which we will use to
		// match and extract individual elements:
		// Thesis (M.F.A.)--University of California, San Diego, 2005
		// Thesis (Ph. D.)--Queen's University, Kingston, Ont., 1977.

		if ( $strThesis != "" )
		{
			// extract degree conferred

			$arrDegree = array ( );
			
			if ( preg_match( "/\(([^\(]*)\)/", $strThesis, $arrDegree ) != 0 )
			{
				$this->degree = $arrDegree[1];
			}
			
			// extract institution

			$iInstPos = strpos( $strThesis, "--" );
			
			if ( $iInstPos !== false )
			{
				$strInstitution = "";
				
				// get everything after the --
				$strInstitution = substr( $strThesis, $iInstPos + 2, strlen( $strThesis ) - 1 );
				
				// find last comma in remaining text
				$iEndPosition = strrpos( $strInstitution, "," );
				
				if ( $iEndPosition !== false )
				{
					$strInstitution = substr( $strInstitution, 0, $iEndPosition );
				}
				
				$this->institution = $strInstitution;
			
			}
			
			// extract year conferred

			$this->year = $this->extractYear( $strThesis );
		}
		
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
			if ( strtolower( substr( $this->title, 0, 4 ) ) == "the " )
			{
				$this->non_sort .= substr( $this->title, 0, 4 );
				$this->title = substr( $this->title, 4 );
			} 
			elseif ( strtolower( substr( $this->title, 0, 2 ) ) == "a " )
			{
				$this->non_sort .= substr( $this->title, 0, 2 );
				$this->title = substr( $this->title, 2 );
			} 
			elseif ( strtolower( substr( $this->title, 0, 3 ) ) == "an " )
			{
				$this->non_sort .= substr( $this->title, 0, 3 );
				$this->title = substr( $this->title, 3 );
			}
		}
		
		### year

		if ( $strDate != "" )
		{
			$this->year = $this->extractYear( $strDate );
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
		
		if ( $this->year == "" && $objDate != null )
		{
			$this->year = $this->extractYear($objDate->nodeValue);
		}
		
		
		#### authors

		// personal primary author
		
		if ( $strPrimaryAuthor != "" )
		{
			$arrAuthor = $this->splitAuthor( $strPrimaryAuthor, "personal" );
			array_push( $this->authors, $arrAuthor );
		} 
		elseif ( $arrAltAuthors != null )
		{
			// editor

			$arrAuthor = $this->splitAuthor( $arrAltAuthors[0], "personal" );
			array_push( $this->authors, $arrAuthor );
			$this->editor = true;
		}
		
		// additional personal authors

		if ( $arrAltAuthors != null )
		{
			$x = 0;
			$y = 0;
			
			// if there is an editor it has already been included in the array
			// so we need to skip the first author in the list
			
			if ( $this->editor == true )
			{
				$x = 1;
			}
			
			foreach ( $arrAltAuthors as $strAuthor )
			{
				if ( $y >= $x )
				{
					$arrAuthor = $this->splitAuthor( $strAuthor, "personal" );
					array_push( $this->authors, $arrAuthor );
				}
				
				$y ++;
			}
		}
		
		// corporate author
		
		if ( $strCorpName != "" )
		{
			$arrAuthor = $this->splitAuthor( $strCorpName, "corporate" );
			array_push( $this->authors, $arrAuthor );
		}
		
		// additional corporate authors

		if ( $arrAddCorp != null )
		{
			foreach ( $arrAddCorp as $strCorp )
			{
				$arrAuthor = $this->splitAuthor( $strCorp, "corporate" );
				array_push( $this->authors, $arrAuthor );
			}
		}
		
		// conference name

		if ( $strConfName != "" )
		{
			$arrAuthor = $this->splitAuthor( $strConfName, "conference" );
			array_push( $this->authors, $arrAuthor );
		}
		
		// additional conference names

		if ( $arrAddConf != null )
		{
			foreach ( $arrAddConf as $strConf )
			{
				$arrAuthor = $this->splitAuthor( $strConf, "conference" );
				array_push( $this->authors, $arrAuthor );
			}
		}
		
		// last-chance from context-object
		
		if ( count($this->authors) == 0 && $objAuthors != null )
		{
			foreach ( $objAuthors as $objAuthor )
			{
				$arrCtxAuthor = array();
				
				foreach ( $objAuthor->childNodes as $objAuthAttr )
				{					
					switch ( $objAuthAttr->localName )
					{
						case "aulast":
							$arrCtxAuthor["last"] = $objAuthAttr->nodeValue;
							$arrCtxAuthor["type"] = "personal";
							break;

						case "aufirst":
							$arrCtxAuthor["first"] = $objAuthAttr->nodeValue;
							break;
							
						case "auinit":
							$arrCtxAuthor["init"] = $objAuthAttr->nodeValue;
							break;
							
						case "aucorp":
							$arrCtxAuthor["name"] = $objAuthAttr->nodeValue;
							$arrCtxAuthor["type"] = "corporate";
							break;							
					}
				}
				
				array_push($this->authors, $arrCtxAuthor);
			}
		}
		
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
		
		
		## de-duping
		
		// make sure no dupes in author array
		
		$author_original = $this->authors;
		$author_other = $this->authors;
		
		for ( $x = 0; $x < count($author_original); $x++ )
		{
			if ( is_array($author_original[$x]) ) // skip those set to null (i.e., was a dupe)
			{
				$this_author = implode(" ", $author_original[$x]);
				
				for ( $a = 0; $a < count($author_other); $a++ )
				{
					if ( $a != $x ) // compare all other authors in the array
					{
						if ( is_array($author_other[$a]) ) // just in case
						{
							$that_author = implode(" ", $author_other[$a]);
							
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
			if ( is_array($author) )
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
				$this->authors[$x][$key] = $this->stripEndPunctuation( $value, "./;,:" );
			}
		}
		
		for ( $s = 0 ; $s < count( $this->subjects ) ; $s ++ )
		{
			$this->subjects[$s] = $this->stripEndPunctuation( $this->subjects[$s], "./;,:" );
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
		$arrReferant = array ( ); // referrant values, minus author
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
			$strKev .= "&rfr_id=info:sid/" . urlencode( $strReferer );
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
				$strKev .= "&" . $key . "=" . urlencode( $value );
			}
		}
		
		// add primary author

		if ( count( $this->authors ) > 0 )
		{
			if ( $this->authors[0]["type"] == "personal" )
			{
				if ( array_key_exists( "last", $this->authors[0] ) )
				{
					$strKev .= "&rft.aulast=" . urlencode( $this->authors[0]["last"] );
					
					if ( $this->editor == true )
					{
						$strKev .= urlencode( ", ed." );
					}
				}
				if ( array_key_exists( "first", $this->authors[0] ) )
				{
					$strKev .= "&rft.aufirst=" . urlencode( $this->authors[0]["first"] );
				}
				if ( array_key_exists( "init", $this->authors[0] ) )
				{
					$strKev .= "&rft.auinit=" . urlencode( $this->authors[0]["init"] );
				}
			} 
			else
			{
				$strKev .= "&rft.aucorp=" . urlencode( $this->authors[0]["name"] );
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
		$arrReferant = $this->referantArray();
		$arrReferantIds = $this->referentIdentifierArray();
		
		$objXml = new DOMDocument( );
		$objXml->loadXML( "<context-objects />" );
		
		$objContextObject = $objXml->createElement( "context-object" );
		$objContextObject->setAttribute( "version", "Z39.88-2004" );
		$objContextObject->setAttribute( "timestamp", date( "c" ) );
		
		$objReferrent = $objXml->createElement( "referent" );
		$objMetadataByVal = $objXml->createElement( "metadata-by-val" );
		$objMetadata = $objXml->createElement( "metadata" );
		$objAuthors = $objXml->createElement( "authors" );
		
		// set data container

		if ( $arrReferant["rft.genre"] == "book" || 
			$arrReferant["rft.genre"] == "bookitem" || 
			$arrReferant["rft.genre"] == "report" )
		{
			$objItem = $objXml->createElement( "book" );
		} 
		elseif ( $arrReferant["rft.genre"] == "dissertation" )
		{
			$objItem = $objXml->createElement( "dissertation" );
		} 
		else
		{
			$objItem = $objXml->createElement( "journal" );
		}
		
		// add authors

		$x = 1;
		
		foreach ( $this->authors as $arrAuthor )
		{
			$objAuthor = $objXml->createElement( "author" );
			
			if ( array_key_exists( "last", $arrAuthor ) )
			{
				$objAuthorLast = $objXml->createElement( "aulast", $this->escapeXml( $arrAuthor["last"] ) );
				$objAuthor->appendChild( $objAuthorLast );
			}
			
			if ( array_key_exists( "first", $arrAuthor ) )
			{
				$objAuthorFirst = $objXml->createElement( "aufirst", $this->escapeXml( $arrAuthor["first"] ) );
				$objAuthor->appendChild( $objAuthorFirst );
			}
			
			if ( array_key_exists( "init", $arrAuthor ) )
			{
				$objAuthorInit = $objXml->createElement( "auinit", $this->escapeXml( $arrAuthor["init"] ) );
				$objAuthor->appendChild( $objAuthorInit );
			}
			
			if ( array_key_exists( "name", $arrAuthor ) )
			{
				$objAuthorCorp = $objXml->createElement( "aucorp", $this->escapeXml( $arrAuthor["name"] ) );
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
			
			$objNode = $objXml->createElement ( "identifier", $this->escapeXml ( $id ) );
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
						$objNode = $objXml->createElement( $key, $this->escapeXml( $element ) );
						$objItem->appendChild( $objNode );
					}
				}
			} 
			elseif ( $value != "" )
			{
				$objNode = $objXml->createElement( $key, $this->escapeXml( $value ) );
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
	 * Convert record to Xerxes_Record XML object
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
			$objTitle = $objXml->createElement("title_normalized",  $this->escapeXML($strTitle));
			$objXml->documentElement->appendChild($objTitle);
		}
		
		// journal title
		
		$strJournalTitle = $this->getJournalTitle(true);
		
		if ( $strJournalTitle != "" )
		{
			$objJTitle = $objXml->createElement("journal_title",  $this->escapeXML($strJournalTitle));
			$objXml->documentElement->appendChild($objJTitle);
		}		
		
		// primary author
		
		$strPrimaryAuthor = $this->getPrimaryAuthor(true);
		
		if ( $strPrimaryAuthor != "")
		{
			$objPrimaryAuthor= $objXml->createElement("primary_author", $this->escapeXML($strPrimaryAuthor));
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
			
			foreach ( $this->authors as $arrAuthor )
			{
				$objAuthor =  $objXml->createElement("author");
				$objAuthor->setAttribute("type", $arrAuthor["type"]);

				if ( array_key_exists("last", $arrAuthor) )
				{					
					$objAuthorLast =  $objXml->createElement("aulast", $this->escapeXml($arrAuthor["last"]) );
					$objAuthor->appendChild($objAuthorLast);
				}
				
				if ( array_key_exists("first", $arrAuthor) )
				{
					$objAuthorFirst =  $objXml->createElement("aufirst", $this->escapeXml($arrAuthor["first"]) );
					$objAuthor->appendChild($objAuthorFirst);
				}
				
				if ( array_key_exists("init", $arrAuthor) )
				{
					$objAuthorInit =  $objXml->createElement("auinit", $this->escapeXml($arrAuthor["init"]) );
					$objAuthor->appendChild($objAuthorInit);
				}

				if ( array_key_exists("name", $arrAuthor) )
				{
					$objAuthorCorp =  $objXml->createElement("aucorp", $this->escapeXml($arrAuthor["name"]) );
					$objAuthor->appendChild($objAuthorCorp);
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
					$objIssn = $objXml->createElement("issn", $this->escapeXml($strIssn));
					$objStandard->appendChild($objIssn);
				}
			}
			
			if ( count($this->isbns) > 0 )
			{
				foreach ( $this->isbns as $strIsbn )
				{
					$objIssn = $objXml->createElement("isbn", $this->escapeXml($strIsbn));
					$objStandard->appendChild($objIssn);
				}
			}
			
			if ( $this->govdoc_number != "" )
			{
				$objGovDoc = $objXml->createElement("gpo", $this->escapeXml($this->govdoc_number));
				$objStandard->appendChild($objGovDoc);
			}
			
			if ( $this->gpo_number != "" )
			{
				$objGPO = $objXml->createElement("govdoc", $this->escapeXml($this->gpo_number));
				$objStandard->appendChild($objGPO);
			}
				
			if ( $this->oclc_number != "" )
			{
				$objOCLC = $objXml->createElement("oclc", $this->escapeXml($this->oclc_number));
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
					
					$objChapterTitle = $objXml->createElement("title",  $this->escapeXml(trim($arrChapterTitleAuth[0])));
					$objChapterAuthor = $objXml->createElement("author",  $this->escapeXml(trim($arrChapterTitleAuth[1])));
					
					$objChapter->appendChild($objChapterTitle);
					$objChapter->appendChild($objChapterAuthor);
				}
				else 
				{
					$objStatement = $objXml->createElement("statement", $this->escapeXml(trim($strTitleStatement)));
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
				$objLink->setAttribute("type", $arrLink[2]);
				
				$objDisplay = $objXml->createElement("display", $this->escapeXml($arrLink[0]));
				$objLink->appendChild($objDisplay);
				
				// if this is a "construct" link, then the second element is an associative 
				// array of marc fields and their values for constructing a link based on
				// the metalib IRD record linking syntax
				
				if ( is_array($arrLink[1]) )
				{
					foreach ( $arrLink[1] as $strField => $strValue )
					{
						$objParam = $objXml->createElement("param", $this->escapeXml($strValue));
						$objParam->setAttribute("field", $strField);
						$objLink->appendChild($objParam);
					}
				}
				else
				{
					$objURL = $objXml->createElement("url", $this->escapeXml($arrLink[1]));
					$objLink->appendChild($objURL);
				}
				
				$objLinks->appendChild($objLink);
			}
			
			$objXml->documentElement->appendChild($objLinks);
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
				
				// these are utility variables, not to be output
				
				$key == "document" ||
				$key == "xpath" || 
				$key == "node" ||
				$key == "format_array")
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
	
	### PRIVATE FUNCTIONS ###	

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
			$objNode = $objDocument->createElement($key, $this->escapeXML($value));
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
		$arrReferant = array ( );
		$strTitle = "";
		
		### simple values

		$arrReferant["rft.genre"] = $this->convertGenreOpenURL( $this->format );
		
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
			case "Journal or Newspaper" :
				
				return "journal";
				break;
			
			case "Issue" :
				
				return "issue";
				break;
			
			case "Book Review" :
			case "Film Review" :
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
				
				return "book";
				break;
			
			case "Book Chapter" :
				
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
	
	/**
	 * Determines the format/genre of the item, broken out here for clarity
	 *
	 * @param string $arrFormat			format fields		
	 * @return string					internal xerxes format designation
	 */
	
	private function parseFormat($arrFormat)
	{
		$strReturn = "Unknown";
		
		$chrLeader6 = "";
		$chrLeader7 = "";
		$chrLeader8 = "";
		
		// we'll combine all of the datafields that explicitly declare the
		// format of the record into a single string

		$strDataFields = "";
		
		foreach ( $arrFormat as $strFormat )
		{
			$strDataFields .= " " . strtolower( $strFormat );
		}
		
		if ( strlen( $this->leader() ) >= 8 )
		{
			$chrLeader6 = substr( $this->leader(), 6, 1 );
			$chrLeader7 = substr( $this->leader(), 7, 1 );
			$chrLeader8 = substr( $this->leader(), 8, 1 );
		}
		
		// grab the 008 for handling
		
		$obj008 = $this->controlfield("008");
		
		// format made explicit

		if ( strstr( $strDataFields, 'dissertation' ) ) $strReturn = "Dissertation"; 
		elseif ( (string) $this->datafield("502") != "" ) $strReturn = "Thesis"; 
		elseif ( strstr( $strDataFields, 'proceeding' ) ) $strReturn = "Conference Proceeding"; 
		elseif ( strstr( $strDataFields, 'conference' ) ) $strReturn = "Conference Paper"; 
		elseif ( strstr( $strDataFields, 'hearing' ) ) $strReturn = "Hearing"; 
		elseif ( strstr( $strDataFields, 'working' ) ) $strReturn = "Working Paper"; 
		elseif ( strstr( $strDataFields, 'book review' ) || strstr( $strDataFields, 'review-book' ) ) $strReturn = "Book Review"; 
		elseif ( strstr( $strDataFields, 'film review' ) || strstr( $strDataFields, 'film-book' ) ) $strReturn = "Film Review";
		elseif ( strstr( $strDataFields, 'book art' ) || strstr( $strDataFields, 'book ch' ) || strstr( $strDataFields, 'chapter' ) ) $strReturn = "Book Chapter"; 
		elseif ( strstr( $strDataFields, 'journal' ) ) $strReturn = "Article"; 
		elseif ( strstr( $strDataFields, 'periodical' ) || strstr( $strDataFields, 'serial' ) ) $strReturn = "Article"; 
		elseif ( strstr( $strDataFields, 'book' ) ) $strReturn = "Book"; 
		elseif ( strstr( $strDataFields, 'article' ) ) $strReturn = "Article"; 

		// format from other sources

		elseif ( $this->journal != "" ) $strReturn = "Article"; 
		elseif ( $chrLeader6 == 'a' && $chrLeader7 == 'a' ) $strReturn = "Book Chapter"; 
		elseif ( $chrLeader6 == 'a' && $chrLeader7 == 'm' )
		{
			$strReturn = "Book"; 
			
			if ( $obj008 != "" )
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
		}
		
		elseif ( $chrLeader8 == 'a' ) $strReturn = "Archive"; 
		elseif ( $chrLeader6 == 'e' || $chrLeader6 == 'f' ) $strReturn = "Map"; 
		elseif ( $chrLeader6 == 'c' || $chrLeader6 == 'd' ) $strReturn = "Printed Music"; 
		elseif ( $chrLeader6 == 'i' ) $strReturn = "Audio Book"; 
		elseif ( $chrLeader6 == 'j' ) $strReturn = "Sound Recording"; 
		elseif ( $chrLeader6 == 'k' ) $strReturn = "Photograph or Slide"; 
		elseif ( $chrLeader6 == 'g' ) $strReturn = "Video"; 
		elseif ( $chrLeader6 == 'm' && $chrLeader7 == 'i' ) $strReturn = "Website"; 
		elseif ( $chrLeader6 == 'm' ) $strReturn = "Computer File"; 
		elseif ( $chrLeader6 == 'a' && $chrLeader7 == 'b' ) $strReturn = "Article"; 
		elseif ( $chrLeader6 == 'a' && $chrLeader7 == 's' ) $strReturn = "Journal or Newspaper"; 
		elseif ( $chrLeader6 == 'a' && $chrLeader7 == 'i' ) $strReturn = "Website"; 

		elseif ( count( $this->isbns ) > 0 ) $strReturn = "Book"; 
		elseif ( count( $this->issns ) > 0 ) $strReturn = "Article";
		
		return $strReturn;
	}
	
	/**
	 * Best-guess regular expression for extracting volume, issue, pagination,
	 * broken out here for clarity 
	 *
	 * @param string $strJournalInfo		any journal info, usually from 773
	 * @return array
	 */
	
	private function parseJournalData($strJournalInfo)
	{
		$arrFinal = array ( );
		$arrCapture = array ( );
		
		// we'll drop the whole thing to lower case and padd it
		// with spaces to make parsing easier
		
		$strJournalInfo = " " . strtolower( $strJournalInfo ) . " ";
		
		// volume

		if ( preg_match( "/ v[a-z]{0,5}[\.]{0,1}[ ]{0,3}([0-9]{1,})/", $strJournalInfo, $arrCapture ) != 0 )
		{
			$arrFinal["volume"] = $arrCapture[1];
			$strJournalInfo = str_replace( $arrCapture[0], "", $strJournalInfo );
		}
		
		// issue

		if ( preg_match( "/ i[a-z]{0,4}[\.]{0,1}[ ]{0,3}([0-9]{1,})/", $strJournalInfo, $arrCapture ) != 0 )
		{
			$arrFinal["issue"] = $arrCapture[1];
			$strJournalInfo = str_replace( $arrCapture[0], "", $strJournalInfo );
		} 
		elseif ( preg_match( "/ n[a-z]{0,5}[\.]{0,1}[ ]{0,3}([0-9]{1,})/", $strJournalInfo, $arrCapture ) != 0 )
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
		elseif ( preg_match( "/ p[a-z]{0,3}[\.]{0,1}[ ]{0,3}([0-9]{1,})/", $strJournalInfo, $arrCapture ) != 0 )
		{
			$arrFinal["spage"] = $arrCapture[1];
			$strJournalInfo = str_replace( $arrCapture[0], "", $strJournalInfo );
		}
		
		return $arrFinal;
	}
	
	private function splitAuthor($strAuthor, $strType)
	{
		$arrReturn = array ( );
		$arrReturn["type"] = $strType;
		
		$iComma = strpos( $strAuthor, "," );
		$iLastSpace = strripos( $strAuthor, " " );
		
		// for personal authors:

		// if there is a comma, we will assume the names are in 'last, first' order
		// otherwise in 'first last' order -- the second one here obviously being
		// something of a guess, assuming the person has a single word for last name
		// rather than 'van der Kamp', but better than the alternative?

		if ( $strType == "personal" )
		{
			$arrMatch = array ( );
			$strLast = "";
			$strFirst = "";
			
			if ( $iComma !== false )
			{
				$strLast = trim( substr( $strAuthor, 0, $iComma ) );
				$strFirst = trim( substr( $strAuthor, $iComma + 1 ) );
			} 

			// some databases like CINAHL put names as 'last first' but first 
			// is just initials 'Walker DS' so we can catch this scenario?
			
			elseif ( preg_match( "/ ([A-Z]{1,3})$/", $strAuthor, $arrMatch ) != 0 )
			{
				$strFirst = $arrMatch[1];
				$strLast = str_replace( $arrMatch[0], "", $strAuthor );
			} 
			else
			{
				$strLast = trim( substr( $strAuthor, $iLastSpace ) );
				$strFirst = trim( substr( $strAuthor, 0, $iLastSpace ) );
			}
			
			if ( preg_match( "/ ([a-zA-Z]{1})\.$/", $strFirst, $arrMatch ) != 0 )
			{
				$arrReturn["init"] = $arrMatch[1];
				$strFirst = str_replace( $arrMatch[0], "", $strFirst );
			}
			
			$arrReturn["last"] = $strLast;
			$arrReturn["first"] = $strFirst;
		
		} 
		else
		{
			$arrReturn["name"] = trim( $strAuthor );
		}
		
		return $arrReturn;
	}
	
	private function stripEndPunctuation($strInput, $strPunct)
	{
		$bolDone = false;
		$arrPunct = str_split( $strPunct );
		
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
	
	private function extractYear($strYear)
	{
		$arrYear = array ( );
		
		if ( preg_match( "/[0-9]{4}/", $strYear, $arrYear ) != 0 )
		{
			return $arrYear[0];
		} 
		else
		{
			return null;
		}
	}
	
	private function convertLanguageCode($strCode, $bolTwo = false)
	{
		if ( $bolTwo == true )
		{
			switch ( strtoupper( $strCode ) )
			{
				case "AA" :
					return "Afar";
					break;
				case "AB" :
					return "Abkhazian";
					break;
				case "AF" :
					return "Afrikaans";
					break;
				case "AM" :
					return "Amharic";
					break;
				case "AR" :
					return "Arabic";
					break;
				case "AS" :
					return "Assamese";
					break;
				case "AY" :
					return "Aymara";
					break;
				case "AZ" :
					return "Azerbaijani";
					break;
				case "BA" :
					return "Bashkir";
					break;
				case "BE" :
					return "Byelorussian";
					break;
				case "BG" :
					return "Bulgarian";
					break;
				case "BH" :
					return "Bihari";
					break;
				case "BI" :
					return "Bislama";
					break;
				case "BN" :
					return "Bengali";
					break;
				case "BO" :
					return "Tibetan";
					break;
				case "BR" :
					return "Breton";
					break;
				case "CA" :
					return "Catalan";
					break;
				case "CO" :
					return "Corsican";
					break;
				case "CS" :
					return "Czech";
					break;
				case "CY" :
					return "Welsh";
					break;
				case "DA" :
					return "Danish";
					break;
				case "DE" :
					return "German";
					break;
				case "DZ" :
					return "Bhutani";
					break;
				case "EL" :
					return "Greek";
					break;
				case "EN" :
					return "English";
					break;
				case "EO" :
					return "Esperanto";
					break;
				case "ES" :
					return "Spanish";
					break;
				case "ET" :
					return "Estonian";
					break;
				case "EU" :
					return "Basque";
					break;
				case "FA" :
					return "Persian";
					break;
				case "FI" :
					return "Finnish";
					break;
				case "FJ" :
					return "Fiji";
					break;
				case "FO" :
					return "Faeroese";
					break;
				case "FR" :
					return "French";
					break;
				case "FY" :
					return "Frisian";
					break;
				case "GA" :
					return "Irish";
					break;
				case "GD" :
					return "Gaelic";
					break;
				case "GL" :
					return "Galician";
					break;
				case "GN" :
					return "Guarani";
					break;
				case "GU" :
					return "Gujarati";
					break;
				case "HA" :
					return "Hausa";
					break;
				case "HI" :
					return "Hindi";
					break;
				case "HR" :
					return "Croatian";
					break;
				case "HU" :
					return "Hungarian";
					break;
				case "HY" :
					return "Armenian";
					break;
				case "IA" :
					return "Interlingua";
					break;
				case "IE" :
					return "Interlingue";
					break;
				case "IK" :
					return "Inupiak";
					break;
				case "IN" :
					return "Indonesian";
					break;
				case "IS" :
					return "Icelandic";
					break;
				case "IT" :
					return "Italian";
					break;
				case "IW" :
					return "Hebrew";
					break;
				case "JA" :
					return "Japanese";
					break;
				case "JI" :
					return "Yiddish";
					break;
				case "JW" :
					return "Javanese";
					break;
				case "KA" :
					return "Georgian";
					break;
				case "KK" :
					return "Kazakh";
					break;
				case "KL" :
					return "Greenlandic";
					break;
				case "KM" :
					return "Cambodian";
					break;
				case "KN" :
					return "Kannada";
					break;
				case "KO" :
					return "Korean";
					break;
				case "KS" :
					return "Kashmiri";
					break;
				case "KU" :
					return "Kurdish";
					break;
				case "KY" :
					return "Kirghiz";
					break;
				case "LA" :
					return "Latin";
					break;
				case "LN" :
					return "Lingala";
					break;
				case "LO" :
					return "Laothian";
					break;
				case "LT" :
					return "Lithuanian";
					break;
				case "LV" :
					return "Latvian";
					break;
				case "MG" :
					return "Malagasy";
					break;
				case "MI" :
					return "Maori";
					break;
				case "MK" :
					return "Macedonian";
					break;
				case "ML" :
					return "Malayalam";
					break;
				case "MN" :
					return "Mongolian";
					break;
				case "MO" :
					return "Moldavian";
					break;
				case "MR" :
					return "Marathi";
					break;
				case "MS" :
					return "Malay";
					break;
				case "MT" :
					return "Maltese";
					break;
				case "MY" :
					return "Burmese";
					break;
				case "NA" :
					return "Nauru";
					break;
				case "NE" :
					return "Nepali";
					break;
				case "NL" :
					return "Dutch";
					break;
				case "NO" :
					return "Norwegian";
					break;
				case "OC" :
					return "Occitan";
					break;
				case "OM" :
					return "Oromo";
					break;
				case "OR" :
					return "Oriya";
					break;
				case "PA" :
					return "Punjabi";
					break;
				case "PL" :
					return "Polish";
					break;
				case "PS" :
					return "Pashto";
					break;
				case "PT" :
					return "Portuguese";
					break;
				case "QU" :
					return "Quechua";
					break;
				case "RM" :
					return "Rhaeto-Romance";
					break;
				case "RN" :
					return "Kirundi";
					break;
				case "RO" :
					return "Romanian";
					break;
				case "RU" :
					return "Russian";
					break;
				case "RW" :
					return "Kinyarwanda";
					break;
				case "SA" :
					return "Sanskrit";
					break;
				case "SD" :
					return "Sindhi";
					break;
				case "SG" :
					return "Sangro";
					break;
				case "SH" :
					return "Serbo-Croatian";
					break;
				case "SI" :
					return "Singhalese";
					break;
				case "SK" :
					return "Slovak";
					break;
				case "SL" :
					return "Slovenian";
					break;
				case "SM" :
					return "Samoan";
					break;
				case "SN" :
					return "Shona";
					break;
				case "SO" :
					return "Somali";
					break;
				case "SQ" :
					return "Albanian";
					break;
				case "SR" :
					return "Serbian";
					break;
				case "SS" :
					return "Siswati";
					break;
				case "ST" :
					return "Sesotho";
					break;
				case "SU" :
					return "Sudanese";
					break;
				case "SV" :
					return "Swedish";
					break;
				case "SW" :
					return "Swahili";
					break;
				case "TA" :
					return "Tamil";
					break;
				case "TE" :
					return "Tegulu";
					break;
				case "TG" :
					return "Tajik";
					break;
				case "TH" :
					return "Thai";
					break;
				case "TI" :
					return "Tigrinya";
					break;
				case "TK" :
					return "Turkmen";
					break;
				case "TL" :
					return "Tagalog";
					break;
				case "TN" :
					return "Setswana";
					break;
				case "TO" :
					return "Tonga";
					break;
				case "TR" :
					return "Turkish";
					break;
				case "TS" :
					return "Tsonga";
					break;
				case "TT" :
					return "Tatar";
					break;
				case "TW" :
					return "Twi";
					break;
				case "UK" :
					return "Ukrainian";
					break;
				case "UR" :
					return "Urdu";
					break;
				case "UZ" :
					return "Uzbek";
					break;
				case "VI" :
					return "Vietnamese";
					break;
				case "VO" :
					return "Volapuk";
					break;
				case "WO" :
					return "Wolof";
					break;
				case "XH" :
					return "Xhosa";
					break;
				case "YO" :
					return "Yoruba";
					break;
				case "ZH" :
					return "Chinese";
					break;
				case "ZU" :
					return "Zulu";
					break;
				default :
					return null;
			}
		} 
		else
		{
			switch ( strtolower( $strCode ) )
			{
				case "aar" :
					return "Afar";
					break;
				case "abk" :
					return "Abkhaz";
					break;
				case "ace" :
					return "Achinese";
					break;
				case "ach" :
					return "Acoli";
					break;
				case "ada" :
					return "Adangme";
					break;
				case "ady" :
					return "Adygei";
					break;
				case "afa" :
					return "Afroasiatic";
					break;
				case "afh" :
					return "Afrihili";
					break;
				case "afr" :
					return "Afrikaans";
					break;
				case "aka" :
					return "Akan";
					break;
				case "akk" :
					return "Akkadian";
					break;
				case "alb" :
					return "Albanian";
					break;
				case "ale" :
					return "Aleut";
					break;
				case "alg" :
					return "Algonquian  ";
					break;
				case "amh" :
					return "Amharic";
					break;
				case "ang" :
					return "Old English";
					break;
				case "apa" :
					return "Apache language";
					break;
				case "ara" :
					return "Arabic";
					break;
				case "arc" :
					return "Aramaic";
					break;
				case "arg" :
					return "Aragonese Spanish";
					break;
				case "arm" :
					return "Armenian";
					break;
				case "arn" :
					return "Mapuche";
					break;
				case "arp" :
					return "Arapaho";
					break;
				case "art" :
					return "Artificial  ";
					break;
				case "arw" :
					return "Arawak";
					break;
				case "asm" :
					return "Assamese";
					break;
				case "ast" :
					return "Bable";
					break;
				case "ath" :
					return "Athapascan";
					break;
				case "aus" :
					return "Australian language";
					break;
				case "ava" :
					return "Avaric";
					break;
				case "ave" :
					return "Avestan";
					break;
				case "awa" :
					return "Awadhi";
					break;
				case "aym" :
					return "Aymara";
					break;
				case "aze" :
					return "Azerbaijani";
					break;
				case "bad" :
					return "Banda";
					break;
				case "bai" :
					return "Bamileke language";
					break;
				case "bak" :
					return "Bashkir";
					break;
				case "bal" :
					return "Baluchi";
					break;
				case "bam" :
					return "Bambara";
					break;
				case "ban" :
					return "Balinese";
					break;
				case "baq" :
					return "Basque";
					break;
				case "bas" :
					return "Basa";
					break;
				case "bat" :
					return "Baltic";
					break;
				case "bej" :
					return "Beja";
					break;
				case "bel" :
					return "Belarusian";
					break;
				case "bem" :
					return "Bemba";
					break;
				case "ben" :
					return "Bengali";
					break;
				case "ber" :
					return "Berber ";
					break;
				case "bho" :
					return "Bhojpuri";
					break;
				case "bih" :
					return "Bihari";
					break;
				case "bik" :
					return "Bikol";
					break;
				case "bin" :
					return "Edo";
					break;
				case "bis" :
					return "Bislama";
					break;
				case "bla" :
					return "Siksika";
					break;
				case "bnt" :
					return "Bantu ";
					break;
				case "bos" :
					return "Bosnian";
					break;
				case "bra" :
					return "Braj";
					break;
				case "bre" :
					return "Breton";
					break;
				case "btk" :
					return "Batak";
					break;
				case "bua" :
					return "Buriat";
					break;
				case "bug" :
					return "Bugis";
					break;
				case "bul" :
					return "Bulgarian";
					break;
				case "bur" :
					return "Burmese";
					break;
				case "cad" :
					return "Caddo";
					break;
				case "cai" :
					return "Central American Indian";
					break;
				case "car" :
					return "Carib";
					break;
				case "cat" :
					return "Catalan";
					break;
				case "cau" :
					return "Caucasian ";
					break;
				case "ceb" :
					return "Cebuano";
					break;
				case "cel" :
					return "Celtic";
					break;
				case "cha" :
					return "Chamorro";
					break;
				case "chb" :
					return "Chibcha";
					break;
				case "che" :
					return "Chechen";
					break;
				case "chg" :
					return "Chagatai";
					break;
				case "chi" :
					return "Chinese";
					break;
				case "chk" :
					return "Truk";
					break;
				case "chm" :
					return "Mari";
					break;
				case "chn" :
					return "Chinook jargon";
					break;
				case "cho" :
					return "Choctaw";
					break;
				case "chp" :
					return "Chipewyan";
					break;
				case "chr" :
					return "Cherokee";
					break;
				case "chu" :
					return "Church Slavic";
					break;
				case "chv" :
					return "Chuvash";
					break;
				case "chy" :
					return "Cheyenne";
					break;
				case "cmc" :
					return "Chamic language";
					break;
				case "cop" :
					return "Coptic";
					break;
				case "cor" :
					return "Cornish";
					break;
				case "cos" :
					return "Corsican";
					break;
				case "cpe" :
					return "Creoles and Pidgins, English-based";
					break;
				case "cpf" :
					return "Creoles and Pidgins, French-based";
					break;
				case "cpp" :
					return "Creoles and Pidgins, Portuguese-based ";
					break;
				case "cre" :
					return "Cree";
					break;
				case "crh" :
					return "Crimean Tatar";
					break;
				case "crp" :
					return "Creoles and Pidgins";
					break;
				case "cus" :
					return "Cushitic";
					break;
				case "cze" :
					return "Czech";
					break;
				case "dak" :
					return "Dakota";
					break;
				case "dan" :
					return "Danish";
					break;
				case "dar" :
					return "Dargwa";
					break;
				case "day" :
					return "Dayak";
					break;
				case "del" :
					return "Delaware";
					break;
				case "den" :
					return "Slave";
					break;
				case "dgr" :
					return "Dogrib";
					break;
				case "din" :
					return "Dinka";
					break;
				case "div" :
					return "Divehi";
					break;
				case "doi" :
					return "Dogri";
					break;
				case "dra" :
					return "Dravidian ";
					break;
				case "dua" :
					return "Duala";
					break;
				case "dum" :
					return "Middle Dutch";
					break;
				case "dut" :
					return "Dutch";
					break;
				case "dyu" :
					return "Dyula";
					break;
				case "dzo" :
					return "Dzongkha";
					break;
				case "efi" :
					return "Efik";
					break;
				case "egy" :
					return "Egyptian";
					break;
				case "eka" :
					return "Ekajuk";
					break;
				case "elx" :
					return "Elamite";
					break;
				case "eng" :
					return "English";
					break;
				case "enm" :
					return "Middle English";
					break;
				case "epo" :
					return "Esperanto";
					break;
				case "est" :
					return "Estonian";
					break;
				case "ewe" :
					return "Ewe";
					break;
				case "ewo" :
					return "Ewondo";
					break;
				case "fan" :
					return "Fang";
					break;
				case "fao" :
					return "Faroese";
					break;
				case "fat" :
					return "Fanti";
					break;
				case "fij" :
					return "Fijian";
					break;
				case "fin" :
					return "Finnish";
					break;
				case "fiu" :
					return "Finno-Ugrian";
					break;
				case "fon" :
					return "Fon";
					break;
				case "fre" :
					return "French";
					break;
				case "frm" :
					return "French, Middle";
					break;
				case "fro" :
					return "Old French";
					break;
				case "fry" :
					return "Frisian";
					break;
				case "ful" :
					return "Fula";
					break;
				case "fur" :
					return "Friulian";
					break;
				case "gaa" :
					return "Gã";
					break;
				case "gay" :
					return "Gayo";
					break;
				case "gba" :
					return "Gbaya";
					break;
				case "gem" :
					return "Germanic";
					break;
				case "geo" :
					return "Georgian";
					break;
				case "ger" :
					return "German";
					break;
				case "gez" :
					return "Ethiopic";
					break;
				case "gil" :
					return "Gilbertese";
					break;
				case "gla" :
					return "Scottish Gaelic";
					break;
				case "gle" :
					return "Irish";
					break;
				case "glg" :
					return "Galician";
					break;
				case "glv" :
					return "Manx";
					break;
				case "gmh" :
					return "Middle High German";
					break;
				case "goh" :
					return "Old High German";
					break;
				case "gon" :
					return "Gondi";
					break;
				case "gor" :
					return "Gorontalo";
					break;
				case "got" :
					return "Gothic";
					break;
				case "grb" :
					return "Grebo";
					break;
				case "grc" :
					return "Ancient Greek";
					break;
				case "gre" :
					return "Modern Greek";
					break;
				case "grn" :
					return "Guarani";
					break;
				case "guj" :
					return "Gujarati";
					break;
				case "gwi" :
					return "Gwich'in";
					break;
				case "hai" :
					return "Haida";
					break;
				case "hat" :
					return "Haitian French Creole";
					break;
				case "hau" :
					return "Hausa";
					break;
				case "haw" :
					return "Hawaiian";
					break;
				case "heb" :
					return "Hebrew";
					break;
				case "her" :
					return "Herero";
					break;
				case "hil" :
					return "Hiligaynon";
					break;
				case "him" :
					return "Himachali";
					break;
				case "hin" :
					return "Hindi";
					break;
				case "hit" :
					return "Hittite";
					break;
				case "hmn" :
					return "Hmong";
					break;
				case "hmo" :
					return "Hiri Motu";
					break;
				case "hun" :
					return "Hungarian";
					break;
				case "hup" :
					return "Hupa";
					break;
				case "iba" :
					return "Iban";
					break;
				case "ibo" :
					return "Igbo";
					break;
				case "ice" :
					return "Icelandic";
					break;
				case "ido" :
					return "Ido";
					break;
				case "iii" :
					return "Sichuan Yi";
					break;
				case "ijo" :
					return "Ijo";
					break;
				case "iku" :
					return "Inuktitut";
					break;
				case "ile" :
					return "Interlingue";
					break;
				case "ilo" :
					return "Iloko";
					break;
				case "ina" :
					return "Interlingua";
					break;
				case "inc" :
					return "Indic";
					break;
				case "ind" :
					return "Indonesian";
					break;
				case "ine" :
					return "Indo-European";
					break;
				case "inh" :
					return "Ingush";
					break;
				case "ipk" :
					return "Inupiaq";
					break;
				case "ira" :
					return "Iranian ";
					break;
				case "iro" :
					return "Iroquoian ";
					break;
				case "ita" :
					return "Italian";
					break;
				case "jav" :
					return "Javanese";
					break;
				case "jpn" :
					return "Japanese";
					break;
				case "jpr" :
					return "Judeo-Persian";
					break;
				case "jrb" :
					return "Judeo-Arabic";
					break;
				case "kaa" :
					return "Kara-Kalpak";
					break;
				case "kab" :
					return "Kabyle";
					break;
				case "kac" :
					return "Kachin";
					break;
				case "kal" :
					return "Kalâtdlisut";
					break;
				case "kam" :
					return "Kamba";
					break;
				case "kan" :
					return "Kannada";
					break;
				case "kar" :
					return "Karen";
					break;
				case "kas" :
					return "Kashmiri";
					break;
				case "kau" :
					return "Kanuri";
					break;
				case "kaw" :
					return "Kawi";
					break;
				case "kaz" :
					return "Kazakh";
					break;
				case "kbd" :
					return "Kabardian";
					break;
				case "kha" :
					return "Khasi";
					break;
				case "khi" :
					return "Khoisan";
					break;
				case "khm" :
					return "Khmer";
					break;
				case "kho" :
					return "Khotanese";
					break;
				case "kik" :
					return "Kikuyu";
					break;
				case "kin" :
					return "Kinyarwanda";
					break;
				case "kir" :
					return "Kyrgyz";
					break;
				case "kmb" :
					return "Kimbundu";
					break;
				case "kok" :
					return "Konkani";
					break;
				case "kom" :
					return "Komi";
					break;
				case "kon" :
					return "Kongo";
					break;
				case "kor" :
					return "Korean";
					break;
				case "kos" :
					return "Kusaie";
					break;
				case "kpe" :
					return "Kpelle";
					break;
				case "kro" :
					return "Kru";
					break;
				case "kru" :
					return "Kurukh";
					break;
				case "kua" :
					return "Kuanyama";
					break;
				case "kum" :
					return "Kumyk";
					break;
				case "kur" :
					return "Kurdish";
					break;
				case "kut" :
					return "Kutenai";
					break;
				case "lad" :
					return "Ladino";
					break;
				case "lah" :
					return "Lahnda";
					break;
				case "lam" :
					return "Lamba";
					break;
				case "lao" :
					return "Lao";
					break;
				case "lat" :
					return "Latin";
					break;
				case "lav" :
					return "Latvian";
					break;
				case "lez" :
					return "Lezgian";
					break;
				case "lim" :
					return "Limburgish";
					break;
				case "lin" :
					return "Lingala";
					break;
				case "lit" :
					return "Lithuanian";
					break;
				case "lol" :
					return "Mongo-Nkundu";
					break;
				case "loz" :
					return "Lozi";
					break;
				case "ltz" :
					return "Letzeburgesch";
					break;
				case "lua" :
					return "Luba-Lulua";
					break;
				case "lub" :
					return "Luba-Katanga";
					break;
				case "lug" :
					return "Ganda";
					break;
				case "lui" :
					return "Luiseño";
					break;
				case "lun" :
					return "Lunda";
					break;
				case "luo" :
					return "Luo";
					break;
				case "lus" :
					return "Lushai";
					break;
				case "mac" :
					return "Macedonian";
					break;
				case "mad" :
					return "Madurese";
					break;
				case "mag" :
					return "Magahi";
					break;
				case "mah" :
					return "Marshallese";
					break;
				case "mai" :
					return "Maithili";
					break;
				case "mak" :
					return "Makasar";
					break;
				case "mal" :
					return "Malayalam";
					break;
				case "man" :
					return "Mandingo";
					break;
				case "mao" :
					return "Maori";
					break;
				case "map" :
					return "Austronesian";
					break;
				case "mar" :
					return "Marathi";
					break;
				case "mas" :
					return "Masai";
					break;
				case "may" :
					return "Malay";
					break;
				case "mdr" :
					return "Mandar";
					break;
				case "men" :
					return "Mende";
					break;
				case "mga" :
					return "Irish, Middle ";
					break;
				case "mic" :
					return "Micmac";
					break;
				case "min" :
					return "Minangkabau";
					break;
				case "mis" :
					return "Miscellaneous language";
					break;
				case "mkh" :
					return "Mon-Khmer";
					break;
				case "mlg" :
					return "Malagasy";
					break;
				case "mlt" :
					return "Maltese";
					break;
				case "mnc" :
					return "Manchu";
					break;
				case "mni" :
					return "Manipuri";
					break;
				case "mno" :
					return "Manobo language";
					break;
				case "moh" :
					return "Mohawk";
					break;
				case "mol" :
					return "Moldavian";
					break;
				case "mon" :
					return "Mongolian";
					break;
				case "mos" :
					return "Mooré";
					break;
				case "mul" :
					return "Multiple languages";
					break;
				case "mun" :
					return "Munda ";
					break;
				case "mus" :
					return "Creek";
					break;
				case "mwr" :
					return "Marwari";
					break;
				case "myn" :
					return "Mayan language";
					break;
				case "nah" :
					return "Nahuatl";
					break;
				case "nai" :
					return "North American Indian";
					break;
				case "nap" :
					return "Neapolitan Italian";
					break;
				case "nau" :
					return "Nauru";
					break;
				case "nav" :
					return "Navajo";
					break;
				case "nbl" :
					return "Ndebele";
					break;
				case "nde" :
					return "Ndebele";
					break;
				case "ndo" :
					return "Ndonga";
					break;
				case "nds" :
					return "Low German";
					break;
				case "nep" :
					return "Nepali";
					break;
				case "new" :
					return "Newari";
					break;
				case "nia" :
					return "Nias";
					break;
				case "nic" :
					return "Niger-Kordofanian";
					break;
				case "niu" :
					return "Niuean";
					break;
				case "nno" :
					return "Norwegian ";
					break;
				case "nob" :
					return "Norwegian ";
					break;
				case "nog" :
					return "Nogai";
					break;
				case "non" :
					return "Old Norse";
					break;
				case "nor" :
					return "Norwegian";
					break;
				case "nso" :
					return "Northern Sotho";
					break;
				case "nub" :
					return "Nubian language";
					break;
				case "nya" :
					return "Nyanja";
					break;
				case "nym" :
					return "Nyamwezi";
					break;
				case "nyn" :
					return "Nyankole";
					break;
				case "nyo" :
					return "Nyoro";
					break;
				case "nzi" :
					return "Nzima";
					break;
				case "oci" :
					return "Occitan ";
					break;
				case "oji" :
					return "Ojibwa";
					break;
				case "ori" :
					return "Oriya";
					break;
				case "orm" :
					return "Oromo";
					break;
				case "osa" :
					return "Osage";
					break;
				case "oss" :
					return "Ossetic";
					break;
				case "ota" :
					return "Turkish, Ottoman";
					break;
				case "oto" :
					return "Otomian language";
					break;
				case "paa" :
					return "Papuan ";
					break;
				case "pag" :
					return "Pangasinan";
					break;
				case "pal" :
					return "Pahlavi";
					break;
				case "pam" :
					return "Pampanga";
					break;
				case "pan" :
					return "Panjabi";
					break;
				case "pap" :
					return "Papiamento";
					break;
				case "pau" :
					return "Palauan";
					break;
				case "peo" :
					return "Old Persian";
					break;
				case "per" :
					return "Persian";
					break;
				case "phi" :
					return "Philippine ";
					break;
				case "phn" :
					return "Phoenician";
					break;
				case "pli" :
					return "Pali";
					break;
				case "pol" :
					return "Polish";
					break;
				case "pon" :
					return "Ponape";
					break;
				case "por" :
					return "Portuguese";
					break;
				case "pra" :
					return "Prakrit language";
					break;
				case "pro" :
					return "Provençal ";
					break;
				case "pus" :
					return "Pushto";
					break;
				case "que" :
					return "Quechua";
					break;
				case "raj" :
					return "Rajasthani";
					break;
				case "rap" :
					return "Rapanui";
					break;
				case "rar" :
					return "Rarotongan";
					break;
				case "roa" :
					return "Romance ";
					break;
				case "roh" :
					return "Raeto-Romance";
					break;
				case "rom" :
					return "Romani";
					break;
				case "rum" :
					return "Romanian";
					break;
				case "run" :
					return "Rundi";
					break;
				case "rus" :
					return "Russian";
					break;
				case "sad" :
					return "Sandawe";
					break;
				case "sag" :
					return "Sango";
					break;
				case "sah" :
					return "Yakut";
					break;
				case "sai" :
					return "South American Indian";
					break;
				case "sal" :
					return "Salishan language";
					break;
				case "sam" :
					return "Samaritan Aramaic";
					break;
				case "san" :
					return "Sanskrit";
					break;
				case "sas" :
					return "Sasak";
					break;
				case "sat" :
					return "Santali";
					break;
				case "scc" :
					return "Serbian";
					break;
				case "sco" :
					return "Scots";
					break;
				case "scr" :
					return "Croatian";
					break;
				case "sel" :
					return "Selkup";
					break;
				case "sem" :
					return "Semitic";
					break;
				case "sga" :
					return "Irish, Old";
					break;
				case "sgn" :
					return "Sign language";
					break;
				case "shn" :
					return "Shan";
					break;
				case "sid" :
					return "Sidamo";
					break;
				case "sin" :
					return "Sinhalese";
					break;
				case "sio" :
					return "Siouan ";
					break;
				case "sit" :
					return "Sino-Tibetan";
					break;
				case "sla" :
					return "Slavic ";
					break;
				case "slo" :
					return "Slovak";
					break;
				case "slv" :
					return "Slovenian";
					break;
				case "sma" :
					return "Southern Sami";
					break;
				case "sme" :
					return "Northern Sami";
					break;
				case "smi" :
					return "Sami";
					break;
				case "smj" :
					return "Lule Sami";
					break;
				case "smn" :
					return "Inari Sami";
					break;
				case "smo" :
					return "Samoan";
					break;
				case "sms" :
					return "Skolt Sami";
					break;
				case "sna" :
					return "Shona";
					break;
				case "snd" :
					return "Sindhi";
					break;
				case "snk" :
					return "Soninke";
					break;
				case "sog" :
					return "Sogdian";
					break;
				case "som" :
					return "Somali";
					break;
				case "son" :
					return "Songhai";
					break;
				case "sot" :
					return "Sotho";
					break;
				case "spa" :
					return "Spanish";
					break;
				case "srd" :
					return "Sardinian";
					break;
				case "srr" :
					return "Serer";
					break;
				case "ssa" :
					return "Nilo-Saharan";
					break;
				case "ssw" :
					return "Swazi";
					break;
				case "suk" :
					return "Sukuma";
					break;
				case "sun" :
					return "Sundanese";
					break;
				case "sus" :
					return "Susu";
					break;
				case "sux" :
					return "Sumerian";
					break;
				case "swa" :
					return "Swahili";
					break;
				case "swe" :
					return "Swedish";
					break;
				case "syr" :
					return "Syriac";
					break;
				case "tah" :
					return "Tahitian";
					break;
				case "tai" :
					return "Tai";
					break;
				case "tam" :
					return "Tamil";
					break;
				case "tat" :
					return "Tatar";
					break;
				case "tel" :
					return "Telugu";
					break;
				case "tem" :
					return "Temne";
					break;
				case "ter" :
					return "Terena";
					break;
				case "tet" :
					return "Tetum";
					break;
				case "tgk" :
					return "Tajik";
					break;
				case "tgl" :
					return "Tagalog";
					break;
				case "tha" :
					return "Thai";
					break;
				case "tib" :
					return "Tibetan";
					break;
				case "tig" :
					return "Tigré";
					break;
				case "tir" :
					return "Tigrinya";
					break;
				case "tiv" :
					return "Tiv";
					break;
				case "tkl" :
					return "Tokelauan";
					break;
				case "tli" :
					return "Tlingit";
					break;
				case "tmh" :
					return "Tamashek";
					break;
				case "tog" :
					return "Tonga ";
					break;
				case "ton" :
					return "Tongan";
					break;
				case "tpi" :
					return "Tok Pisin";
					break;
				case "tsi" :
					return "Tsimshian";
					break;
				case "tsn" :
					return "Tswana";
					break;
				case "tso" :
					return "Tsonga";
					break;
				case "tuk" :
					return "Turkmen";
					break;
				case "tum" :
					return "Tumbuka";
					break;
				case "tup" :
					return "Tupi language";
					break;
				case "tur" :
					return "Turkish";
					break;
				case "tut" :
					return "Altaic ";
					break;
				case "tvl" :
					return "Tuvaluan";
					break;
				case "twi" :
					return "Twi";
					break;
				case "tyv" :
					return "Tuvinian";
					break;
				case "udm" :
					return "Udmurt";
					break;
				case "uga" :
					return "Ugaritic";
					break;
				case "uig" :
					return "Uighur";
					break;
				case "ukr" :
					return "Ukrainian";
					break;
				case "umb" :
					return "Umbundu";
					break;
				case "und" :
					return "Undetermined language";
					break;
				case "urd" :
					return "Urdu";
					break;
				case "uzb" :
					return "Uzbek";
					break;
				case "vai" :
					return "Vai";
					break;
				case "ven" :
					return "Venda";
					break;
				case "vie" :
					return "Vietnamese";
					break;
				case "vol" :
					return "Volapük";
					break;
				case "vot" :
					return "Votic";
					break;
				case "wak" :
					return "Wakashan language";
					break;
				case "wal" :
					return "Walamo";
					break;
				case "war" :
					return "Waray";
					break;
				case "was" :
					return "Washo";
					break;
				case "wel" :
					return "Welsh";
					break;
				case "wen" :
					return "Sorbian language";
					break;
				case "wln" :
					return "Walloon";
					break;
				case "wol" :
					return "Wolof";
					break;
				case "xal" :
					return "Kalmyk";
					break;
				case "xho" :
					return "Xhosa";
					break;
				case "yao" :
					return "Yao ";
					break;
				case "yap" :
					return "Yapese";
					break;
				case "yid" :
					return "Yiddish";
					break;
				case "yor" :
					return "Yoruba";
					break;
				case "ypk" :
					return "Yupik language";
					break;
				case "zap" :
					return "Zapotec";
					break;
				case "zen" :
					return "Zenaga";
					break;
				case "zha" :
					return "Zhuang";
					break;
				case "znd" :
					return "Zande";
					break;
				case "zul" :
					return "Zulu";
					break;
				case "zun" :
					return "Zuni";
					break;
				default :
					return null;
			}
		}
	}
	
	private function escapeXml($string)
	{
		// NOTE: if you make a change to this function, make a corresponding change 
		// in the Xerxes_Parser class, since this one here is a duplicate function 
		// allowing Xerxes_Record it be as a stand-alone class 
		
		$string = str_replace( '&', '&amp;', $string );
		$string = str_replace( '<', '&lt;', $string );
		$string = str_replace( '>', '&gt;', $string );
		$string = str_replace( '\'', '&#39;', $string );
		$string = str_replace( '"', '&quot;', $string );
		
		$string = str_replace( "&amp;#", "&#", $string );
		$string = str_replace( "&amp;amp;", "&amp;", $string );
		
		return $string;
	}
	
	private function toTitleCase($strInput)
	{
		// NOTE: if you make a change to this function, make a corresponding change 
		// in the Xerxes_Parser class, since this one here is a duplicate function 
		// allowing Xerxes_Record to be a stand-alone class

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
			$strInput = strtolower( $strInput );
		}
		
		// array of small words
		
		$arrSmallWords = array ('of', 'a', 'the', 'and', 'an', 'or', 'nor', 'but', 'is', 'if', 'then', 
		'else', 'when', 'at', 'from', 'by', 'on', 'off', 'for', 'in', 'out', 'over', 'to', 'into', 'with', 'as' );
		
		// split the string into separate words

		$arrWords = explode( ' ', $strInput );
		
		foreach ( $arrWords as $key => $word )
		{
			// if this word is the first, or it's not one of our small words, capitalise it 

			if ( $key == 0 || ! in_array( strtolower( $word ), $arrSmallWords ) )
			{
				$arrWords[$key] = ucwords( $word );
			} 
			elseif ( in_array( strtolower( $word ), $arrSmallWords ) )
			{
				$arrWords[$key] = strtolower( $word );
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
		// inside the title to ensure this isn't a quote for a contraction or for possisive; seperate
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
	
	private function ordinal($value)
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
	
	private function isFullText($arrLink)
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
	
	// non-standard properties

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
			$arrFinal = array ( );
			
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
	 * @param bool $bolFormat		[optional] return the author names as strings (otherwise as name parts in array), default false
	 * @param bool $bolReverse		[optional] return author names as strings, last name first
	 * @return array
	 */
	
	public function getAuthors($bolPrimary = false, $bolFormat = false, $bolReverse = false)
	{
		$arrFinal = array ( );
		
		foreach ( $this->authors as $arrAuthor )
		{
			if ( $bolFormat == true )
			{
				$strAuthor = ""; // author name formatted
				$strLast = ""; // last name
				$strFirst = ""; // first name
				$strInit = ""; // middle initial
				$strName = ""; // full name, not personal

				if ( array_key_exists( "first", $arrAuthor ) )
					$strFirst = $arrAuthor["first"];
				if ( array_key_exists( "last", $arrAuthor ) )
					$strLast = $arrAuthor["last"];
				if ( array_key_exists( "init", $arrAuthor ) )
					$strInit = $arrAuthor["init"];
				if ( array_key_exists( "name", $arrAuthor ) )
					$strName = $arrAuthor["name"];
				
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
				array_push( $arrFinal, $arrAuthor );
			}
			
			if ( $bolPrimary == true )
			{
				break;
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
	
	public function getEmbeddedText()
	{
		return $this->embedded_text;
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
}

?>
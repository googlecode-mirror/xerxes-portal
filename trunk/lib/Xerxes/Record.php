<?php

	/**
	 * Extract properties for books, articles, and dissertations from MARC-XML record 
	 * with special handling for Metalib X-Server response
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */

	class Xerxes_Record
	{        
		private $objMarcXML = null;			// original marc-xml dom document
		private $objXPath = null;			// xpath object
		
		private $strResolver = "";			// base address of openurl resolver
		private $strReferer = "";			// rfr_id
		
		private $strMetalibID = "";			// source database id
		private $strSource = "";			// source parser name
		private $strRecordNumber = "";		// metalib record number id
		private $strResultSet = "";			// metalib result set id
		private $strDatabaseName = "";		// database name
		
		private $strFormat = "";			// format
		private $strTechnology ="";			// technology/system format
		
		private $strControlNumber = "";		// the 001 basically, OCLC or otherwise
		private $strOCLC = "";				// oclc number
		private $strGovDoc = "";			// gov doc number
		private $strGPO = "";				// gov't printing office (gpo) number
		private $strEric = "";				// eric document number
		private $arrIsbn = array();			// isbn
		private $arrIssn = array();			// issn
		private $strCallNumber = "";		// lc call number

		private $arrAuthors = array();		// authors
		private $strAuthorFromTitle = "";	// author from title statement
		private $bolEditor = false;			// whether primary author is an editor

		private $strNonSort = "";			// non-sort portion of title
		private $strTitle = "";				// main title
		private $strSubTitle = "";			// subtitle	
		private $strSeriesTitle = "";		// series title
		private $bolTransTitle = false; 	// whether title is translated
		
		private $strPlace = "";				// place of publication	
		private $strPublisher = "";			// publisher	
		private $strDate = "";				// date of publication

		private $strEdition = "";			// edition
		private $strTPages = "";			// total pages
		private $strPrice = "";				// price

		private $strBookTitle = "";			// book title (for book chapters)
		private $strJournalTitle = "";		// journal title
		private $strJournal = "";			// journal source information
		private $strShortTitle = "";		// journal short title
		private $strVolume = "";			// volume
		private $strIssue = "";				// issue
		private $strStartPage = "";			// start page
		private $strEndPage = "";			// end page
		
		private $strDegree = "";			// thesis degree conferred
		private $strInstitution = "";		// thesis granting institution
		
		private $strDescription = "";		// physical description
		private $strAbstract = "";			// abstract
		private $strSummary = "";			// summary
    	private $arrEmbeddedText = array(); // full text
		private $strLanguage = "";			// primary language of the record
		private $arrNotes = array();		// notes that are not the abstract, language, or table of contents
		private $arrSubjects = array();		// subjects
		private $strTOC = "";				// table of contents note
		
		private $arrLinks = array();		// all supplied links in the record both full text and non
		
		private $arrAltScript = array();	// alternate character-scripts like cjk or hebrew, taken from 880s
		private $strAltScript = "";			// the name of the alternate character-script; we'll just assume one for now, I guess
		
    static $TemplateEmptyValue = "Xerxes_Record_lookupTemplateValue_placeholder_missing";
    
		### PUBLIC FUNCTIONS ###
		
		
		/**
		 * Load XML record to be processed into this Xerxes_Record. 
     *
     * Note that the record will NOT have been enhanced with metalib
     * templated links (original_record, holdings). Use completeUrlTemplates()
     * to add those. 
		 *
		 * @param mixed $xml [string or DOMDocument or DOMElement] single record
     * @param array $databaseLinkTemplates An optional hash of metalib-style link templates to apply to the MARC to generate links to include in the Xerxes XML. Should be of the form: { "metalib_id" => { "xerxes_link_type" => "metalib_template", "different_link_type" => "template"}, "metalib_id2" => [etc] }     
		 */
		
     public function loadXML( $xml, $databaseLinkTemplates = null )
		{
			// type check
      
      
			if ( ! ( is_string($xml) || get_class($xml) == "DOMDocument" || get_class($xml) == "DOMElement" ) )
				throw new Exception("param 1 must be XML of type string, DOMDocument, or DOMElement");
						
			// type conversion
				
			if ( is_string($xml))
			{
				// supplied record is a string, convert to DOMDocument
				
				$objXml = new DOMDocument();
				$objXml->loadXML($xml);
				$xml= $objXml;
			}
			elseif ( get_class($xml) == "DOMElement" )
			{				
				// supplied record is a DOMElement, convert to DOMDocument
				
				$objXml = new DOMDocument();
				$objXml->loadXML("<collection />");
				$objImport = $objXml->importNode($xml, true);
				$objXml->documentElement->appendChild($objImport);
				$xml = $objXml;
			}
			
      
			### set XPath object and namespaces
			
			$strMarcNS = "http://www.loc.gov/MARC21/slim";
			
			$objXPath = new DOMXPath($xml);
			$objXPath->registerNamespace("ctx", "info:ofi/fmt:xml:xsd:ctx");
			$objXPath->registerNamespace("marc", $strMarcNS);
			
			// test to see what profile the context object is using; set namespace accordingly
			
			if ( $xml->getElementsByTagNameNS("info:ofi/fmt:xml:xsd:book", "book")->item(0) != null )
			{
				$objXPath->registerNamespace("rft","info:ofi/fmt:xml:xsd:book");
			}
			elseif ( $xml->getElementsByTagNameNS("info:ofi/fmt:xml:xsd:dissertation", "dissertation")->item(0) != null )
			{
				$objXPath->registerNamespace("rft","info:ofi/fmt:xml:xsd:dissertation");
			}
			elseif ( $xml->getElementsByTagNameNS("info:ofi/fmt:xml:xsd", "journal")->item(0) != null )
			{
				// this is not an actual namespace reference, but a bug in the metalib
				// x-server that causes it to send back a mislabelled namespace (2007-02-19)
				
				$objXPath->registerNamespace("rft","info:ofi/fmt:xml:xsd");
			}
			else
			{
				$objXPath->registerNamespace("rft","info:ofi/fmt:xml:xsd:journal");
			}
			
			// check to make sure there is a marc-xml document
			
			if ( $xml->getElementsByTagNameNS($strMarcNS, "record")->item(0) == null )
			{
				throw new Exception("supplied xml document did not contain marc-xml");
			}
			else
			{
				// we'll save these as member variables so other functions might use them
			
				$this->objMarcXML = $xml;
				$this->objXPath = $objXPath;        
        
        
				### process record

				$strPrimaryAuthor = "";
				$strStitle = ""; 
				$arrAddConf = array();
				$arrAddCorp = array();
				$arrAltAuthors = array();
				$arrPrimaryAuthor = array();
				
				
				### get the marc values for manipulation
				
				// source database
				
				$this->strMetalibID = $this->extractMarcDataField($objXPath, "SID", "d");
				$this->strRecordNumber = $this->extractMarcDataField($objXPath, "SID", "j");
				$this->strResultSet = $this->extractMarcDataField($objXPath, "SID", "s");
				$this->strDatabaseName = $this->extractMarcDataField($objXPath, "SID", "t");
				
				// source may have been explicitly set in the calling code, so make sure
				// there is no value here before we extract it from the marc record
				
				if ( $this->strSource == "")
				{
					$this->strSource = $this->extractMarcDataField($objXPath, "SID", "b");
				}
				
				// control and standard numbers
				
				$str001 = $this->extractMarcControlField($objXPath, 1);
				$this->strControlNumber = $str001;
				
				$str003 = $this->extractMarcControlField($objXPath, 3);
				$str008 = $this->extractMarcControlField($objXPath, 8);
				$str035 = $this->extractMarcDataField($objXPath, 35, "a");
				$arrIssn = $this->extractMarcArray($objXPath, 22, "a");
				$arrIsbn = $this->extractMarcArray($objXPath, 20, "a");
				$this->strGovDoc = $this->extractMarcDataField($objXPath, 86, "a");
				$this->strGPO = $this->extractMarcDataField($objXPath, 74, "a");
				
				$strJournalIssn = $this->extractMarcDataField($objXPath, 773, "x");
				if ( $strJournalIssn != null ) array_push($arrIssn, $strJournalIssn);
				
				// call number
				
				$strCallNumber = $this->extractMarcDataField($objXPath, 50, "*");
				$strCallNumberLocal = $this->extractMarcDataField($objXPath, 90, "*");
				
				if ( $strCallNumber != null )
				{
					$this->strCallNumber = $strCallNumber;
				}
				elseif ( $strCallNumberLocal != null)
				{
					$this->strCallNumber = $strCallNumberLocal;
				}
				
				// format
	
				$arrFormat = $this->extractMarcArray($objXPath, 513, "a");
				$strTitleFormat = $this->extractMarcDataField($objXPath, 245, "k");
				$this->strTechnology = $this->extractMarcDataField($objXPath, 538, "a");
				
				if ( $strTitleFormat != null ) array_push($arrFormat, $strTitleFormat);				
				
				// thesis degree, institution, date awarded
				  
				$strThesis = $this->extractMarcDataField($objXPath, 502, "a");				
				
				// authors
				
				$arrPrimaryAuthor = $this->extractMarcArray($objXPath, 100, "a");
				$strCorpName = $this->extractMarcDataField($objXPath, 110, "ab");
				$strConfName = $this->extractMarcDataField($objXPath, 111, "anc");
				$this->strAuthorFromTitle = $this->extractMarcDataField($objXPath, 245, "c");
				
				$arrAltAuthors = $this->extractMarcArray($objXPath, 700, "a");
				$arrAddCorp = $this->extractMarcArray($objXPath, 710, "ab");
				$arrAddConf = $this->extractMarcArray($objXPath, 711, "acn");
				
				// conference and corporate names from title ?
				
				$arrConferenceTitle = $this->extractMarcArray($objXPath, 811, "*");
				if ( $arrAddConf == null && $arrConferenceTitle != null) $arrAddConf = $arrConferenceTitle;
				
				$arrCorporateTitle = $this->extractMarcArray($objXPath, 810, "*");
				if ( $arrAddCorp == null && $arrCorporateTitle != null) $arrAddCorp = $arrCorporateTitle;
				
				if ( $strConfName != null || $arrAddConf != null ) array_push($arrFormat, "conference paper");
				
				// titles
				
				$this->strTitle = $this->extractMarcDataField($objXPath, 245, "a");
				$this->strSubTitle = $this->extractMarcDataField($objXPath, 245, "b");
				$this->strSeriesTitle = $this->extractMarcDataField($objXPath, 440, "a");

				// sometimes the title appears in a 242 or even a 246 if it is translated from another
				// language, although the latter is probably bad practice.  We will only take these
				// if the title in the 245 is blank, and take a 242 over the 246
				
				$strTransTitle = $this->extractMarcDataField($objXPath, 242, "a");
				$strTransSubTitle = $this->extractMarcDataField($objXPath, 242, "b");
				
				$strVaryingTitle = $this->extractMarcDataField($objXPath, 246, "a");
				$strVaryingSubTitle = $this->extractMarcDataField($objXPath, 246, "b");
				
				if ( $this->strTitle == "" && $strTransTitle != "" )
				{
					$this->strTitle = $strTransTitle;
					$this->bolTransTitle = true;
				}
				elseif ( $this->strTitle == "" && $strVaryingTitle != "" )
				{
					$this->strTitle = $strVaryingTitle;
					$this->bolTransTitle = true;				
				}
				
				if ( $this->strSubTitle == "" && $strTransSubTitle != "" )
				{
					$this->strSubTitle = $strTransTitle;
					$this->bolTransTitle = true;
				}
				elseif ( $this->strSubTitle == "" && $strVaryingSubTitle != "" )
				{
					$this->strSubTitle = $strVaryingSubTitle;
					$this->bolTransTitle = true;					
				}
				
				// leader
				
				$objLeader = $objXPath->query("//marc:leader")->item(0);
				$strLeaderMetalib =  $this->extractMarcControlField($objXPath, "LDR");
	
				// edition, extent, description
				
				$this->strEdition = $this->extractMarcDataField($objXPath, 250, "a");			
				$this->strTPages = $this->extractMarcDataField($objXPath, 300, "a");
				$this->strDescription = $this->extractMarcDataField($objXPath, 300, "*");
				$this->strPrice = $this->extractMarcDataField($objXPath, 365, "*");
				
				// publisher
				
				$this->strPlace = $this->extractMarcDataField($objXPath, 260, "a");
				$this->strPublisher = $this->extractMarcDataField($objXPath, 260, "b");
				
				// date
				
				$strDate = $this->extractMarcDataField($objXPath, 260, "c");
				$strYear = $this->extractMarcDataField($objXPath, "YR ", "a");
				
				// notes
				
				$this->strTOC = $this->extractMarcDataField($objXPath, 505, "agrt");				
				$arrAbstract = $this->extractMarcArray($objXPath, 520, "a");
				$strLanguageNote = $this->extractMarcDataField($objXPath, 546, "a");
				
				// actual text is sometimes embedded in the marc
				$this->arrEmbeddedText = $this->extractMarcArray( $objXPath, 900, "a" );
				
				$this->arrNotes = $this->extractMarcArray($objXPath, null, "*", 
					"//marc:datafield[@tag >= 500 and @tag < 600 and @tag != 505 and @tag != 520 and @tag != 546]");
					
				// subjects
				
				// we'll exclude the numeric subfields since they contain information about the
				// source of the subject terms, which are probably not needed for display?
				
				$this->arrSubjects = $this->extractMarcArray($objXPath, "600-669", "abcdfghijklmnopqrstuvwxyz");
				
				// full-text
				
				$objFullTextList = $objXPath->query("//marc:datafield[@tag=856]");
				
				// journal
				
				$this->strJournal = $this->extractMarcDataField($objXPath, 773, "*");
				$strJournal = $this->extractMarcDataField($objXPath, 773, "agpt");
				$this->strJournalTitle = $this->extractMarcDataField($objXPath, 773, "t");
				$this->strShortTitle = $this->extractMarcDataField($objXPath, 773, "p");
				$strExtentHost = $this->extractMarcDataField($objXPath, 773, "h");
				
				// alternate character-scripts
				
				// the 880 represents an alternative character-script, like Hebrew or CJK;
				// for simplicity's sake, we just dump them all here in an array, with the 
				// intent of displaying them in paragraphs together in the interface or something?
				
				// we get every field except for the $6 which is a linking field
				
				$this->arrAltScript = $this->extractMarcArray($objXPath, 880, "abcdefghijklmnopqrstuvwxyz12345789");
				
				// now use the $6 to figure out which character-script this is
				// assume just one for now
				
				$strAltScript = $this->extractMarcDataField($objXPath, 880, "6");
				
				if ( $strAltScript != null )
				{
					$arrMatchCodes = array();
					
					$arrScriptCodes = array(
						"(3" => "Arabic",
						"(B" => "Latin",
						'$1' => "CJK",
						"(N" => "Cyrillic",
						"(S" => "Greek",
						"(2" => "Hebrew"
					);
					
					if ( preg_match("/[0-9]{3}-[0-9]{2}\/(.*)/", $strAltScript, $arrMatchCodes) )
					{
						if ( array_key_exists($arrMatchCodes[1], $arrScriptCodes) )
						{
							$this->strAltScript = $arrScriptCodes[$arrMatchCodes[1]];
						}
					}
				}
				
				############################
				## database-specific code ##
				############################
				
				// eric doc number
				
				$this->strEric = $this->extractMarcDataField($objXPath, "ERI", "a");
				
				// various places ebsco shoves format information
				
				$strEbscoPsycFormat = $this->extractMarcDataField($objXPath, 656, "a");	
				$strEbscoFormat = $this->extractMarcDataField($objXPath, 514, "a");
				$strEbscoType = $this->extractMarcDataField($objXPath, "072", "a");
				
				// psycinfo and related dbs are an exception to the ebsco format type
				
				if ( strstr($this->strSource, "EBSCO_PSY") || strstr($this->strSource, "EBSCO_PDH") )
				{ 
					$strEbscoType = ""; 
				}
				
				if ( $strEbscoPsycFormat != null ) array_push($arrFormat, $strEbscoPsycFormat);
				if ( $strEbscoFormat != null ) array_push($arrFormat, $strEbscoFormat);
				if ( $strEbscoType != null ) array_push($arrFormat, $strEbscoType);
				
				
				// oclc dissertation abstracts
				//
				// (HACK) 10/1/2007 this assumes that the diss abs record includes the 904, which means
				// there needs to be a local search config that performs an 'add new' action rather than
				// the  'remove' action that the parser uses by default
				
				if ( strstr($this->strSource, "OCLC_DABS") )
				{
					$this->strDegree = $this->extractMarcDataField($objXPath, 904, "j");
					$this->strInstitution = $this->extractMarcDataField($objXPath, 904, "h");
					$this->strJournalTitle = $this->extractMarcDataField($objXPath, 904, "c");
					
					$this->strJournal = $this->strJournalTitle . " " . $this->strJournal;
					
					if ( $this->strJournalTitle == "MAI" )
					{
						array_push($arrFormat, "Thesis");
					}
					else
					{
						array_push($arrFormat, "Dissertation");
					}
					
					$strThesis = "";
				}
				
				// gale puts issn in 773b
				
				$strGaleIssn = $this->extractMarcDataField($objXPath, 773, "b");
				if ( $strGaleIssn != null ) array_push($arrIssn, $strGaleIssn);
				
				// ebsco book chapter
				
				$strEbscoBookTitle = $this->extractMarcDataField($objXPath, 771, "a");
				if ( $strEbscoBookTitle != "" ) array_push($arrFormat, "Book Chapter") ;

				
				// JSTOR book review correction: title is meaningless, but subjects
				// contain the title of the books, so we'll swap them to the title here
				
				if ( strstr($this->strSource,'JSTOR') && $this->strTitle == "Review: [untitled]" )
				{
					$this->strTitle = "";
					
					foreach( $this->arrSubjects as $strSubject )
					{
						$this->strTitle .= " " . $strSubject;
					}
					
					$this->strTitle = trim( $this->strTitle);
					$this->arrSubjects = null;
					
					array_push($arrFormat, "Book Review");
				}
				
				// gale title clean-up, because for some reason unknown to man 
				// they put weird notes and junk at the end of the title. so remove them 
				// here and add them to notes.
				
				if ( strstr($this->strSource,'GALE_') )
				{
					$iEndPoint = strlen($this->strTitle) - 1;
					$arrMatches = array();
					$strGaleRegExp = "/\(([^)]*)\)/";
					
					if ( preg_match_all($strGaleRegExp, $this->strTitle, $arrMatches) != 0 )
					{
						$this->strTitle = preg_replace($strGaleRegExp, "", $this->strTitle);
					}
					
					foreach ( $arrMatches[1] as $strMatch )
					{
						array_push($this->arrNotes, "From title: " . $strMatch);
					}
					
					// subtitle only appears to be one of these notes
					
					if ( $this->strSubTitle != "" )
					{
						array_push($this->arrNotes, "From title: " . $this->strSubTitle);
						$this->strSubTitle = "";
					}
				}
				
				// google books: nothing indicates that this is actually a book
				
				if ( $this->strSource == "GOOGLE_B")
				{
					array_push($arrFormat, "Book");
				}
				
				// google scholar: extract non-article format information from the title
				// maybe ask Ere in Finland to do this?
				
				if ( stristr($this->strDatabaseName, "Google Scholar") )
				{
					$strGoogleRefExp = "/\[([^\]]*)\]/";
					$arrMatches = array();
					
					if ( preg_match_all($strGoogleRefExp, $this->strTitle, $arrMatches) != 0 )
					{
						$this->strTitle = preg_replace($strGoogleRefExp, "", $this->strTitle);
					}
					
					foreach ( $arrMatches[1] as $strMatch )
					{
						array_push($arrFormat, $strMatch);
					}
				}
				
				// encyclopedia britannica, full text is in summary field, swap them. 
				
				if ( $this->strSource == "BRITANNICA_ENCY" )
				{
					if ( count( $this->getEmbeddedText() ) == 0 && $arrAbstract )
					{
						$text = join( " ", $this->extractMarcArray( $objXPath, 520, "a" ) );
						$text = str_replace( '^', '', $text );
						
						$this->arrEmbeddedText = array ($text );
						$arrAbstract = array ( );
					
					}
				}
				
				############################
				##  end database-specific ##
				############################
				
				
				### issue and volume
				
				// for some reason Metalib misses these sometimes, so we'll parse them out here;
				// only taking a value in the context object over it
				
				$this->strIssue = $this->extractMarcDataField($objXPath, "ISS", "a");
				$this->strVolume= $this->extractMarcDataField($objXPath, "VOL", "a");

				
				### openurl context object: journal title, volume, issue, pages from context object
				
				$objSTitle = $objXPath->query("//rft:stitle")->item(0);
				$objTitle = $objXPath->query("//rft:title")->item(0);
				$objVolume = $objXPath->query("//rft:volume")->item(0);
				$objIssue = $objXPath->query("//rft:issue")->item(0);
				$objStartPage = $objXPath->query("//rft:spage")->item(0);
				$objEndPage = $objXPath->query("//rft:epage")->item(0);
				$objISSN = $objXPath->query("//rft:issn")->item(0);
				$objISBN = $objXPath->query("//rft:isbn")->item(0);
				
				if ( $objSTitle != null ) $strStitle = $objSTitle->nodeValue;
				if ( $objVolume != null ) $this->strVolume = $objVolume->nodeValue;
				if ( $objIssue != null ) $this->strIssue = $objIssue->nodeValue;
				if ( $objStartPage != null ) $this->strStartPage = $objStartPage->nodeValue;
				if ( $objEndPage != null ) $this->strEndPage = $objEndPage->nodeValue;
				if ( $this->strJournalTitle == "" && $objTitle != null ) $this->strJournalTitle =  $objTitle->nodeValue;
				
				$strAltIsbn = ""; if ( $objISBN != null ) $strAltIsbn = $objISBN->nodeValue;
				$strAltIssn = ""; if ( $objISSN != null ) $strAltIssn = $objISSN->nodeValue;
				
				
				### generic regular expression parser
				
				// we'll use this as a back-up for extracting volume, issue, pages in
				// case Metalib misses that information in context object
				
				$arrRegExJournal = $this->parseJournalData($strJournal);
				
				### leader
				
				// get best leader
				
				$strLeader = "";
				
				if ( $objLeader != null )
				{
					$strLeader = $objLeader->nodeValue;
				}
				elseif ( $strLeaderMetalib != "")
				{
					$strLeader = $strLeaderMetalib;
				}
				
				### standard number clean-up
				
				// take the standard numbers in the context object if 020 and 022
				// didn't have any -- essentially for sites that stuff them somewhere odd
				
				if ( count($arrIsbn) == 0 && $strAltIsbn != "") array_push($arrIsbn, $strAltIsbn);
				if ( count($arrIssn) == 0 && $strAltIssn != "") array_push($arrIssn, $strAltIssn);
				 
				
				// some sources include ^ as a filler character in issn/isbn, these people should be shot!
				
				foreach ( $arrIssn as $strIssn )
				{
					if ( strpos($strIssn, "^") === false )
					{
						array_push($this->arrIssn, $strIssn);
					}
				}

				foreach ( $arrIsbn as $strIsbn )
				{
					if ( strpos($strIsbn, "^") === false )
					{
						array_push($this->arrIsbn, $strIsbn);
					}
				}
				
				### language
								
				// take an explicit lanugage note over leader if available
				
				if ( $strLanguageNote != null )
				{
					$strLanguageNote = $this->stripEndPunctuation($strLanguageNote, ".");
					
					if (strlen($strLanguageNote) == 2 )
					{
						$this->strLanguage = $this->convertLanguageCode($strLanguageNote, true);
					}
					elseif (strlen($strLanguageNote) == 3 )
					{
						$this->strLanguage = $this->convertLanguageCode($strLanguageNote);
					}
					elseif (! stristr($strLanguageNote, "Undetermined"))
					{
						$this->strLanguage = str_ireplace("In ", "", $strLanguageNote);
					}
				}
				elseif ( strlen($str008) > 37 )
				{
					// get the language chars from the 008; need to check to see
					// if article databases do this consistently, and don't just hardwire
					// the characters -- i'm looking at you, ebscohost!
	
					$this->strLanguage = $this->convertLanguageCode( substr($strLeader, 35, 3) );
				}
				
				### format
				
								
				$this->strFormat = $this->parseFormat( $this->strSource, $this->strTitle . " " . $this->strSubTitle, 
					$this->strJournal, $arrFormat, $strLeader, $this->strEric, $strThesis, $this->arrIsbn, $this->arrIssn );
					
					
				### full-text non-856
				
				// some databases have full-text but no 856
				// will capture these here and add to links array
				
				// pychcritiques -- no indicator of full-text either, assume all to be (9/5/07)
				// no unique metalib config either, using psycinfo, so make determination based on name. yikes!
				
				if ( stristr($this->strDatabaseName, "psycCRITIQUES"))
				{
					array_push($this->arrLinks, array("Full-Text in HTML", array("001" => $str001), "html"));
				}
				
				// factiva -- no indicator of full-text either, assume all to be (9/5/07)
				
				if ( stristr($this->strSource, "FACTIVA"))
				{
					array_push($this->arrLinks, array("Full-Text Available", array("035_a" => $str035), "online"));
				}
				
				// eric -- document is recent enough to likely contain full-text;
				// 340000 being a rough approximation of the document number after which they 
				// started digitizing
				
				if ( strstr($this->strSource, "ERIC") && strlen($this->strEric) >  3 )
				{
					$strEricType = substr($this->strEric, 0, 2);
					$strEricNumber = (int) substr($this->strEric, 2);
					
					if ( $strEricType == "ED" && $strEricNumber >= 340000 )
					{
						$strFullTextPdf = "http://www.eric.ed.gov/ERICWebPortal/contentdelivery/servlet/ERICServlet?accno=" .
							$this->strEric;
						
						array_push($this->arrLinks, array("Full-text at ERIC.gov", $strFullTextPdf, "pdf"));
					}
				}
        
        // 7 Apr 09, jrochkind. Gale Biography Resource Center
        // No 856 is included at all, but a full text link can be
        // constructed from the 001 record id.
        if ($this->strSource == "GALE_ZBRC") {
            $url = "http://galenet.galegroup.com/servlet/BioRC?docNum=" . $this->strControlNumber;
            
            array_push($this->arrLinks, array("Full-Text in HTML", $url, "html"));                      
        }
				
				### full-text 856
				
				// examine the 856s present in the record to see if they are in
				// fact to full-text, and not to a table of contents or something
				// stupid like that, by checking for existence of subfield code 3

				foreach ( $objFullTextList as $objFullText)
				{
					$strUrl = "";
					$strDisplay = "";
					$strEbscoFullText = "";
					$bolToc = false;
					
					foreach ( $objFullText->getElementsByTagName("subfield") as $objSubField )
					{
						if ( $objSubField->getAttribute("code") == "a" ||	// host name (generic field for $z-like info)
						     $objSubField->getAttribute("code") == "q" ||	// electronic format type
						     $objSubField->getAttribute("code") == "y" ||	// link text
						     $objSubField->getAttribute("code") == "z")		// note
						{
							$strDisplay = $objSubField->nodeValue;
						}
						elseif ( $objSubField->getAttribute("code") == "i" )
						{
							$strEbscoFullText = $objSubField->nodeValue;
						}						
						elseif ( $objSubField->getAttribute("code") == "u")
						{
							$strUrl = $objSubField->nodeValue;
						}
						elseif( $objSubField->getAttribute("code") == "3" )
						{
							$bolToc = true;
						}
					}
           
          
          
					// empty link, skip to next foreach entry
					
					if ( $strUrl == "")
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


					if ( stristr($strUrl, "$3") || 	
						 stristr($this->strSource, "METAPRESS_XML") ||
						 stristr($this->strSource, "EBSCO_RZH") ||
						 stristr($this->strSource, "CABI") || 
					     stristr($this->strSource, "GOOGLE_SCH") || 
					     stristr($this->strSource, "AMAZON") || 
					     stristr($this->strSource, "ABCCLIO") || 
					     stristr($this->strSource, "EVII") || 
					     stristr($this->strSource, "WILEY_IS") || 
					     ( stristr($this->strSource, "OXFORD_JOU") && ! strstr($strUrl, "content/full/") ) ||
					     ( strstr($this->strSource, "GALE") && ! $this->strSource == "GALE_GVRL" && ! in_array("Text available", $this->arrNotes)  ) ||
					     stristr($strUrl, "www.loc.gov/catdir") ||
					     stristr($this->strSource, "IEEE_XPLORE"))
					{
						$bolToc = true;
					}
          // Mark Scopus as original_record, not full text
          if ($this->strSource == "ELSEVIER_SCOPUS") {
            $strLinkFormat = "original_record";
          }
          
					
					if ( $bolToc == false )
					{
						// ebsco html
						// there is (a) an indicator from ebsco that the record has full-text, or 
						// (b) an abberant 856 link that doesn't work, but the construct link will work, 
						// so we take that as something of a full-text indicator
						
						if ( strstr($this->strSource, "EBSCO") && ( strstr($strEbscoFullText, "T") || strstr($strDisplay, "View Full Text") ) )
						{
							array_push($this->arrLinks, array($strDisplay, array("001" => $str001), "html"));
						}

						else
						{
							// look for the letters PDF in the label or the url, or HTML
							// in the label to see if we can pin-down format, otherwise
							// map it to the generic full-text property
							
							if (empty($strLinkFormat)) $strLinkFormat = "online";
							
							if ( stristr($strDisplay, "PDF") || stristr($strUrl, "PDF") )
							{
								$strLinkFormat = "pdf";
							}
							elseif ( stristr($strDisplay, "HTML") )
							{
								$strLinkFormat = "html";
							}
							
							array_push($this->arrLinks, array($strDisplay, $strUrl, $strLinkFormat));
							
						}

					}
					else
					{
						array_push($this->arrLinks, array($strDisplay, $strUrl, "none"));
					}
				}	
        
        ### Add link to native record and to external holdings URL too, if
        # available from metalib template. 
        
        if ($databaseLinkTemplates && $this->getMetalibID() && array_key_exists($this->getMetalibID(), $databaseLinkTemplates)) { 
          
          $arrTemplates = $databaseLinkTemplates[ $this->getMetalibID() ];
          
          foreach( $arrTemplates as $type => $template ) {
            $filled_in_link = $this->resolveUrlTemplate($template);
            if (! empty($filled_in_link)) {
            array_push($this->arrLinks, array(null, $filled_in_link, $type));
            }
          }
        }
        
        #$objXPath->evaluate();
        #$this->objMarcXML = $xml;
			  #$this->objXPath = $objXPath;   
        #exit;
							
				
				### oclc number
				
				// oclc number can be either in the 001 or in the 035$a
				// make sure 003 says 001 is oclc number or 001 includes an oclc prefix, 
				// unless this is worldcat, in which case 001 is by definition an oclc number
				
				if ( $str001 != "" && 
				( ( $str003 == "" && preg_match('/^\(?([Oo][Cc])/', $str001) ) || 
				  $str003 == "OCoLC" || 
				  stristr($this->strSource, "WORLDCAT") ) )
				{
					$this->strOCLC = $str001;
				}
				elseif ( strpos($str035, "OCoLC") !== false )
				{
					$this->strOCLC = $str035;
				}
							
				// get just the number
				
				$arrOclc = array();
				
				if ( preg_match("/[0-9]{1,}/", $this->strOCLC, $arrOclc) != 0 )
				{
					$strJustOclcNumber = $arrOclc[0];
					
					// strip out leading 0s
					
					$strJustOclcNumber = preg_replace("/^0{1,8}/", "", $strJustOclcNumber);
					
					$this->strOCLC = $strJustOclcNumber;
				}
				
				### summary
				
				// abstract
								
				foreach ( $arrAbstract as $strAbstract )
				{
					$this->strAbstract .= " " . $strAbstract;
				}
								
				$this->strAbstract = trim(strip_tags($this->strAbstract));
								
				// summary
				
				if ( $this->strAbstract != "")
				{
					$this->strSummary = $this->strAbstract;
				}
				elseif ( $this->strTOC != "")
				{
					$this->strSummary = "Includes chapters on: " . $this->strTOC;
				}
				elseif ( count($this->arrSubjects) > 0 )
				{
					$this->strSummary = "Covers the topics: ";
					
					for ( $x = 0; $x < count($this->arrSubjects); $x++ )
					{
						$this->strSummary .= $this->arrSubjects[$x];
						
						if ( $x < count($this->arrSubjects) - 1 )
						{
							$this->strSummary .= "; ";
						}
					}
				}
				
				### journal title
				
				// we'll take the journal title form the 773$t as the best option,
				// otherwise we'll see if Metalib was able to extract the title from
				// what will likely be the 773$a into the context object, note that 
				// Metalib incorrectly (?) maps this to the 'rft:stitle' field, and also
				// will map book titles to the stitle if no issn or isbn!
				
				if ( $this->strJournalTitle == "" )
				{
					if ( $strStitle != "" && ( $this->strFormat == "Article" ||  $this->strFormat =="Journal or Newspaper"))
					{
						$this->strJournalTitle = $strStitle;
					}
				}
				
				### volume
				
				if ( $this->strVolume == "" )
				{
					if ( array_key_exists("volume", $arrRegExJournal) )
					{
						$this->strVolume =  $arrRegExJournal["volume"];
					}
				}
	
				### issue
				
				if ( $this->strIssue == "" )
				{
					if ( array_key_exists("issue", $arrRegExJournal) )
					{
						$this->strIssue =  $arrRegExJournal["issue"];
					}
				}
				
				### pages
				
				// start page
				
				if ( $this->strStartPage == "")
				{
					if ( array_key_exists("spage", $arrRegExJournal) )
					{
						$this->strStartPage = $arrRegExJournal["spage"];
					}
				}
				
				// end page
				
				if ( $this->strEndPage == "")
				{
					if ( array_key_exists("epage", $arrRegExJournal) )
					{
						// found an end page from our generic regular expression parser
						
						$this->strEndPage = $arrRegExJournal["epage"];
					}
					elseif ( $strExtentHost != "" && $this->strStartPage != "")
					{
						// there is an extent note, indicating the number of pages,
						// most likely from Ebsco, so calculate end page based on that
						
						$arrExtent = array();
						
						if ( preg_match("/([0-9]{1})\/([0-9]{1})/", $strExtentHost, $arrExtent) != 0 )
						{						
							// if extent expressed as a fraction of a page, just take
							// the start page as the end page
							
							$this->strEndPage = $this->strStartPage;						
						}
						elseif ( preg_match("/[0-9]{1,}/", $strExtentHost, $arrExtent) != 0 )
						{						
							// otherwise take whole number
							
							$iStart = (int) $this->strStartPage;
							$iEnd = (int) $arrExtent[0];
							
							$this->strEndPage = $iStart + ( $iEnd - 1 )	;					
						}
					}

				}
				
				// page normalization
				
				if ( $this->strEndPage != "" && $this->strStartPage != "")
				{
					// pages were input as 197-8 or 197-82, or similar, so convert
					// the last number to the actual page number
					
					if ( strlen($this->strEndPage) < strlen($this->strStartPage) )
					{
						$strMissing = substr($this->strStartPage, 0, strlen($this->strStartPage) - strlen($this->strEndPage) );
						$this->strEndPage = $strMissing . $this->strEndPage;
					}
				}
				
				### edition
				
				// get just the number
				
				$arrEdition = array();
				
				if ( preg_match("/[0-9]{1,}/", $this->strEdition, $arrEdition) != 0 )
				{
					$this->strEdition = $arrEdition[0];
				}
				elseif ( substr($this->strEdition, -4) == " ed." )
				{
					$this->strEdition = substr($this->strEdition, 0, -4);
				}
				
				### isbn
				
				// get just the isbn minus format notes
				
				for ( $x = 0; $x < count($this->arrIsbn); $x++ )
				{				
					$arrIsbnExtract = array();
	
					$this->arrIsbn[$x] = str_replace("-", "", $this->arrIsbn[$x]);
					
					if ( preg_match("/[0-9]{12,13}X{0,1}/", $this->arrIsbn[$x], $arrIsbnExtract) != 0 )
					{
						$this->arrIsbn[$x] = $arrIsbnExtract[0];
					}
					elseif ( preg_match("/[0-9]{9,10}X{0,1}/", $this->arrIsbn[$x], $arrIsbnExtract) != 0 )
					{
						$this->arrIsbn[$x] = $arrIsbnExtract[0];
					}
				}
	
				### book chapter
				
				if ( $this->strFormat == "Book Chapter")
				{
					// ebsco places book title and author in unusual fields, so we'll
					// map them here to their proper elements
					
					if ( $strEbscoBookTitle != "" )
					{
						$this->strBookTitle = $strEbscoBookTitle;
					}
					elseif ($this->strJournalTitle != "")
					{
						$this->strBookTitle = $this->strJournalTitle;
						$this->strJournalTitle = "";
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
					
					$arrDegree = array();
						
					if ( preg_match("/\(([^\(]*)\)/", $strThesis, $arrDegree) != 0 )
					{
						$this->strDegree = $arrDegree[1];
					}
		
					// extract institution
						
					$iInstPos = strpos($strThesis,"--");
						
					if ( $iInstPos !== false )
					{
						$strInstitution = "";
						
						// get everything after the --
						$strInstitution = substr($strThesis, $iInstPos + 2, strlen($strThesis) - 1 );
							
						// find last comma in remaining text
						$iEndPosition = strrpos($strInstitution,",");
		
						if ( $iEndPosition !== false )
						{
							$strInstitution = substr($strInstitution, 0, $iEndPosition );
						}
		
					$this->strInstitution = $strInstitution;
					
					}
						
					// extract year conferred
						
					$this->strDate = $this->extractYear($strThesis);
				}
				
				### title
				
				$this->strNonSort = strip_tags($this->strNonSort);
				$this->strTitle = strip_tags($this->strTitle);
				$this->strSubTitle = strip_tags($this->strSubTitle);
				
				// make sure subtitle is properly parsed out
				
				$iColon = strpos($this->strTitle,":");
				
				if ( $this->strSubTitle == "" && $iColon !== false )
				{
					
					$this->strSubTitle = trim(substr($this->strTitle, $iColon + 1));
					$this->strTitle = trim(substr($this->strTitle, 0, $iColon));
				}
				
				// make sure nonSort portion of the title is extracted

				// punctuation; we'll also *add* the definite/indefinite article below should 
				// the quote be followed by one of those -- this is all in english, yo!
				
				if ( strlen($this->strTitle) > 0 )
				{
					if ( substr($this->strTitle, 0, 1) == "\"" || substr($this->strTitle, 0, 1) == "'")
					{
						$this->strNonSort = substr($this->strTitle, 0, 1);
						$this->strTitle = substr($this->strTitle, 1);
					}
				}
				
				// common definite and indefinite articles
				
				if ( strlen($this->strTitle) > 4 )
				{
					if ( strtolower( substr($this->strTitle, 0, 4) ) == "the " )
					{
						$this->strNonSort .= substr($this->strTitle, 0, 4);
						$this->strTitle = substr($this->strTitle, 4);
					}
					elseif ( strtolower( substr($this->strTitle, 0, 2) ) == "a " )
					{
						$this->strNonSort .= substr($this->strTitle, 0, 2);
						$this->strTitle = substr($this->strTitle, 2);						
					}
					elseif ( strtolower( substr($this->strTitle, 0, 3) ) == "an " )
					{
						$this->strNonSort .= substr($this->strTitle, 0, 3);
						$this->strTitle = substr($this->strTitle, 3);						
					}
				}
				
				### year
				
				if ( $strYear != "" )
				{
					// metalib puts in carots for blank values, just leave it null
					
					if ( $strYear != "^^^^")
					{
						$this->strDate = $this->extractYear($strYear);
					}
				}
				elseif ( $strDate != "" )
				{
					$this->strDate = $this->extractYear($strDate);
				}
				elseif ( $this->extractYear($this->strPublisher ) )
				{
					// off chance that the date is hanging out in the publisher field;
					// might as well strip it out here as well
					
					$this->strDate = $this->extractYear($this->strPublisher);
					$this->strPublisher = str_replace($this->strDate, "", $this->strPublisher);
				}
				elseif ( $this->extractYear($this->strJournal ) )
				{
					// perhaps somewhere in the 773$g but not parsed by Metalib
					
					$this->strDate = $this->extractYear($this->strJournal);
				}
				
				
				#### authors
				
				// most of the metalib external programs (screen-scrapers) return
				// multiple authors as a series of repeating 100 fields; so need
				// to extract primary author, put rest in alt author array; no guarantee
				// that these are actually personal authors, but what can you do?
				
				if  ( count($arrPrimaryAuthor) > 1 )
				{
					$strPrimaryAuthor = $arrPrimaryAuthor[0];
					
					for ( $x = 1; $x < count($arrPrimaryAuthor); $x++ )
					{
						array_push($arrAltAuthors, $arrPrimaryAuthor[$x] );
					}
				}
				elseif ( count($arrPrimaryAuthor) == 1 )
				{
					$strPrimaryAuthor = $arrPrimaryAuthor[0];
				}
	
				// personal primary author
	
				if ( $strPrimaryAuthor != "" )
				{
					$arrAuthor = $this->splitAuthor($strPrimaryAuthor, "personal");
					array_push( $this->arrAuthors, $arrAuthor );
				}
				elseif ( $arrAltAuthors != null )
				{
					// editor
					
					$arrAuthor = $this->splitAuthor($arrAltAuthors[0], "personal");
					array_push( $this->arrAuthors, $arrAuthor );
					$this->bolEditor = true;
				}
				
				// additional personal authors
				
				if ( $arrAltAuthors != null )
				{
					$x = 0;
					$y = 0;
					
					// if there is an editor it has already been included in the array
					// or if this is ebsco, then there is a duplicate 700 entry, so
					// we need to skip the first author in the list
					
					if ( $this->bolEditor == true || strstr($this->strSource, "EBSCO"))
					{
						$x = 1;
					}
					
					foreach ( $arrAltAuthors as $strAuthor )
					{
						if ( $y >= $x )
						{
							$arrAuthor = $this->splitAuthor($strAuthor, "personal");
							array_push( $this->arrAuthors, $arrAuthor );
						}
						
						$y++;
					}
				}
				
				// corporate author
	
				if ( $strCorpName != "" )
				{			
					$arrAuthor = $this->splitAuthor($strCorpName, "corporate");
					array_push( $this->arrAuthors, $arrAuthor );
				}
				
				// additional corporate authors
				
				if ( $arrAddCorp != null )
				{
					foreach ( $arrAddCorp as $strCorp )
					{
						$arrAuthor = $this->splitAuthor($strCorp, "corporate");
						array_push( $this->arrAuthors, $arrAuthor );
					}
				}			
	
				// conference name
	
				if ( $strConfName != "" )
				{
					$arrAuthor = $this->splitAuthor($strConfName, "conference");
					array_push( $this->arrAuthors, $arrAuthor );
				}
				
				// additional conference names
				
				if ( $arrAddConf != null )
				{
					foreach ( $arrAddConf as $strConf )
					{
						$arrAuthor = $this->splitAuthor($strConf, "conference");
						array_push( $this->arrAuthors, $arrAuthor );
					}
				}
				
				### punctuation clean-up
				
				$this->strBookTitle = $this->stripEndPunctuation($this->strBookTitle, "./;,:" );
				$this->strTitle = $this->stripEndPunctuation($this->strTitle, "./;,:" );
				$this->strSubTitle = $this->stripEndPunctuation($this->strSubTitle, "./;,:" );
				$this->strShortTitle = $this->stripEndPunctuation($this->strShortTitle, "./;,:" );
				$this->strJournalTitle = $this->stripEndPunctuation($this->strJournalTitle, "./;,:" );
				$this->strSeriesTitle = $this->stripEndPunctuation($this->strSeriesTitle, "./;,:" );
				$this->strTechnology = $this->stripEndPunctuation($this->strTechnology, "./;,:" );
				
				$this->strPlace = $this->stripEndPunctuation($this->strPlace, "./;,:" );
				$this->strPublisher = $this->stripEndPunctuation($this->strPublisher, "./;,:" );
				$this->strEdition = $this->stripEndPunctuation($this->strEdition, "./;,:" );
				
				for ( $x = 0; $x < count($this->arrAuthors); $x++ )
				{
					foreach ( $this->arrAuthors[$x] as $key => $value )
					{
						$this->arrAuthors[$x][$key] = $this->stripEndPunctuation($value, "./;,:" );
					}
				}

				for ( $s = 0; $s < count($this->arrSubjects); $s++ )
				{
					$this->arrSubjects[$s] = $this->stripEndPunctuation($this->arrSubjects[$s], "./;,:" );
				}
			}
		}

    /**
		 * Take a Metalib-style template for a URL, including $100_a style
     * placeholders, and replace placeholders with actual values
     * taken from $this->marcXML
		 *
		 * @param string $template
		 * @return string url
		 */
    protected function resolveUrlTemplate($template) {
      # For some reason Metalib uses $0100 placeholder to correspond
      # to an SID field. If I understand how this works, this is nothing
      # but a synonym for $SID_c, so we'll use that. Absolutely no idea
      # why Metalib uses $0100 as syntactic sugar instead. 
      $template = str_replace('$0100', '$SID_c', $template);
      
      $filled_out = preg_replace_callback('/\$(...)(_(.))?/', array($this, 'lookupTemplateValue'), $template);
      
      // Make sure it doesn't have our special value indicating a placeholder
      // could not be resolved. 
      if ( strpos($filled_out, self::$TemplateEmptyValue)) {
        // Consistent with Metalib behavior, if a placeholder can't be resolved,
        // there is no link generated. 
        return null;
      }
      
      return $filled_out;
      
    }
    
    /* This function is just used as a callback in resolveUrlTemplate. 
       Takes a $matches array returned  by PHP regexp function that
       has a MARC field in $matches[1] and a subfield in $matches[3]. 
       Returns the value from $this->marcXML */
    protected function lookupTemplateValue($matches) {
      $field = $matches[1];
      $subfield = (count($matches) >= 4) ? $matches[3] : null;
      
      $value = null;
      if ( $subfield ) {
        $value = $this->extractMarcDataField($this->objXPath, $field, $subfield);
      }
      else {
        //assume it's a control field, those are the only ones without subfields
        $value = $this->extractMarcControlField($this->objXPath, $field);
      }
      if ( empty($value) && true ) {
        // Couldn't resolve the placeholder, that means we should NOT
        // generate a URL, in this mode. Sadly we can't just throw
        // an exception, PHP eats it before we get it. I hate PHP. 
        // Put a special token in there. 
        return self::$TemplateEmptyValue;
      }
     //URL escape it please
     $value = urlencode($value);
     
     return $value;
    }
    /* Fills out an array of Xerxes_Record to include links that are created
       by Metalib link templates (type 'holdings', 'original_record'). 
       
      @param $records, an array of Xerxes_Record 
      @param &$database_links_dom a DOMDocument containing a <database_links> section with Xerxes db information. Note that this is an optional parameter, if not given it will be calculated internally. If a variable with a null value is passed in, the variable will actually be SET to a valid DOMDocument on the way out (magic of pass by reference), so you can
      use this method to calculate a <database_links> section for you. */
    public static function completeUrlTemplates($records, $objRequest, $objRegistry, &$database_links_dom = null) {
      // If we weren't passed in a cached DOMDocument with a database_links
      // section, create one. Note that the var was passed by reference,
      // so this is available to the caller.   
            
      if ( $database_links_dom == null) {
        $metalib_ids = array();
                  
        foreach($records as $r) { 
          

          
          array_push($metalib_ids, $r->getMetalibID()); 
        }        
        
        $objData = new Xerxes_DataMap();
        $databases = $objData->getDatabases($metalib_ids);
                 
        $database_links_dom = new DOMDocument( );
        $database_links_dom->loadXML( "<database_links/>" );
        
        foreach($databases as $db ) {
          $objNodeDatabase = Xerxes_Helper::databaseToLinksNodeset($db, $objRequest, $objRegistry);
      
          $objNodeDatabase = $database_links_dom->importNode($objNodeDatabase, true);
          $database_links_dom->documentElement->appendChild($objNodeDatabase);    
        }                
      }
      
      // Pick out the templates into a convenient structure
      $linkTemplates = self::getLinkTemplates($database_links_dom);
      
      ### Add link to native record and to external holdings URL too, if
      # available from metalib template. 
      foreach($records as $r ) {
        if ($r->getMetalibID() && array_key_exists($r->getMetalibID(), $linkTemplates)) { 
          
          $arrTemplates = $linkTemplates[ $r->getMetalibID() ];
          
          foreach( $arrTemplates as $type => $template ) {
            $filled_in_link = $r->resolveUrlTemplate($template);
            if (! empty($filled_in_link)) {
            array_push($r->arrLinks, array(null, $filled_in_link, $type));
            }
          }
        }
      }
    }
        
  /* Creates a hash data structure of metalib-style URL templates for a given
     set of databases. Extracts this from Xerxes XML including a
     <database_links> section. Extracts into a hash for more convenient
     and quicker use.  Structure of hash is:
     { metalib_id1 => { "xerxes_link_type_a" => template,
                        "xerxes_link_type_b" => template }
       metalib_id2 => [...]
       
       Input is an XML DOMDocument containing a Xerxes <database_links>
       structure. 
  */
  protected function getLinkTemplates($xml) {
    

    
    $link_templates = array();
    $dbXPath = new DOMXPath($xml);
    $objDbXml = $dbXPath->evaluate('//database_links/database');
    
    for ( $i = 0; $i < $objDbXml->length ; $i ++) {
      $dbXml = $objDbXml->item($i);
      $metalib_id = $dbXml->getAttribute("metalib_id");
      $link_templates[$metalib_id] = array();
      
      for ( $j = 0; $j < $dbXml->childNodes->length ; $j++) {
        $node = $dbXml->childNodes->item($j);
        if ($node->tagName == 'link_native_record' ) {
          $link_templates[$metalib_id]["original_record"] = $node->textContent; 
        }
        if ($node->tagName == 'link_native_holdings') {
          $link_templates[$metalib_id]["holdings"] = $node->textContent;
        }
      }
    }

    
    return $link_templates;
  }


    
		/**
		 * Get an OpenURL 1.0 formatted URL
		 *
		 * @param string $strResolver	base url of the link resolver
		 * @param string $strReferer	referrer (unique identifier)
		 * @return string
		 */
		
		public function getOpenURL($strResolver, $strReferer = null)
		{
			$arrReferant = array();		// referrant values, minus author
			$strBaseUrl = "";			// base url of openurl request
			$strKev = "";				// key encoded values
			
			// set base url and referrer with database name
			
			$strKev = "url_ver=Z39.88-2004";

			if ( $strResolver != "" ) $strBaseUrl = $strResolver . "?";
			if ( $strReferer != "" ) $strKev .= "&rfr_id=info:sid/" .  urlencode($strReferer);
			if ( $this->strDatabaseName != "" )  $strKev .= urlencode(" ( " . $this->strDatabaseName . ")");
			
      
      // add rft_id's
      $arrReferentId = $this->referentIdentifierArray();
      foreach ($arrReferentId as $id) {
        $strKev .= "&rft_id=" . urlencode($id); 
      }
      
			// add simple referrant values
			
			$arrReferant = $this->referentArray();      
			foreach ($arrReferant as $key => $value )
			{
				if ($value != "")
				{
					$strKev .= "&" . $key . "=" . urlencode($value);
				}
			}
			
			// add primary author
			
			if ( count($this->arrAuthors) > 0 )
			{
				if ( $this->arrAuthors[0]["type"] == "personal")
				{
					if ( array_key_exists("last",$this->arrAuthors[0]) )
					{
						$strKev .= "&rft.aulast=" . urlencode($this->arrAuthors[0]["last"]);
						
						if ( $this->bolEditor == true )
						{
							$strKev .= urlencode(", ed.");
						}
					}
					if ( array_key_exists("first",$this->arrAuthors[0]) )
					{
						$strKev .= "&rft.aufirst=" . urlencode($this->arrAuthors[0]["first"]);
					}
					if ( array_key_exists("init",$this->arrAuthors[0]) )
					{
						$strKev .= "&rft.auinit=" . urlencode($this->arrAuthors[0]["init"]);
					}
				}
				else
				{
					$strKev .= "&rft.aucorp=" . urlencode($this->arrAuthors[0]["name"]);
				}
			}
			
			return $strBaseUrl . $strKev;
			
		}
		
		/**
		 * Convert record to OpenURL 1.0 formatted XML Context Object
     * jrochkind 6 April 09.: I don't believe this creates a legal XML doc, I
     * don't think it uses namespaces properly.
		 *
		 * @return DOMDocument
		 */
		
		public function getContextObject()
		{	
			$arrReferant = $this->referentArray();
      $arrReferantIds = $this->referentIdentifierArray();
			
			$objXml = new DOMDocument();
			$objXml->loadXML("<context-objects />");
			
			$objContextObject = $objXml->createElement("context-object");
			$objContextObject->setAttribute("version", "Z39.88-2004");
			$objContextObject->setAttribute("timestamp", date("c") );
			
			$objReferrent = $objXml->createElement("referent");
			$objMetadataByVal = $objXml->createElement("metadata-by-val");
			$objMetadata = $objXml->createElement("metadata");
			$objAuthors = $objXml->createElement("authors");
			
			// set data container
			
			if ( $arrReferant["rft.genre"] == "book" || 
				 $arrReferant["rft.genre"] =="bookitem" ||
				 $arrReferant["rft.genre"] == "report" )
			{
				$objItem = $objXml->createElement("book");
			}
			elseif ($arrReferant["rft.genre"] == "dissertation")
			{
				$objItem = $objXml->createElement("dissertation");
			}
			else
			{
				$objItem = $objXml->createElement("journal");
			}
			
			// add authors
			
			$x = 1;

			foreach ( $this->arrAuthors as $arrAuthor )
			{
				$objAuthor =  $objXml->createElement("author");
				
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
				
				if ( $x == 1 && $this->bolEditor == true )
				{
					$objAuthor->setAttribute("editor", "true");
				}
				
				$objAuthors->appendChild($objAuthor);
				
				$x++;
				
			}
			
			$objItem->appendChild($objAuthors);
			
      // add rft_id's. 
      
      foreach($arrReferantIds as $id) {
        # rft_id goes in the <referent> element directly, as a <ctx:identifier>
        $objNode = $objXml->createElement( "identifier", $this->escapeXml($id) );
        $objReferent->appendChild($objNode);
      }
			
			// add simple referrant values
			
			foreach ($arrReferant as $key => $value )
			{
				if ( is_array($value) ) 
				{
					if ( count ($value) > 0 )
					{
						foreach ( $value as $element )
						{
							$objNode = $objXml->createElement( $key, $this->escapeXml($element) );
							$objItem->appendChild($objNode);
						}
					}
				}
				elseif ( $value != "")
				{
					$objNode = $objXml->createElement( $key, $this->escapeXml($value) );
					$objItem->appendChild($objNode);
				}
			}
			
			
			$objMetadata->appendChild($objItem);
			$objMetadataByVal->appendChild($objMetadata);
			$objReferrent->appendChild($objMetadataByVal);
			$objContextObject->appendChild($objReferrent);
			$objXml->documentElement->appendChild($objContextObject);
			
			return $objXml;
		}
		
		/**
		 * Convert record to Xerxes_Record XML object
		 *
		 * @param string $strCustomFields		xerxes query syntax for marc fields 'field_name = 945|a'; 'field_name = 900|*';
		 * @return DOMDocument
		 */
		
		public function toXML()
		{
			$objXml = new DOMDocument();
			$objXml->loadXML("<xerxes_record />");
			
			$objRecord = $objXml->documentElement;
			
			$strTitle = $this->getTitle(true);
			$strPrimaryAuthor = $this->getPrimaryAuthor(true);
			$arrIssn = $this->getAllISSN();
			$arrIsbn = $this->getAllISBN();
			$arrSubjects = $this->getSubjects();
			$arrNotes = $this->getNotes();
			$arrEmbeddedText = $this->getEmbeddedText();
			
			// simple elements
			
			if ($this->hasFullText() != null ) $objRecord->appendChild($objXml->createElement("full_text_bool", $this->escapeXml($this->hasFullText())));
			if ($strPrimaryAuthor != null ) $objRecord->appendChild($objXml->createElement("primary_author", $this->escapeXml($strPrimaryAuthor)));
			if ($strTitle != null ) $objRecord->appendChild($objXml->createElement("title_normalized", $this->escapeXML($strTitle)));
			if ($this->getMetalibID() != null ) $objRecord->appendChild($objXml->createElement("metalib_id", $this->escapeXML($this->getMetalibID()))); 
			if ($this->getResultSet() != null ) $objRecord->appendChild($objXml->createElement("result_set", $this->escapeXML($this->getResultSet()))); 
			if ($this->getRecordNumber() != null ) $objRecord->appendChild($objXml->createElement("record_number", $this->escapeXML($this->getRecordNumber()))); 
			if ($this->getTechnology()!= null ) $objRecord->appendChild($objXml->createElement("technology", $this->escapeXML($this->getTechnology())));
			if ($this->getNonSort() != null ) $objRecord->appendChild($objXml->createElement("non_sort", $this->escapeXML($this->getNonSort())));
			if ($this->getMainTitle() != null ) $objRecord->appendChild($objXml->createElement("title", $this->escapeXML($this->getMainTitle()))); 
			if ($this->getSubTitle() != null ) $objRecord->appendChild($objXml->createElement("sub_title", $this->escapeXML($this->getSubTitle())));
			if ($this->getSeriesTitle() != null ) $objRecord->appendChild($objXml->createElement("series_title", $this->escapeXML($this->getSeriesTitle())));
			if ($this->getAbstract() != null ) $objRecord->appendChild($objXml->createElement("abstract", $this->escapeXML($this->getAbstract()))); 
			if ($this->getSummary() != null ) $objRecord->appendChild($objXml->createElement("summary", $this->escapeXML($this->getSummary()))); 
			if ($this->getDescription() != null ) $objRecord->appendChild($objXml->createElement("description", $this->escapeXML($this->getDescription())));
			if ($this->getLanguage() != null ) $objRecord->appendChild($objXml->createElement("language", $this->escapeXML($this->getLanguage()))); 
			if ($this->getPlace() != null ) $objRecord->appendChild($objXml->createElement("place", $this->escapeXML($this->getPlace()))); 
			if ($this->getPublisher() != null ) $objRecord->appendChild($objXml->createElement("publisher", $this->escapeXML($this->getPublisher()))); 
			if ($this->getYear() != null ) $objRecord->appendChild($objXml->createElement("year", $this->escapeXML($this->getYear()))); 
			if ($this->getJournal() != null ) $objRecord->appendChild($objXml->createElement("journal", $this->escapeXML($this->getJournal()))); 
			if ($this->getJournalTitle() != null ) $objRecord->appendChild($objXml->createElement("journal_title", $this->escapeXML($this->getJournalTitle(true)))); 
			if ($this->getBookTitle() != null ) $objRecord->appendChild($objXml->createElement("book_title", $this->escapeXML($this->getBookTitle(true)))); 
			if ($this->getVolume() != null ) $objRecord->appendChild($objXml->createElement("volume", $this->escapeXML($this->getVolume()))); 
			if ($this->getIssue() != null ) $objRecord->appendChild($objXml->createElement("issue", $this->escapeXML($this->getIssue()))); 
			if ($this->getStartPage() != null ) $objRecord->appendChild($objXml->createElement("start_page", $this->escapeXML($this->getStartPage()))); 
			if ($this->getEndPage() != null ) $objRecord->appendChild($objXml->createElement("end_page", $this->escapeXML($this->getEndPage()))); 
			if ($this->getExtent()!= null ) $objRecord->appendChild($objXml->createElement("extent", $this->escapeXML($this->getExtent())));
			if ($this->getInstitution() != null ) $objRecord->appendChild($objXml->createElement("institution", $this->escapeXML($this->getInstitution()))); 
			if ($this->getDegree() != null ) $objRecord->appendChild($objXml->createElement("degree", $this->escapeXML($this->getDegree()))); 
			if ($this->getDatabaseName() != null ) $objRecord->appendChild($objXml->createElement("database_name", $this->escapeXML($this->getDatabaseName())));
			if ($this->getEdition() != null ) $objRecord->appendChild($objXml->createElement("edition", $this->escapeXML($this->getEdition()))); 
			if ($this->getCallNumber() != null ) $objRecord->appendChild($objXml->createElement("call_number", $this->escapeXML($this->getCallNumber())));
			if ($this->getPrice() != null ) $objRecord->appendChild($objXml->createElement("price", $this->escapeXML($this->getPrice())));
			if ($this->getControlNumber()!= null ) $objRecord->appendChild($objXml->createElement("control_number", $this->escapeXML($this->getControlNumber())));	

			$strFormat = $this->getFormat();
			
			if ( $strFormat != null && ! stristr($strFormat,"unknown") )
			{
				$objRecord->appendChild($objXml->createElement("format", $this->escapeXML($strFormat))); 
			}
			
			// embedded text, seperated into paragraphs

			if ( count( $arrEmbeddedText ) > 0 )
			{
				$objEmbeddedText = $objXml->createElement( "embeddedText" );
				foreach ( $arrEmbeddedText as $paragraph )
				{
					$objParagraph = $objXml->createelement( "paragraph", Xerxes_Parser::escapeXml( trim( $paragraph ) ) );
					$objEmbeddedText->appendChild( $objParagraph );
				}
				$objRecord->appendChild( $objEmbeddedText );
			}
      
			// table of contents
			
			if ($this->getTOC() != null )
			{
				$objTOC = $objXml->createElement("toc");
				
				$arrChapterTitles = explode("--",$this->getTOC());
				
				foreach ( $arrChapterTitles as $strTitleStatement )
				{
					$objChapter = $objXml->createElement("chapter");
					
					if ( strpos($strTitleStatement, "/") !== false )
					{
						$arrChapterTitleAuth = explode("/", $strTitleStatement);
						
						$objChapterTitle = $objXml->createElement("title",  Xerxes_Parser::escapeXml(trim($arrChapterTitleAuth[0])));
						$objChapterAuthor = $objXml->createElement("author",  Xerxes_Parser::escapeXml(trim($arrChapterTitleAuth[1])));
						
						$objChapter->appendChild($objChapterTitle);
						$objChapter->appendChild($objChapterAuthor);
					}
					else 
					{
						$objStatement = $objXml->createElement("statement", Xerxes_Parser::escapeXml(trim($strTitleStatement)));
						$objChapter->appendChild($objStatement);
					}
					
					$objTOC->appendChild($objChapter);
				}
				$objRecord->appendChild($objTOC);
			}
			
			// standard numbers
			
			if ( count($arrIssn) > 0 || count($arrIsbn) > 0 || $this->strGovDoc != "" || $this->strGPO != "" || $this->strOCLC != "")
			{
				$objStandard = $objXml->createElement("standard_numbers");
				
				if ( count($arrIssn) > 0 )
				{
					foreach ( $arrIssn as $strIssn )
					{
						$objIssn = $objXml->createElement("issn", $this->escapeXml($strIssn));
						$objStandard->appendChild($objIssn);
					}
				}
				
				if ( count($arrIsbn) > 0 )
				{
					foreach ( $arrIsbn as $strIsbn )
					{
						$objIssn = $objXml->createElement("isbn", $this->escapeXml($strIsbn));
						$objStandard->appendChild($objIssn);
					}
				}
				
				if ( $this->strGovDoc != "" )
				{
					$objGovDoc = $objXml->createElement("gpo", $this->escapeXml($this->strGovDoc));
					$objStandard->appendChild($objGovDoc);
				}
				
				if ( $this->strGPO != "" )
				{
					$objGPO = $objXml->createElement("govdoc", $this->escapeXml($this->strGPO));
					$objStandard->appendChild($objGPO);
				}
				
				if ( $this->strOCLC != "" )
				{
					$objOCLC = $objXml->createElement("oclc", $this->escapeXml($this->strOCLC));
					$objStandard->appendChild($objOCLC);					
				}
				
				
				$objRecord->appendChild($objStandard);
			}
			
			// authors
			
			if ( count($this->arrAuthors) > 0 )
			{
				
				$objAuthors = $objXml->createElement("authors");
				$x = 1;
				
				foreach ( $this->arrAuthors as $arrAuthor )
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
					
					if ( $x == 1 && $this->bolEditor == true )
					{
						$objAuthor->setAttribute("editor", "true");
					}
					
					$objAuthors->appendChild($objAuthor);
					
					$x++;
				}
				
				$objRecord->appendChild($objAuthors);
			}
			
			// subjects
			
			if ( count($arrSubjects) > 0 )
			{
				$objSubjects = $objXml->createElement("subjects");
				
				foreach ( $arrSubjects as $strSubject )
				{
					$objSubject = $objXml->createElement("subject", $this->escapeXml($strSubject));
					$objSubjects->appendChild($objSubject);
				}
				
				$objRecord->appendChild($objSubjects);
			}
			
			// notes
			
			if ( count($arrNotes) > 0 )
			{
				$objNotes = $objXml->createElement("notes");
				
				foreach ( $this->arrNotes as $strNote )
				{
					$objNote = $objXml->createElement("note", $this->escapeXml($strNote));
					$objNotes->appendChild($objNote);
				}
				
				$objRecord->appendChild($objNotes);
			}
					
			// links
			
			if ( $this->arrLinks != null )
			{
				$objLinks = $objXml->createElement("links");
			
				foreach ( $this->arrLinks as $arrLink )
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
				
				$objRecord->appendChild($objLinks);
			}
			
			// information about the item in an alternate character-script
			
			if ( $this->arrAltScript != null )
			{
				$objScript = $objXml->createElement("alternate_script");
				$objScript->setAttribute("name", $this->strAltScript);
				$objRecord->appendChild($objScript);
				
				foreach ($this->arrAltScript as $strScriptInfo )
				{
					$objElement = $objXml->createElement("info", $strScriptInfo);
					$objScript->appendChild($objElement);
				}
			}
			
			return $objXml;
		}
		
		
		### PRIVATE FUNCTIONS ###		
		
		
    
    
		/**
		 * Returns the object's properties that correspond to the OpenURL standard
		 * as an easy to use associative array. Does not include rft_id, see
     * referentIdentifierArray. 
		 *
		 * @return array
		 */
		
		private function referentArray()
		{
			$arrReferant = array();
      # There can be multiple rft_ids, treat them special. 
			$strTitle = "";
			
				
			### simple values
			
			$arrReferant["rft.genre"] = $this->convertGenreOpenURL($this->strFormat);
			
			if ( count($this->arrIsbn) > 0 ) $arrReferant["rft.isbn"] = $this->arrIsbn[0];
			if ( count($this->arrIssn) > 0 ) $arrReferant["rft.issn"] = $this->arrIssn[0];
			
			// rft.ed_number not an actual openurl 1.0 standard element, 
			// but sfx recognizes it. But only add if the eric type
			// is ED, adding an EJ or other as an ED just confuses SFX. 
			
			if ( $this->strEric )
			{
				$strEricType = substr( $this->strEric, 0, 2 );
				
				if ( $strEricType == "ED" )
				{
					$arrReferant["rft.ed_number"] = $this->strEric;
				}
			}
			
			$arrReferant["rft.series"] = $this->strSeriesTitle;
			$arrReferant["rft.place"] = $this->strPlace;
			$arrReferant["rft.pub"] = $this->strPublisher;
			$arrReferant["rft.date"] = $this->strDate;
			$arrReferant["rft.edition"] = $this->strEdition;
			$arrReferant["rft.tpages"] = $this->strTPages;
			$arrReferant["rft.jtitle"] = $this->strJournalTitle;
			$arrReferant["rft.stitle"] = $this->strShortTitle;
			$arrReferant["rft.volume"] = $this->strVolume;
			$arrReferant["rft.issue"] = $this->strIssue;
			$arrReferant["rft.spage"] = $this->strStartPage;
			$arrReferant["rft.epage"] = $this->strEndPage;
			$arrReferant["rft.degree"] = $this->strDegree;
			$arrReferant["rft.inst"] = $this->strInstitution;
			
			### title
			
			if ( $this->strNonSort != "" ) $strTitle = $this->strNonSort . " ";
			if ( $this->strTitle != "" ) $strTitle .= $this->strTitle . " ";
			if ( $this->strSubTitle != "" ) $strTitle .= ": " . $this->strSubTitle . " ";
			
			// map title to appropriate element based on genre
			
			if ( $arrReferant["rft.genre"] == "book" ||
				 $arrReferant["rft.genre"] == "conference" ||
				 $arrReferant["rft.genre"] == "proceeding" ||
				 $arrReferant["rft.genre"] == "report")
			{
				$arrReferant["rft.btitle"] = $strTitle;
			}
			elseif ( $arrReferant["rft.genre"] == "bookitem")
			{
				$arrReferant["rft.atitle"] = $strTitle;
				$arrReferant["rft.btitle"] = $this->strBookTitle;
			}
			elseif ( $arrReferant["rft.genre"] == "dissertation")
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
     private function referentIdentifierArray() {
        $results = array();
       
        if ( $this->strOCLC != "" ) array_push($results, "info:oclcnum/" . $this->strOCLC);

        # sudoc, using rsinger's convention, http://dilettantes.code4lib.org/2009/03/a-uri-scheme-for-sudocs/
        if ( $this->strGovDoc != "") array_push($results, "http://purl.org/NET/sudoc/" . urlencode($this->strGovDoc));
          
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
			switch ($strFormat)
			{
				case "Journal or Newspaper":
					
					return "journal";
					break;

				case "Issue":
					
					return "issue";
					break;

				case "Book Review":
				case "Article":
					
					return "article";
					break;

				case "Conference Proceeding":

					// take this over 'conference' ?
					return "proceeding";
					break;

				case "Preprint":
					
					return "preprint";
					break;
					
				case "Book":
					
					return "book";
					break;
				
				case "Book Chapter":	

					return "bookitem";
					break;
					
				case "Report":

					return "report";
					break;
					
				case "Dissertation":
				case "Thesis":
					
					// not an actual openurl genre
					return "dissertation";
					break;
				
				default:
					
					// take this over 'document'?
					return "unknown";
			}
		}
		
		/**
		 * Determines the format/genre of the item, broken out here for convenience
		 *
		 * @param string $strSource			database source id
		 * @param string $strTitle			item's title
		 * @param string $strJournal		journal title
		 * @param string $arrFormat			format fields		
		 * @param string $strLeader
		 * @param string $strEric
		 * @param string $arrIsbn
		 * @param array $arrIssn
		 * @return string					internal xerxes format designation
		 */
		
		private function parseFormat( $strSource, $strTitle, $strJournal, $arrFormat , $strLeader, $strEric, $strThesis, $arrIsbn, $arrIssn )
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
				$strDataFields .= " " . strtolower($strFormat);
			}
			
			if ( strlen($strLeader) >= 8 )
			{
				$chrLeader6 = substr($strLeader,6,1);
				$chrLeader7 = substr($strLeader,7,1);
				$chrLeader8 = substr($strLeader,8,1);
			}
			
			// ebsco supplies a hardcoded leader that says the item
			// is a monographic component part, even though it is usually not,
			// so blank it here to avoid false type
			
			if ( strstr($strSource, "EBSCO") )
			{
				$chrLeader7 = null;
			}
			
			// database specific code
			
			if ( strstr($strSource,'ERIC') && strstr($strEric,'ED') && ! stristr($strTitle, "proceeding") ) $strReturn = "Report";
			elseif ( strstr($strSource,'ERIC') && ! strstr($strEric,'ED') )$strReturn = "Article";
			elseif ( strstr($strSource,'OCLC_PAPERS') ) $strReturn = "Conference Paper";
			elseif ( strstr($strSource,'PCF1') ) $strReturn = "Conference Proceeding";
			
			// format made explicit
			
			elseif ( strstr($strDataFields,'dissertation') ) $strReturn = "Dissertation";
			elseif ( $strThesis != "" ) $strReturn = "Thesis";
			elseif ( strstr($strDataFields,'proceeding') ) $strReturn = "Conference Proceeding";
			elseif ( strstr($strDataFields,'conference') ) $strReturn = "Conference Paper";
			elseif ( strstr($strDataFields,'hearing') ) $strReturn = "Hearing";
			elseif ( strstr($strDataFields,'working') ) $strReturn = "Working Paper";
			elseif ( strstr($strDataFields,'book review') || strstr($strDataFields,'review-book') ) $strReturn = "Book Review";
			elseif ( strstr($strDataFields,'book art') || strstr($strDataFields,'book ch') || strstr($strDataFields,'chapter') ) $strReturn = "Book Chapter"; 
			elseif ( strstr($strDataFields,'journal') ) $strReturn = "Article";
			elseif ( strstr($strDataFields,'periodical') || strstr($strDataFields,'serial') ) $strReturn = "Article";
			elseif ( strstr($strDataFields,'book') ) $strReturn = "Book";
			elseif ( strstr($strDataFields,'article') ) $strReturn  ="Article";
			
			// format from other sources
			
			elseif ( $strJournal != "" ) $strReturn = "Article";
			elseif ( $chrLeader6 =='a' && $chrLeader7 =='a' ) $strReturn = "Book Chapter";
			elseif ( $chrLeader6 =='a' && $chrLeader7 =='m' ) $strReturn = "Book";
			elseif ( $chrLeader8 =='a' ) $strReturn = "Archive";
				 
			elseif ( $chrLeader6 =='e' || $chrLeader6 =='f' ) $strReturn = "Map";
			elseif ( $chrLeader6 =='c' || $chrLeader6 =='d' ) $strReturn = "Printed Music";
			elseif ( $chrLeader6 =='i' || $chrLeader6 =='j' ) $strReturn = "Sound Recording";
			elseif ( $chrLeader6 =='k' ) $strReturn = "Photograph or Slide";
			elseif ( $chrLeader6 =='g' ) $strReturn = "Video";
			elseif ( $chrLeader6 =='m' && $chrLeader7 =='i' ) $strReturn = "Website";
			elseif ( $chrLeader6 =='m') $strReturn = "Computer File";

			elseif ( $chrLeader6 =='a' && $chrLeader7 =='b' ) $strReturn = "Article";
			elseif ( $chrLeader6 =='a' && $chrLeader7 =='s' ) $strReturn = "Journal or Newspaper";
			elseif ( $chrLeader6 =='a' && $chrLeader7 =='i' ) $strReturn = "Website";
				
			elseif ( count($arrIsbn) > 0 ) $strReturn = "Book";
			elseif ( count($arrIssn) > 0 ) $strReturn = "Article";

			
			return $strReturn;
		}
		
		private function parseJournalData( $strJournalInfo )
		{			
			$arrFinal = array();
			$arrCapture = array();
			
			// we'll drop the whole thing to lower case and padd it
			// with spaces to make parsing easier
			
			$strJournalInfo = " " . strtolower($strJournalInfo) . " ";
			
			// volume
			
			if ( preg_match("/ v[a-z]{0,5}[\.]{0,1}[ ]{0,3}([0-9]{1,})/", $strJournalInfo, $arrCapture) != 0 )
			{
				$arrFinal["volume"] = $arrCapture[1];
				$strJournalInfo = str_replace($arrCapture[0], "", $strJournalInfo);
			}
			
			// issue
			
			if ( preg_match("/ i[a-z]{0,4}[\.]{0,1}[ ]{0,3}([0-9]{1,})/", $strJournalInfo, $arrCapture) != 0 )
			{
				$arrFinal["issue"] = $arrCapture[1];
				$strJournalInfo = str_replace($arrCapture[0], "", $strJournalInfo);
			}
			elseif ( preg_match("/ n[a-z]{0,5}[\.]{0,1}[ ]{0,3}([0-9]{1,})/", $strJournalInfo, $arrCapture) != 0 )
			{
				$arrFinal["issue"] = $arrCapture[1];
				$strJournalInfo = str_replace($arrCapture[0], "", $strJournalInfo);
			}
			
			// pages
						
			if ( preg_match("/([0-9]{1,})-([0-9]{1,})/", $strJournalInfo, $arrCapture) != 0 )
			{
				$arrFinal["spage"] = $arrCapture[1];
				$arrFinal["epage"] = $arrCapture[2];
				
				$strJournalInfo = str_replace($arrCapture[0], "", $strJournalInfo);
			}
			elseif ( preg_match("/ p[a-z]{0,3}[\.]{0,1}[ ]{0,3}([0-9]{1,})/", $strJournalInfo, $arrCapture) != 0 )
			{
				$arrFinal["spage"] = $arrCapture[1];
				$strJournalInfo = str_replace($arrCapture[0], "", $strJournalInfo);
			}
			
			return $arrFinal;
		}
		
		private function splitAuthor($strAuthor, $strType)
		{
			$arrReturn = array();
			$arrReturn["type"] = $strType;
			
			$iComma = strpos($strAuthor,",");
			$iLastSpace = strripos($strAuthor, " ");
			
			// for personal authors:
			
			// if there is a comma, we will assume the names are in 'last, first' order
			// otherwise in 'first last' order -- the second one here obviously being
			// something of a guess, assuming the person has a single word for last name
			// rather than 'van der Kamp', but better than the alternative?
			
			if ( $strType == "personal")
			{
				$arrMatch = array();
				$strLast = "";
				$strFirst = "";
				
				if ( $iComma !== false )
				{
					$strLast = trim(substr($strAuthor, 0, $iComma));
					$strFirst = trim(substr($strAuthor, $iComma + 1));
				}
				
				// some databases like CINAHL put names as 'last first' but first 
				// is just initials 'Walker DS' so we can catch this scenario?
				
				elseif ( preg_match("/ ([A-Z]{1,3})$/", $strAuthor, $arrMatch) != 0 )
				{
					
					$strFirst = $arrMatch[1];
					$strLast = str_replace($arrMatch[0], "", $strAuthor);
				}
				else
				{
					$strLast = trim(substr($strAuthor, $iLastSpace));
					$strFirst = trim(substr($strAuthor, 0, $iLastSpace));
				}
				
				if ( preg_match("/ ([a-zA-Z]{1})\.$/", $strFirst, $arrMatch) != 0 )
				{
					$arrReturn["init"] = $arrMatch[1];
					$strFirst = str_replace($arrMatch[0], "", $strFirst);
				}
					
				$arrReturn["last"] = $strLast;
				$arrReturn["first"] = $strFirst;
				
			}
			else
			{
				$arrReturn["name"] = trim($strAuthor);
			}
			
			return $arrReturn;
		}
		
		private function stripEndPunctuation($strInput, $strPunct)
		{
			$bolDone = false;
			$arrPunct = str_split($strPunct);
			
			while ( $bolDone == false )
			{
				$iEnd = strlen($strInput) - 1 ;
				
				foreach ( $arrPunct as $strPunct )
				{
					if ( substr($strInput, $iEnd ) == $strPunct )
					{
						$strInput = substr($strInput, 0, $iEnd);
						$strInput = trim($strInput);
					}
				}
				
				$bolDone = true;
				
				foreach ( $arrPunct as $strPunct )
				{
					if ( substr($strInput, $iEnd ) == $strPunct )
					{
						$bolDone = false;
					}
				}				
			}
			
			return $strInput;
		}
		
		private function extractYear( $strYear )
		{
			$arrYear = array();
				
			if ( preg_match("/[0-9]{4}/", $strYear, $arrYear) != 0 )
			{
				return $arrYear[0];
			}
			else
			{
				return  $strYear;
			}
		}
		
		private function extractMarcControlField($objXPath, $field)
		{
			$field = $this->normalizeField($field);
			
			$strReturn = "";
			
			$objNode = $objXPath->query("//marc:controlfield[@tag=$field]")->item(0);
				
			if ( $objNode != null )
			{
				$strReturn = $objNode->nodeValue;
			}
			
			return trim($strReturn);
		}
		
		private function extractMarcDataField($objXPath, $field, $subfields)
		{			
			$strReturn = "";
			$strMarcNS ="http://www.loc.gov/MARC21/slim";
			
			$field = $this->normalizeField($field);
			
			
			
			$arrSubFields = str_split($subfields);
			$objNode = $objXPath->query("//marc:datafield[@tag=$field]")->item(0);
				
			if ( $objNode != null )
			{
				$objSubFields = $objNode->getElementsByTagNameNS($strMarcNS, "subfield");
				
				foreach ( $objSubFields as $objSubField )
				{
					foreach ( $arrSubFields as $strSubField )
					{
						if ( $objSubField->getAttribute("code") == $strSubField || $subfields == "*")
						{
							$strReturn .= $objSubField->nodeValue . " ";
						}
					}
				}
			}
			
			return trim($strReturn);
		}
		
		private function extractMarcArray($objXPath, $field, $subfields, $strQuery = null)
		{	
			$field = $this->normalizeField($field);
			
			$arrReturn = array();
			$strMarcNS ="http://www.loc.gov/MARC21/slim";
			
			if ( $strQuery == "" )
			{
				if ( strstr($field, "-") )
				{
					$arrRange = explode("-", $field);
					$strStart = $arrRange[0];
					$strEnd = $arrRange[1];
					$strQuery = "//marc:datafield[@tag >= $strStart and @tag < $strEnd]";
				}
				else
				{
					$strQuery = "//marc:datafield[@tag=$field]";
				}
			}

			$arrSubFields = str_split($subfields);
			
			$objNodeList = $objXPath->query($strQuery);
				
			if ( $objNodeList != null )
			{
				foreach ( $objNodeList as $objNode )
				{
					$objSubFields = $objNode->getElementsByTagNameNS($strMarcNS, "subfield");
					$strSubFieldData = "";
					
					foreach ( $objSubFields as $objSubField )
					{						
						foreach ( $arrSubFields as $strSubField )
						{
							if ( $objSubField->getAttribute("code") == $strSubField || $subfields == "*")
							{
								$strSubFieldData .= $objSubField->nodeValue . " ";
							}
						}
					}

					array_push($arrReturn, trim($strSubFieldData));
				}
			}
			
			return $arrReturn;
		}
		
		private function normalizeField($field)
		{
			// numbers need to be padded out with 0s
			// and letters need to be quoted, but if its
			// a range than we'll treat it seperately

			if ( is_int($field) )
			{
				$field = str_pad($field, 3, "0", STR_PAD_LEFT);
			}
			elseif ( ! strstr($field, "-") )
			{
				$field = "'" . $field . "'";
			}

			return $field;		
		}
		
		private function convertLanguageCode($strCode, $bolTwo = false)
		{			
			if ( $bolTwo == true )
			{
				switch ( strtoupper($strCode) )
				{
					case "AA": return "Afar"; break;
					case "AB": return "Abkhazian"; break;
					case "AF": return "Afrikaans"; break;
					case "AM": return "Amharic"; break;
					case "AR": return "Arabic"; break;
					case "AS": return "Assamese"; break;
					case "AY": return "Aymara"; break;
					case "AZ": return "Azerbaijani"; break;
					case "BA": return "Bashkir"; break;
					case "BE": return "Byelorussian"; break;
					case "BG": return "Bulgarian"; break;
					case "BH": return "Bihari"; break;
					case "BI": return "Bislama"; break;
					case "BN": return "Bengali"; break;
					case "BO": return "Tibetan"; break;
					case "BR": return "Breton"; break;
					case "CA": return "Catalan"; break;
					case "CO": return "Corsican"; break;
					case "CS": return "Czech"; break;
					case "CY": return "Welsh"; break;
					case "DA": return "Danish"; break;
					case "DE": return "German"; break;
					case "DZ": return "Bhutani"; break;
					case "EL": return "Greek"; break;
					case "EN": return "English"; break;
					case "EO": return "Esperanto"; break;
					case "ES": return "Spanish"; break;
					case "ET": return "Estonian"; break;
					case "EU": return "Basque"; break;
					case "FA": return "Persian"; break;
					case "FI": return "Finnish"; break;
					case "FJ": return "Fiji"; break;
					case "FO": return "Faeroese"; break;
					case "FR": return "French"; break;
					case "FY": return "Frisian"; break;
					case "GA": return "Irish"; break;
					case "GD": return "Gaelic"; break;
					case "GL": return "Galician"; break;
					case "GN": return "Guarani"; break;
					case "GU": return "Gujarati"; break;
					case "HA": return "Hausa"; break;
					case "HI": return "Hindi"; break;
					case "HR": return "Croatian"; break;
					case "HU": return "Hungarian"; break;
					case "HY": return "Armenian"; break;
					case "IA": return "Interlingua"; break;
					case "IE": return "Interlingue"; break;
					case "IK": return "Inupiak"; break;
					case "IN": return "Indonesian"; break;
					case "IS": return "Icelandic"; break;
					case "IT": return "Italian"; break;
					case "IW": return "Hebrew"; break;
					case "JA": return "Japanese"; break;
					case "JI": return "Yiddish"; break;
					case "JW": return "Javanese"; break;
					case "KA": return "Georgian"; break;
					case "KK": return "Kazakh"; break;
					case "KL": return "Greenlandic"; break;
					case "KM": return "Cambodian"; break;
					case "KN": return "Kannada"; break;
					case "KO": return "Korean"; break;
					case "KS": return "Kashmiri"; break;
					case "KU": return "Kurdish"; break;
					case "KY": return "Kirghiz"; break;
					case "LA": return "Latin"; break;
					case "LN": return "Lingala"; break;
					case "LO": return "Laothian"; break;
					case "LT": return "Lithuanian"; break;
					case "LV": return "Latvian"; break;
					case "MG": return "Malagasy"; break;
					case "MI": return "Maori"; break;
					case "MK": return "Macedonian"; break;
					case "ML": return "Malayalam"; break;
					case "MN": return "Mongolian"; break;
					case "MO": return "Moldavian"; break;
					case "MR": return "Marathi"; break;
					case "MS": return "Malay"; break;
					case "MT": return "Maltese"; break;
					case "MY": return "Burmese"; break;
					case "NA": return "Nauru"; break;
					case "NE": return "Nepali"; break;
					case "NL": return "Dutch"; break;
					case "NO": return "Norwegian"; break;
					case "OC": return "Occitan"; break;
					case "OM": return "Oromo"; break;
					case "OR": return "Oriya"; break;
					case "PA": return "Punjabi"; break;
					case "PL": return "Polish"; break;
					case "PS": return "Pashto"; break;
					case "PT": return "Portuguese"; break;
					case "QU": return "Quechua"; break;
					case "RM": return "Rhaeto-Romance"; break;
					case "RN": return "Kirundi"; break;
					case "RO": return "Romanian"; break;
					case "RU": return "Russian"; break;
					case "RW": return "Kinyarwanda"; break;
					case "SA": return "Sanskrit"; break;
					case "SD": return "Sindhi"; break;
					case "SG": return "Sangro"; break;
					case "SH": return "Serbo-Croatian"; break;
					case "SI": return "Singhalese"; break;
					case "SK": return "Slovak"; break;
					case "SL": return "Slovenian"; break;
					case "SM": return "Samoan"; break;
					case "SN": return "Shona"; break;
					case "SO": return "Somali"; break;
					case "SQ": return "Albanian"; break;
					case "SR": return "Serbian"; break;
					case "SS": return "Siswati"; break;
					case "ST": return "Sesotho"; break;
					case "SU": return "Sudanese"; break;
					case "SV": return "Swedish"; break;
					case "SW": return "Swahili"; break;
					case "TA": return "Tamil"; break;
					case "TE": return "Tegulu"; break;
					case "TG": return "Tajik"; break;
					case "TH": return "Thai"; break;
					case "TI": return "Tigrinya"; break;
					case "TK": return "Turkmen"; break;
					case "TL": return "Tagalog"; break;
					case "TN": return "Setswana"; break;
					case "TO": return "Tonga"; break;
					case "TR": return "Turkish"; break;
					case "TS": return "Tsonga"; break;
					case "TT": return "Tatar"; break;
					case "TW": return "Twi"; break;
					case "UK": return "Ukrainian"; break;
					case "UR": return "Urdu"; break;
					case "UZ": return "Uzbek"; break;
					case "VI": return "Vietnamese"; break;
					case "VO": return "Volapuk"; break;
					case "WO": return "Wolof"; break;
					case "XH": return "Xhosa"; break;
					case "YO": return "Yoruba"; break;
					case "ZH": return "Chinese"; break;
					case "ZU": return "Zulu"; break;
					default: return null;
				}
			}
			else
			{
				switch ( strtolower($strCode))
				{
					case "aar": return "Afar"; break;
					case "abk": return "Abkhaz"; break;
					case "ace": return "Achinese"; break;
					case "ach": return "Acoli"; break;
					case "ada": return "Adangme"; break;
					case "ady": return "Adygei"; break;
					case "afa": return "Afroasiatic"; break;
					case "afh": return "Afrihili"; break;
					case "afr": return "Afrikaans"; break;
					case "aka": return "Akan"; break;
					case "akk": return "Akkadian"; break;
					case "alb": return "Albanian"; break;
					case "ale": return "Aleut"; break;
					case "alg": return "Algonquian  "; break;
					case "amh": return "Amharic"; break;
					case "ang": return "Old English"; break;
					case "apa": return "Apache language"; break;
					case "ara": return "Arabic"; break;
					case "arc": return "Aramaic"; break;
					case "arg": return "Aragonese Spanish"; break;
					case "arm": return "Armenian"; break;
					case "arn": return "Mapuche"; break;
					case "arp": return "Arapaho"; break;
					case "art": return "Artificial  "; break;
					case "arw": return "Arawak"; break;
					case "asm": return "Assamese"; break;
					case "ast": return "Bable"; break;
					case "ath": return "Athapascan"; break;
					case "aus": return "Australian language"; break;
					case "ava": return "Avaric"; break;
					case "ave": return "Avestan"; break;
					case "awa": return "Awadhi"; break;
					case "aym": return "Aymara"; break;
					case "aze": return "Azerbaijani"; break;
					case "bad": return "Banda"; break;
					case "bai": return "Bamileke language"; break;
					case "bak": return "Bashkir"; break;
					case "bal": return "Baluchi"; break;
					case "bam": return "Bambara"; break;
					case "ban": return "Balinese"; break;
					case "baq": return "Basque"; break;
					case "bas": return "Basa"; break;
					case "bat": return "Baltic"; break;
					case "bej": return "Beja"; break;
					case "bel": return "Belarusian"; break;
					case "bem": return "Bemba"; break;
					case "ben": return "Bengali"; break;
					case "ber": return "Berber "; break;
					case "bho": return "Bhojpuri"; break;
					case "bih": return "Bihari"; break;
					case "bik": return "Bikol"; break;
					case "bin": return "Edo"; break;
					case "bis": return "Bislama"; break;
					case "bla": return "Siksika"; break;
					case "bnt": return "Bantu "; break;
					case "bos": return "Bosnian"; break;
					case "bra": return "Braj"; break;
					case "bre": return "Breton"; break;
					case "btk": return "Batak"; break;
					case "bua": return "Buriat"; break;
					case "bug": return "Bugis"; break;
					case "bul": return "Bulgarian"; break;
					case "bur": return "Burmese"; break;
					case "cad": return "Caddo"; break;
					case "cai": return "Central American Indian"; break;
					case "car": return "Carib"; break;
					case "cat": return "Catalan"; break;
					case "cau": return "Caucasian "; break;
					case "ceb": return "Cebuano"; break;
					case "cel": return "Celtic"; break;
					case "cha": return "Chamorro"; break;
					case "chb": return "Chibcha"; break;
					case "che": return "Chechen"; break;
					case "chg": return "Chagatai"; break;
					case "chi": return "Chinese"; break;
					case "chk": return "Truk"; break;
					case "chm": return "Mari"; break;
					case "chn": return "Chinook jargon"; break;
					case "cho": return "Choctaw"; break;
					case "chp": return "Chipewyan"; break;
					case "chr": return "Cherokee"; break;
					case "chu": return "Church Slavic"; break;
					case "chv": return "Chuvash"; break;
					case "chy": return "Cheyenne"; break;
					case "cmc": return "Chamic language"; break;
					case "cop": return "Coptic"; break;
					case "cor": return "Cornish"; break;
					case "cos": return "Corsican"; break;
					case "cpe": return "Creoles and Pidgins, English-based"; break;
					case "cpf": return "Creoles and Pidgins, French-based"; break;
					case "cpp": return "Creoles and Pidgins, Portuguese-based "; break;
					case "cre": return "Cree"; break;
					case "crh": return "Crimean Tatar"; break;
					case "crp": return "Creoles and Pidgins"; break;
					case "cus": return "Cushitic"; break;
					case "cze": return "Czech"; break;
					case "dak": return "Dakota"; break;
					case "dan": return "Danish"; break;
					case "dar": return "Dargwa"; break;
					case "day": return "Dayak"; break;
					case "del": return "Delaware"; break;
					case "den": return "Slave"; break;
					case "dgr": return "Dogrib"; break;
					case "din": return "Dinka"; break;
					case "div": return "Divehi"; break;
					case "doi": return "Dogri"; break;
					case "dra": return "Dravidian "; break;
					case "dua": return "Duala"; break;
					case "dum": return "Middle Dutch"; break;
					case "dut": return "Dutch"; break;
					case "dyu": return "Dyula"; break;
					case "dzo": return "Dzongkha"; break;
					case "efi": return "Efik"; break;
					case "egy": return "Egyptian"; break;
					case "eka": return "Ekajuk"; break;
					case "elx": return "Elamite"; break;
					case "eng": return "English"; break;
					case "enm": return "Middle English"; break;
					case "epo": return "Esperanto"; break;
					case "est": return "Estonian"; break;
					case "ewe": return "Ewe"; break;
					case "ewo": return "Ewondo"; break;
					case "fan": return "Fang"; break;
					case "fao": return "Faroese"; break;
					case "fat": return "Fanti"; break;
					case "fij": return "Fijian"; break;
					case "fin": return "Finnish"; break;
					case "fiu": return "Finno-Ugrian"; break;
					case "fon": return "Fon"; break;
					case "fre": return "French"; break;
					case "frm": return "French, Middle"; break;
					case "fro": return "Old French"; break;
					case "fry": return "Frisian"; break;
					case "ful": return "Fula"; break;
					case "fur": return "Friulian"; break;
					case "gaa": return "Gã"; break;
					case "gay": return "Gayo"; break;
					case "gba": return "Gbaya"; break;
					case "gem": return "Germanic"; break;
					case "geo": return "Georgian"; break;
					case "ger": return "German"; break;
					case "gez": return "Ethiopic"; break;
					case "gil": return "Gilbertese"; break;
					case "gla": return "Scottish Gaelic"; break;
					case "gle": return "Irish"; break;
					case "glg": return "Galician"; break;
					case "glv": return "Manx"; break;
					case "gmh": return "Middle High German"; break;
					case "goh": return "Old High German"; break;
					case "gon": return "Gondi"; break;
					case "gor": return "Gorontalo"; break;
					case "got": return "Gothic"; break;
					case "grb": return "Grebo"; break;
					case "grc": return "Ancient Greek"; break;
					case "gre": return "Modern Greek"; break;
					case "grn": return "Guarani"; break;
					case "guj": return "Gujarati"; break;
					case "gwi": return "Gwich'in"; break;
					case "hai": return "Haida"; break;
					case "hat": return "Haitian French Creole"; break;
					case "hau": return "Hausa"; break;
					case "haw": return "Hawaiian"; break;
					case "heb": return "Hebrew"; break;
					case "her": return "Herero"; break;
					case "hil": return "Hiligaynon"; break;
					case "him": return "Himachali"; break;
					case "hin": return "Hindi"; break;
					case "hit": return "Hittite"; break;
					case "hmn": return "Hmong"; break;
					case "hmo": return "Hiri Motu"; break;
					case "hun": return "Hungarian"; break;
					case "hup": return "Hupa"; break;
					case "iba": return "Iban"; break;
					case "ibo": return "Igbo"; break;
					case "ice": return "Icelandic"; break;
					case "ido": return "Ido"; break;
					case "iii": return "Sichuan Yi"; break;
					case "ijo": return "Ijo"; break;
					case "iku": return "Inuktitut"; break;
					case "ile": return "Interlingue"; break;
					case "ilo": return "Iloko"; break;
					case "ina": return "Interlingua"; break;
					case "inc": return "Indic"; break;
					case "ind": return "Indonesian"; break;
					case "ine": return "Indo-European"; break;
					case "inh": return "Ingush"; break;
					case "ipk": return "Inupiaq"; break;
					case "ira": return "Iranian "; break;
					case "iro": return "Iroquoian "; break;
					case "ita": return "Italian"; break;
					case "jav": return "Javanese"; break;
					case "jpn": return "Japanese"; break;
					case "jpr": return "Judeo-Persian"; break;
					case "jrb": return "Judeo-Arabic"; break;
					case "kaa": return "Kara-Kalpak"; break;
					case "kab": return "Kabyle"; break;
					case "kac": return "Kachin"; break;
					case "kal": return "Kalâtdlisut"; break;
					case "kam": return "Kamba"; break;
					case "kan": return "Kannada"; break;
					case "kar": return "Karen"; break;
					case "kas": return "Kashmiri"; break;
					case "kau": return "Kanuri"; break;
					case "kaw": return "Kawi"; break;
					case "kaz": return "Kazakh"; break;
					case "kbd": return "Kabardian"; break;
					case "kha": return "Khasi"; break;
					case "khi": return "Khoisan"; break;
					case "khm": return "Khmer"; break;
					case "kho": return "Khotanese"; break;
					case "kik": return "Kikuyu"; break;
					case "kin": return "Kinyarwanda"; break;
					case "kir": return "Kyrgyz"; break;
					case "kmb": return "Kimbundu"; break;
					case "kok": return "Konkani"; break;
					case "kom": return "Komi"; break;
					case "kon": return "Kongo"; break;
					case "kor": return "Korean"; break;
					case "kos": return "Kusaie"; break;
					case "kpe": return "Kpelle"; break;
					case "kro": return "Kru"; break;
					case "kru": return "Kurukh"; break;
					case "kua": return "Kuanyama"; break;
					case "kum": return "Kumyk"; break;
					case "kur": return "Kurdish"; break;
					case "kut": return "Kutenai"; break;
					case "lad": return "Ladino"; break;
					case "lah": return "Lahnda"; break;
					case "lam": return "Lamba"; break;
					case "lao": return "Lao"; break;
					case "lat": return "Latin"; break;
					case "lav": return "Latvian"; break;
					case "lez": return "Lezgian"; break;
					case "lim": return "Limburgish"; break;
					case "lin": return "Lingala"; break;
					case "lit": return "Lithuanian"; break;
					case "lol": return "Mongo-Nkundu"; break;
					case "loz": return "Lozi"; break;
					case "ltz": return "Letzeburgesch"; break;
					case "lua": return "Luba-Lulua"; break;
					case "lub": return "Luba-Katanga"; break;
					case "lug": return "Ganda"; break;
					case "lui": return "Luiseño"; break;
					case "lun": return "Lunda"; break;
					case "luo": return "Luo"; break;
					case "lus": return "Lushai"; break;
					case "mac": return "Macedonian"; break;
					case "mad": return "Madurese"; break;
					case "mag": return "Magahi"; break;
					case "mah": return "Marshallese"; break;
					case "mai": return "Maithili"; break;
					case "mak": return "Makasar"; break;
					case "mal": return "Malayalam"; break;
					case "man": return "Mandingo"; break;
					case "mao": return "Maori"; break;
					case "map": return "Austronesian"; break;
					case "mar": return "Marathi"; break;
					case "mas": return "Masai"; break;
					case "may": return "Malay"; break;
					case "mdr": return "Mandar"; break;
					case "men": return "Mende"; break;
					case "mga": return "Irish, Middle "; break;
					case "mic": return "Micmac"; break;
					case "min": return "Minangkabau"; break;
					case "mis": return "Miscellaneous language"; break;
					case "mkh": return "Mon-Khmer"; break;
					case "mlg": return "Malagasy"; break;
					case "mlt": return "Maltese"; break;
					case "mnc": return "Manchu"; break;
					case "mni": return "Manipuri"; break;
					case "mno": return "Manobo language"; break;
					case "moh": return "Mohawk"; break;
					case "mol": return "Moldavian"; break;
					case "mon": return "Mongolian"; break;
					case "mos": return "Mooré"; break;
					case "mul": return "Multiple languages"; break;
					case "mun": return "Munda "; break;
					case "mus": return "Creek"; break;
					case "mwr": return "Marwari"; break;
					case "myn": return "Mayan language"; break;
					case "nah": return "Nahuatl"; break;
					case "nai": return "North American Indian"; break;
					case "nap": return "Neapolitan Italian"; break;
					case "nau": return "Nauru"; break;
					case "nav": return "Navajo"; break;
					case "nbl": return "Ndebele"; break;
					case "nde": return "Ndebele"; break;
					case "ndo": return "Ndonga"; break;
					case "nds": return "Low German"; break;
					case "nep": return "Nepali"; break;
					case "new": return "Newari"; break;
					case "nia": return "Nias"; break;
					case "nic": return "Niger-Kordofanian"; break;
					case "niu": return "Niuean"; break;
					case "nno": return "Norwegian "; break;
					case "nob": return "Norwegian "; break;
					case "nog": return "Nogai"; break;
					case "non": return "Old Norse"; break;
					case "nor": return "Norwegian"; break;
					case "nso": return "Northern Sotho"; break;
					case "nub": return "Nubian language"; break;
					case "nya": return "Nyanja"; break;
					case "nym": return "Nyamwezi"; break;
					case "nyn": return "Nyankole"; break;
					case "nyo": return "Nyoro"; break;
					case "nzi": return "Nzima"; break;
					case "oci": return "Occitan "; break;
					case "oji": return "Ojibwa"; break;
					case "ori": return "Oriya"; break;
					case "orm": return "Oromo"; break;
					case "osa": return "Osage"; break;
					case "oss": return "Ossetic"; break;
					case "ota": return "Turkish, Ottoman"; break;
					case "oto": return "Otomian language"; break;
					case "paa": return "Papuan "; break;
					case "pag": return "Pangasinan"; break;
					case "pal": return "Pahlavi"; break;
					case "pam": return "Pampanga"; break;
					case "pan": return "Panjabi"; break;
					case "pap": return "Papiamento"; break;
					case "pau": return "Palauan"; break;
					case "peo": return "Old Persian"; break;
					case "per": return "Persian"; break;
					case "phi": return "Philippine "; break;
					case "phn": return "Phoenician"; break;
					case "pli": return "Pali"; break;
					case "pol": return "Polish"; break;
					case "pon": return "Ponape"; break;
					case "por": return "Portuguese"; break;
					case "pra": return "Prakrit language"; break;
					case "pro": return "Provençal "; break;
					case "pus": return "Pushto"; break;
					case "que": return "Quechua"; break;
					case "raj": return "Rajasthani"; break;
					case "rap": return "Rapanui"; break;
					case "rar": return "Rarotongan"; break;
					case "roa": return "Romance "; break;
					case "roh": return "Raeto-Romance"; break;
					case "rom": return "Romani"; break;
					case "rum": return "Romanian"; break;
					case "run": return "Rundi"; break;
					case "rus": return "Russian"; break;
					case "sad": return "Sandawe"; break;
					case "sag": return "Sango"; break;
					case "sah": return "Yakut"; break;
					case "sai": return "South American Indian"; break;
					case "sal": return "Salishan language"; break;
					case "sam": return "Samaritan Aramaic"; break;
					case "san": return "Sanskrit"; break;
					case "sas": return "Sasak"; break;
					case "sat": return "Santali"; break;
					case "scc": return "Serbian"; break;
					case "sco": return "Scots"; break;
					case "scr": return "Croatian"; break;
					case "sel": return "Selkup"; break;
					case "sem": return "Semitic"; break;
					case "sga": return "Irish, Old"; break;
					case "sgn": return "Sign language"; break;
					case "shn": return "Shan"; break;
					case "sid": return "Sidamo"; break;
					case "sin": return "Sinhalese"; break;
					case "sio": return "Siouan "; break;
					case "sit": return "Sino-Tibetan"; break;
					case "sla": return "Slavic "; break;
					case "slo": return "Slovak"; break;
					case "slv": return "Slovenian"; break;
					case "sma": return "Southern Sami"; break;
					case "sme": return "Northern Sami"; break;
					case "smi": return "Sami"; break;
					case "smj": return "Lule Sami"; break;
					case "smn": return "Inari Sami"; break;
					case "smo": return "Samoan"; break;
					case "sms": return "Skolt Sami"; break;
					case "sna": return "Shona"; break;
					case "snd": return "Sindhi"; break;
					case "snk": return "Soninke"; break;
					case "sog": return "Sogdian"; break;
					case "som": return "Somali"; break;
					case "son": return "Songhai"; break;
					case "sot": return "Sotho"; break;
					case "spa": return "Spanish"; break;
					case "srd": return "Sardinian"; break;
					case "srr": return "Serer"; break;
					case "ssa": return "Nilo-Saharan"; break;
					case "ssw": return "Swazi"; break;
					case "suk": return "Sukuma"; break;
					case "sun": return "Sundanese"; break;
					case "sus": return "Susu"; break;
					case "sux": return "Sumerian"; break;
					case "swa": return "Swahili"; break;
					case "swe": return "Swedish"; break;
					case "syr": return "Syriac"; break;
					case "tah": return "Tahitian"; break;
					case "tai": return "Tai"; break;
					case "tam": return "Tamil"; break;
					case "tat": return "Tatar"; break;
					case "tel": return "Telugu"; break;
					case "tem": return "Temne"; break;
					case "ter": return "Terena"; break;
					case "tet": return "Tetum"; break;
					case "tgk": return "Tajik"; break;
					case "tgl": return "Tagalog"; break;
					case "tha": return "Thai"; break;
					case "tib": return "Tibetan"; break;
					case "tig": return "Tigré"; break;
					case "tir": return "Tigrinya"; break;
					case "tiv": return "Tiv"; break;
					case "tkl": return "Tokelauan"; break;
					case "tli": return "Tlingit"; break;
					case "tmh": return "Tamashek"; break;
					case "tog": return "Tonga "; break;
					case "ton": return "Tongan"; break;
					case "tpi": return "Tok Pisin"; break;
					case "tsi": return "Tsimshian"; break;
					case "tsn": return "Tswana"; break;
					case "tso": return "Tsonga"; break;
					case "tuk": return "Turkmen"; break;
					case "tum": return "Tumbuka"; break;
					case "tup": return "Tupi language"; break;
					case "tur": return "Turkish"; break;
					case "tut": return "Altaic "; break;
					case "tvl": return "Tuvaluan"; break;
					case "twi": return "Twi"; break;
					case "tyv": return "Tuvinian"; break;
					case "udm": return "Udmurt"; break;
					case "uga": return "Ugaritic"; break;
					case "uig": return "Uighur"; break;
					case "ukr": return "Ukrainian"; break;
					case "umb": return "Umbundu"; break;
					case "und": return "Undetermined language"; break;
					case "urd": return "Urdu"; break;
					case "uzb": return "Uzbek"; break;
					case "vai": return "Vai"; break;
					case "ven": return "Venda"; break;
					case "vie": return "Vietnamese"; break;
					case "vol": return "Volapük"; break;
					case "vot": return "Votic"; break;
					case "wak": return "Wakashan language"; break;
					case "wal": return "Walamo"; break;
					case "war": return "Waray"; break;
					case "was": return "Washo"; break;
					case "wel": return "Welsh"; break;
					case "wen": return "Sorbian language"; break;
					case "wln": return "Walloon"; break;
					case "wol": return "Wolof"; break;
					case "xal": return "Kalmyk"; break;
					case "xho": return "Xhosa"; break;
					case "yao": return "Yao "; break;
					case "yap": return "Yapese"; break;
					case "yid": return "Yiddish"; break;
					case "yor": return "Yoruba"; break;
					case "ypk": return "Yupik language"; break;
					case "zap": return "Zapotec"; break;
					case "zen": return "Zenaga"; break;
					case "zha": return "Zhuang"; break;
					case "znd": return "Zande"; break;
					case "zul": return "Zulu"; break;
					case "zun": return "Zuni"; break;
					default: return null;
				}
			}
		}
		
		private function escapeXml( $string )
		{
			// NOTE: if you make a change to this function, make a corresponding change 
			// in the Xerxes_Parser class, since this one here is a duplicate function 
			// allowing Xerxes_Record it be as a stand-alone class 
			
			
			
	        $string = str_replace('&', '&amp;', $string);
	        $string = str_replace('<', '&lt;', $string);
	        $string = str_replace('>', '&gt;', $string);
	        $string = str_replace('\'', '&#39;', $string);
	        $string = str_replace('"', '&quot;', $string);
	        
	        $string = str_replace("&amp;#", "&#", $string);
			$string = str_replace("&amp;amp;", "&amp;", $string);

	        
	        return $string;
		}
		
		private function toTitleCase( $strInput )
		{
			// NOTE: if you make a change to this function, make a corresponding change 
			// in the Xerxes_Parser class, since this one here is a duplicate function 
			// allowing Xerxes_Record to be a stand-alone class
			
			
			
			
			$arrMatches = "";			// matches from regular expression
			$arrSmallWords = "";		// words that shouldn't be capitalized if they aren't the first word.
			$arrWords = "";				// individual words in input
			$strFinal = "";				// final string to return
			$strLetter = "";			// first letter of subtitle, if any
						
			// if there are no lowercase letters (and its sufficiently long a title to 
			// not just be an aconym or something) then this is likely a title stupdily
			// entered into a database in ALL CAPS, so drop it entirely to 
			// lower-case first

			$iMatch = preg_match("/[a-z]/", $strInput);

			if ($iMatch == 0 && strlen($strInput) > 10)
			{
				$strInput = strtolower($strInput);
			}
			
			// array of small words
			
			$arrSmallWords = array( 'of','a','the','and','an','or','nor','but','is','if','then','else',
				'when', 'at','from','by','on','off','for','in','out','over','to','into','with', 'as' );
				
			// split the string into separate words
			
			$arrWords = explode(' ', $strInput);
			
			foreach ($arrWords as $key => $word)
			{ 
					// if this word is the first, or it's not one of our small words, capitalise it 
					
					if ( $key == 0 || !in_array( strtolower($word), $arrSmallWords) )
					{
						$arrWords[$key] = ucwords($word);
					}
					elseif ( in_array( strtolower($word), $arrSmallWords) )
					{
						$arrWords[$key] = strtolower($word);
					}
			} 
			
			// join the words back into a string
			
			$strFinal = implode(' ', $arrWords);
			
			// catch subtitles
			
			if ( preg_match("/: ([a-z])/", $strFinal, $arrMatches) )
			{
				$strLetter = ucwords($arrMatches[1]);
				$strFinal = preg_replace("/: ([a-z])/", ": " . $strLetter, $strFinal );
			}

			// catch words that start with double quotes
			
			if ( preg_match("/\"([a-z])/", $strFinal, $arrMatches) )
			{
				$strLetter = ucwords($arrMatches[1]);
				$strFinal = preg_replace("/\"[a-z]/", "\"" . $strLetter, $strFinal );
			}
			
			// catch words that start with a single quote
			// need to be a little more cautious here and make sure there is a space before the quote when
			// inside the title to ensure this isn't a quote for a contraction or for possisive; seperate
			// case to handle when the quote is the first word
			
			if ( preg_match("/ '([a-z])/", $strFinal, $arrMatches) )
			{
				$strLetter = ucwords($arrMatches[1]);
				$strFinal = preg_replace("/ '[a-z]/", " '" . $strLetter, $strFinal );
			}
			
			if ( preg_match("/^'([a-z])/", $strFinal, $arrMatches) )
			{
				$strLetter = ucwords($arrMatches[1]);
				$strFinal = preg_replace("/^'[a-z]/", "'" . $strLetter, $strFinal );
			}
			
			return $strFinal;

		}
		
		private function ordinal($value)
		{
			if ( is_numeric($value) )
			{
				if( substr($value, -2, 2) == 11 || substr($value, -2, 2) == 12 || substr($value, -2, 2) == 13 )
				{
			        $suffix = "th";
			    }
			    elseif ( substr($value, -1, 1) == 1 )
			    {
			        $suffix = "st";
			    }
			    elseif ( substr($value, -1, 1) == 2 )
			    {
			        $suffix = "nd";
			    }
			    elseif ( substr($value, -1, 1) == 3 )
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
		

		### PROPERTIES ###
		
		public function setSource($value)
		{
			$this->strSource = $value;
		}

		public function getMarcXML()
		{
			return $this->objMarcXML;
		}
		
		public function getMarcXMLString()
		{
			return $this->objMarcXML->saveXML();
		}
		
		public function hasFullText()
		{
			$bolFullText = false;
			
			foreach($this->arrLinks as $arrLink )
			{
        //  this should really be based on INCLUSION, which ones are
        //  fulltext, not the current exclusion. Oh well.
				if ( $arrLink[2] != "none" && $arrLink[2] != "original_record" && $arrLink[2] != "holdings")
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
				
				foreach ( $this->arrLinks as $arrLink )
				{
					if ( $arrLink[2] != "none")
					{
						array_push($arrFinal, $arrLink);
					}
				}
				
				return $arrFinal;
			}
			else
			{	
				// all the links
				
				return $this->arrLinks;
			}
		}
		
		public function getPrimaryAuthor($bolReverse = false)
		{
			$arrPrimaryAuthor = $this->getAuthors(true, true, $bolReverse);
			
			if ( count($arrPrimaryAuthor) > 0 )
			{
				return $arrPrimaryAuthor[0];
			}
			elseif ( $this->strAuthorFromTitle != "" )
			{
				return trim($this->strAuthorFromTitle);
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
			$arrFinal = array();
			
			foreach ( $this->arrAuthors as $arrAuthor)
			{
				if ( $bolFormat == true )
				{
					$strAuthor = ""; // author name formatted
					$strLast = ""; // last name
					$strFirst = ""; // first name
					$strInit = ""; // middle initial
					$strName = ""; // full name, not personal
					
					if ( array_key_exists("first", $arrAuthor)) $strFirst = $arrAuthor["first"];
					if ( array_key_exists("last", $arrAuthor)) $strLast = $arrAuthor["last"];
					if ( array_key_exists("init", $arrAuthor)) $strInit = $arrAuthor["init"];
					if ( array_key_exists("name", $arrAuthor)) $strName = $arrAuthor["name"];
					
					if ( $strName != "" )
					{
						$strAuthor = $strName;
					}
					else
					{		
						if ( $bolReverse == false )
						{
							$strAuthor = $strFirst . " ";
							
							if ( $strInit != "")
							{ 
								$strAuthor .= $strInit . " ";
							}  
							
							$strAuthor .= $strLast;
						}
						else
						{
							$strAuthor = $strLast  . ", " . $strFirst. " ". $strInit;
						}
					}
					
					array_push($arrFinal, $strAuthor);
				}
				else
				{
					array_push($arrFinal, $arrAuthor);
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
			
			if ( $this->strNonSort != "") 
			{
				$strTitle = $this->strNonSort;
			}

			$strTitle .= $this->strTitle;
			
			if ( $this->strSubTitle != "")
			{
				$strTitle .= ": " . $this->strSubTitle;
			}
			
			if ( $bolTitleCase == true )
			{
				$strTitle = $this->toTitleCase($strTitle);
			}
			
			return $strTitle;
		}
		public function getBookTitle($bolTitleCase = false)
		{		
			if ( $bolTitleCase == true )
			{
				return $this->toTitleCase($this->strBookTitle);
			}
			else
			{
				return $this->strBookTitle;
			}
		}
		public function getJournalTitle($bolTitleCase = false)
		{		
			if ( $bolTitleCase == true )
			{
				return $this->toTitleCase($this->strJournalTitle);
			}
			else
			{
				return $this->strJournalTitle;
			}
		}
		
		public function getISSN()
		{ 
			if ( count($this->arrIssn) > 0 )
			{
				return str_replace("-", "", $this->arrIssn[0]);
			}
			else
			{
				return null;
			}
		}
		public function getISBN()
		{ 
			if ( count($this->arrIsbn) > 0 )
			{
				return str_replace("-", "", $this->arrIsbn[0]);
			}
			else
			{
				return null;
			}
		}
		
		public function getAllISSN()
		{ 
			$arrClean = array();
			$arrUnique = array_unique($this->arrIssn);
			
			foreach ($arrUnique as $strISSN )
			{
				if ( $strISSN != null )
				{
					$strISSN = str_replace("-", "", $strISSN);
					array_push($arrClean, $strISSN);
				}
			}
			
			return $arrClean;
		}
		public function getAllISBN()
		{ 
			$arrClean = array();
			$arrUnique = array_unique($this->arrIsbn);
			
			foreach ($arrUnique as $strIsbn )
			{
				if ( $strIsbn != "" )
				{
					$strIsbn = str_replace("-", "", $strIsbn);
					array_push($arrClean, $strIsbn);
				}
			}
			
			return $arrClean;
		}
		
		public function getMetalibID() { return $this->strMetalibID; }
		
		public function getResultSet() { return $this->strResultSet; }
		public function setResultSet($data) { $this->strResultSet = $data; }
		
		public function getRecordNumber() { return $this->strRecordNumber; }
		public function setRecordNumber($data) { $this->strRecordNumber = $data; }
		
		public function isEditor() { return $this->bolEditor; }
		public function getFormat() { return $this->strFormat; }
		public function getTechnology() { return $this->strTechnology; }

		public function getNonSort() { return $this->strNonSort; }
		public function getMainTitle() { return $this->strTitle; }
		public function getSubTitle() { return $this->strSubTitle; }
		public function getSeriesTitle() { return $this->strSeriesTitle; }
				
		public function getAbstract() { return $this->strAbstract; }
		public function getSummary() { return $this->strSummary; }
		public function getDescription() { return $this->strDescription; }
		
		public function getEmbeddedText() { return $this->arrEmbeddedText; }
		public function getLanguage() { return $this->strLanguage; }
		public function getTOC() { return $this->strTOC; }
		
		public function getPlace() { return $this->strPlace; }
		public function getPublisher() { return $this->strPublisher; }
		public function getYear() { return $this->strDate; }
		
		public function getJournal() { return $this->strJournal; }
		public function getVolume() { return $this->strVolume; }
		public function getIssue() { return $this->strIssue; }
		public function getStartPage() { return $this->strStartPage; }
		public function getEndPage() { return $this->strEndPage; }
		public function getExtent() { return  $this->strTPages; }
		public function getPrice() { return  $this->strPrice; }
		
		public function getDatabaseName() { return $this->strDatabaseName; }
		
		public function getNotes() { return $this->arrNotes; }
		
		public function getSubjects() 
		{
			return $this->arrSubjects;
		}
		
		public function getInstitution()
		{
			return $this->strInstitution;
		}
		
		public function getDegree()
		{
			return $this->strDegree;
		}
		
		public function getEdition()
		{
			return $this->ordinal($this->strEdition);
		}
		
		public function getCallNumber()
		{
			return $this->strCallNumber;
		}
		
		public function getOCLCNumber()
		{
			return $this->strOCLC;
		}
	
		public function getControlNumber()
		{
			return $this->strControlNumber;
		}
	}

  
  class UrlTemplatePlaceholderMissing extends Exception {
  }
?>
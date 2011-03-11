<?php

/**
 * Extract properties for books, articles, and dissertations from Summon
 * 
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Model_Summon_Record extends Xerxes_Record
{
	private $original_array;

	public function __sleep()
	{
		$this->serialized = $this->original_array;
		return array("serialized");
	}
	
	public function __wakeup()
	{
		$this->load($this->serialized);
	}	
	
	public function load($document)
	{
		$this->original_array = $document;
		$this->map($document);
	}	
	
	protected function map($document)
	{
		$this->source = "Summon";
		$this->database_name = "TBD";
		
		$this->record_id = $this->extractValue($document, "ID/0");
		
		// title
		
		$this->title = $this->extractValue($document, "Title/0");
		$this->sub_title = $this->extractValue($document, "Subtitle/0");
		
		// basic info
		
		$this->language = $this->extractValue($document, "Language/0");
		$this->year = $this->extractValue($document, "PublicationDate_xml/0/year");
		$this->extent = $this->extractValue($document, "PageCount/0");
		$this->format = $this->extractValue($document, "ContentType/0");		
		
		// summary
		
		$this->snippet = $this->extractValue($document, "Snippet/0");
		$this->abstract = $this->extractValue($document, "Abstract/0");
		
		// books
		
		$this->edition = $this->extractValue($document, "Edition/0");
		$this->publisher = $this->toTitleCase($this->extractValue($document, "Publisher/0"));
		$this->place = $this->extractValue($document, "PublicationPlace_xml/0/name");
		
		// article
		
		$this->journal_title = $this->toTitleCase($this->extractValue($document, "PublicationTitle/0"));
		$this->issue = $this->extractValue($document, "Issue/0");
		$this->volume = $this->extractValue($document, "Volume/0");
		$this->start_page = $this->extractValue($document, "StartPage/0");
		$this->doi = $this->extractValue($document, "DOI/0");
		
		// subjects
		
		if ( array_key_exists('SubjectTerms', $document) )
		{
			foreach ( $document['SubjectTerms'] as $subject)
			{
				$subject = Xerxes_Framework_Parser::toSentenceCase($subject);
				
				$subject_object = new Xerxes_Record_Subject();
				$subject_object->display = $subject;
				$subject_object->value = $subject;
				array_push($this->subjects, $subject_object);
			}
		}

		// isbn
		
		if ( array_key_exists('ISBN', $document) )
		{
			$this->isbns = $document['ISBN'];
		}

		// issn
		
		if ( array_key_exists('ISSN', $document) )
		{
			$this->issns = $document['ISSN'];
		}
		elseif ( array_key_exists('EISSN', $document) )
		{
			$this->issns = $document['EISSN'];
		}
		
		// notes

		if ( array_key_exists('Notes', $document) )
		{
			$this->notes = $document['Notes'];
		}			
		
		// authors
		
		if ( array_key_exists('Author_xml', $document) )
		{
			foreach ( $document['Author_xml'] as $author )
			{
				$author_object = new Xerxes_Record_Author();
				
				if ( array_key_exists('givenname', $author) )
				{
					$author_object->type = "personal";
					$author_object->last_name = $author['surname'];
					$author_object->first_name = $author['givenname'];
				}
				elseif ( array_key_exists('fullname', $author) )
				{
					
					$author_object = $this->splitAuthor($author['fullname'], null, 'personal');
				}
				
				array_push($this->authors, $author_object);
			}
		}
		
		$this->cleanup();
	}
	
	/**
	 * Conventience function for extracting data from Summon json
	 * 
	 * @param array	$document
	 * @param string $path		path to the value
	 * 
	 * @return mixed			strign if found data, null otherwise
	 */
	
	private function extractValue($document, $path )
	{
		$path = explode('/', $path);
		$pointer = $document;
		
		foreach ( $path as $part )
		{
			if ( array_key_exists($part, $pointer) )
			{
				$pointer = $pointer[$part];
			}
		}
		
		if ( is_array($pointer) )
		{
			return ""; // we didn't actually get our value
		}
		else
		{
			return strip_tags($pointer);
		}
	}
}
	

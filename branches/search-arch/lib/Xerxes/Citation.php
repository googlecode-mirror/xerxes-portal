<?php

/**
 * Citation Style Language Engine
 *
 * TODO: look at this http://xbiblio.svn.sourceforge.net/viewvc/xbiblio/csl/schema/trunk/csl-terms.rnc?view=markup
 * 
 */

class Xerxes_Citation
{
	private $data; // the supplied data object to be formatted
	private $csl; // simple-xml object csl file
	private $formatted = ""; // final formatted citation
	private $bibliography_options = array(); // bibliography options
	private $citation_options = array(); // citation options
	
	public function loadStyle($file)
	{
		$this->csl = new SimpleXMLElement($file, null, true);
		$this->csl->registerXPathNamespace("csl", "http://purl.org/net/xbiblio/csl");
	}
	
	public function process($record)
	{
		if ( $record instanceof Xerxes_Record )
		{
			$record = $this->convertXerxesRecord($record);
		}
		
		// check to make sure a csl file has been loaded first

		if ( ! $this->csl instanceof SimpleXMLElement )
		{
			throw new Exception( "must load a csl file via loadStyle() before processing" );
		}
		
		// blank the previously processed citation

		$this->formatted = "";
		
		// set the new data object as a property
		
		$this->data = $record;
		
		// bibliography
		
		$this->recursive($this->csl->bibliography);
		
		echo $this->formatted;
	}
	
	private function convertXerxesRecord(Xerxes_Record $xerxes)
	{
		$citation = new Xerxes_Citation_Data();
		
		$map = array (
			"type" => "format",
			"issued" => "year",
			// "accessed" => "",
			"number" => "",
			"number_of_volumes" => "",
			// "container_title" => "",
			// "collection_title" => "",
			"publisher_place" => "place",
			"event" => "",
			"event_place" => "",
			"page" => "startPage",
			"locator" => "",
			"genre" => "format"
			// "url" => "",
		);
				
		foreach ( $citation as $key => $value )
		{
			if ( $key == "names")
			{
				foreach ( $xerxes->getAuthors() as $author )
				{
					$name = new Xerxes_Citation_Name();
					
					foreach ( $author as $key => $value )
					{
						$name->$key = $value;
					}
					
					array_push($citation->names, $name);
				}
			}
			
			$target = $key;
			
			if ( array_key_exists($key, $map) )
			{
				$target = $map[$key];
			}
			
			$method = strtoupper(substr($target, 0, 1));
			$method .= substr($target, 1);
			$method = "get$method";
			
			if ( method_exists($xerxes, $method) )
			{
				$citation->$key = $xerxes->$method();
			}
		}
		
		print_r($citation);
	}
	
	private function choose($choose)
	{
		foreach ( $choose->children() as $condition )
		{
			if ( $condition->getName() == "if" || $condition->getName() == "else-if" )
			{
				$match = (string) $condition["match"];
				
				// check the data type
				
				if ( $condition["type"] )
				{
					$done = false;
					
					$arrTypes = explode(" ", (string) $condition["type"]);
					
					foreach ( $arrTypes as $type )
					{
						if ( $this->data->type == $type )
						{
							if ( $match == "any")
							{
								$done = true;
								break;
							}
							elseif ( $match == "none")
							{
								$done = false;
								break;
							}
							else // all or default?
							{
								$done = true;
							}
						}
						else
						{
							if ( $match == "all")
							{
								$done = false;
								break;
							}
							if ( $match == "none")
							{
								$done = true;
							}
						}
					}
					
					if ( $done == true )
					{
						return $condition;
					}
				}
				
				// checking to see if a property of the data object exists
				
				elseif ( $condition["variable"] )
				{
					$variable = (string) $condition["variable"];
					
					if ( isset($this->data->$variable) )
					{
						return $condition;
					}
				}

				elseif ( $condition["is-numeric"] )
				{
					$variable = (string) $condition["is-numeric"];
					
					if ( isset($this->data->$variable) )
					{
						if ( (int) $this->data->$variable != 0 )
						{
							return $condition;
						}
					}
				}				
				
				elseif ( $condition["is-date"] )
				{
					$variable = (string) $condition["is-date"];
					
					if ( isset($this->data->$variable) )
					{
						if ( $this->data->$variable instanceof Xerxes_Citation_Date )
						{
							return $condition;
						}
					}
				}
								
				// position { "first" | "subsequent" | "ibid" | "ibid-with-locator" }
				// disambiguate
				// locator
			}
			elseif ( $condition->getName() == "else")
			{
				return $condition;
			}
			else
			{
				throw new Exception("child of choose must be 'if', 'else-if' or 'else'");
			}
		}
	}
	
	private function text($text)
	{
		// get the text from the bib data
		
		if ( $text["variable"] )
		{
			$variable = (string) $text["variable"];
			
			if ( isset($this->data->$variable) )
			{
				$data = $this->data->$variable;
			}
		}

		// get the text from a macro
		
		if ( $text["macro"] )
		{
			$macro_name = (string) $text["macro"];
			$macros = $this->csl->xpath("//csl:macro[@name ='$macro_name']");
			
			if ( count($macros) == 1 )
			{
				$this->recursive($macros[0]);
			}
			else
			{
				throw new Exception("no macro defined for '$macro_name'");
			}
		}
	}
	
	private function formatting($data, $node)
	{
		$style = ""; // stylistic rendering
		
		// stylistic elements
		
		foreach ( $node->attributes as $attribute )
		{
			if ( $attribute->getName() == "font-family" || 
				$attribute->getName() == "font-style" ||  
				$attribute->getName() == "font-variant" ||  
				$attribute->getName() == "font-weight" ||  
				$attribute->getName() == "text-decoration" ||  
				$attribute->getName() == "vertical-align" ||  
				$attribute->getName() == "display")
			{
				$style .= " " . $attribute->getName() . ": " . (string) $attribute;
			}
		}
		
		// capitalization
		
		if ( $node["text-case"] )
		{
			switch ( (string) $node["text-case"] )
			{
				case "lowercase":
					$data = strtolower($data);
					break;
					
				case "uppercase":
					$data = strtoupper($data);
					break;
					
				case "capitalize-first":
				case "sentence":
					$data = strtoupper(substr($data, 0, 1)) . substr($data, 1);
					break;
					
				case "capitalize-all":
					//TODO: add this to parser?
					break;
					
				case "title":
					//TODO: make reference to parser?
					break;
			}
		}
		
		// stylistic rendering
		
		if ( $style != "" )
		{
			$data = "<span style=\"$style\">$data</span>";
		}
		
		// add quotes
		
		if ( $node["quotes"] )
		{
			$data = "\"" . $data . "\"";
		}
		
		return $node["prefix"] . $data . $node["suffix"];
	}
	
	private function recursive($element)
	{
		$name = $element->getName();
		
		if ($name == "choose")
		{
			$fork = $this->choose($element);
				
			if ( $fork != null )
			{
				$this->recursive($fork);
			}
		}
		else
		{
			if ( method_exists($this, $name) )
			{
				return $this->{$name}($element);
			}
			
			foreach ( $element->children() as $element_child )
			{
				$this->recursive($element_child);
			}				
		}
	}

	private function load_options()
	{
		// citation: et-al | et-al-subsequent | disambiguate | collapse
		// bibliography: et-al | hanging-indent | second-field-align | subsequent-author-substitute | line-formatting
		
		foreach ( $this->csl->bibliography->option as $option )
		{
			$name = (string) $option["name"];
			$value = (string) $option["value"];
			
			$this->bibliography_options[$name] = $value;
		}
	}
}

class Xerxes_Citation_Data
{
	/**
	 *   article
	 *   article-magazine
	 *   article-newspaper
	 *   article-journal
	 *   bill
	 *   book
	 *   broadcast
	 *   chapter
	 *   entry
	 *   entry-dictionary
	 *   entry-encyclopedia
	 *   figure
	 *   graphic
	 *   interview
	 *   legislation
	 *   legal_case
	 *   manuscript
	 *   map
	 *   motion_picture
	 *   musical_score
	 *   pamphlet
	 *   paper-conference
	 *   patent
	 *   post
	 *   post-weblog
	 *   personal_communication
	 *   report
	 *   review
	 *   review-book
	 *   song
	 *   speech
	 *   thesis
	 *   treaty
	 *   webpage
	 */

	public $type;
	
	// names
	
	public $names = array();
	
	/**
	 * the primary title for the cited item
	 */ 
		
	public $title;
	
	/**
	 * the secondary title for the cited item; for a book chapter, this
	 * would be a book title, for an article the journal title, etc.
	 */ 
	
	public $container_title;
	
	/**
	 * the tertiary title for the cited item; for example, a series title
	 */
	
	public $collection_title;
	
	/**
	 * collection number; for example, series number
	 */ 
	
	public $collection_number;
	
	/**
	 * title of a related original version; often useful in cases of translation
	 */ 
	
	public $original_title;
	
	/**
	 * the name of the publisher
	 */ 
	
	public $publisher;
	
	/**
	 * the location of the publisher
	 */ 
	
	public $publisher_place;
	
	/**
	 * the name of the archive
	 */
	
	public $archive;
	
	/**
	 * the location of the archive
	 */ 
	
	public $archive_place;
	
	/**
	 * issuing authority (for patents) or judicial authority (such as court
	 * for legal cases)
	 */
	
	public $authority;
	
	/**
	 * the location within an archival collection (for example, box and folder)
	 */
	
	public $archive_location;
	
	/**
	 *  the name or title of a related event such as a conference or hearing
	 */
	
	public $event;
	
	/**
	 * the location or place for the related event
	 */
	
	public $event_place;
	
	/**
	 *  the range of pages an item covers in a containing item
	 */
	
	public $page;
	
	/**
	 * the first page of an item within a containing item
	 */
	
	public $page_first;
	
	/**
	 * a description to locate an item within some larger container or
	 * collection; a volume or issue number is a kind of locator, for example.
	 */
	
	public $locator;
	
	/**
	 * version description
	 */
	
	public $version;
	
	/**
	 * volume number for the container periodical
	 */
	
	public $volume;
	
	/**
	 * refers to the number of items in multi-volume books and such
	 */
	
	public $number_of_volumes;
	
	/**
	 * refers to the number of items in multi-volume books and such
	 */
	
	public $number_of_pages;
	
	/**
	 * the issue number for the container publication
	 */
	
	public $issue;
	
	public $chapter_number;
	
	/**
	 * medium description (DVD, CD, etc.)
	 */
	
	public $medium;
	
	/**
	 * the (typically publication) status of an item; for example "forthcoming"
	 */
	
	public $status;
	 
	/**
	 * an edition description
	 */
	
	public $edition;
	 
	/**
	 * a section description (for newspapers, etc.)
	 */
	
	public $section;
	 
	public $genre;
	 
	/**
	 * a short inline note, often used to refer to additional details of the resource
	 */
	
	public $note;
	 
	/**
	 * notes made by a reader about the content of the resource
	 */
	
	public $annote;
	 
	public $abstract;
	 
	public $keyword;
	 
	/**
	 * a document number; useful for reports and such
	 */
	
	public $number;
	 
	/**
	 * for related referenced resources; this is here for legal case
	 * histories, but may be relevant for other contexts.
	 */
	
	public $references;
	
	public $URL;
	
	public $DOI;
	
	public $ISBN;
	
	public $call_number;
	 
	/**
	 * the number used for the in-text citation mark in numeric styles
	 */
	
	public $citation_number;
	 
	/**
	 * the label used for the in-text citation mark in label styles
	 */
	
	public $citation_label;
	
	/**
	 * The number of a preceding note containing the first reference to
	 * this item. Relevant only for note-based styles, and null for first references.
	 */
	
	public $first_reference_note_number;
	 
	/**
	 * The year suffix for author-date styles; e.g. the 'a' in 'a'.
	 */
	
	public $year_suffix;
	
}

class Xerxes_Citation_Date
{
	public $year;
	public $month;
	public $day;
}

class Xerxes_Citation_Name
{
	public $first;
	public $last;
	public $init;
	public $name;
	
	/**
	 * the person or entitiy's role, valid values include:
	 * 
	 *   author
	 *   editor
	 *   translator
	 *   recipient
	 *   interviewer
	 *   publisher
	 *   composer
	 *   original-publisher
	 *   original-author
	 *   container-author
	 *   collection-editor
	 */
	
	public $role;

}

?>

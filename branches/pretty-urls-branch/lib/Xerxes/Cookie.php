<?php	
	
	/**
	 * Manages saved records via a cookie
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Cookie
	{
		private $cookie = "";		// string value of the cookie
		private $name = "";			// name of the cookie

		/**
		 * Constructor
		 *
		 * @param string $strName		name of the cookie
		 */
		
		public function __construct($strName)
		{
			$this->name = $strName;
			
			if ( array_key_exists($strName, $_COOKIE) )
			{
				$this->cookie = $_COOKIE[$strName];
			}
		}
		
		/**
		 * Determine whether the specified record is alredy in the cookie
		 *
		 * @param string $strRecord		the record identifier
		 * @return bool					true if the record is missing from the cookie
		 */
		
		public function isAdd($strRecord)
		{
			if ( strstr($this->cookie,  $strRecord))
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		
		/**
		 * Will update the cookie depending, either adding the record or deleting it, depending
		 * on whether the record is already present in the cookie
		 *
		 * @param string $strRecord			record identifier
		 * @param string $strDelimiter		character delimiting entries in the cookie
		 * @param bool $bolDelete			[optional] whether to delete regardless of whether item is in cookie
		 * @return int						number of remaining records in the cookie
		 */
		
		public function updateCookie($strRecord, $strDelimiter, $bolDelete = false)
		{
			// get total number of records
		
			$iRecordsCookie = 0;
			$iRecordsCookie = count(explode($strDelimiter, $this->cookie));			
			
			if ( $this->cookie == null && $bolDelete == false)
			{
				// if there is no named cookie, must be add command, so create cookie

				$this->cookie = $strRecord . $strDelimiter;
				$iRecordsCookie = 1;
			}
			elseif ( ! $this->isAdd($strRecord) || $bolDelete == true )
			{
				// if the item already exists in the named cookie, must be delete command
				// $bolDelete boolean overide is set to true when deleting from saved records
				// area to prevent old records added from an earlier session from being added 
				// to the cookie, since their absence from the cookie is not an add action

				$this->cookie = str_replace($strRecord . $strDelimiter, "", $this->cookie);
				$iRecordsCookie--;
			}
			else
			{
				// must already be a master cookie but no record, add the new record to cookie
					
				$this->cookie .= $strRecord . $strDelimiter;
				$iRecordsCookie++;
			}
			
			setcookie($this->name, $this->cookie);
			
			return $iRecordsCookie;
		}
	}

?>
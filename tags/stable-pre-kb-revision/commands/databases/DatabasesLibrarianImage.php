<?php

/**
 * Display a single 'subject' in Xerxes, which is an inlined display of a subcategories
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_DatabasesLibrarianImage extends Xerxes_Command_Databases
{
	public function doExecute()
	{
		$url = $this->request->getProperty("url");
		$size = $this->registry->getConfig("LIBRARIAN_IMAGE_SIZE", false, 150);
		$domains = $this->registry->getConfig("LIBRARIAN_IMAGE_DOMAINS", false);
		
		// images can only come from these domains, for added security
		
		if ( $domains != null )
		{
			$bolPassed = Xerxes_Framework_Parser::withinDomain($url,$domains);
			
			if ( $bolPassed == false )
			{
				throw new Exception("librarian image not allowed from that domain");	
			}
		}
		
		if ( ! function_exists("gd_info") )
		{
			$this->request->setRedirect($url);
			return 1;
		}
		else
		{
			$image_string = file_get_contents($url);
			
			header("Content-type: image/jpg");
			
			if ( $image_string == "")
			{
				createblank();
			}
			else
			{
				// convert to a thumbnail
				
				$original = imagecreatefromstring($image_string);
					
				if ( $original == false )
				{
					createblank();
					exit;
				}
					
				$old_x = imagesx($original);
				$old_y = imagesy($original);
				
				if ($old_x > $old_y) 
				{
					$thumb_w = $size;
					$thumb_h = $old_y*($size/$old_x);
				}
				if ($old_x < $old_y) 
				{
					$thumb_w = $old_x*($size/$old_y);
					$thumb_h = $size;
				}
				if ($old_x == $old_y) 
				{
					$thumb_w = $size;
					$thumb_h = $size;
				}
					
				$thumb = imagecreatetruecolor($thumb_w,$thumb_h);
					
				imagecopyresampled($thumb,$original,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);
				imagejpeg($thumb, null, 100);
	
				imagedestroy($thumb); 
				imagedestroy($original);
			}
			
			return 1;
		}
	}

	function createblank()
	{
		$blank = imagecreatetruecolor(1,1);
		imagejpeg($blank);
	}
}
?>
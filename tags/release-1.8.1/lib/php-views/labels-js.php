<?php
/**

 author: David Walker
 copyright: 2010 California State University
 version: $Id$
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
*/
?>
xerxes_labels = new Array();
<?php 
	$labels = $objXml->getElementsByTagName("variable");	
	foreach ( $labels as $label )
	{
		$name = $label->getAttribute("name");
		$value = $label->nodeValue;
		$value = str_replace('"', '\\"', $value);
		$value = str_replace("\n", " ", $value);
		
		echo "xerxes_labels['$name'] = \"$value\";\n";
	}
?>
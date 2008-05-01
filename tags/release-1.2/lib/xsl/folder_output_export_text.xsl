<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2007 California State University
 version 1.1
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->


<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl">
<xsl:include href="includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

<xsl:template match="/folder">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="username" 	select="request/session/username" />
	<xsl:variable name="sort" 		select="request/sortkeys" />
	
	<form action="{$base_url}/" method="get">
    <input type="hidden" name="base" value="folder" />
	<input type="hidden" name="action" value="export" />
	<input type="hidden" name="type" value="text" />
	<input type="hidden" name="username" value="{$username}" />
	
	<div id="folderArea">
		
		<h2>My Saved Records</h2>
		
		<div class="folderOptions">
	
			<xsl:call-template name="folder_options" />
  
	  		<div class="folderOutputOptions">
	  	
	  			<h3>Text File</h3>
	  			
		  	  	<table cellpadding="5">
				<tr>
					<td align="right" valign="top">Export</td>
					<td>
					  <input name="items" type="radio" value="all" checked="checked" />
					  all of my saved records<br />
					  <input name="items" type="radio" value="selected" />
					  only the records I have selected below
					</td>
				</tr>
				<tr>
				  <td align="right" valign="top">file name: </td>
				  <td><input name="file_name" type="text" id="file_name" /></td>
				</tr>
				<tr>
				  <td align="right" valign="top"> </td>
				  <td><input type="submit" name="Submit" value="Download" /></td>
				  </tr>	
				</table>
			</div>
	
	  	</div>
	  
		<xsl:call-template name="folder_brief_results" />
		
	</div>

	</form>
	
</xsl:template>
</xsl:stylesheet>

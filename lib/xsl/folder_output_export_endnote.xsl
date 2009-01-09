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

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="username" 	select="request/session/username" />
	<xsl:variable name="sort" 		select="request/sortkeys" />

	<form action="{$base_url}/" method="get">
    <input type="hidden" name="base" value="folder" />
	<input type="hidden" name="action" value="export" />
	<input type="hidden" name="username" value="{$username}" />
	
	<div id="folderArea">
      <div id="sidebar_float" class="sidebar_float">
        <xsl:call-template name="account_sidebar"/>
      </div>
      
	  	<xsl:call-template name="folder_header" />
	  
	  	<div class="folderOptions">
		  
			<h2 class="folderOptionHeader">Download to Endnote, Zotero, etc.</h2>
			
			<xsl:call-template name="folder_export_options" />
			
			<fieldset class="folderExportSet">
			<legend>Download</legend>
			
			<ul class="folderExportSelections">
				<li>
					<input name="format" type="radio" value="bibliographic" checked="checked" id="bibliographic" />
					<label for="bibliographic"> directly into Endnote, Zotero, or other citation management application</label>
				</li>
				<li>
					<input name="format" type="radio" value="ris-file" id="ris" />
					<label for="ris"> to a file I will import myself</label>
				</li>
			</ul>
			
			</fieldset>
			
			<div class="folderExportSubmit">
				<input type="submit" name="Submit" value="Download" />
			</div>

		</div>
		
		<xsl:call-template name="folder_brief_results" />
		
	</div>

	</form>
	
</xsl:template>
</xsl:stylesheet>

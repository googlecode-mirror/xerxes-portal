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
	
	<xsl:variable name="username" 		select="request/session/username" />
	<xsl:variable name="sort" 			select="request/sortkeys" />

	<form action="{$base_url}/" method="get">
    <input type="hidden" name="base" value="folder" />
	<input type="hidden" name="action" value="email" />
	<input type="hidden" name="username" value="{$username}" />
	
	<div id="folderArea">
		
    <div id="sidebar_float" class="sidebar_float">
      <xsl:call-template name="account_sidebar"/>
    </div>  
  
		<xsl:call-template name="folder_header" />
		
		<div class="folderOptions">
				
			<h2 class="folderOptionHeader">Email</h2>
			
			<xsl:if test="request/message = 'done'">
				<div class="folderEmailSuccess">Email successfully sent</div>
			</xsl:if>
			
			<xsl:call-template name="folder_export_options" />
			
			<fieldset class="folderExportSet">
				<legend>Email Options</legend>

				<table class="folderEmailOptionsTable">
					<tr>
						<td class="folderEmailAttribute">email address:</td>
						<td>
							<input name="email" type="text" id="email">
								<xsl:attribute name="value"><xsl:value-of select="//logged_in_user/email_addr"/></xsl:attribute>
							</input>
						</td>
					</tr>
					<tr>
						<td class="folderEmailAttribute">subject:</td>
						<td><input name="subject" type="text" id="subject" /></td>
					</tr>
					<tr>
						<td class="folderEmailAttribute">notes:</td>
						<td><textarea rows="4" name="notes" cols="40" id="notes"></textarea></td>
					</tr>
				</table>
				
			</fieldset>
			
			<div class="folderExportSubmit">
				<input type="submit" name="Submit" value="Send" />
			</div>
			
		</div>
		
		<xsl:call-template name="folder_brief_results" />
		
	</div>
	</form>
	
</xsl:template>
</xsl:stylesheet>

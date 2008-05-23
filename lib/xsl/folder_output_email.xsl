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
	
	<xsl:variable name="username" 		select="request/session/username" />
	<xsl:variable name="sort" 			select="request/sortkeys" />

	<form action="{$base_url}/" method="get">
    <input type="hidden" name="base" value="folder" />
	<input type="hidden" name="action" value="email" />
	<input type="hidden" name="username" value="{$username}" />
	
	<div id="folderArea">
		
		<h2>My Saved Records</h2>
		
		<div class="folderOptions">
			
			<xsl:call-template name="folder_options" />
			
			<div class="folderOutputOptions">
				
				<h3>Email</h3>
				
				<xsl:if test="request/message = 'done'">
					<div class="folderEmailSuccess">Email successfully sent</div>
				</xsl:if>
				
				<table cellpadding="5">
					<tr>
						<td align="right" valign="top">Send</td>
						<td>
						<input name="items" type="radio" value="all" checked="checked" />
						all of my saved records<br />
						<input name="items" type="radio" value="selected" />
						only the records I have selected below
						</td>
					</tr>
					<tr>
						<td align="right">to:</td>
						<td>
						<input name="email" type="text" id="email">
              <xsl:attribute name="value"><xsl:value-of select="//logged_in_user/email_addr"/></xsl:attribute>
            </input>
						</td>
					</tr>
					<tr>
						<td align="right"> subject: </td>
						<td><input name="subject" type="text" id="subject" /></td>
					</tr>
					<tr>
						<td align="right" valign="top">notes:</td>
						<td><textarea rows="4" name="notes" cols="40" id="notes"></textarea></td>
					</tr>
					<tr>
						<td align="right" valign="top"> </td>
						<td><input type="submit" name="Submit" value="Send" /></td>
					</tr>
				</table>
			</div>
		</div>
		
		<xsl:call-template name="folder_brief_results" />
		
	</div>
	</form>
	
</xsl:template>
</xsl:stylesheet>

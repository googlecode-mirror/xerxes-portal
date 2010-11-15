<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2007 California State University
 version: $Id$
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->
 
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
<xsl:import href="includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="sidebar">
	<xsl:call-template name="account_sidebar" />
</xsl:template>

<xsl:template name="breadcrumb">
	<xsl:call-template name="breadcrumb_folder" />
	<xsl:call-template name="page_name" />
</xsl:template>

<xsl:template name="page_name">
	<xsl:value-of select="$text_folder_email_pagename" />
</xsl:template>

<xsl:template name="main">
	
	<xsl:variable name="username" 		select="request/session/username" />
	<xsl:variable name="sort" 			select="request/sortkeys" />

	<form action="{$base_url}/" name="export_form" method="get">
	<input type="hidden" name="lang" value="{//request/lang}" />
    <input type="hidden" name="base" value="folder" />
	<input type="hidden" name="action" value="email" />
	<input type="hidden" name="username" value="{$username}" />

	<div id="export">

		<h1><xsl:call-template name="page_name" /></h1>
		
		<xsl:call-template name="folder_header_limit" />
		
		<!-- @todo make this a flash message -->
		
		<xsl:if test="request/message = 'done'">
			<div class="folderEmailSuccess"><xsl:copy-of select="$text_folder_email_success" /></div>
		</xsl:if>
			
		<fieldset id="export_email_options" class="exportOptions">
			<legend><xsl:copy-of select="$text_folder_email_options" /></legend>
			
			<div>
			<label for="email"><xsl:copy-of select="$text_folder_email_address" />:</label>
				<input name="email" type="text" id="email">
					<xsl:attribute name="value"><xsl:value-of select="//logged_in_user/email_addr"/></xsl:attribute>
				</input>
			</div>
			
			<div>
				<label for="subject"><xsl:copy-of select="$text_folder_email_subject" />:</label>
				<input name="subject" type="text" id="subject" />
			</div>
			
			<div>
				<label for="notes"><xsl:copy-of select="$text_folder_email_notes" />:</label>
				<textarea rows="4" name="notes" cols="40" id="notes"></textarea>
			</div>

			<div>
				<input type="submit" name="Submit" value="{$text_folder_export_send}" />
			</div>

		</fieldset>
			
		<xsl:call-template name="folder_brief_results" />
		
	</div>

	</form>
	
</xsl:template>
</xsl:stylesheet>

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
	<xsl:value-of select="$text_folder_file_pagename" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="username" 	select="request/session/username" />
	<xsl:variable name="sort" 		select="request/sortkeys" />
	
	<div id="export">

		<h1><xsl:call-template name="page_name" /></h1>
		
		<xsl:call-template name="folder_header_limit" />

		<form action="{$base_url}/" name="export_form"  method="get">
		<input type="hidden" name="lang" value="{//request/lang}" />
		<input type="hidden" name="base" value="folder" />
		<input type="hidden" name="action" value="export" />
		<input type="hidden" name="format" value="text-file" />
		<input type="hidden" name="username" value="{$username}" />
		
		
		<input id="export_single" type="submit" name="Submit" value="{$text_folder_export_download}" class="submit_export_text{$language_suffix}" />
		
		<xsl:call-template name="folder_brief_results" />
		
		</form>
	</div>
	
</xsl:template>

</xsl:stylesheet>

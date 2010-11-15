<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2009 California State University
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

<xsl:template name="page_name">
	<xsl:value-of select="//category/@name" />
</xsl:template>

<xsl:template name="breadcrumb">
	<xsl:call-template name="breadcrumb_databases" />
	<xsl:call-template name="page_name" />
</xsl:template>

<xsl:template name="sidebar">
	<xsl:call-template name="account_sidebar" />
	<xsl:call-template name="subcategories_sidebar" />
	<xsl:call-template name="snippet_sidebar" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="category_name"	select="//category/@name" />
	<xsl:variable name="request_uri"	select="//request/server/request_uri" />
	
	<!--  check if any databases are searchable (at all or for this particular user) -->

	<xsl:variable name="should_lock_nonsearchable" select=" (//request/authorization_info/affiliated = 'true' 
	or //request/session/role = 'guest')" />
	
	<xsl:variable name="show_search_subject" select="count(//category/subcategory/database/searchable_by_user[. = '1']) &gt; 0 
	or (not($should_lock_nonsearchable) and count(//category/subcategory/database/searchable[. = '1']) &gt; 0)" />

	<div id="databases_subject">

		<h1><xsl:call-template name="page_name" /></h1>
		
		<form name="form1" method="get" action="{$base_url}/" class="metasearchForm">
		<input type="hidden" name="lang" value="{//request/lang}" />
		<input type="hidden" name="base" value="metasearch" />
		<input type="hidden" name="action" value="search" />
		<input type="hidden" name="context" value="{$category_name}" />
		<input type="hidden" name="context_url" value="{$request_uri}" />
		
		<xsl:if test="$show_search_subject">
			<xsl:call-template name="search_box" />
		</xsl:if>
		
		<xsl:call-template name="subject_databases_list"/>
		
		</form>

	</div>

</xsl:template>

</xsl:stylesheet>

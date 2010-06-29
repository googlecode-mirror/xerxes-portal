<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2007 California State University
 version: $Id$
 package: Worldcat
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->
 
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">

<xsl:import href="../includes.xsl" />
<xsl:import href="worldcat.xsl" />

<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround">
		<xsl:with-param name="surround_template">none</xsl:with-param>
		<xsl:with-param name="sidebar">none</xsl:with-param>
	</xsl:call-template>
</xsl:template>

<xsl:template name="breadcrumb">
	<xsl:call-template name="breadcrumb_worldcat" />
	<xsl:call-template name="page_name" />
</xsl:template>

<xsl:template name="page_name">
	Search Results
</xsl:template>

<xsl:template name="title">
	<xsl:value-of select="//request/query" />
</xsl:template>


<xsl:template name="main">

	<xsl:variable name="source" select="request/source" />
			
	<div class="yui-gc">
		<div class="yui-u first">
			<h1><xsl:value-of select="$text_worldcat_name" /></h1>
			<xsl:call-template name="worldcat_searchbox" />
		</div>
		<div class="yui-u">
			<div id="sidebar">
				<xsl:call-template name="account_sidebar" />
			</div>
		</div>
	</div>
	
	<xsl:if test="count(//worldcat_groups/group) > 1 and $source != ''">
		
		<ul id="tabnav">
	
		<xsl:for-each select="worldcat_groups/group">
			<xsl:variable name="id" select="@id" />
			<li>
				<xsl:if test="//request/source = $id">
					<xsl:attribute name="class">here</xsl:attribute>
				</xsl:if>
				<a href="{//source_functions/source_option[@source=$id]/url}">
	
					<span><xsl:value-of select="@label" /></span>
				</a>
			</li>
		</xsl:for-each>
		
		</ul>
	
	</xsl:if>
	
	<xsl:call-template name="generic_sort_bar" />
			
	<ul id="results">
		
		<xsl:for-each select="results/records/record/xerxes_record">
			<xsl:call-template name="worldcat_result">
				<xsl:with-param name="source" select="$source" />
			</xsl:call-template>		
		</xsl:for-each>
			
	</ul>
		
	<!-- Paging Navigation -->
	<xsl:call-template name="paging_navigation" />
	
	<!-- tag input -->
	<xsl:call-template name="hidden_tag_layers" />
	
</xsl:template>

<xsl:template name="generic_no_hits">
			
	<div class="worldcatNoHits">
	
		<p class="error"><xsl:value-of select="$text_metasearch_hits_no_match" /></p>
	
		<xsl:for-each select="worldcat_groups/group">
			<xsl:variable name="id" select="@id" />
			<xsl:if test="//request/source = $id">
				<xsl:if test="following-sibling::group">
					<xsl:variable name="next" select="following-sibling::group/@id" />
					<p>Try your search in 
					<a href="{//source_functions/source_option[@source=$next]/url}"><xsl:value-of select="following-sibling::group/@label" /></a>
					</p>
				</xsl:if>
			</xsl:if>
		</xsl:for-each>
	
	</div>

</xsl:template>

</xsl:stylesheet>

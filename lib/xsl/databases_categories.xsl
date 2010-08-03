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
	<xsl:value-of select="$text_databases_category_pagename" />
</xsl:template>

<xsl:template name="breadcrumb">
	<xsl:call-template name="breadcrumb_databases" />
	<xsl:call-template name="page_name" />
</xsl:template>

<xsl:template name="sidebar">
	<xsl:call-template name="account_sidebar" />
</xsl:template>


<xsl:template name="main">

	<xsl:variable name="search_limit" select="config/search_limit" />
	<xsl:variable name="quick_search_category" select="category/@name" />
	
	<div id="databases_categories">
	
		<xsl:choose>
			<xsl:when test="$quick_search_category != ''">
				<form action="./" method="get" name="form1">
					<input type="hidden" name="base" value="metasearch" />
					<input type="hidden" name="action" value="search" />
					<input type="hidden" name="context" value="{$quick_search_category}" />
					<input type="hidden" name="context_url" value="{$base_url}" />
					<input type="hidden" name="subject">
						<xsl:attribute name="value"><xsl:value-of select="category/@normalized" /></xsl:attribute>
					</input>
					
					<div id="categories_quicksearch">
						<h1>
							<xsl:call-template name="text_databases_category_names">
								<xsl:with-param name="option" select="$quick_search_category" />
							</xsl:call-template>
						</h1>
						<p><xsl:copy-of select="$text_databases_category_quick_desc" /></p>
						<div id="search">
							<xsl:call-template name="search_box" />
						</div>
					</div>
				</form>
			</xsl:when>
			<xsl:otherwise>
				<h1><xsl:call-template name="page_name" /></h1>
			</xsl:otherwise>
		</xsl:choose>
	
		<h2><xsl:copy-of select="$text_databases_category_subject" /></h2>
		<p><xsl:copy-of select="$text_databases_category_subject_desc" /></p>
		
		<div>
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="$categories_num_columns = 2">yui-g</xsl:when>
					<xsl:otherwise>yui-gb</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:call-template name="loop_columns" />
		</div>
		
	</div>
			
</xsl:template>

<!-- 
	TEMPLATE: LOOP_COLUMNS
	
	A recursively called looping template for dynamically determined number of columns.
	produces the following logic 
	
	for ($i = $initial-value; $i<=$maxount; ($i = $i + 1)) {
		// print column
	}
-->

<xsl:template name="loop_columns">
	<xsl:param name="num_columns" select="$categories_num_columns"/>
	<xsl:param name="iteration_value">1</xsl:param>
	
	<xsl:variable name="total" select="count(categories/category)" />
	<xsl:variable name="numRows" select="ceiling($total div $num_columns)"/>

	<xsl:if test="$iteration_value &lt;= $num_columns">
		
		<div>
		<xsl:attribute name="class">
			<xsl:text>yui-u</xsl:text><xsl:if test="$iteration_value = 1"><xsl:text> first</xsl:text></xsl:if>
		</xsl:attribute>
			
			<ul>
			<xsl:for-each select="categories/category[@position &gt; ($numRows * ($iteration_value -1)) and 
				@position &lt;= ( $numRows * $iteration_value )]">
				
				<xsl:variable name="normalized" select="normalized" />
				<li><a href="{url}">
					<xsl:call-template name="text_databases_category_names">
						<xsl:with-param name="option" select="name" />
					</xsl:call-template>
				</a></li>
			</xsl:for-each>
			</ul>
		</div>
		
		<xsl:call-template name="loop_columns">
			<xsl:with-param name="num_columns" select="$num_columns"/>
			<xsl:with-param name="iteration_value"  select="$iteration_value+1"/>
		</xsl:call-template>
	
	</xsl:if>
	
</xsl:template>


</xsl:stylesheet>


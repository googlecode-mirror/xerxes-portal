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

<xsl:template name="breadcrumb">
	<xsl:call-template name="breadcrumb_folder">
		<xsl:with-param name="condition">1</xsl:with-param>
	</xsl:call-template>
	<xsl:call-template name="page_name" />
	<xsl:call-template name="results_return" />
</xsl:template>

<xsl:template name="page_name">
	<xsl:value-of select="$text_header_savedrecords" />
</xsl:template>


<xsl:template name="sidebar">

	<xsl:call-template name="account_sidebar"/>
	
	<xsl:if test="results/records/record">
	
		<div id="exports" class="box">
			<h2><xsl:copy-of select="$text_folder_header_export" /></h2>
			<ul>
				<li id="export-email"><a href="{export_functions/export_option[@id='email']/url}"><xsl:copy-of select="$text_folder_email_pagename" /></a></li>
				<li id="export-refworks"><a href="{export_functions/export_option[@id='refworks']/url}"><xsl:copy-of select="$text_folder_refworks_pagename" /></a></li>
				<li id="export-text"><a href="{export_functions/export_option[@id='text']/url}"><xsl:copy-of select="$text_folder_file_pagename" /></a></li>
				<li id="export-endnote"><a href="{export_functions/export_option[@id='endnote']/url}"><xsl:copy-of select="$text_folder_endnote_pagename" /></a></li>
			</ul>
		</div>
		
		<div id="format_limit" class="box">
			<h2><xsl:copy-of select="$text_folder_options_format" /></h2>
			<ul>
			<xsl:for-each select="format_facets/facet">
				<xsl:variable name="format_name">
					<xsl:call-template name="text_results_format">
						<xsl:with-param name="format" select="@name" />
					</xsl:call-template>
				</xsl:variable>
				<li>
					<xsl:choose>
						<xsl:when test="@name = //request/type">
							<strong><xsl:value-of select="$format_name" /></strong> ( <xsl:value-of select="text()" /> )
						</xsl:when>
						<xsl:otherwise>
							<a href="{@url}"><xsl:value-of select="$format_name" /></a> ( <xsl:value-of select="text()" /> )
						</xsl:otherwise>
					</xsl:choose>
				</li>
			</xsl:for-each>
			</ul>
		</div>
		
		<xsl:if test="$temporarySession != 'true'">
			<div id="labelsMaster" class="box">
				<xsl:call-template name="tags_display" />
			</div>
		</xsl:if>
	</xsl:if>

</xsl:template>


<xsl:template name="main">

	<xsl:variable name="username" 	select="request/session/username" />
	<xsl:variable name="sort" 		select="request/sortkeys" />

	<div id="folder_home">
		
		<xsl:call-template name="folder_header" />
		
		<xsl:choose>
		<xsl:when test="results/records/record">
		
			<div id="sort">
				<div class="yui-gd">
					<div class="yui-u first">
						<xsl:copy-of select="$text_metasearch_results_summary" />
					</div>
					<div class="yui-u">
						<xsl:if test="sort_display">
							<div id="sortOptions">
								<xsl:copy-of select="$text_results_sort_by" /><xsl:text>: </xsl:text>
								<xsl:for-each select="sort_display/option">
									<xsl:choose>
										<xsl:when test="@active = 'true'">
											<xsl:call-template name="text_results_sort_by">
												<xsl:with-param name="option" select="text()" />
											</xsl:call-template>
										</xsl:when>
										<xsl:otherwise>
											<xsl:variable name="link" select="@link" />
											<a href="{$link}">
												<xsl:call-template name="text_results_sort_by">
													<xsl:with-param name="option" select="text()" />
												</xsl:call-template>
											</a>
										</xsl:otherwise>
									</xsl:choose>
									<xsl:if test="following-sibling::option">
										<xsl:text> | </xsl:text>
									</xsl:if>
								</xsl:for-each>
							</div>
						</xsl:if>
					</div>
				</div>
			</div>
			
			<xsl:call-template name="brief_results" />
		
			<xsl:call-template name="paging_navigation" />
	
		</xsl:when>
		<xsl:otherwise>
			<div class="folderNoRecords">
				<xsl:copy-of select="$text_folder_no_records" />
				<xsl:choose>
					<xsl:when test="//request/label">
						<xsl:text> </xsl:text><xsl:copy-of select="$text_folder_no_records_for" /><xsl:text> </xsl:text><strong><xsl:value-of select="//request/label" /></strong><xsl:text>.</xsl:text>
					</xsl:when>
					<xsl:when test="//request/type">
						<xsl:text> </xsl:text><xsl:copy-of select="$text_folder_no_records_for" /><xsl:text> </xsl:text><strong><xsl:value-of select="//request/type" /></strong><xsl:text>.</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>.</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</div>
		</xsl:otherwise>
		</xsl:choose>
	</div>
	
	<!-- tag stuff -->
	
	<div id="tag_suggestions" class="autocomplete" style="display:none;"></div>
	<xsl:call-template name="safari_tag_fix" />

</xsl:template>
</xsl:stylesheet>

<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2009 California State University
 version 1.5
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
	<xsl:call-template name="surround">
		<xsl:with-param name="template">yui-t3</xsl:with-param>
	</xsl:call-template>
</xsl:template>

<xsl:template name="breadcrumb">
	<xsl:call-template name="breadcrumb_folder">
		<xsl:with-param name="condition">1</xsl:with-param>
	</xsl:call-template>
	<xsl:call-template name="page_name" />
	<xsl:call-template name="results_return" />
</xsl:template>

<xsl:template name="page_name">
	My Saved Records
</xsl:template>

<xsl:variable name="temporarySession">
	<xsl:choose>
		<xsl:when test="request/session/role = 'guest' or request/session/role = 'local'">
			<xsl:text>true</xsl:text>
		</xsl:when>
		<xsl:otherwise>
			<xsl:text>false</xsl:text>
		</xsl:otherwise>
	</xsl:choose>
</xsl:variable>

<xsl:template name="sidebar">

	<div id="sidebar">
		<xsl:call-template name="account_sidebar"/>
		
		<xsl:if test="results/records/record">
		
			<div id="exports" class="box">
				<h2>Export Records</h2>
				<ul>
					<li id="export-email"><a href="{export_functions/export_option[@id='email']/url}">Email records to yourself</a></li>
					<li id="export-refworks"><a href="{export_functions/export_option[@id='refworks']/url}">Export to Refworks</a></li>
					<li id="export-text"><a href="{export_functions/export_option[@id='text']/url}">Download to text file</a></li>
					<li id="export-endnote"><a href="{export_functions/export_option[@id='endnote']/url}">Download to Endnote, Zotero, etc.</a></li>
				</ul>
			</div>
			
			<div id="format_limit" class="box">
				<h2>Limit by Format</h2>
				<ul>
				<xsl:for-each select="format_facets/facet">
					<li>
						<xsl:choose>
							<xsl:when test="@name = //request/type">
								<strong><xsl:value-of select="@name" /></strong> ( <xsl:value-of select="text()" /> )
							</xsl:when>
							<xsl:otherwise>
								<a href="{@url}"><xsl:value-of select="@name" /></a> ( <xsl:value-of select="text()" /> )
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
	</div>

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
						Results <strong><xsl:value-of select="summary/range" /></strong> of 
						<strong><xsl:value-of select="summary/total" /></strong>
					</div>
					<div class="yui-u">
						<xsl:if test="sort_display">
							sort by:<xsl:text> </xsl:text>
							<xsl:for-each select="sort_display/option">
								<xsl:choose>
									<xsl:when test="@active = 'true'">
										<strong><xsl:value-of select="text()" /></strong>
									</xsl:when>
									<xsl:otherwise>
										<xsl:variable name="link" select="@link" />
										<a href="{$link}">
											<xsl:value-of select="text()" />
										</a>
									</xsl:otherwise>
								</xsl:choose>
								<xsl:if test="following-sibling::option">
									<xsl:text> | </xsl:text>
								</xsl:if>
							</xsl:for-each>
						</xsl:if>
					</div>
				</div>
			</div>
			
			<xsl:call-template name="brief_results" />
		
			<xsl:call-template name="paging_navigation" />
	
		</xsl:when>
		<xsl:otherwise>
			<div class="folderNoRecords">
				<xsl:text>There are currently no saved records</xsl:text>
				<xsl:choose>
					<xsl:when test="//request/label">
						<xsl:text> with the label </xsl:text><strong><xsl:value-of select="//request/label" /></strong><xsl:text>.</xsl:text>
					</xsl:when>
					<xsl:when test="//request/type">
						<xsl:text> that are </xsl:text><strong><xsl:value-of select="//request/type" />s</strong><xsl:text>.</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>.</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</div>
		</xsl:otherwise>
		</xsl:choose>
	</div>
	
	<!-- @todo factor this out to includes? -->
	<!-- used by autocompleter -->
	<div class="autocomplete" id="tag_suggestions" style="display:none"></div>

</xsl:template>
</xsl:stylesheet>

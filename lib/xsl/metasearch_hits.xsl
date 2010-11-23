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
	<xsl:call-template name="breadcrumb_metasearch" />
	<xsl:call-template name="page_name" />
</xsl:template>

<xsl:template name="page_name">
	<xsl:value-of select="$text_metasearch_hits_pagename" />
</xsl:template>

<xsl:template name="sidebar">
	<xsl:call-template name="account_sidebar" />
</xsl:template>

<xsl:template name="main">

	<!-- meta-refresh set in 'header' template and onLoad in body in 'surround' template -->

	<xsl:variable name="context" 		select="results/search/context" />
	<xsl:variable name="context_url" 	select="results/search/context_url" />
	<xsl:variable name="group" 			select="request/group" />
	<xsl:variable name="progress" 		select="results/progress" />
	

	<div id="metasearch_hits">
	
		<h1><xsl:value-of select="$context" /></h1>
		
		<!-- catch a serious search error -->
		
		<xsl:choose>
			<xsl:when test="$progress = '10' and //error_code = '2007'">
				<h2 class="error"><xsl:copy-of select="$text_metasearch_hits_error" /></h2>
				<p><xsl:copy-of select="$text_metasearch_hits_error_explain" /></p>
			</xsl:when>
			<xsl:otherwise>
				
				<form action="./" method="get">
				<input type="hidden" name="lang" value="{//request/lang}" />
				<input type="hidden" name="base" value="metasearch" />
				<input type="hidden" name="action" value="search" />
				<input type="hidden" name="context" value="{$context}" />
				<input type="hidden" name="context_url" value="{$context_url}" />
			
				<xsl:call-template name="search_box" />
				
				</form>
				
				<xsl:choose>
					<xsl:when test="$progress = '10'">
					
						<xsl:variable name="in_progress">
							<xsl:for-each select="//base_info[find_status != 'DONE']">
								<xsl:text>n</xsl:text>
							</xsl:for-each>
						</xsl:variable>
						
						<h2 class="error"><xsl:copy-of select="$text_metasearch_hits_no_match" /></h2>
						
						<xsl:if test="$in_progress != ''">
							<h3><xsl:copy-of select="$text_metasearch_hits_unfinished" /></h3>
						</xsl:if>
						
					</xsl:when>
					<xsl:otherwise>
					
						<!-- let users of screen readers and opera mobile manually refresh the page -->
						
						<xsl:choose>
							<xsl:when test="($is_mobile = 1 and contains(//server/http_user_agent,'Opera')) or request/session/ada">
								<p><xsl:text>Your search is still in progress. </xsl:text></p>
								<form action="./" method="get">
									<input type="hidden" name="lang" value="{//request/lang}" />
									<input type="hidden" name="base" value="metasearch" />
									<input type="hidden" name="action" value="hits" />
									<input type="hidden" name="group" value="{//request/group}" />
									
									<input type="submit" value="Check the status of the search" />
								</form>
							</xsl:when>
							<xsl:otherwise>						
								<h2><xsl:call-template name="page_name" /></h2>
								<div id="progress"><img src="images/progress_small{$progress}.gif" alt="" /></div>
							</xsl:otherwise>
						</xsl:choose>
							
					</xsl:otherwise>
				</xsl:choose>
					
				<table>
					
					<thead>
						<tr>
							<th><xsl:copy-of select="$text_metasearch_hits_table_database" /></th>
							<th><xsl:copy-of select="$text_metasearch_hits_table_status" /></th>
							<th><xsl:copy-of select="$text_metasearch_hits_table_count" /></th>
						</tr>	
					</thead>
					
					<xsl:for-each select="//base_info">
						
					<tr>
						<td>
							<xsl:value-of select="full_name"/>
						</td>
						<td>
							<xsl:choose>
								<xsl:when test="find_status = 'DONE1' or find_status = 'DONE2' or find_status = 'DONE3'">
									<xsl:copy-of select="$text_metasearch_status_fetching" />
								</xsl:when>
								<xsl:when test="find_status = 'START'">
									<xsl:copy-of select="$text_metasearch_status_start" />
								</xsl:when>
								<xsl:when test="find_status = 'FIND' or find_status = 'FORK'">
									<xsl:copy-of select="$text_metasearch_status_started" />
								</xsl:when>
								<xsl:when test="find_status = 'FETCH'">
									<xsl:copy-of select="$text_metasearch_status_fetching" />
								</xsl:when>
								<xsl:when test="find_status = 'STOP'">
									<xsl:copy-of select="$text_metasearch_status_stopped" />
								</xsl:when>
								<xsl:when test="find_status = 'ERROR'">
									<xsl:copy-of select="$text_metasearch_status_error" />
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="find_status" />
								</xsl:otherwise>
							</xsl:choose>
						</td>
						<td class="hitCount">
							<xsl:choose>
								<xsl:when test="no_of_documents = '888888888'">
									<xsl:copy-of select="$text_metasearch_results_found" />
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="no_of_documents"/>
								</xsl:otherwise>
							</xsl:choose>
						</td>
					</tr>
					</xsl:for-each>
					
					<!-- excluded databases -->
					
					<xsl:for-each select="//excluded_dbs/database">
						<tr>
							<td><xsl:value-of select="title_display"/></td>
							<td colspan="2" class="error">
								<xsl:copy-of select="$text_metasearch_status_error" /><xsl:text>: </xsl:text>
								<xsl:choose>
									<xsl:when test="group_restriction">
										<xsl:call-template name="db_restriction_display" />
									</xsl:when>
									<xsl:when test="subscription = '1'">
										<xsl:copy-of select="$text_database_available_registered" />
									</xsl:when>
								</xsl:choose>
							</td>
						</tr>
					</xsl:for-each>
				</table>
				
			</xsl:otherwise>
		</xsl:choose>
	</div>

</xsl:template>
</xsl:stylesheet>

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
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="breadcrumb">
	<xsl:call-template name="breadcrumb_metasearch" />
	<xsl:call-template name="page_name" />
</xsl:template>

<xsl:template name="page_name">
	Search Status
</xsl:template>

<xsl:template name="sidebar">
	<div id="sidebar">
		<xsl:call-template name="account_sidebar" />
	</div>
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
				<div class="loginBox longBox">
					<p class="error">Sorry, we're having technical difficulties right now.</p>
					<p>You can still search each database individually by following the links below.</p>
					<ul>
						<xsl:for-each select="//database_links/database[link_native_home != '']">
						<xsl:variable name="metalib_id" select="@metalib_id" />
						<li>
							<a href="{link_native_home}">
								<xsl:value-of select="//base_info[base_001 = $metalib_id]/full_name" />
							</a>
						</li>
						</xsl:for-each>
					</ul>
				</div>
			</xsl:when>
			<xsl:otherwise>
				
				<form action="./" method="get">
				<input type="hidden" name="base" value="metasearch" />
				<input type="hidden" name="action" value="search" />
				<input type="hidden" name="context" value="{$context}" />
				<input type="hidden" name="context_url" value="{$context_url}" />
			
				<xsl:call-template name="search_box" />
				
				</form>
				
				<xsl:choose>
					<xsl:when test="$progress = '10'">
						<h2 class="error">Sorry, your search did not match any records</h2>
					</xsl:when>
					<xsl:otherwise>
						<h2>Search Status</h2>
						<div id="progress"><img src="images/progress_small{$progress}.gif" alt="search progress" /></div>
					</xsl:otherwise>
				</xsl:choose>
					
				<table>
					
					<thead>
						<tr>
							<th>Database</th>
							<th>Status</th>
							<th>Hits</th>
						</tr>	
					</thead>
					
					<xsl:for-each select="//base_info">
					
					<!-- variables -->
						
					<xsl:variable name="set_number" select="set_number" />
					<xsl:variable name="hits" select="number(no_of_documents)" />
					<xsl:variable name="groupID" select="//find_group_info_response/@id" />
						
					<tr>
						<td>
							<xsl:value-of select="full_name"/>
						</td>
						<td>
							<xsl:choose>
								<xsl:when test="find_status = 'DONE1' or find_status = 'DONE2' or find_status = 'DONE3'">
									<xsl:text>FETCHING</xsl:text>
								</xsl:when>
								<xsl:when test="find_status = 'START'">
									<xsl:text>START</xsl:text>
								</xsl:when>
								<xsl:when test="find_status = 'FIND' or find_status = 'FORK'">
									<xsl:text>STARTED</xsl:text>
								</xsl:when>
								<xsl:when test="find_status = 'FETCH'">
									<xsl:text>FETCHING</xsl:text>
								</xsl:when>
								<xsl:when test="find_status = 'STOP'">
									<xsl:text>STOPPED</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="find_status" />
								</xsl:otherwise>
							</xsl:choose>
						</td>
						<td class="hitCount">
							<xsl:choose>
								<xsl:when test="no_of_documents = '888888888'">
									<xsl:text>results found</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="$hits"/>
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
								ERROR:
								<xsl:choose>
									<xsl:when test="group_restriction">
										<xsl:call-template name="db_restriction_display" />
									</xsl:when>
									<xsl:when test="subscription = '1'">
										Only available to registered users.
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

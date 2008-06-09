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

<xsl:template match="/metasearch">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="main">

	<!-- meta-refresh set in 'header' template and onLoad in body in 'surround' template -->

	<xsl:variable name="context" 		select="results/search/context" />
	<xsl:variable name="context_url" 	select="results/search/context_url" />
	<xsl:variable name="group" 		select="request/group" />
	<xsl:variable name="progress" 		select="results/progress" />
	
	<!-- catch a serious search error -->
	
	<xsl:choose>
	<xsl:when test="$progress = '10' and //error_code = '2007'">
		<div class="loginBox" style="width: 400px">
			<p class="error">Sorry, we're having technical difficulties right now.</p>
			<p>You can still search each database individually by following the links below.</p>
			<ul style="margin: 20px">
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
	
		<div id="container">
		
			<div id="searchArea">
				<div class="subject">
					<h1><xsl:value-of select="$context" /></h1>
				</div>
				
				<div id="search">
					<xsl:call-template name="search_box" />
				</div>
			</div>
				
			<div id="sidebar"> </div>	
			
		</div>
				

    
		<div id="resultsArea">      
			<xsl:choose>
				<xsl:when test="$progress = '10'">
					<p class="error" style="margin: 30px">Sorry, your search did not match any records</p>
				</xsl:when>
				<xsl:otherwise>
					<h3>Searching</h3>
					<p><img src="images/progress_small{$progress}.gif" alt="search progress" /></p>				
				</xsl:otherwise>
			</xsl:choose>
			
			<table cellpadding="5">
			
				<xsl:for-each select="//base_info">
				
				<!-- variables -->
					
				<xsl:variable name="set_number" select="set_number" />
				<xsl:variable name="hits" 	select="number(no_of_documents)" />
				<xsl:variable name="groupID" 	select="//find_group_info_response/@id" />
					
				<tr>
					<td>
						<!--
							<xsl:choose>
								<xsl:when test=" ( find_status = 'DONE' or find_status = 'DONE1' or find_status = 'DONE2') 
													and no_of_documents != '000000000'">
									<a href="metasearch?action=results&amp;group={$groupID}&amp;resultSet={$set_number}">		
									<xsl:value-of select="full_name"/></a>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="full_name"/>
								</xsl:otherwise>
							</xsl:choose>
						-->
						<xsl:value-of select="full_name"/>
						
					</td>
					<td>
							<xsl:choose>		
								<xsl:when test="find_status = 'DONE1' or find_status = 'DONE2'">
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
							
							(
							<xsl:choose>
								<xsl:when test="no_of_documents = '888888888'">
									<xsl:text>results found</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="$hits"/>
								</xsl:otherwise>
							</xsl:choose>
							)
						</td>
					</tr>
				</xsl:for-each>
			</table>
			
			<xsl:if test="//excluded_dbs/database">
				<div class="box">
					<h3 class="error"><img src="{$base_url}/images/warning.gif" alt="Warning" /> Warning: Databases Left Out</h3>
					<p>Some selected databases are not available to you, and are not included in the search:</p>
					<xsl:call-template name="excluded_db_list" />
				</div>
			</xsl:if>
			
		</div>
		</form>
	</xsl:otherwise>
	</xsl:choose>
	
</xsl:template>
</xsl:stylesheet>

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
	xmlns:php="http://php.net/xsl">
<xsl:import href="includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="breadcrumb">
	<xsl:call-template name="breadcrumb_metasearch" />
	<xsl:call-template name="page_name" />
</xsl:template>

<xsl:template name="page_name">
	<xsl:value-of select="/*/results/database" />
</xsl:template>

<xsl:template name="title">
	<xsl:value-of select="/*/results/database" />
	<xsl:text> ( </xsl:text>
	<xsl:for-each select="//search/pair">
		<xsl:value-of select="query" />
		<xsl:text> </xsl:text>
	</xsl:for-each>
	<xsl:text>)</xsl:text>
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="this_page"			select="php:function('urlencode', string(request/server/request_uri))" />
	<xsl:variable name="context" 			select="results/search/context" />
	<xsl:variable name="context_url" 		select="results/search/context_url" />
	<xsl:variable name="group" 				select="request/group" />
	<xsl:variable name="this_result_set"	select="request/resultset" />
	
	<xsl:variable name="merge_bug">
		<xsl:choose>	
			<xsl:when test="//search_and_link = '' or 
				( not(results/records/record/xerxes_record) and not(//search_and_link) )">true</xsl:when>
			<xsl:otherwise>false</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<div id="metasearch_results">
	
		<h1><xsl:value-of select="$context" /></h1>
		
		<form action="./" method="get">
		<input type="hidden" name="base" value="metasearch" />
		<input type="hidden" name="action" value="search" />
		<input type="hidden" name="context" value="{$context}" />
		<input type="hidden" name="context_url" value="{$context_url}" />
			
			<xsl:call-template name="search_box" />
		</form>
		
		<h2><xsl:value-of select="results/database" /></h2>
		
		<xsl:choose>
			<xsl:when test="results/facet_name != ''">
				<h3>
					<a href="./?base=metasearch&amp;action=results&amp;group={$group}&amp;resultSet={$this_result_set}">
						<img src="{$base_url}/images/delete.gif" alt="remove limit" />
					</a>
					Limit: <xsl:value-of select="results/facet_name" />
				</h3>
			</xsl:when>
		</xsl:choose>

		<xsl:if test="$merge_bug = 'false'">
			<div id="sort">
				<div class="yui-gd">
					<div class="yui-u first">
						Results <strong><xsl:value-of select="summary/range" /></strong> of 
						<strong><xsl:value-of select="summary/total" /></strong>
					</div>
					<div class="yui-u">
						<xsl:if test="sort_display and not(results/facet_name)">						
							sort by:
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
						&#160;
					</div>
				</div>
			</div>
		</xsl:if>
		
		<xsl:if test="$merge_bug = 'true'">

			<h2 class="error">Sorry, there was an error.</h2> 
			<p>
			Please <a href="{request/server/request_uri}">try again</a>
			or select an individual set of results to the right.
			</p>
		
		</xsl:if>
		
		<xsl:if test="//search_and_link != ''">
		
			<xsl:variable name="database_metalib_id">
				<xsl:for-each select="//base_info">
					<xsl:if test="set_number = $this_result_set">
						<xsl:value-of select="base_001" />
					</xsl:if>
				</xsl:for-each>
			</xsl:variable>
		
			<div class="resultsSearchLink">
				<a target="{$link_target}">
					<xsl:attribute name="href">
						<xsl:text>./?base=databases&amp;action=proxy</xsl:text>
						<xsl:text>&amp;database=</xsl:text>
						<xsl:value-of select="$database_metalib_id" />
						<xsl:text>&amp;url=</xsl:text>
						<xsl:value-of select="php:function('urlencode', string(//search_and_link))" />
					</xsl:attribute>
					View results at <xsl:value-of select="results/database" />
				</a>
			</div>	
		</xsl:if>
		
		<xsl:call-template name="brief_results" />
		
		<!-- paging Navigation -->
		
		<xsl:if test="$merge_bug = 'false'">
			<xsl:call-template name="paging_navigation" />
		</xsl:if>
		
		<!-- @todo make this a template
			used by ajax to add a tag input form after record is saved -->
		
		<div id="tag_suggestions" class="autocomplete" style="display:none;"></div>

		<div id="template_tag_input" class="results_label resultsFullText" style="display:none;">
			<xsl:call-template name="tag_input">
				<xsl:with-param name="id">template</xsl:with-param>
			</xsl:call-template> 
		</div>
	
		<div id="labelsMaster" class="folderOutput" style="display: none">
			<xsl:call-template name="tags_display" />
		</div>

	</div>
		
</xsl:template>
		
<xsl:template name="sidebar">

	<xsl:variable name="group" 				select="request/group" />
	<xsl:variable name="this_result_set"	select="request/resultset" />

	<div id="sidebar">
		
		<xsl:call-template name="account_sidebar" />
		
		<!-- database list -->
		
		<div class="box">
			
			<h2>Search results</h2>
			
			<ul id="merged">
			
			<!-- merged set -->
			
			<xsl:for-each select="//base_info[base = 'MERGESET']">
				<li>
					<xsl:choose>
						<xsl:when test="set_number = $this_result_set">
							<strong>Top Results</strong>
						</xsl:when>
						<xsl:otherwise>
							<a href="./?base=metasearch&amp;action=results&amp;group={$group}&amp;resultSet={set_number}">Top Results</a>
						</xsl:otherwise>
					</xsl:choose>
					<xsl:text> ( </xsl:text>
					<xsl:value-of select="number(no_of_documents)" />
					<xsl:text> )</xsl:text>
				</li>
			</xsl:for-each>
			
			</ul>
			
			<!-- databases -->
					
			<ul>

			<xsl:for-each select="//base_info[base != 'MERGESET']">

				<xsl:variable name="set_number" select="set_number" />
				<xsl:variable name="hits">
					<xsl:choose>
						<xsl:when test="no_of_documents = '888888888'">
							<xsl:text>0</xsl:text>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="number(no_of_documents)" />
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				
				<li>
					<xsl:choose>
						<xsl:when test=" ( find_status = 'DONE' or find_status = 'DONE1' or find_status = 'DONE2') 
											and no_of_documents != '000000000'">
							<xsl:choose>
							<xsl:when test="$set_number = $this_result_set">
								<strong>
									<xsl:call-template name="database_name">
										<xsl:with-param name="database" select="full_name" />
									</xsl:call-template>
								</strong>
							</xsl:when>
							<xsl:otherwise>
								<a href="./?base=metasearch&amp;action=results&amp;group={$group}&amp;resultSet={$set_number}">
									<xsl:call-template name="database_name">
										<xsl:with-param name="database" select="full_name" />
									</xsl:call-template>
								</a>
							</xsl:otherwise>
							</xsl:choose>
						</xsl:when>
						<xsl:otherwise>
							<xsl:call-template name="database_name">
								<xsl:with-param name="database" select="full_name" />
							</xsl:call-template>
						</xsl:otherwise>
					</xsl:choose>
					<span class="nonBreaking">
					<xsl:text> ( </xsl:text>
					<xsl:choose>
						<xsl:when test="no_of_documents = '888888888'">
							<xsl:text>results found</xsl:text>
						</xsl:when>
						<xsl:when test="find_status = 'DONE' or find_status = 'DONE1' or find_status = 'DONE2'">
							<xsl:value-of select="$hits"/>
						</xsl:when>
						<xsl:otherwise>
							ERROR
						</xsl:otherwise>
					</xsl:choose>
					<xsl:text> )</xsl:text>
					</span>
				</li>
					
			</xsl:for-each>
			
			<!-- databases excluded -->
			
			<xsl:for-each select="//excluded_dbs/database">
				<li>
					<xsl:value-of select="title_display"/>
					<xsl:text> (</xsl:text>
					ERROR: 
					<xsl:choose>
						<xsl:when test="group_restriction">
							<xsl:call-template name="db_restriction_display" />
						</xsl:when>
						<xsl:when test="subscription = '1'">
							Only available to registered users.
						</xsl:when>
					</xsl:choose>
					<xsl:text>)</xsl:text>
				</li>
			</xsl:for-each>
			
			</ul>

		</div>
		
		<!-- facets -->
		<xsl:call-template name="facets" />

	</div>
	
</xsl:template>

<!--
	TEMPLATE: database_name
	strips off the platform (e.g., ebsco, oclc, proquest) from the database name for a tighter
	display here; assumes you've added that in parentheses, as is common in metalib kb
-->

<xsl:template name="database_name">
	<xsl:param name="database" />
	
	<xsl:choose>
		<xsl:when test="contains($database, '(')">
			<xsl:value-of select="substring-before($database, '(')" />
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="$database" />
		</xsl:otherwise>
	</xsl:choose>
	
</xsl:template>

</xsl:stylesheet>

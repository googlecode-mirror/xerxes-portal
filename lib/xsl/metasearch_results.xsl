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
	<xsl:choose>
		<xsl:when test="/*/results/database = 'Top Results'">
			<xsl:copy-of select="$text_metasearch_top_results" />
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="/*/results/database" />
		</xsl:otherwise>
	</xsl:choose>
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
	
	<h2><xsl:call-template name="page_name" /></h2>
	
	<xsl:choose>
		<xsl:when test="results/facet_name != ''">
			<h3>
				<a href="{//base_info[base = 'MERGESET']/url}">
					<img src="{$base_url}/images/delete.gif" alt="{$text_results_hint_remove_limit}" />
				</a>
				<xsl:copy-of select="$text_metasearch_results_limit" /><xsl:text>: </xsl:text><xsl:value-of select="results/facet_name" />
			</h3>
		</xsl:when>
	</xsl:choose>

	<xsl:if test="$merge_bug = 'false' and not(//search_and_link)">
		<div id="sort">
			<div class="yui-gd">
				<div class="yui-u first">
					<xsl:copy-of select="$text_metasearch_results_summary" />
				</div>
				<div class="yui-u">
					<xsl:choose>
						<xsl:when test="sort_display and not(results/facet_name)">
							<div id="sortOptions">
								<xsl:copy-of select="$text_results_sort_by" /><xsl:text>: </xsl:text>
								<xsl:for-each select="sort_display/option">
									<xsl:choose>
										<xsl:when test="@active = 'true'">
											<strong>
											<xsl:call-template name="text_results_sort_by">
												<xsl:with-param name="option" select="text()" />
											</xsl:call-template>
											</strong>
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
						</xsl:when>
						<xsl:otherwise>&#160;</xsl:otherwise>
					</xsl:choose>
				</div>
			</div>
		</div>
	</xsl:if>
	
	<xsl:if test="$merge_bug = 'true'">

		<h2 class="error"><xsl:copy-of select="$text_metasearch_results_error_merge_bug" /></h2> 
		<p><xsl:copy-of select="$text_metasearch_results_error_merge_bug_try_again" /></p>
	
	</xsl:if>
	
	<xsl:if test="//search_and_link and $merge_bug = 'false'">
	
		<xsl:choose>
			<xsl:when test="//search_and_link_type = 'POST'">
				
				<form method="post" action="{//post/form/@action}" target="{$link_target}">
					<xsl:for-each select="//post/form/input">
						<input name="{@name}" value="{@value}" type="hidden" />
					</xsl:for-each>
					
					<input type="submit">
						<xsl:attribute name="value">
							<xsl:copy-of select="$text_metasearch_results_native_results" /><xsl:text> </xsl:text>
							<xsl:value-of select="results/database" />
						</xsl:attribute>
					</input>	
				</form>
				
			</xsl:when>
			<xsl:otherwise>
	
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
						<xsl:copy-of select="$text_metasearch_results_native_results" /><xsl:text> </xsl:text><xsl:value-of select="results/database" />
					</a>
				</div>	
				
			</xsl:otherwise>
		</xsl:choose>
		
	</xsl:if>
	
	<xsl:call-template name="brief_results" />
	
	<!-- paging navigation -->
	
	<xsl:if test="$merge_bug = 'false' and not(//search_and_link)">
		<xsl:call-template name="paging_navigation" />
	</xsl:if>
	
	<!-- tag input -->
	
	<xsl:call-template name="hidden_tag_layers" />

</div>
	
</xsl:template>
	
<xsl:template name="sidebar">
	<xsl:call-template name="account_sidebar" />
	<xsl:call-template name="results_sidebar" />
</xsl:template>
	
<xsl:template name="results_sidebar">
	<xsl:call-template name="results_sidebar-merged_set" />
	<xsl:call-template name="results_sidebar-individual_databases" />
	<xsl:call-template name="facets" />
</xsl:template>

<xsl:template name="results_sidebar-merged_set">
	<xsl:variable name="group" 				select="request/group" />
	<xsl:variable name="this_result_set"	select="request/resultset" />

	<xsl:if test="//base_info[base = 'MERGESET']">
	
		<div class="box merge_set">
			
			<h2><xsl:copy-of select="$text_metasearch_results_search_results" /></h2>
			
			<ul id="merged">
			
			<!-- merged set -->
			
			<xsl:for-each select="//base_info[base = 'MERGESET']">
				<li>
					<xsl:choose>
						<xsl:when test="set_number = $this_result_set and not(//request/action = 'facet')">
							<strong><xsl:copy-of select="$text_metasearch_top_results" /></strong>
						</xsl:when>
						<xsl:otherwise>
							<a href="{url}"><xsl:copy-of select="$text_metasearch_top_results" /></a>
						</xsl:otherwise>
					</xsl:choose>
					<xsl:text> ( </xsl:text>
					<xsl:value-of select="no_of_documents" />
					<xsl:text> )</xsl:text>
				</li>
			</xsl:for-each>
			
			</ul>
			
		</div>
	
	</xsl:if>
</xsl:template>
	
<xsl:template name="results_sidebar-individual_databases">
	<xsl:variable name="group" 				select="request/group" />
	<xsl:variable name="this_result_set"	select="request/resultset" />

	<xsl:if test="count(//base_info) > 1 or count(//excluded_dbs/database) > 0">
	
		<div class="box database_sets">
			
			<h2><xsl:copy-of select="$text_metasearch_results_by_db" /></h2>
					
			<ul>

			<xsl:for-each select="//base_info[base != 'MERGESET']">

				<xsl:variable name="set_number" select="set_number" />
				<xsl:variable name="hits">
					<xsl:choose>
						<xsl:when test="no_of_documents = '888888888'">
							<xsl:text>0</xsl:text>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="no_of_documents" />
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				
				<li>
					<xsl:choose>
						<xsl:when test=" ( find_status = 'DONE' or find_status = 'DONE1' or find_status = 'DONE2') 
											and no_of_documents != '0'">
							<xsl:choose>
							<xsl:when test="$set_number = $this_result_set">
								<strong>
									<xsl:call-template name="database_name">
										<xsl:with-param name="database" select="full_name" />
									</xsl:call-template>
								</strong>
							</xsl:when>
							<xsl:otherwise>
								<a href="{url}">
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
					<xsl:text> </xsl:text>
					<span class="nonBreaking">
					<xsl:text>( </xsl:text>
					<xsl:choose>
						<xsl:when test="no_of_documents = '888888888'">
							<xsl:copy-of select="$text_metasearch_results_found" />
						</xsl:when>
						<xsl:when test="find_status = 'DONE' or find_status = 'DONE1' or find_status = 'DONE2'">
							<xsl:value-of select="$hits"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:copy-of select="$text_metasearch_status_error" />
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
					<xsl:copy-of select="$text_metasearch_status_error" /><xsl:text>: </xsl:text>
					<xsl:choose>
						<xsl:when test="group_restriction">
							<xsl:call-template name="db_restriction_display" />
						</xsl:when>
						<xsl:when test="subscription = '1'">
							<xsl:copy-of select="$text_database_available_registered" />
						</xsl:when>
					</xsl:choose>
					<xsl:text>)</xsl:text>
				</li>
			</xsl:for-each>
			
			</ul>

		</div>
		
	</xsl:if>
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

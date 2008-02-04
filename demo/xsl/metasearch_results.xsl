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

	<xsl:variable name="this_page"			select="php:function('urlencode', string(request/server/request_uri))" />
	<xsl:variable name="context" 			select="results/search/context" />
	<xsl:variable name="context_url" 		select="results/search/context_url" />
	<xsl:variable name="group" 			select="request/group" />
	<xsl:variable name="this_result_set"		select="request/resultset" />
    
	<xsl:variable name="facet_return">
		<xsl:value-of select="php:function('urlencode', concat('./?base=metasearch&amp;action=results&amp;group=', $group, '&amp;resultSet=', $this_result_set))" />
	</xsl:variable>
	
	<xsl:variable name="merge_bug">
		<xsl:choose>	
			<xsl:when test="//search_and_link = '' or 
				( not(results/records/record/xerxes_record) and not(//search_and_link) )">true</xsl:when>
			<xsl:otherwise>false</xsl:otherwise>
		</xsl:choose>
	
	</xsl:variable>

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
			
		<div id="sidebar">
			
		</div>	
	</div>
	
	<div id="resultsArea">
			
		<div id="resultsOptions">
			
			<div class="box">
	
				<h2 class="sidebar-title">Search Results</h2>
				<xsl:for-each select="//base_info">
					<xsl:if test="base = 'MERGESET'">
						<ul class="hitsList">
						<xsl:variable name="set_number" select="set_number" />
						
						<li class="hitsListBullet">
							
							<xsl:choose>
								<xsl:when test="$set_number = $this_result_set">
									<strong>Top Results</strong>
								</xsl:when>
								<xsl:otherwise>
									<a href="./?base=metasearch&amp;action=results&amp;group={$group}&amp;resultSet={$set_number}">Top Results</a>
								</xsl:otherwise>
							</xsl:choose>
							<xsl:text> ( </xsl:text>
							<xsl:value-of select="number(no_of_documents)" />
							<xsl:text> )</xsl:text>
						</li>
						</ul>
					</xsl:if>
				</xsl:for-each>
				
				
				<h2 class="sidebar-title">Limit results by database: </h2>
				<ul class="hitsList">
				<xsl:for-each select="//base_info">
					<xsl:if test="base != 'MERGESET'">
								
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
						
						<li class="hitsListBullet">
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
						
						</li>
					</xsl:if>
				</xsl:for-each>
				</ul>

			</div>
			
			<!-- FACETS -->
			
			<xsl:if test="//cluster_facet and results/database = 'Top Results'">
				
				<div class="box">
					<h2 class="sidebar-title">Limit top results by:</h2>
					<xsl:for-each select="//cluster_facet">
					
						<xsl:variable name="name" select="@name" />
						
						<xsl:if test="//cluster_facet[@name = $name]/node[node_no_of_docs > 2 and @name != 'Other']">
						
							<xsl:variable name="facet_number" select="position()" />
							<h4>
								<xsl:value-of select="@name" />
								<!-- ( <xsl:value-of select="number(no_of_nodes)" /> ) -->
							</h4>
							
							<ul>
							
							

							<xsl:choose>
								<xsl:when test="@name != 'DATE'">
									<xsl:for-each select="node[node_no_of_docs > 2 and @name != 'Other' and @name != 'Target not returning the record']">
										
										<xsl:call-template name="facet_display">
											<xsl:with-param name="group" select="$group" />
											<xsl:with-param name="this_result_set" select="$this_result_set" />
											<xsl:with-param name="facet_number" select="$facet_number" />
											<xsl:with-param name="facet_return" select="$facet_return" />
										</xsl:call-template>
									</xsl:for-each>
								</xsl:when>
								<xsl:when test="@name = 'DATE'">
									<xsl:for-each select="node[node_no_of_docs > 2 and @name != 'Other' and @name != 'Target not returning the record']">
										<xsl:sort select="@name" order="descending" />
										<xsl:call-template name="facet_display">
											<xsl:with-param name="group" select="$group" />
											<xsl:with-param name="this_result_set" select="$this_result_set" />
											<xsl:with-param name="facet_number" select="$facet_number" />
											<xsl:with-param name="facet_return" select="$facet_return" />
										</xsl:call-template>
									</xsl:for-each>
								</xsl:when>

							</xsl:choose>
							
							</ul>
						</xsl:if>
					</xsl:for-each>
				</div>
			</xsl:if>
		</div>
		
		<div class="results">
			
			<h2><xsl:value-of select="results/database" /></h2>
			
			<xsl:choose>
				<xsl:when test="results/facet_name != ''">
					<div class="resultsFacetNav">
						<a href="./?base=metasearch&amp;action=results&amp;group={$group}&amp;resultSet={$this_result_set}">All Results</a>
						<xsl:text> &gt; </xsl:text><xsl:value-of select="results/facet_name" />
					</div>
				</xsl:when>
			</xsl:choose>
                        
			<xsl:if test="$merge_bug = 'false'">
				<div class="resultsPageOptions">
					<xsl:if test="sort_display and not(results/facet_name)">
						<div class="resultsSorting">
							sort by:
							<xsl:for-each select="sort_display/option">
								<xsl:choose>
									<xsl:when test="@active = 'true'">
										<strong><xsl:value-of select="text()" /></strong>
									</xsl:when>
									<xsl:otherwise>
										<xsl:variable name="link" select="@link" />
										<a href="{$link}" class="resultsSortLink">
											<xsl:value-of select="text()" />
										</a>
									</xsl:otherwise>
								</xsl:choose>
								<xsl:if test="following-sibling::option">
									<xsl:text> | </xsl:text>
								</xsl:if>					
							</xsl:for-each>
						</div>
					</xsl:if>
					<div class="resultsPageSummary">
						Results <strong><xsl:value-of select="summary/range" /></strong> of 
						<strong><xsl:value-of select="summary/total" /></strong>
					</div>			
				</div>
			</xsl:if>
			
			<xsl:if test="$merge_bug = 'true'">

				<div class="resultsSearchLink">	
					<p><strong>Sorry, there was an error.</strong></p> 
					<p>
					Please <a>
						<xsl:attribute name="href">
							<xsl:value-of select="request/server/request_uri" />
						</xsl:attribute>
						try again
					</a>
					or select an individual set of results to the right.
					</p>
				</div>
			
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
			
			<xsl:for-each select="results/records/record/xerxes_record">
			
				<xsl:variable name="issn" 		select="standard_numbers/issn" />
				<xsl:variable name="year" 		select="year" />
				<xsl:variable name="result_set" 	select="result_set" />
				<xsl:variable name="record_number" 	select="record_number" />

		                <xsl:variable name="this_start_number">
		                	<xsl:choose>
		                    	<xsl:when test="/metasearch/request/startrecord">
		                        	<xsl:value-of select="number(/metasearch/request/startrecord) - 1" />
		                        </xsl:when>
		                        <xsl:otherwise>0</xsl:otherwise>
		                     </xsl:choose>
		                </xsl:variable>
		                <xsl:variable name="this_record_number" select="position() + $this_start_number" />

				<div class="resultsMain">				
					
					<!-- keep the url in the current result set, unless this is a facet, in which
					     case we'll link to the record by original resultset, plus return param -->
					<xsl:choose>
						<xsl:when test="//request/action = 'facet'">
							<a href="./?base=metasearch&amp;action=record&amp;group={$group}&amp;resultSet={$result_set}&amp;startRecord={$record_number}&amp;return={$this_page}" class="resultsTitle">
								<xsl:value-of select="title_normalized" />
							</a>
						</xsl:when>
						<xsl:otherwise>
							<a href="./?base=metasearch&amp;action=record&amp;group={$group}&amp;resultSet={$this_result_set}&amp;startRecord={$this_record_number}" class="resultsTitle">
								<xsl:value-of select="title_normalized" />
							</a>
						</xsl:otherwise>
					</xsl:choose>
					
					
					<div class="resultsType">
						<xsl:value-of select="format" />
						<xsl:if test="language and language != 'English' and format != 'Video'">
							<span class="resultsLanguage"> written in <xsl:value-of select="language" /></span>
						</xsl:if>
						<xsl:if test="standard_numbers/issn and format != 'Book Review'">
							<xsl:text> </xsl:text><img src="./?base=availability&amp;action=refereed&amp;issn={$issn}" width="129" height="14" alt="" />
						</xsl:if>
					</div>			

					<div class="resultsAbstract">
						<xsl:choose>
							<xsl:when test="string-length(summary) &gt; 300">
								<xsl:value-of select="substring(summary, 1, 300)" /> . . .
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="summary" />
							</xsl:otherwise>
						</xsl:choose>
					</div>
					
					<xsl:if test="primary_author">
						<span class="resultsAuthor">
							<strong>By: </strong><xsl:value-of select="primary_author" />
						</span>				
					</xsl:if>

					<xsl:if test="year">
						<span class="resultsYear">
							<strong>Year: </strong>
							<xsl:value-of select="year" />
						</span>
					</xsl:if>
					
					<xsl:if test="journal or journal_title">
						<span class="resultsPublishing">
							<strong>Published in: </strong>
							<xsl:choose>
								<xsl:when test="journal_title">
									<xsl:value-of select="journal_title" />
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="journal" />
								</xsl:otherwise>
							</xsl:choose>
						</span>
					</xsl:if>
					
					<div class="resultsAvailability">
						
						<!-- Full-Text -->
						
						<xsl:choose>
							<xsl:when test="full_text_bool">
				
								<xsl:call-template name="full_text_links">
									<xsl:with-param name="class">resultsFullTextOption</xsl:with-param>
								</xsl:call-template>
									
							</xsl:when>
							<xsl:otherwise>
						
								<a href="./?base=metasearch&amp;action=sfx&amp;resultSet={$result_set}&amp;startRecord={$record_number}" class="resultsFullText"  target="{$link_target}" >
									<img src="./?base=availability&amp;action=fulltext&amp;issn={$issn}&amp;year={$year}" alt="check for availability" border="0"/>
								</a>
								
							</xsl:otherwise>
						</xsl:choose>
							
						<!-- Save Facility -->
							
						<span class="resultsAvailableOption">
							<img id="folder_{$result_set}{$record_number}" src="images/folder.gif" width="17" height="15" alt="" border="0" />
							<xsl:text> </xsl:text>
							<a onClick="return saveRecord('{$group}', '{$result_set}','{$record_number}');"
								href="./?base=metasearch&amp;action=save-delete&amp;group={$group}&amp;resultSet={$result_set}&amp;startRecord={$record_number}" 
								class="resultsFullText">Save this record</a>
						</span>	
								
					</div>
			
				</div>
				
			</xsl:for-each>
			
			<!-- Paging Navigation -->
			
			<xsl:if test="pager/page and $merge_bug = 'false'">
			
				<table class="resultsPager" align="center" summary="paging navigation">
					<tr>
					<xsl:for-each select="pager/page">
						<td>
						<xsl:variable name="link" select="@link" />
						<xsl:choose>
							<xsl:when test="@here = 'true'">
								<strong><xsl:value-of select="text()" /></strong>
							</xsl:when>
							<xsl:otherwise>
								<a href="{$link}">
								<xsl:choose>
									<xsl:when test="@type = 'next'">
										<xsl:attribute name="class">resultsPagerNext</xsl:attribute>
									</xsl:when>
									<xsl:otherwise>
										<xsl:attribute name="class">resultsPagerLink</xsl:attribute>
									</xsl:otherwise>
								</xsl:choose>
									<xsl:value-of select="text()" />
								</a>
							</xsl:otherwise>
						</xsl:choose>
						</td>
					</xsl:for-each>
					</tr>
				</table>
			
			</xsl:if>
		</div>
	</div>
	
	</form>
	
</xsl:template>

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


<xsl:template name="facet_display">

<xsl:variable name="node_pos" select="@position" />
	<xsl:param name="group" />
	<xsl:param name="this_result_set" />
	<xsl:param name="facet_number" />
	<xsl:param name="facet_return" />

	<xsl:if test="@node_level = 1">
		<li>
		<xsl:choose>
			<xsl:when test="//request/node = $node_pos and //request/facet = $facet_number">
				<strong><xsl:value-of select="@name" /></strong> ( <xsl:value-of select="node_no_of_docs" /> )
			</xsl:when>
			<xsl:otherwise>
				<a href="./?base=metasearch&amp;action=facet&amp;group={$group}&amp;resultSet={$this_result_set}&amp;facet={$facet_number}&amp;node={$node_pos}&amp;return={$facet_return}"><xsl:value-of select="@name" /></a>
		 		(&#160;<xsl:value-of select="node_no_of_docs" />&#160;)
			</xsl:otherwise>
		</xsl:choose>
		</li>
		
	</xsl:if>
</xsl:template>

</xsl:stylesheet>

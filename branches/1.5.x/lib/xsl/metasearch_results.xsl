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

		<ul id="results">
		
		<xsl:for-each select="results/records/record/xerxes_record">
		
			<xsl:variable name="issn" 		select="standard_numbers/issn" />
			<xsl:variable name="year" 		select="year" />
			<xsl:variable name="result_set" 	select="result_set" />
			<xsl:variable name="record_number" 	select="record_number" />
			<xsl:variable name="metalib_db_id" 	select="metalib_id" />
			<xsl:variable name="record_id">
				<xsl:value-of select="$result_set" />:<xsl:value-of select="$record_number" />
			</xsl:variable>
			
			<xsl:variable name="this_start_number">
				<xsl:choose>
					<xsl:when test="/metasearch/request/startrecord">
						<xsl:value-of select="number(/metasearch/request/startrecord) - 1" />
					</xsl:when>
					<xsl:otherwise>0</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			<xsl:variable name="this_record_number" select="position() + $this_start_number" />
			
			<li class="result">
				
				<xsl:variable name="title">
					<xsl:choose>
						<xsl:when test="title_normalized != ''">
							<xsl:value-of select="title_normalized" />
						</xsl:when>
						<xsl:otherwise>
							<xsl:text>[ No Title ]</xsl:text>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				
				<div class="resultsTitle">
					<a href="{../url_full}"><xsl:value-of select="$title" /></a>
				</div>
				
				<div class="resultsInfo">
				
					<div class="resultsType">
						<xsl:value-of select="format" />
						<xsl:if test="language and language != 'English' and format != 'Video'">
							<span class="resultsLanguage"> written in <xsl:value-of select="language" /></span>
						</xsl:if>
						
						<!-- peer reviewed -->
						
						<xsl:if test="//refereed/issn = standard_numbers/issn and format != 'Book Review'">
							<xsl:text> </xsl:text><img src="images/refereed_hat.gif" width="20" height="14" alt="" />
							<xsl:text> Peer Reviewed</xsl:text>
						</xsl:if>
					</div>
					
					<div class="resultsAbstract">
						<xsl:choose>
							<xsl:when test="string-length(summary) &gt; 300">
								<xsl:value-of select="substring(summary, 1, 300)" /> . . .
							</xsl:when>
							<xsl:when test="summary">
								<xsl:value-of select="summary" />
							</xsl:when>
							
							<!-- take from embedded text, if available -->
							
							<xsl:when test="embeddedText">
								<xsl:variable name="usefulContent" select="embeddedText/paragraph[ string-length(translate(text(), '- ', '')) &gt; 20]" />
								<xsl:value-of select="substring($usefulContent, 1, 300)" />
								<xsl:if test="string-length($usefulContent) &gt; 300">. . . </xsl:if>
							</xsl:when>
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
					
					<div class="resultsAvailability recordOptions">
						
						<!-- Full-Text -->
						
						<xsl:variable name="link_resolver_allowed" select="not(//results/search/database_links/database[@metalib_id = $metalib_db_id]/sfx_suppress = '1')" />
						
						<xsl:choose>
						
							<xsl:when test="full_text_bool">
								
								<xsl:call-template name="full_text_links">
									<xsl:with-param name="class">resultsFullTextOption</xsl:with-param>
								</xsl:call-template>
									
							</xsl:when>
							
							<xsl:when test="$link_resolver_allowed and //fulltext/issn = standard_numbers/issn">
								<a href="{../url_open}&amp;fulltext=1" target="{$link_target}" >
									<img src="{$base_include}/images/html.gif" alt="" width="16" height="16" border="0" />
									<xsl:text> </xsl:text>
									<xsl:copy-of select="$text_link_resolver_available" />
								</a>
							</xsl:when>
							
							<xsl:when test="$link_resolver_allowed">
								<a href="{../url_open}" target="{$link_target}" >
									<img src="{$base_url}/images/sfx.gif" alt="" />
									<xsl:text> </xsl:text>
									<xsl:copy-of select="$text_link_resolver_check" />
								</a>
							</xsl:when>
							
							<!-- if no direct link or link resolver, do we have an original record link? -->
							
							<xsl:when test="links/link[@type='original_record'] and    (//config/show_all_original_record_links = 'true' or //config/original_record_links/database[@metalib_id = $metalib_db_id])">
								<xsl:call-template name="record_link">
								<xsl:with-param name="type">original_record</xsl:with-param>
								<xsl:with-param name="text" select="$text_link_original_record"/>
								<xsl:with-param name="img_src" select="concat($base_url,'/images/famfamfam/link.png')"/>
								</xsl:call-template>
							</xsl:when>
							
							<!-- if none of the above, but we DO have text in the record, tell them so. -->
							
							<xsl:when test="embeddedText/paragraph">
								<a href="{../url_full}">
								<img src="{$base_url}/images/famfamfam/page_go.png" alt="" />
									Text in <xsl:value-of select="//config/application_name"/> record
								</a>
							</xsl:when>
						</xsl:choose>
						
						<!-- Holdings (to catalog)  -->
						
						<xsl:if test="links/link[@type='holdings'] and (//config/show_all_holdings_links = 'true' or //config/holdings_links/database[@metalib_id=$metalib_db_id])">
							<span class="resultsAvailableOption">
								<xsl:call-template name="record_link">
									<xsl:with-param name="type">holdings</xsl:with-param>
									<xsl:with-param name="text" select="$text_link_holdings"/>
									<xsl:with-param name="img_src" select="concat($base_url, '/images/book.gif')"/>
								</xsl:call-template>
							</span>
						</xsl:if>
							
						<!-- Save Facility -->
						
						<span class="resultsAvailableOption" id="saveRecordOption_{$result_set}_{$record_number}">
							<img id="folder_{$result_set}{$record_number}"	width="17" height="15" alt="" border="0" >
							<xsl:attribute name="src">
								<xsl:choose> 
									<xsl:when test="//request/session/resultssaved[@key = $record_id]">images/folder_on.gif</xsl:when>
									<xsl:otherwise>images/folder.gif</xsl:otherwise>
								</xsl:choose>
							</xsl:attribute>
							</img>
							
							<xsl:text> </xsl:text>
							<a id="link_{$result_set}:{$record_number}" href="{../url_save_delete}">
								<!-- 'saved' class used as a tag by ajaxy stuff -->
								<xsl:attribute name="class">
									saveThisRecord resultsFullText <xsl:if test="//request/session/resultssaved[@key = $record_id]">saved</xsl:if>
								</xsl:attribute>
								<xsl:choose>
									<xsl:when test="//request/session/resultssaved[@key = $record_id]">
										<xsl:choose>
											<xsl:when test="//session/role = 'named'">Record saved</xsl:when>
											<xsl:otherwise>Temporarily Saved</xsl:otherwise>
										</xsl:choose>
									</xsl:when>
									<xsl:otherwise>Save this record</xsl:otherwise>
								</xsl:choose>
							</a>
							
							<xsl:if test="//request/session/resultssaved[@key = $record_id] and //request/session/role != 'named'"> 
								<span class="temporary_login_note">
									(<a href="{//navbar/element[@id = 'login']/url}">login to save permanently</a>)
								</span>
							</xsl:if>
						</span>
					</div>
					
					<!-- label/tag input for saved records, if record is saved and it's not a temporary session -->
					
					<xsl:if test="//request/session/resultssaved[@key = $record_id] and not(//request/session/role = 'guest' or //request/session/role = 'local')">
						<div class="results_label resultsFullText" id="label_{$result_set}:{$record_number}" > 
							<xsl:call-template name="tag_input">
								<xsl:with-param name="record" select="//saved_records/saved[@id = $record_id]" />
								<xsl:with-param name="context">the results page</xsl:with-param>
							</xsl:call-template>	
						</div>
					</xsl:if>
				</div>
			</li>
			
		</xsl:for-each>
		
		</ul>
		
		<!-- Paging Navigation -->
		
		<xsl:if test="$merge_bug = 'false'">
			<xsl:call-template name="paging_navigation" />
		</xsl:if>
		
		<!-- used by ajax to add a tag input form after record is saved -->
		
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

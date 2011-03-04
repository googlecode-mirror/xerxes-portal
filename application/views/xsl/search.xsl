<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet  [
	<!ENTITY nbsp   "&#160;">
	<!ENTITY copy   "&#169;">
	<!ENTITY reg    "&#174;">
	<!ENTITY trade  "&#8482;">
	<!ENTITY mdash  "&#8212;">
	<!ENTITY ldquo  "&#8220;">
	<!ENTITY rdquo  "&#8221;"> 
	<!ENTITY pound  "&#163;">
	<!ENTITY yen    "&#165;">
	<!ENTITY euro   "&#8364;">
]>

<!--

 author: David Walker
 copyright: 2011 California State University
 version: $Id$
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	
	<!--
		TEMPLATE: SEARCH PAGE
	-->

	<xsl:template name="search_page">
	
		<div class="yui-ge">
			<div class="yui-u first">
				<h1>Testing</h1>
				<xsl:call-template name="searchbox" />
			</div>
			<div class="yui-u">
				<div id="sidebar">
					<xsl:call-template name="account_sidebar" />
				</div>
			</div>
		</div>
		
		<xsl:call-template name="tabs" />
		
		<div class="yui-ge">
			<div class="yui-u first">	
			
				<xsl:call-template name="facets_applied" />
		
				<div class="tabs">
					<xsl:call-template name="sort_bar" />
				</div>
		
				<xsl:call-template name="brief_results" />

				<xsl:call-template name="paging_navigation" />
				<xsl:call-template name="hidden_tag_layers" />
	
			</div>
			<div class="yui-u">
				<div id="sidebar">
					<xsl:call-template name="sidebar" />
				</div>
			</div>
		</div>	
		
	</xsl:template>

	<!--
		TEMPLATE: SORT BAR
	-->

	<xsl:template name="sort_bar">
	
		<xsl:choose>
			<xsl:when test="results/total = '0'">
				<xsl:call-template name="no_hits" />
			</xsl:when>
			<xsl:otherwise>
	
				<div id="sort">
					<div class="yui-gd">
						<div class="yui-u first">
							<xsl:copy-of select="$text_metasearch_results_summary" />
						</div>
						<div class="yui-u">
							<xsl:choose>
								<xsl:when test="//sort_display">
									<div id="sortOptions">
										<xsl:copy-of select="$text_results_sort_by" /><xsl:text>: </xsl:text>
										<xsl:for-each select="//sort_display/option">
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
									</div>
								</xsl:when>
								<xsl:otherwise>&#160;</xsl:otherwise>
							</xsl:choose>
						</div>
					</div>
				</div>
				
			</xsl:otherwise>
		</xsl:choose>
	
	</xsl:template>

	<!--
		TEMPLATE: NO HITS
	-->
	
	<xsl:template name="no_hits">
	
		<p class="error"><xsl:value-of select="$text_metasearch_hits_no_match" /></p>
	
	</xsl:template>
	
	<!--
		TEMPLATE: SEARCH BOX
	-->
	
	<xsl:template name="searchbox">
	
		<form action="./" method="get">
	
			<input type="hidden" name="lang" value="{//request/lang}" />
			<input type="hidden" name="base" value="{//request/base}" />
			<input type="hidden" name="action" value="search" />
			
			<xsl:call-template name="searchbox_hidden_fields_local" />
	
			<xsl:if test="request/sortkeys">
				<input type="hidden" name="sortKeys" value="{request/sortkeys}" />
			</xsl:if>
	
		<xsl:choose>
			<xsl:when test="$is_mobile = '1'">
				<xsl:call-template name="searchbox_mobile" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="searchbox_full" />
			</xsl:otherwise>
		</xsl:choose>
	
		</form>	
		
	</xsl:template>

	<!--
		TEMPLATE: SEARCH BOX MOBILE
	-->	
	
	<xsl:template name="searchbox_mobile">
	
		<xsl:variable name="search_query" select="//request/query" />
		<xsl:call-template name="mobile_search_box">
			<xsl:with-param name="query" select="$search_query" />
		</xsl:call-template>
			
	</xsl:template>

	<!--
		TEMPLATE: SEARCH BOX FULL
	-->
	
	<xsl:template name="searchbox_full">
	
		<xsl:choose>
			<xsl:when test="request/advanced or request/advancedfull">
				<xsl:call-template name="advanced_search" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="simple_search" />			
			</xsl:otherwise>
		</xsl:choose>
	
	</xsl:template>

	<!--
		TEMPLATE: SIMPLE SEARCH ????
	-->
	
	<xsl:template name="simple_search">
	
		<xsl:variable name="query"	select="request/query" />
		
		<div class="raisedBox searchBox">
	
			<div class="searchLabel">
				<label for="field">Search</label><xsl:text> </xsl:text>
			</div>
			
			<div class="searchInputs">
	
				<select id="field" name="field">
					
					<xsl:for-each select="config/basic_search_fields/field">
					
						<xsl:variable name="internal">
							<xsl:choose>
								<xsl:when test="@id"><xsl:value-of select="@id" /></xsl:when>
								<xsl:otherwise><xsl:value-of select="@internal" /></xsl:otherwise>
							</xsl:choose>
						</xsl:variable>
					
						<option value="{$internal}">
						<xsl:if test="//request/field = $internal">
							<xsl:attribute name="selected">seleted</xsl:attribute>
						</xsl:if>
						<xsl:value-of select="@public" />
						</option>
						
					</xsl:for-each>
				</select>
				
				<xsl:text> </xsl:text><label for="query"><xsl:value-of select="$text_searchbox_for" /></label><xsl:text> </xsl:text>
				
				<input id="query" name="query" type="text" size="32" value="{$query}" /><xsl:text> </xsl:text>
				
				<input type="submit" name="Submit" value="GO" class="submit_searchbox{$language_suffix}" />
			
			</div>
			
			<xsl:if test="results/spelling != ''">
				<p class="spellSuggest error">
					<xsl:value-of select="$text_searchbox_spelling_error" /><xsl:text> </xsl:text>
					<a href="{results/spelling/@url}"><xsl:value-of select="results/spelling" /></a>
				</p>
			</xsl:if>	
			
			<xsl:call-template name="advanced_search_option" />
			
		</div>
	
	</xsl:template>
	
	
	<!-- search box fields overriden in templates -->
	
	<xsl:template name="advanced_search_option" />
	<xsl:template name="advanced_search" />
	<xsl:template name="searchbox_hidden_fields_local" />
	
	<!-- 	
		TEMPLATE: TABS
		displays a tab configuration in the search architecture
	-->
	
	<xsl:template name="tabs">
	
		<xsl:if test="config/tabs">
		
			<div class="tabs">
				
				<xsl:for-each select="config/tabs/top">
						
					<ul id="tabnav">
						<xsl:call-template name="tab_options" />
					</ul>
					<div style="clear:both"></div>
				</xsl:for-each>
				
			</div>
		
		</xsl:if>
	</xsl:template>
	
	<xsl:template name="subtabs">
	
		<xsl:if test="config/tabs/sub[@parent = //request/base]">
		
			<div id="subtab" class="box">
			
				<h2>Expand your search</h2>
				
				<xsl:for-each select="config/tabs/sub[@parent = //request/base]">
						
					<ul>
						<xsl:call-template name="tab_options" />
					</ul>
	
				</xsl:for-each>
				
			</div>
		
		</xsl:if>
	
	</xsl:template>
	
	<xsl:template name="tab_options">
	
		<xsl:for-each select="tab">
			<xsl:variable name="id" select="@id" />
			<xsl:variable name="source">
				<xsl:if test="@source">
					<xsl:value-of select="@source" />
				</xsl:if>
			</xsl:variable>
			<xsl:variable name="module">
				<xsl:value-of select="$id">
				<xsl:if test="@source">
					<xsl:text>-</xsl:text>
					<xsl:value-of select="@source" />
				</xsl:if>			
				</xsl:value-of>
			</xsl:variable>
			
			<li>
				<xsl:if test="//request/base = $id and ($source = //request/source or $source = '')">
					<xsl:attribute name="class">here</xsl:attribute>
				</xsl:if>
				
				<a href="{$id}/results/{$source}?{//query_normalized/@url}">
					<xsl:value-of select="@public" /> 
					
					<span class="tabsHit">
						<xsl:choose>
							<xsl:when test="//request/base = $id and ($source = //request/source or $source = '')">
								(<xsl:value-of select="//results/total" />)
							</xsl:when>
							<xsl:otherwise>
								<xsl:choose>
									<xsl:when test="//results/other[@module=$module and (not(@source) or @source = $source)]">
										(<xsl:value-of select="//results/other[@module=$module and (not(@source) or @source = $source)]" />)
									</xsl:when>
									<xsl:otherwise>
										<span class="tabsHitNumber" id="tab:{$id}:{$source}"></span>
									</xsl:otherwise>
								</xsl:choose>								
							</xsl:otherwise>
						</xsl:choose>
					</span>
				</a>
			</li>
		</xsl:for-each>
		
	</xsl:template>

	<!-- 
		TEMPLATE PAGING NAVIGATION
		Provides the visual display for moving through a set of results
	-->
	
	<xsl:template name="paging_navigation">
	
		<xsl:if test="//pager/page">
			<div class="resultsPager">
	
				<ul class="resultsPagerList">
				<xsl:for-each select="//pager/page">
					<li>
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
										<xsl:copy-of select="$text_results_next" />
									</xsl:when>
									<xsl:otherwise>
										<xsl:attribute name="class">resultsPagerLink</xsl:attribute>
									</xsl:otherwise>
								</xsl:choose>
								<xsl:call-template name="text_results_sort_options">
									<xsl:with-param name="option" select="text()" />
								</xsl:call-template>
							</a>
						</xsl:otherwise>
					</xsl:choose>
					</li>
				</xsl:for-each>
				</ul>
			</div>
		</xsl:if>
	
	</xsl:template>

	
	<xsl:template name="sidebar">
	
		<xsl:call-template name="subtabs" />
		
		<xsl:if test="//facets/group">
		
			<div class="box">
			
				<h2>Narrow your results</h2>
				
				<xsl:for-each select="//facets/group">
		
					<h3 style="margin-top: 1em"><xsl:value-of select="@name" /></h3>
					
					<!-- only show first 10, unless there is 12 or fewer, in which case show all 12 -->
					
					<ul>
					<xsl:for-each select="facet[position() &lt;= 10 or count(../facet) &lt;= 12]">
						<li>
							<a href="{@url}"><xsl:value-of select="@name" /></a>&#160;(<xsl:value-of select="text()" />)
						</li>
					</xsl:for-each>
					</ul>
					
					<xsl:if test="count(facet) &gt; 12">
						
						<p id="facet-more-{@id}"  style="padding: 1.3em; padding-top: .7em; display:none"> 
							[ <a id="facet-more-link-{@id}" href="#" class="facetMoreOption"> 
								<xsl:value-of select="count(facet[position() &gt; 10])" /> more
							</a> ] 
						</p>
						
						<ul id="facet-list-{@id}" class="facetListMore">
							<xsl:for-each select="facet[position() &gt; 10]">
								<li>
									<a href="{@url}"><xsl:value-of select="@name" /></a>&#160;(<xsl:value-of select="text()" />) 
								</li>
							</xsl:for-each>
						</ul>
						
						<p id="facet-less-{@id}"  style="padding: 1.3em; padding-top: .7em; display:none"> 
							[ <a id="facet-less-link-{@id}" href="#" class="facetLessOption"> 
								show less
							</a> ] 
						</p>
	
					</xsl:if>
		
				</xsl:for-each>
			</div>
			
			<xsl:call-template name="sidebar_additional" />
		
		</xsl:if>
	
	</xsl:template>
	
	<xsl:template name="facets_applied">
	
		<xsl:if test="results/facets_applied">
			<div class="resultsFacetsApplied">
				<ul>
					<xsl:for-each select="results/facets_applied/facet_level">
						<li>
							<div class="remove" style="position: absolute; top: 10px; right: 10px;">
								<a href="{@url}">
									<xsl:call-template name="img_facet_remove">
										<xsl:with-param name="alt">remove limit</xsl:with-param>
									</xsl:call-template>
								</a>
							</div> 
							Limited to: <xsl:value-of select="text()" /> 
						</li>
					</xsl:for-each>
				</ul>
			</div>
		</xsl:if>
		
	</xsl:template>	

	<!-- 
		TEMPLATE: BRIEF RESULTS
		deprecated, used in the metasearch and folder brief results pages
	-->
	
	<xsl:template name="brief_results">
	
		<ul id="results">
		
		<xsl:for-each select="//records/record/xerxes_record">
			<xsl:call-template name="brief_result_article">
				<xsl:with-param name="result_set" select="result_set" />
				<xsl:with-param name="record_number" select="record_number" />	
				<xsl:with-param name="record_id">
					<xsl:value-of select="result_set" />:<xsl:value-of select="record_number" />
				</xsl:with-param>	
			</xsl:call-template>
		</xsl:for-each>
		
		</ul>
		
	</xsl:template>
	
	<!-- 
		TEMPLATE: BRIEF RESULT ARTICLE
		display of results geared toward articles (or really any non-book display)
	-->
	
	<xsl:template name="brief_result_article">
		
			<xsl:param name="result_set" />
			<xsl:param name="record_number" />
			<xsl:param name="record_id" />
			
			<!-- peer reviewed calculated differently in folder and metasearch -->
			
			<xsl:variable name="refereed">
				<xsl:choose>
					<xsl:when test="refereed = 1 and not(contains(format,'Review'))">
						<xsl:text>true</xsl:text>
					</xsl:when>
					<xsl:when test="../refereed = 1 and not(contains(format,'Review'))">
						<xsl:text>true</xsl:text>
					</xsl:when>
					<xsl:when test="//refereed/issn = standard_numbers/issn and not(contains(format,'Review'))">
						<xsl:text>true</xsl:text>
					</xsl:when>
				</xsl:choose>
			</xsl:variable>
			
			<li class="result">
				
				<xsl:variable name="title">
					<xsl:choose>
						<xsl:when test="title_normalized != ''">
							<xsl:value-of select="title_normalized" />
						</xsl:when>
						<xsl:otherwise>
							<xsl:copy-of select="$text_results_no_title" />
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				
				<div class="resultsTitle">
					<a href="{../url_full}"><xsl:value-of select="$title" /></a>
				</div>
				
				<div class="resultsInfo">
				
					<div class="resultsType">
						<xsl:call-template name="text_results_format">
							<xsl:with-param name="format" select="format" />
						</xsl:call-template>
						
						<xsl:call-template name="text_results_language" />
						
						<!-- peer reviewed -->
						
						<xsl:if test="$refereed = 'true'">
							<xsl:text> </xsl:text><xsl:call-template name="img_refereed" />
							<xsl:text> </xsl:text><xsl:copy-of select="$text_results_refereed" />
						</xsl:if>
					</div>
					
					<div class="resultsAbstract">
					
						<xsl:choose>
							<xsl:when test="summary_type = 'toc'">
								<xsl:value-of select="$text_record_summary_toc" /><xsl:text>: </xsl:text>
							</xsl:when>
							<xsl:when test="summary_type = 'subjects'">
								<xsl:value-of select="$text_record_summary_subjects" /><xsl:text>: </xsl:text>
							</xsl:when>					
						</xsl:choose>
					
						<xsl:choose>
							<xsl:when test="string-length(summary) &gt; 300">
								<xsl:value-of select="substring(summary, 1, 300)" /> . . .
							</xsl:when>
							<xsl:when test="summary">
								<xsl:value-of select="summary" />
							</xsl:when>
							
							<!-- @todo remove this after we make sure this feature is truly gone -->
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
							<strong><xsl:copy-of select="$text_results_author" />: </strong><xsl:value-of select="primary_author" />
						</span>
					</xsl:if>
					
					<xsl:if test="year">
						<span class="resultsYear">
							<strong><xsl:copy-of select="$text_results_year" />: </strong>
							<xsl:value-of select="year" />
						</span>
					</xsl:if>
					
					<xsl:if test="journal or journal_title">
						<span class="resultsPublishing">
							<strong><xsl:copy-of select="$text_results_published_in" />: </strong>
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
					
					<xsl:call-template name="additional_brief_record_data" />
					
					<div class="recordActions">
						
						<xsl:call-template name="full_text_options" />
						
						<xsl:call-template name="additional_record_links" />
						
						<xsl:choose>
							
							<!-- @todo: give saved record area it's own template -->
							
							<xsl:when test="/folder">
							
								<div class="folderAvailability deleteRecord">
									<a class="recordAction deleteRecord" href="{../url_delete}">
										<xsl:call-template name="img_delete">
											<xsl:with-param name="class">miniIcon deleteRecordLink</xsl:with-param>
											<xsl:with-param name="alt">remove limit</xsl:with-param>
										</xsl:call-template>
										<xsl:text> </xsl:text>
										<xsl:copy-of select="$text_results_record_delete" />
									 </a>
								</div>
								
								<xsl:if test="$temporarySession != 'true'">
									<xsl:call-template name="tag_input">
										<xsl:with-param name="record" select=".."/>
									</xsl:call-template>
								</xsl:if>						
							
							</xsl:when>
							<xsl:otherwise>
							
								<!-- save facility in search results area -->
								
								<div id="saveRecordOption_{$result_set}_{$record_number}" class="recordAction saveRecord">
									<xsl:call-template name="img_save_record">
										<xsl:with-param name="id" select="concat('folder_', $result_set, $record_number)" />
										<xsl:with-param name="class">miniIcon saveRecordLink</xsl:with-param>
										<xsl:with-param name="test" select="//request/session/resultssaved[@key = $record_id]" />
									</xsl:call-template>
									<xsl:text> </xsl:text>
									<a id="link_{$result_set}:{$record_number}" href="{../url_save_delete}">
										<!-- 'saved' class used as a tag by ajaxy stuff -->
										<xsl:attribute name="class">
											 saveRecord <xsl:if test="//request/session/resultssaved[@key = $record_id]">saved</xsl:if>
										</xsl:attribute>
										<xsl:choose>
											<xsl:when test="//request/session/resultssaved[@key = $record_id]">
												<xsl:choose>
													<xsl:when test="//session/role = 'named'">
														<xsl:copy-of select="$text_results_record_saved" />
													</xsl:when>
													<xsl:otherwise>
														<xsl:copy-of select="$text_results_record_saved_temp" />
													</xsl:otherwise>
												</xsl:choose>
											</xsl:when>
											<xsl:otherwise><xsl:copy-of select="$text_results_record_save_it" /></xsl:otherwise>
										</xsl:choose>
									</a>
									
									<xsl:if test="//request/session/resultssaved[@key = $record_id] and //request/session/role != 'named'"> 
										<span class="temporary_login_note">
											(<xsl:text> </xsl:text><a href="{//navbar/element[@id = 'login']/url}">
												<xsl:copy-of select="$text_results_record_saved_perm" />
											</a><xsl:text> </xsl:text>)
										</span>
									</xsl:if>
								</div>
								
								<!-- label/tag input for saved records, if record is saved and it's not a temporary session -->
								
								<xsl:if test="//request/session/resultssaved[@key = $record_id] and $temporarySession != 'true'">
									<div id="label_{$result_set}:{$record_number}" > 
										<xsl:call-template name="tag_input">
											<xsl:with-param name="record" select="//saved_records/saved[@id = $record_id]" />
											<xsl:with-param name="context">the results page</xsl:with-param>
										</xsl:call-template>	
									</div>
								</xsl:if>
	
							</xsl:otherwise>
	
						</xsl:choose>					
					</div>
				</div>
			</li>
	
	</xsl:template>
	
	<xsl:template name="full_text_options">
	
		<xsl:variable name="metalib_db_id" 	select="metalib_id" />
		<xsl:variable name="link_resolver_allowed" select="not(//database_links/database[@metalib_id = $metalib_db_id]/sfx_suppress = '1')" />
	
		<!-- holdings (to catalog)  -->
		
		<xsl:if test="links/link[@type='holdings'] and (//config/show_all_holdings_links = 'true' or //config/holdings_links/database[@metalib_id=$metalib_db_id])">
				<xsl:call-template name="record_link">
					<xsl:with-param name="type">holdings</xsl:with-param>
					<xsl:with-param name="text" select="$text_link_holdings"/>
					<xsl:with-param name="img_src" select="$img_src_holdings"/>
				</xsl:call-template>
		</xsl:if>
		
		<xsl:choose>
		
			<!-- native full-text -->
		
			<xsl:when test="full_text_bool">
				
				<xsl:call-template name="full_text_links"/>							
					
			</xsl:when>
			
			<!-- link resolver -->
			
			<xsl:when test="$link_resolver_allowed and (subscription = 1 or //fulltext/issn = standard_numbers/issn)">
					<a href="{../url_open}&amp;fulltext=1" target="{$link_target}" class="recordAction linkResolverLink">
						<xsl:call-template name="img_format_html">
							<xsl:with-param name="class">miniIcon linkResolverLink</xsl:with-param>
						</xsl:call-template>
						<xsl:text> </xsl:text>
						<xsl:copy-of select="$text_link_resolver_available" />
					</a>
			</xsl:when>
			
			<xsl:when test="$link_resolver_allowed">
					<a href="{../url_open}" target="{$link_target}" class="recordAction linkResoverLink">
						<img src="{$image_sfx}" alt="" class="miniIcon linkResolverLink "/>
						<xsl:text> </xsl:text>
						<xsl:copy-of select="$text_link_resolver_check" />
					</a>
			</xsl:when>
			
			<!-- if no direct link or link resolver, do we have an original record link? -->
			
			<xsl:when test="links/link[@type='original_record'] and (//config/show_all_original_record_links = 'true' or //config/original_record_links/database[@metalib_id = $metalib_db_id])">
				<xsl:call-template name="record_link">
				<xsl:with-param name="type">original_record</xsl:with-param>
				<xsl:with-param name="text" select="$text_link_original_record"/>
				<xsl:with-param name="img_src" select="$img_src_chain"/>
				</xsl:call-template>
			</xsl:when>
			
			<!-- @todo remove this 
			if none of the above, but we DO have text in the record, tell them so. -->
			
			<xsl:when test="embeddedText/paragraph">
				<a href="{../url_full}" class="recordAction textLink">
				<img src="{$base_url}/images/famfamfam/page_go.png" alt="" class="miniIcon textLink"/>
					Text in record
				</a>
			</xsl:when>
		</xsl:choose>
		
	</xsl:template>
	
	<!-- 
		TEMPLATE: HIDDEN TAG LAYERS
		These are used in the metasearch results (but not folder results because it already has some of these) 
		and record pages for the auto-complete tag input
	-->
	
	<xsl:template name="hidden_tag_layers">
		
		<div id="tag_suggestions" class="autocomplete" style="display:none;"></div>
	
		<div id="template_tag_input" class="results_label" style="display:none;">
			<xsl:call-template name="tag_input">
				<xsl:with-param name="id">template</xsl:with-param>
			</xsl:call-template> 
		</div>
	
		<div id="labelsMaster" class="folderOutput" style="display: none">
			<xsl:call-template name="tags_display" />
		</div>
		
		<xsl:call-template name="safari_tag_fix" />
		
	</xsl:template>

	<!-- 
		TEMPLATE: SAFARI TAG FIX
		This hidden iframe essentially thwarts the Safari backforward cache so that
		tags don't get wacky
	-->
	
	<xsl:template name="safari_tag_fix">
		
		<xsl:if test="contains(//server/http_user_agent,'Safari')">
		
			<iframe style="height:0px;width:0px;visibility:hidden" src="about:blank">
				<!-- this frame prevents back-forward cache for safari -->
			</iframe>
			
		</xsl:if>
	
	</xsl:template>

	<!-- 
		TEMPLATE: TAGS DISPLAY
		used by a couple of pages in the folder area for displaying tags
	-->
	
	<xsl:template name="tags_display">
		
		<h2><xsl:copy-of select="$text_folder_options_tags" /></h2>
		<ul>
		<xsl:for-each select="tags/tag">
			<li>
			<xsl:choose>
				<xsl:when test="@label = //request/label">
					<strong><xsl:value-of select="@label" /></strong> ( <xsl:value-of select="@total" /> )
				</xsl:when>
				<xsl:otherwise>
					<a href="{@url}"><span class="label_list_item"><xsl:value-of select="@label" /></span></a> ( <xsl:value-of select="@total" /> )
				</xsl:otherwise>
			</xsl:choose>
			</li>
		</xsl:for-each>
		</ul>
		
	</xsl:template>


	<!--
		TEMPLATE: TAG INPUT
		tab/label input form used to enter labels/tags for saved record, on both folder page and search results
		page (for saved records only) one of record (usually) or id (unusually) are required. 
		parameter: record  =>  XSL node representing a savedRecord with a child <id> and optional children <tags>
		parameter: id => pass a string id instead of a record in nodeset. Used for the 'template' form for ajax 
		label input adder. 
	-->
	
	<xsl:template name="tag_input">
		<xsl:param name="record" select="." />
		<xsl:param name="id" select="$record/id" /> 
		<xsl:param name="context">the saved records page</xsl:param>
	
		<div class="folderLabels recordAction" id="tag_input_div-{$id}">
			<form action="./" method="get" class="tags">
			
				<!-- note that if this event is fired with ajax, the javascript changes
				the action element here to 'tags_edit_ajax' so the server knows to display a 
				different view, which the javascript captures and uses to updates the totals above. -->
				
				<input type="hidden" name="base" value="folder" />
				<input type="hidden" name="lang" value="{//request/lang}" />
				<input type="hidden" name="action" value="tags_edit" />
				<input type="hidden" name="record" value="{$id}" />
				<input type="hidden" name="context" value="{$context}" />
				
				<xsl:variable name="tag_list">
					<xsl:for-each select="$record/tag">
						<xsl:value-of select="text()" />
						<xsl:if test="following-sibling::tag">
							<xsl:text>, </xsl:text>
						</xsl:if>
					</xsl:for-each>
				</xsl:variable>
				
				<input type="hidden" name="tagsShaddow" id="shadow-{$id}" value="{$tag_list}" />
				
				<label for="tags-{$id}"><xsl:copy-of select="$text_records_tags" /></label>
				
				<input type="text" name="tags" id="tags-{$id}" class="tagsInput" value="{$tag_list}" />			
				<xsl:text> </xsl:text>
				<input id="submit-{$id}" type="submit" name="submitButton" value="Update" class="tagsSubmit{$language_suffix}" />
			</form>
		</div>
		
	</xsl:template>












	<xsl:template name="additional_record_links" />
	<xsl:template name="additional_brief_record_data" />
	<xsl:template name="additional_full_record_data_main" />
	<xsl:template name="additional_full_record_data_supplemental" />
	
	

	
	
	
	
	
	
	
	
	
	
	<xsl:template name="full_text_links" />
	
</xsl:stylesheet>

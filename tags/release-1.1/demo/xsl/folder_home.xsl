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

<xsl:template match="/folder">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="username" 	select="request/session/username" />
	<xsl:variable name="sort" 		select="request/sortkeys" />
	<xsl:variable name="return" 	select="php:function('urlencode', string(request/server/request_uri))" />
    
	<xsl:variable name="full">
		<xsl:choose>
			<xsl:when test="$rewrite = 'true'">
				<xsl:value-of select="$username" /><xsl:text>/record/</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>./?base=folder&amp;action=full&amp;username=</xsl:text><xsl:value-of select="$username" /><xsl:text>&amp;record=</xsl:text>
			</xsl:otherwise>
		</xsl:choose>	
	</xsl:variable>
	
	<xsl:call-template name="results_return" />
	
	<div id="folderArea">	
		
		<xsl:choose>
			<xsl:when test="request/session/role = 'local' or request/session/role = 'guest'">
				<h2>Temporary Saved Records</h2>
				<xsl:if test="request/session/role = 'local'">
					<p>( <a href="{$base_url}/?base=authenticate&amp;action=login&amp;return={$return}">Log-in</a> 
					to save and retrieve records across sessions.)</p>
				</xsl:if>
			</xsl:when>
			<xsl:otherwise>
				<h2>My Saved Records</h2>
			</xsl:otherwise>
		</xsl:choose>
		
		<xsl:choose>
		<xsl:when test="results/records/record">
		
			<div class="folderOptions">
				<xsl:call-template name="folder_options" />
			</div>
			
			<div class="resultsPageOptions">
				<div class="resultsSorting">
				<xsl:if test="sort_display">
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
				</div>
				<div class="resultsPageSummary">
					Results <strong><xsl:value-of select="summary/range" /></strong> of 
					<strong><xsl:value-of select="summary/total" /></strong>
				</div>			
			</div>
		
			<table>
			<xsl:for-each select="results/records/record/xerxes_record">
				<xsl:variable name="issn" 		select="standard_numbers/issn" />
				<xsl:variable name="year" 		select="year" />
				<xsl:variable name="result_set" 	select="result_set" />
				<xsl:variable name="record_number" 	select="record_number" />
				<xsl:variable name="position" 		select="position()" />
				<xsl:variable name="id" 		select="../id" />
				<xsl:variable name="original_id"	select="../original_id" />
				<xsl:variable name="source" 		select="../source" />
				
				<tr valign="top">
					<td align="left" class="folderRecord" width="100%">			
						<a name="{$position}"></a>
						
						<a href="{$full}{$id}" class="resultsTitle">
							<xsl:value-of select="title_normalized" />
						</a>
			
						<div class="resultsType">
							<xsl:value-of select="format" />
							<xsl:if test="../refereed = 1 and format != 'Book Review'">
								<xsl:text> </xsl:text><img src="{$base_url}/images/refereed.gif" width="129" height="14" alt="" />
							</xsl:if>
						</div>			
			
						<div class="resultsAbstract">
							<xsl:choose>
								<xsl:when test="string-length(summary) &gt; 300">
									<xsl:value-of select="substring(summary, 1, 300)" />
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
							
									<a href="{$base_url}/?base=folder&amp;action=redirect&amp;type=openurl&amp;id={$id}" class="resultsFullText" target="{$link_target}">
										<img src="{$base_url}/?base=availability&amp;action=fulltext&amp;issn={$issn}&amp;year={$year}" 
											 alt="check for availability" border="0"/>
									</a>
									
								</xsl:otherwise>
							</xsl:choose>		
						</div>
						
					</td>
					<td align="center" class="folderRecord">
						<a onClick="return sureDelete()" 
							href="{$base_url}/?base=folder&amp;action=delete&amp;username={$username}&amp;source={$source}&amp;id={$original_id}&amp;sortKeys={$sort}">
							<img src="{$base_url}/images/folder_delete.gif" alt="delete" border="0" />
						</a>
						<br />
						<a onClick="return sureDelete()" 
							href="{$base_url}/?base=folder&amp;action=delete&amp;username={$username}&amp;source={$source}&amp;id={$original_id}&amp;sortKeys={$sort}">Delete</a>
					</td>
				</tr>
				</xsl:for-each>				
			</table>
			
			<!-- Paging Navigation -->
			
			<xsl:if test="pager/page">
			
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
							<a href="{$link}" class="resultsPagerLink">
								<xsl:value-of select="text()" />
							</a>
						</xsl:otherwise>
					</xsl:choose>
					</td>
				</xsl:for-each>
				</tr>
			</table>
			
			</xsl:if>
		</xsl:when>
		<xsl:otherwise>
			<div class="folderNoRecords">
				There are currently no saved records.
			</div>
		</xsl:otherwise>
		</xsl:choose>
	</div>
	
</xsl:template>
</xsl:stylesheet>

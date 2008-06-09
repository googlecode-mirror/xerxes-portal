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

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="username" 	select="request/session/username" />
	<xsl:variable name="sort" 		select="request/sortkeys" />
	
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
	
	<xsl:call-template name="results_return" />
	
	<div id="folderArea">	
		
		<xsl:call-template name="folder_header" />
		
		<xsl:choose>
		<xsl:when test="results/records/record">
					
			<div id="results1" class="folderOutputs">

				<div class="folderOutput">
					<h2>Export Records</h2>
					<ul>					
						<li><a href="{export_functions/export_option[@id='email']/url}">Email records to yourself</a></li>
						<li><a href="{export_functions/export_option[@id='refworks']/url}">Export to Refworks</a></li>
						<li><a href="{export_functions/export_option[@id='text']/url}">Download to text file</a></li>
						<li><a href="{export_functions/export_option[@id='endnote']/url}">Download to Endnote, Zotero, etc.</a></li>
					</ul>
				</div>

				<div class="folderOutput">
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
					<div id="labelsMaster" class="folderOutput">
						<xsl:call-template name="tags_display" />
					</div>
				</xsl:if>
				
			</div>
			
			<div class="folderResults">

				<div class="resultsPageOptions">

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

					<div>
						Results <strong><xsl:value-of select="summary/range" /></strong> of 
						<strong><xsl:value-of select="summary/total" /></strong>
					</div>	
				</div>

		
				<xsl:for-each select="results/records/record/xerxes_record">
					<xsl:variable name="issn" 		select="standard_numbers/issn" />
					<xsl:variable name="year" 		select="year" />
					<xsl:variable name="result_set" 	select="result_set" />
					<xsl:variable name="record_number" 	select="record_number" />
					<xsl:variable name="position" 		select="position()" />
					<xsl:variable name="id" 		select="../id" />
					
					<div class="folderRecord">
						<a name="{$position}"></a>
						
						<a href="{../url_full}" class="resultsTitle">
							<xsl:value-of select="title_normalized" />
						</a>
			
						<div class="resultsType">
							<xsl:value-of select="format" />
							<xsl:if test="../refereed = 1 and format != 'Book Review'">
								<xsl:text> </xsl:text><img src="{$base_url}/images/refereed_hat.gif" alt="" />
								<xsl:text> Peer Reviewed</xsl:text>
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
		
						<div class="resultsAvailability folderAvailability">
							
							<!-- Full-Text -->
							
							<xsl:choose>
							<xsl:when test="full_text_bool">
							
								<xsl:call-template name="full_text_links">
									<xsl:with-param name="class">resultsFullTextOption</xsl:with-param>
								</xsl:call-template>
								
							</xsl:when>
							<xsl:when test="//fulltext/issn = standard_numbers/issn">
								<a href="{../url_open}" class="resultsFullText" target="{$link_target}">
									<img src="{$base_include}/images/html.gif" alt="full text online" width="16" height="16" border="0" /> Full-Text Online
								</a>
							</xsl:when>
							<xsl:otherwise>
								<a href="{../url_open}" class="resultsFullText" target="{$link_target}">
									<img src="{$base_url}/images/sfx.gif" alt="check for availability" /> Check for availability
								</a>
							</xsl:otherwise>
							</xsl:choose>
								
						</div>
						
						<!--
						<div class="folderAvailability">
							<a href="#" class="resultsFullText">
								<img src="{$base_url}/images/edit.gif" alt="edit" border="0" />
								Edit this record
							 </a>
						</div>
						-->
						
						<div class="folderAvailability">
							<a class="deleteRecord resultsFullText" href="{../url_delete}">
								<img src="{$base_url}/images/delete.gif" alt="delete" border="0" />
								Delete this record
							 </a>
						</div>
						
						<xsl:if test="$temporarySession != 'true'">
						
							<div class="folderLabels">
								<form action="./" method="get" class="tags">
								
									<!-- note that if this event is fired with ajax, the javascript changes
									the action element here to 'tags_edit_ajax' so the server knows to display a 
									different view, which the javascript captures and uses to updates the totals above. -->
									
									<input type="hidden" name="base" value="folder" />
									<input type="hidden" name="action" value="tags_edit" />
									<input type="hidden" name="record" value="{$id}" />
									
									<xsl:variable name="tag_list">
										<xsl:for-each select="../tag">
											<xsl:value-of select="text()" />
											<xsl:if test="following-sibling::tag">
												<xsl:text>, </xsl:text>
											</xsl:if>
										</xsl:for-each>
									</xsl:variable>
									
									<input type="hidden" name="tagsShaddow" id="shadow-{$id}" value="{$tag_list}" />
									
									<label for="tags-{$id}">Labels: </label>
									
									<input type="text" name="tags" id="tags-{$id}" class="tagsInput" value="{$tag_list}" />
									
									<span class="folderLabelsSubmit">
										<input id="submit-{$id}" type="submit" name="submitButton" value="Update" class="tagsSubmit" />
									</span>
								</form>
							</div>
							
						</xsl:if>	
							
					</div>
				</xsl:for-each>
			</div>
			
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
	
</xsl:template>
</xsl:stylesheet>
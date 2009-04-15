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
<xsl:include href="citation/styles.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template name="sidebar">
	<div id="sidebar">
		<xsl:call-template name="account_sidebar" />
	</div>
</xsl:template>

<xsl:template name="page_name">
	<xsl:value-of select="//records/record/xerxes_record/title_normalized" />
</xsl:template>

<xsl:template name="record">
	
	<div id="record">

		<xsl:for-each select="//records/record/xerxes_record">
	
			<xsl:variable name="result_set" 	select="result_set" />
			<xsl:variable name="record_number" 	select="record_number" />
			<xsl:variable name="group" 			select="//request/group" />
			<xsl:variable name="issn" 			select="standard_numbers/issn" />
			<xsl:variable name="record_id">
				<xsl:value-of select="$result_set" />:<xsl:value-of select="$record_number" />
			</xsl:variable>
		
			<!-- Title -->
			
			<h1><xsl:call-template name="page_name" /></h1>
			
			<dl>
			
			<!-- Authors -->
			
			<xsl:if test="authors/author[@type = 'personal']">
				<div>
				<dt>By:</dt>
				<dd>
					<xsl:for-each select="authors/author[@type = 'personal']">
						<xsl:value-of select="aufirst" /><xsl:text> </xsl:text>
						<xsl:value-of select="auinit" /><xsl:text> </xsl:text>
						<xsl:value-of select="aulast" /><xsl:text> </xsl:text>
						
						<xsl:if test="following-sibling::author[@type = 'personal']">
							<xsl:text> ; </xsl:text>
						</xsl:if>
					</xsl:for-each>
				</dd>
				</div>
			</xsl:if>
	
			<!-- Corp. Authors -->
			
			<xsl:if test="authors/author[@type = 'corporate']">
				<div>
				<dt>Corporate author:</dt>
				<dd>
					<xsl:for-each select="authors/author[@type = 'corporate']">
						<xsl:value-of select="aucorp" /><xsl:text> </xsl:text>
						
						<xsl:if test="following-sibling::author[@type = 'corporate']">
							<xsl:text> ; </xsl:text>
						</xsl:if>
					</xsl:for-each>
				</dd>
				</div>
			</xsl:if>
	
			<!-- Conference -->
			
			<xsl:if test="authors/author[@type = 'conference']">
				<div>
				<dt>Conference:</dt>
				<dd>
					<xsl:for-each select="authors/author[@type = 'conference']">
						
						<xsl:value-of select="aucorp" /><xsl:text> </xsl:text>
						
						<xsl:if test="following-sibling::author[@type = 'conference']">
							<br />
						</xsl:if>
					</xsl:for-each>
				</dd>
				</div>
			</xsl:if>
			
			<!-- Format -->
			
			<xsl:if test="format">
				<div>
				<dt>Format:</dt>
				<dd>
					<xsl:value-of select="format" />
					
					<xsl:choose>
					<xsl:when test="../refereed = 1 and format != 'Book Review'">
						<xsl:text> </xsl:text><img src="images/refereed_hat.gif" width="20" height="14" alt="" />
						<strong> Peer Reviewed</strong>
					</xsl:when>
					<xsl:when test="//refereed/issn = standard_numbers/issn and format != 'Book Review'">
						<xsl:text> </xsl:text><img src="images/refereed_hat.gif" width="20" height="14" alt="" />
						<strong> Peer Reviewed</strong>
					</xsl:when>
					</xsl:choose>
				</dd>
				</div>
			</xsl:if>
			
			<!-- Year -->
			
			<xsl:if test="format">
				<div>
				<dt>Year:</dt>
				<dd><xsl:value-of select="year" /></dd>
				</div>
			</xsl:if>
			
			<!-- Institution -->
			
			<xsl:if test="institution">
				<div>
				<dt>Institution:</dt>
				<dd><xsl:value-of select="institution" /></dd>
				</div>
			</xsl:if>
			
			<!-- Degree -->
			
			<xsl:if test="degree">
				<div>
				<dt class="recordAttribute">Degree:</dt>
				<dd><xsl:value-of select="degree" /></dd>
				</div>
			</xsl:if>
			
			<!-- Source -->
			
			<div>
			<xsl:choose>
				<xsl:when test="journal">
					<dt>Published in:</dt>
					<dd>
						<xsl:choose>
							<xsl:when test="book_title">
								<xsl:value-of select="book_title" />
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="journal" />
							</xsl:otherwise>
						</xsl:choose>
					</dd>
				</xsl:when>
				<xsl:when test="format = 'Book'">
					<dt>Publisher:</dt>
					<dd>
						<xsl:value-of select="place" /><xsl:text>: </xsl:text>
						<xsl:value-of select="publisher" /><xsl:text>, </xsl:text>
						<xsl:value-of select="year" />
					</dd>
				</xsl:when>
			</xsl:choose>
			</div>
			
			<!-- Database -->
			
			<div>
			<dt>Database:</dt>
			<dd>
				<xsl:variable name="metalib_id" select="metalib_id"/>
				
				<a>
				<xsl:attribute name="href">
					<xsl:value-of select="//database_links/database[@metalib_id = $metalib_id]/url"/>
				</xsl:attribute>
				
				<xsl:value-of select="database_name" />
				</a>
			</dd>
			</div>
			
			</dl>
			
			<!-- @todo factor this out to includes? -->
			
			<div id="recordFullText" class="raisedBox recordOptions">
				
				<!-- Full-Text -->
				
				<xsl:variable name="database_code" select="metalib_id"/>
				
				<xsl:if test="full_text_bool != ''">
					<xsl:call-template name="full_text_links">
						<xsl:with-param name="class">recordFullTextOption</xsl:with-param>
					</xsl:call-template>
				</xsl:if>
				
				<!-- original_record and holdings links, if appropriate -->
				
				<xsl:if test="links/link[@type='original_record'] and (//config/show_all_original_record_links = 'true' or //config/original_record_links/database[@metalib_id = $database_code])">
					<div class="recordFullTextOption">
						<xsl:call-template name="record_link">
							<xsl:with-param name="type">original_record</xsl:with-param>
							<xsl:with-param name="text" select="$text_link_original_record"/>
							<xsl:with-param name="img_src" select="concat($base_url, '/images/famfamfam/link.png')"/>
						</xsl:call-template>
					</div>
				</xsl:if>
				
				<xsl:if test="links/link[@type='holdings'] and (//config/show_all_holdings_links = 'true' or //config/holdings_links/database[@metalib_id=$database_code])">
					<div class="recordFullTextOption">
						<xsl:call-template name="record_link">
							<xsl:with-param name="type">holdings</xsl:with-param>
							<xsl:with-param name="text" select="$text_link_holdings"/>
							<xsl:with-param name="img_src" select="concat($base_url, '/images/book.gif')"/>
						</xsl:call-template>
					</div>
				</xsl:if>
				
				<div class="recordFullTextOption">
				
					<xsl:variable name="db_metalib_id" select="metalib_id" />
					<xsl:variable name="link_resolver_allowed" select="not(//database_links/database[@metalib_id = $db_metalib_id]/sfx_suppress) or //database_links/database[@metalib_id = $db_metalib_id]/sfx_suppress != '1'" />
					
					<!-- open url -->
					
					<xsl:if test="$link_resolver_allowed">
						<a href="{../url_open}" class="resultsFullText"	target="{$link_target}" >
							<img src="{$base_url}/images/sfx.gif" alt="" />
							<xsl:text> </xsl:text>
							<xsl:copy-of select="$text_link_resolver_check" />
						</a>
					</xsl:if>
					
					<!-- label/tag input, we know record is saved because we are in folder section. -->
					
					<xsl:if test="/folder">

						<div class="results_label resultsFullText" id="label_{$result_set}:{$record_number}" > 
							<xsl:call-template name="tag_input">
								<xsl:with-param name="record" select=".." />
								<xsl:with-param name="context">the record page</xsl:with-param>
							</xsl:call-template>	
						</div>
						
					</xsl:if>

				</div>
				
				<!-- save option -->
				
				<xsl:if test="/metasearch">
					<div class="recordFullTextOption" id="saveRecordOption_{$result_set}_{$record_number}">
						<img id="folder_{$result_set}{$record_number}"	width="17" height="15" alt="" border="0">
							<xsl:attribute name="src">
								<xsl:choose> 
									<xsl:when test="//request/session/resultssaved[@key = $record_id]">images/folder_on.gif</xsl:when>
									<xsl:otherwise>images/folder.gif</xsl:otherwise>
								</xsl:choose>
							</xsl:attribute>
						</img>
						<xsl:text> </xsl:text>
						<a id="link_{$result_set}:{$record_number}"
							href="{../url_save_delete}" 
							class="saveThisRecord resultsFullText">
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
					</div>
					
					<!-- label/tag input for saved records, if record is saved and it's not a temporary session -->
					
					<xsl:if test="//request/session/resultssaved[@key = $record_id] and not(//request/session/role = 'guest' or //request/session/role = 'local')">
						<div class="results_label resultsFullText" id="label_{$result_set}:{$record_number}" > 
							<xsl:call-template name="tag_input">
								<xsl:with-param name="record" select="//saved_records/saved[@id = $record_id]" />
								<xsl:with-param name="context">the record page</xsl:with-param>
							</xsl:call-template>	
						</div>
					</xsl:if>
				</xsl:if>
			</div>
			
			<!-- Abstract -->
			
			<xsl:if test="abstract">
				<h2>Summary</h2>
				<div class="recordAbstract">
					<xsl:value-of select="abstract" />
				</div>
			</xsl:if>
	
			<!-- Abstract -->
			
			<xsl:if test="toc">
				<h2>
					<xsl:choose>
						<xsl:when test="format = 'Book'">
							<xsl:text>Chapters:</xsl:text>
						</xsl:when>
						<xsl:otherwise>
							<xsl:text>Contents:</xsl:text>
						</xsl:otherwise>
					</xsl:choose>
				</h2>
				<div class="recordAbstract">
					<ul>
					<xsl:for-each select="toc/chapter">
						<li>
							<xsl:choose>
								<xsl:when test="statement">
									<xsl:value-of select="statement" />
								</xsl:when>
								<xsl:otherwise>
									<em><xsl:value-of select="title" /></em>
									<xsl:text> by </xsl:text><xsl:value-of select="author" />
								</xsl:otherwise>
							</xsl:choose>
						</li>
					</xsl:for-each>
					</ul>
				</div>
			</xsl:if>
			
			<!-- Language -->
			
			<xsl:if test="language">
				<h2>Language</h2>
				<ul>
					<li><xsl:value-of select="language" /></li>
				</ul>
			</xsl:if>
			
			<!-- Subjects -->
			
			<xsl:if test="subjects">
				<h2>Covers the topics:</h2>
				<ul>
					<xsl:for-each select="subjects/subject">
						<li><xsl:value-of select="text()" /></li>
					</xsl:for-each>
				</ul>
			</xsl:if>
	
			<!-- Standard Numbers -->
			
			<xsl:if test="standard_numbers">
				<h2>Standard Numbers:</h2>
				<ul>
				<xsl:for-each select="standard_numbers/issn">
					<li><strong>ISSN</strong>: <xsl:value-of select="text()" /></li>
				</xsl:for-each>
				<xsl:for-each select="standard_numbers/isbn">
					<li><strong>ISBN</strong>: <xsl:value-of select="text()" /></li>
				</xsl:for-each>
				<xsl:for-each select="standard_numbers/gpo">
					<li><strong>GPO Item Number</strong>: <xsl:value-of select="text()" /></li>
				</xsl:for-each>
				<xsl:for-each select="standard_numbers/govdoc">
					<li><strong>Gov Doc Number</strong>: <xsl:value-of select="text()" /></li>
				</xsl:for-each>
				
				<xsl:for-each select="standard_numbers/oclc">
					<li><strong>OCLC number</strong>: <xsl:value-of select="text()" /></li>
				</xsl:for-each>
				</ul>
			</xsl:if>
				
			<!-- Actual text embedded only if we have no links to full text-->
			
			<xsl:if test="count(links/link[@type != 'none']) = 0 and embeddedText">
				<h2>Text</h2>
				<xsl:for-each select="embeddedText/paragraph">
					<p class="recordAbstract"><xsl:value-of select="text()" /></p>
				</xsl:for-each>
			</xsl:if>
			
			<!-- Notes -->
			
			<xsl:if test="notes">
				<h2>Additional Notes: </h2>
				<ul>
					<xsl:for-each select="notes/note">
						<li><xsl:value-of select="text()" /></li>
					</xsl:for-each>
				</ul>
			</xsl:if>
	
		</xsl:for-each>
		
		<!-- @todo factor this out to includes? -->
		
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



</xsl:stylesheet>

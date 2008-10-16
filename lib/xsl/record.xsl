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
<xsl:output method="html" encoding="utf-8" indent="yes" />
<xsl:include href="citation/styles.xsl" />

<xsl:template name="record">
	
	<xsl:for-each select="//records/record/xerxes_record">

		<xsl:variable name="result_set" 	select="result_set" />
		<xsl:variable name="record_number" 	select="record_number" />
		<xsl:variable name="group" 		select="//request/group" />
		<xsl:variable name="issn" 		select="standard_numbers/issn" />
		<xsl:variable name="record_id">
			<xsl:value-of select="$result_set" />:<xsl:value-of select="$record_number" />
		</xsl:variable>
		
		<div id="citation">
			
			<h2>Cite this <xsl:value-of select="format" />:</h2>
			
			<div class="citations">
				
				<h3>APA</h3>
				<div class="citationStyle">
					<xsl:call-template name="apa" />
				</div>
				
				<h3>MLA</h3>
				<div class="citationStyle">
					<xsl:call-template name="mla" />
				</div>
				
				<h3>Turabian</h3>
				<div class="citationStyle">
					<xsl:call-template name="turabian" />
				</div>
			</div>
			<div class="citationNote">
				These citations are software generated and may contain errors. 
				To verify accuracy, check the appropriate style guide.
			</div>
		</div>
		
		<div id="record">
		
			<!-- Title -->			
			<h2 class="recordTitle"><xsl:value-of select="title_normalized" /></h2>
					
			<table class="recordBasicTable">
			
			<!-- Authors -->
			
			<xsl:if test="authors/author[@type = 'personal']">
				<tr>
				<td class="recordAttribute">By:</td>
				<td>
					<xsl:for-each select="authors/author[@type = 'personal']">
						<xsl:value-of select="aufirst" /><xsl:text> </xsl:text>
						<xsl:value-of select="auinit" /><xsl:text> </xsl:text>
						<xsl:value-of select="aulast" /><xsl:text> </xsl:text>
						
						<xsl:if test="following-sibling::author[@type = 'personal']">
							<xsl:text> ; </xsl:text>
						</xsl:if>
					</xsl:for-each>
				</td>
				</tr>
			</xsl:if>

			<!-- Corp. Authors -->
			
			<xsl:if test="authors/author[@type = 'corporate']">
				<tr>
				<td class="recordAttribute">Corporate author:</td>
				<td>
					<xsl:for-each select="authors/author[@type = 'corporate']">
						<xsl:value-of select="aucorp" /><xsl:text> </xsl:text>
						
						<xsl:if test="following-sibling::author[@type = 'corporate']">
							<xsl:text> ; </xsl:text>
						</xsl:if>
					</xsl:for-each>
				</td>
				</tr>
			</xsl:if>

			<!-- Conference -->
			
			<xsl:if test="authors/author[@type = 'conference']">
				<tr>
				<td class="recordAttribute">Conference:</td>
				<td>
					<xsl:for-each select="authors/author[@type = 'conference']">
						
						<xsl:value-of select="aucorp" /><xsl:text> </xsl:text>
						
						<xsl:if test="following-sibling::author[@type = 'conference']">
							<br />
						</xsl:if>
					</xsl:for-each>
				</td>
				</tr>
			</xsl:if>

			
			<!-- Format -->
			<xsl:if test="format">
				<tr>
				<td class="recordAttribute">Format:</td>
				<td>
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
				</td>
				</tr>
			</xsl:if>
	
			<!-- Year -->
			<xsl:if test="format">
				<tr>
				<td class="recordAttribute">Year:</td>
				<td><xsl:value-of select="year" /></td>
				</tr>
			</xsl:if>
			
			<!-- Institution -->
			<xsl:if test="institution">
				<tr>
				<td class="recordAttribute">Institution:</td>
				<td><xsl:value-of select="institution" /></td>
				</tr>
			</xsl:if>
			
			<!-- Degree -->
			<xsl:if test="degree">
				<tr>
				<td class="recordAttribute">Degree:</td>
				<td><xsl:value-of select="degree" /></td>
				</tr>
			</xsl:if>
			
			<!-- Source -->
			
			<xsl:choose>
				<xsl:when test="journal">
					<tr>
					<td class="recordAttribute">Published in:</td>
					<td>
						<xsl:choose>
							<xsl:when test="book_title">
								<xsl:value-of select="book_title" />
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="journal" />
							</xsl:otherwise>
						</xsl:choose>
					</td>
					</tr>
				</xsl:when>
				<xsl:when test="format = 'Book'">
					<tr>
					<td class="recordAttribute">Publisher:</td>
					<td>
						<xsl:value-of select="place" /><xsl:text>: </xsl:text>
						<xsl:value-of select="publisher" /><xsl:text>, </xsl:text>
						<xsl:value-of select="year" />
					</td>
					</tr>				
				
				</xsl:when>
			</xsl:choose>
	
			<!-- Database -->
			<tr>
			<td class="recordAttribute">Database:</td>
			<td>
				<xsl:value-of select="database_name" />
			</td>
			</tr>
			
			</table>
	
			
			<div class="recordFullText recordOptions">
				
				<!-- Full-Text -->
				
				<xsl:variable name="database_code" select="metalib_id"/>
				
				<xsl:if test="full_text_bool != ''">
	
					<xsl:call-template name="full_text_links">
						<xsl:with-param name="class">recordFullTextOption</xsl:with-param>
					</xsl:call-template>
				
				</xsl:if>
				
        <div class="recordFullTextOption">
          <xsl:variable name="db_metalib_id" select="metalib_id" />
          <xsl:variable name="link_resolver_allowed" select="not(//database_links/database[@metalib_id = $db_metalib_id]/sfx_suppress) or //database_links/database[@metalib_id = $db_metalib_id]/sfx_suppress != '1'" />
          
					<xsl:choose>
						<xsl:when test="/metasearch">
              <xsl:if test="$link_resolver_allowed">
                <a href="{$base_url}/./?base=metasearch&amp;action=sfx&amp;resultSet={$result_set}&amp;startRecord={$record_number}" class="resultsFullText"	target="{$link_target}" >
                  <img src="{$base_url}/images/sfx.gif" alt="" /> Check for availability
                </a>
              </xsl:if>
						</xsl:when>
						<xsl:when test="/folder">
							<xsl:variable name="id" select="../id" />
              <xsl:if test="$link_resolver_allowed">
                <a href="{$base_url}/?base=folder&amp;action=redirect&amp;type=openurl&amp;id={$id}" class="resultsFullText"	target="{$link_target}" >
                  <img src="{$base_url}/images/sfx.gif" alt="" /> Check for availability
                </a>
              </xsl:if>
							
							<!-- label/tag input, we know record is saved because we
									 are in folder section. -->
							<div class="results_label resultsFullText" id="label_{$result_set}:{$record_number}" > 
								<xsl:call-template name="tag_input">
									<xsl:with-param name="record" select=".." />
									<xsl:with-param name="context" select="'the record page'" />
								</xsl:call-template>	
							</div>
							
						</xsl:when>
					</xsl:choose>
				</div>
				<xsl:if test="/metasearch">
					<div class="recordFullTextOption" id="saveRecordOption">
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
							href="./?base=metasearch&amp;action=save-delete&amp;group={$group}&amp;resultSet={$result_set}&amp;startRecord={$record_number}" 
							class="saveThisRecord resultsFullText">
									<!-- 'saved' class used as a tag by ajaxy stuff -->
									<xsl:attribute name="class">
										saveThisRecord resultsFullText <xsl:if test="//request/session/resultssaved[@key = $record_id]">saved</xsl:if>
									</xsl:attribute>
							
									<xsl:choose>
										<xsl:when test="//request/session/resultssaved[@key = $record_id]">Record saved</xsl:when>
										<xsl:otherwise>Save this record</xsl:otherwise>
									</xsl:choose>						
						</a>
					</div>
					<!-- label/tag input for saved records, if record is saved and it's not a temporary session -->
					<xsl:if test="//request/session/resultssaved[@key = $record_id] and not(//request/session/role = 'guest' or //request/session/role = 'local')">
						<div class="results_label resultsFullText" id="label_{$result_set}:{$record_number}" > 
							<xsl:call-template name="tag_input">
								<xsl:with-param name="record" select="//saved_records/saved[@id = $record_id]" />
								<xsl:with-param name="context" select="'the record page'" />
							</xsl:call-template>	
						</div>
					</xsl:if>
				</xsl:if>
			</div>

			<!-- Abstract -->
			<xsl:if test="abstract">
				<h4>Summary</h4>
				<div class="recordAbstract">
					<xsl:value-of select="abstract" />
				</div>
			</xsl:if>
	
			<!-- Abstract -->
			<xsl:if test="toc">
				<h4>Chapters:</h4>
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
									<xsl:text>By </xsl:text><xsl:value-of select="author" />
	
								</xsl:otherwise>
							</xsl:choose>
						</li>
					</xsl:for-each>
					</ul>
				</div>
			</xsl:if>
			
			<!-- Language -->
			<xsl:if test="language">
				<h4>Language</h4>
				<ul>
					<li><xsl:value-of select="language" /></li>
				</ul>
			</xsl:if>
			
			<!-- Subjects -->
			<xsl:if test="subjects">
				<h4>Covers the topics:</h4>
				<ul>
					<xsl:for-each select="subjects/subject">
						<li><xsl:value-of select="text()" /></li>
					</xsl:for-each>
				</ul>
			</xsl:if>
	
			<!-- Standard Numbers -->
			
			<xsl:if test="standard_numbers">
				<h4>Standard Numbers:</h4>
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
        <h4>Text</h4>
        <xsl:for-each select="embeddedText/paragraph">
          <p class="recordAbstract"><xsl:value-of select="text()" /></p>
        </xsl:for-each>
      </xsl:if>

		
			<!-- Notes -->
			<xsl:if test="notes">
				<h4>Additional Notes: </h4>
				<ul>
					<xsl:for-each select="notes/note">
						<li><xsl:value-of select="text()" /></li>
					</xsl:for-each>
				</ul>
			</xsl:if>
			
		</div>
	
	</xsl:for-each>
	
		 <!-- hidden div to be used for autocompletion suggestions -->
	<div id="tag_suggestions" class="autocomplete" style="display:none;"></div>
	
		<!-- include a hidden template copy of the label/tab input form. Used by AJAX to add
			 a tag input form after record is saved -->
	<div id="template_tag_input" class="results_label resultsFullText" style="display:none;">
		<xsl:call-template name="tag_input">
			<xsl:with-param name="id" select="'template'" />
		</xsl:call-template> 
	</div>

	<!-- Label list display in a hidden div. We include this for javascript purposes
			 for two reasons:
			 1) The AJAX label code we are re-purposing from folder home expects it, and errors
					if it's not there. But even more importantly...
			 2) The AJAX autocompleter logic will use this to build the autocomplete possibilities,
					which we also want on this page. 

			So we include it hidden, because the user doesn't need to see it on this page, but we
			include it.
	-->

	<div id="labelsMaster" class="folderOutput" style="display: none">
		<xsl:call-template name="tags_display" />
	</div>

	
</xsl:template>



</xsl:stylesheet>

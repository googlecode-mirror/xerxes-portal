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
	
	<xsl:include href="results.xsl" />

	<!--
		TEMPLATE: RECORD
	-->	

	<xsl:template name="record">
		<div id="record">
		
			<xsl:for-each select="/*/results/records/record/xerxes_record">
				
				<!-- Title -->
				
				<h1><xsl:value-of select="title_normalized" /></h1>
				
				<!-- Basic record information (Author, Year, Format, Database, ...) -->
				
				<xsl:call-template name="record-summary" />
							
				<!-- A box with actions for current record (get full-text, link to holdings, save record) -->
				
				<xsl:call-template name="record-actions" />
				
				<!-- Umlaut stuff -->
				
				<div id="library_copies" class="umlaut_content" style="display:none;"></div>
				<div id="document_delivery" class="umlaut_content" style="display:none;"></div>
				<div id="search_inside" class="umlaut_content" style="display:none;"></div>
				<div id="limited_preview" class="umlaut_content" style="display:none"></div>
	
				<!-- Detailed record information (Summary, Topics, Standard numbers, ...) -->
				
				<xsl:call-template name="record-details" />
				
			</xsl:for-each>
				
			<!-- tag input -->
			
			<xsl:call-template name="hidden_tag_layers" />
		</div>
	</xsl:template>

	<!--
		TEMPLATE: RECORD SUMMARY
	-->	
	
	<xsl:template name="record-summary">
		<dl>
			<xsl:call-template name="record-authors" />	<!-- Authors -->
			<xsl:call-template name="record-corp-authors" />	<!-- Corp. Authors -->
			<xsl:call-template name="record-conference" />	<!-- Conference -->
			<xsl:call-template name="record-format" />	<!-- Format -->
			<xsl:call-template name="record-year" />	<!-- Year -->
			<xsl:call-template name="record-institution" /> <!-- Institution -->
			<xsl:call-template name="record-degree" />	<!-- Degree -->
			<xsl:call-template name="record-source" />	<!-- Source -->
			<xsl:call-template name="record-database" />	<!-- Database -->
		</dl>
	</xsl:template>

	<!--
		TEMPLATE: RECORD DETAILS
	-->
	
	<xsl:template name="record-details">
		<xsl:call-template name="record-abstract" />
		<xsl:call-template name="record-recommendations" />
		<xsl:call-template name="record-toc" />
		<xsl:call-template name="record-language" />
		<xsl:call-template name="record-subjects" />
		<xsl:call-template name="record-standard_numbers" />
		<xsl:call-template name="record-notes" />
	</xsl:template>

	<!--
		TEMPLATE: RECORD AUTHORS
	-->	
	
	<xsl:template name="record-authors">
		<xsl:if test="authors/author[@type = 'personal']">
			<div>
			<dt><xsl:copy-of select="$text_results_author" />:</dt>
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
	</xsl:template>

	<!--
		TEMPLATE: RECORD CORPORATE AUTHORS
	-->	
	
	<xsl:template name="record-corp-authors">
		<xsl:if test="authors/author[@type = 'corporate']">
			<div>
			<dt><xsl:copy-of select="$text_record_author_corp" />:</dt>
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
	</xsl:template>

	<!--
		TEMPLATE: RECORD CONFERENCE AUTHORS
	-->	
	
	<xsl:template name="record-conference">
		<xsl:if test="authors/author[@type = 'conference']">
			<div>
			<dt><xsl:copy-of select="$text_record_conf" />:</dt>
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
	</xsl:template>

	<!--
		TEMPLATE: RECORD FORMAT
	-->	
	
	<xsl:template name="record-format">
		<xsl:if test="format">
			<div>
			<dt><xsl:copy-of select="$text_record_format_label" />:</dt>
			<dd>
				<xsl:call-template name="text_results_format">
					<xsl:with-param name="format" select="format" />
				</xsl:call-template>

				<xsl:if test="refereed = 1 and not(contains(format,'Review'))">
					<xsl:text> </xsl:text><xsl:call-template name="img_refereed" />
					<xsl:text> </xsl:text><strong><xsl:copy-of select="$text_results_refereed" /></strong>
				</xsl:if>
			</dd>
			</div>
		</xsl:if>
	</xsl:template>

	<!--
		TEMPLATE: RECORD YEAR
	-->	
	
	<xsl:template name="record-year">
		<xsl:if test="year">
			<div>
			<dt><xsl:copy-of select="$text_results_year" />:</dt>
			<dd><xsl:value-of select="year" /></dd>
			</div>
		</xsl:if>
	</xsl:template>

	<!--
		TEMPLATE: RECORD INSTITUTION
	-->	
	
	<xsl:template name="record-institution">
		<xsl:if test="institution">
			<div>
			<dt><xsl:copy-of select="$text_record_inst" />:</dt>
			<dd><xsl:value-of select="institution" /></dd>
			</div>
		</xsl:if>
	</xsl:template>

	<!--
		TEMPLATE: RECORD DEGREE
	-->	
	
	<xsl:template name="record-degree">
		<xsl:if test="degree">
			<div>
			<dt><xsl:copy-of select="$text_record_degree" />:</dt>
			<dd><xsl:value-of select="degree" /></dd>
			</div>
		</xsl:if>
	</xsl:template>

	<!--
		TEMPLATE: RECORD SOURCE
	-->	
	
	<xsl:template name="record-source">
		<div>
		<xsl:choose>
			<xsl:when test="journal">
				<dt><xsl:copy-of select="$text_results_published_in" />:</dt>
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
				<xsl:if test="format = 'Book Chapter'">
					<xsl:if test="publisher">
						<dt><xsl:copy-of select="$text_record_publisher" />:</dt>
						<dd>
							<xsl:value-of select="place" /><xsl:text>: </xsl:text>
							<xsl:value-of select="publisher" /><xsl:text>, </xsl:text>
							<xsl:value-of select="year" />
						</dd>
					</xsl:if>
				</xsl:if>
			</xsl:when>
			<xsl:when test="format = 'Book'">
				<xsl:if test="publisher">
					<dt><xsl:copy-of select="$text_record_publisher" />:</dt>
					<dd>
						<xsl:value-of select="place" /><xsl:text>: </xsl:text>
						<xsl:value-of select="publisher" /><xsl:text>, </xsl:text>
						<xsl:value-of select="year" />
					</dd>
				</xsl:if>
			</xsl:when>
		</xsl:choose>
		</div>
	</xsl:template>

	<!--
		TEMPLATE: RECORD DATABASE
	-->	
	
	<xsl:template name="record-database">
		<div>
		<dt><xsl:copy-of select="$text_record_database" />:</dt>
		<dd>
			<xsl:variable name="metalib_id" select="metalib_id"/>
			
			<xsl:choose>
				<xsl:when test="//database_links/database[@metalib_id = $metalib_id]/url">				
					<a>
					<xsl:attribute name="href">
						<xsl:value-of select="//database_links/database[@metalib_id = $metalib_id]/url"/>
					</xsl:attribute>
					
					<xsl:value-of select="database_name" />
					</a>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="database_name" />
				</xsl:otherwise>
			</xsl:choose>
		</dd>
		</div>
	</xsl:template>

	<!--
		TEMPLATE: RECORD ACTIONS
	-->	
	
	<xsl:template name="record-actions">
		<div id="recordFullText" class="raisedBox recordActions">
			<xsl:call-template name="record-action-fulltext" />	<!-- Full-Text -->
			<xsl:call-template name="record-action-save" />		<!-- save record option -->
		</div>
	</xsl:template>
	
	<xsl:template name="record-action-fulltext">
	
		<div id="umlaut_fulltext" class="umlaut_content" style="display:none;"></div>
		
		<xsl:if test="full_text_bool != ''">
			<xsl:call-template name="full_text_links">
				<xsl:with-param name="class">recordFullTextOption fullTextLink</xsl:with-param>
			</xsl:call-template>
		</xsl:if>
		
		<!-- lings to catalog -->
		<xsl:call-template name="record-action-fulltext-catalog"/>
		
		<!-- other fulltext links -->
		<xsl:call-template name="record-action-fulltext-option"/>
		
	</xsl:template>

	<!--
		TEMPLATE: RECORD ABSTRACT
	-->
	
	<xsl:template name="record-abstract">
		<xsl:if test="abstract">
			<h2><xsl:copy-of select="$text_record_summary" /></h2>
			<div class="recordAbstract">
				<xsl:value-of select="abstract" />
			</div>
		</xsl:if>
	</xsl:template>

	<!--
		TEMPLATE: RECORD RECOMMENDATIONS
	-->
	
	<xsl:template name="record-recommendations">
		<xsl:if test="//recommendations/record">
		
			<h2><xsl:call-template name="text_recommendation_header" />:</h2>
			<ul id="recommendations">
				<xsl:for-each select="//recommendations/record/xerxes_record">
					<li class="result">
						<div class="resultsTitle">
							<a href="{../url_open}"><xsl:value-of select="title_normalized" /></a>
						</div>
						<div class="resultsInfo">
							<div class="resultsType">
								<xsl:call-template name="text_results_format">
									<xsl:with-param name="format" select="format" />
								</xsl:call-template>
							</div>
							
							<xsl:value-of select="$text_results_author" /><xsl:text> </xsl:text><xsl:value-of select="primary_author" /><br />
							<xsl:value-of select="journal" />
							<xsl:call-template name="full_text_options" />
						</div>
					</li>
				</xsl:for-each>	
			</ul>	
		</xsl:if>
	</xsl:template>

	<!--
		TEMPLATE: RECORD TABLE OF CONTENTS
	-->
	
	<xsl:template name="record-toc">
		<xsl:if test="toc">
			<h2>
				<xsl:choose>
					<xsl:when test="format = 'Book'">
						<xsl:copy-of select="$text_record_chapters" />:
					</xsl:when>
					<xsl:otherwise>
						<xsl:copy-of select="$text_record_contents" />:
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
								<xsl:text> </xsl:text><xsl:copy-of select="$text_results_author" /><xsl:text> </xsl:text>
								<xsl:value-of select="author" />
							</xsl:otherwise>
						</xsl:choose>
					</li>
				</xsl:for-each>
				</ul>
			</div>
		</xsl:if>
	</xsl:template>

	<!--
		TEMPLATE: RECORD LANGUAGE
	-->
	
	<xsl:template name="record-language">
		<xsl:if test="language">
			<h2><xsl:copy-of select="$text_record_language_label" /></h2>
			<ul>
				<li><xsl:value-of select="language" /></li>
			</ul>
		</xsl:if>
	</xsl:template>

	<!--
		TEMPLATE: RECORD SUBJECTS
	-->
	
	<xsl:template name="record-subjects">
		<xsl:if test="subjects">
			<h2><xsl:copy-of select="$text_record_subjects" />:</h2>
			<ul>
				<xsl:for-each select="subjects/subject">
					<li><xsl:value-of select="text()" /></li>
				</xsl:for-each>
			</ul>
		</xsl:if>
	</xsl:template>

	<!--
		TEMPLATE: RECORD STANDARD NUMBERS
	-->
	
	<xsl:template name="record-standard_numbers">
		<xsl:if test="standard_numbers">
			<h2><xsl:copy-of select="$text_record_standard_nos" />:</h2>
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
	</xsl:template>

	<!--
		TEMPLATE: RECORD NOTES
	-->

	<xsl:template name="record-notes">
		<xsl:if test="notes">
			<h2><xsl:copy-of select="$text_record_notes" />:</h2>
			<ul>
				<xsl:for-each select="notes/note">
					<li><xsl:value-of select="text()" /></li>
				</xsl:for-each>
			</ul>
		</xsl:if>
	</xsl:template>












	<xsl:template name="record-action-save" />
	<xsl:template name="record-action-fulltext-catalog" />
	<xsl:template name="record-action-fulltext-option" />



</xsl:stylesheet>

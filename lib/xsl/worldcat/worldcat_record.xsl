<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2010 California State University
 version: $Id$
 package: Worldcat
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl"
	xmlns:amazon="http://webservices.amazon.com/AWSECommerceService/2005-10-05"
	exclude-result-prefixes="php amazon">

<xsl:import href="../includes.xsl" />
<xsl:import href="../books.xsl" />
<xsl:import href="worldcat.xsl" />

<xsl:import href="../citation/styles.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="breadcrumb">
	<xsl:call-template name="breadcrumb_worldcat">
		<xsl:with-param name="condition">2</xsl:with-param>
	</xsl:call-template>
	Record
</xsl:template>

<xsl:template name="page_name">
	<xsl:value-of select="//records/record/xerxes_record/title_normalized" />
</xsl:template>

<xsl:template name="main">

	<xsl:call-template name="individual_record_pager" />

	<div id="record">
	
	<xsl:for-each select="/*/results/records/record/xerxes_record">

		<xsl:variable name="source" 	select="request/source" />
		<xsl:variable name="isbn" 		select="standard_numbers/isbn[string-length(text()) = 10]" />
		<xsl:variable name="oclc" 		select="standard_numbers/oclc" />
		<xsl:variable name="record_id" 	select="record_id" />
		<xsl:variable name="year" 		select="year" />
		
			<div id="worldcatRecordBookCover" style="display:none">
				<img src="http://images.amazon.com/images/P/{$isbn}.01.jpg" alt="" class="book-jacket-large" />
			</div>
			
			<div id="worldcatRecord">
				
				<!-- Title -->
				
				<h1><xsl:call-template name="page_name" /></h1>
				
				<dl>
				
				<!-- Primary Author -->
				
				<xsl:if test="authors/author[@type = 'personal' and not(@additional)]">
					<div>
					<dt><xsl:copy-of select="$text_results_author" />:</dt>
					<dd>
						<xsl:value-of select="authors/author[@type = 'personal' and not(@additional)]/aufirst" />
						<xsl:text> </xsl:text>
						<xsl:value-of select="authors/author[@type = 'personal' and not(@additional)]/auinit" />
						<xsl:text> </xsl:text>
						<xsl:value-of select="authors/author[@type = 'personal' and not(@additional)]/aulast" />
					</dd>
					</div>
				</xsl:if>
		
				<!-- Corp. Authors -->
				
				<xsl:if test="authors/author[@type = 'corporate' and not(@additional)]">
					<div>
					<dt><xsl:copy-of select="$text_record_author_corp" />:</dt>
					<dd>
						<xsl:value-of select="authors/author[@type = 'corporate' and not(@additional)]/aucorp" />
					</dd>
					</div>
				</xsl:if>
		
				<!-- Conference -->
				
				<xsl:if test="authors/author[@type = 'conference' and not(@additional)]">
					<div>
					<dt><xsl:copy-of select="$text_record_conf" />:</dt>
					<dd>
						<xsl:value-of select="authors/author[@type = 'conference' and not(@additional)]/aucorp" />
					</dd>
					</div>
				</xsl:if>
				
				<!-- Format -->
				
				<xsl:if test="format">
					<div>
					<dt><xsl:copy-of select="$text_record_format_label" />:</dt>
					<dd>
						<xsl:call-template name="text_results_format">
							<xsl:with-param name="format" select="format" />
						</xsl:call-template>
					</dd>
					</div>
				</xsl:if>
				
				<!-- Edition -->

				<xsl:if test="edition">
					<div>
					<dt>Edition:</dt>
					<dd>
						<xsl:value-of select="edition" />
					</dd>
					</div>
				</xsl:if>
				
				<!-- Year -->
				
				<xsl:if test="year">
					<div>
					<dt><xsl:copy-of select="$text_results_year" />:</dt>
					<dd><xsl:value-of select="year" /></dd>
					</div>
				</xsl:if>
				
				<!-- Institution -->
				
				<xsl:if test="institution">
					<div>
					<dt><xsl:copy-of select="$text_record_inst" />:</dt>
					<dd><xsl:value-of select="institution" /></dd>
					</div>
				</xsl:if>
				
				<!-- Degree -->
				
				<xsl:if test="degree">
					<div>
					<dt><xsl:copy-of select="$text_record_degree" />:</dt>
					<dd><xsl:value-of select="degree" /></dd>
					</div>
				</xsl:if>
				
				<!-- Source -->
				
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
					</xsl:when>
					<xsl:otherwise>
						<xsl:if test="publisher">
							<dt><xsl:copy-of select="$text_record_publisher" />:</dt>
							<dd>
								<xsl:value-of select="place" /><xsl:text>: </xsl:text>
								<xsl:value-of select="publisher" /><xsl:text>, </xsl:text>
								<xsl:value-of select="year" />
							</dd>
						</xsl:if>
					</xsl:otherwise>
				</xsl:choose>
				</div>
				
				<!-- Description: extent and stuff like that -->
				
				<xsl:if test="description">
				
					<div>
					<dt>Description:</dt>
					<dd>
						<xsl:value-of select="description" />
					</dd>
					</div>
				</xsl:if>

				</dl>
				
				<xsl:call-template name="additional_full_record_data_main" />
				
				<xsl:call-template name="google_preview" />

				<div style="clear:both"></div>
			</div>
			
			<!-- Availability -->
			
			<div id="recordFullText" class="raisedBox recordActions">

				<xsl:call-template name="availability_lookup">
					<xsl:with-param name="record_id">
						<xsl:value-of select="$record_id" />
					</xsl:with-param>
					<xsl:with-param name="isbn"><xsl:value-of select="$isbn" /></xsl:with-param>
					<xsl:with-param name="oclc"><xsl:value-of select="$oclc" /></xsl:with-param>
					<xsl:with-param name="type">full</xsl:with-param>
					<xsl:with-param name="context">record</xsl:with-param>
				</xsl:call-template>
				
				<xsl:call-template name="additional_record_links" />
				
				<xsl:call-template name="sms_option" />

				<xsl:call-template name="worldcat_save_record">
					<xsl:with-param name="element">div</xsl:with-param>
					<xsl:with-param name="class">resultsAvailability</xsl:with-param>
					<xsl:with-param name="id"><xsl:value-of select="$record_id" /></xsl:with-param>
				</xsl:call-template>
				
				<xsl:if test="//worldcat_holdings/holdings">
									
					<div class="worldcatHoldings">
						
						<p>
							<img src="images/book.png" alt="" /><xsl:text> </xsl:text>
							
							<xsl:variable name="campus_count" select="count(//holdings/holding)" />
							
							<xsl:variable name="campus_count_word">
								<xsl:choose>
									<xsl:when test="$campus_count = 1">
										<xsl:text>One library has</xsl:text>
									</xsl:when>
									<xsl:when test="$campus_count = 2">
										<xsl:text>Two libraries have</xsl:text>
									</xsl:when>
									<xsl:when test="$campus_count = 3">
										<xsl:text>Three libraries have</xsl:text>
									</xsl:when>
									<xsl:when test="$campus_count = 4">
										<xsl:text>Four libraries have</xsl:text>
									</xsl:when>
									<xsl:when test="$campus_count = 5">
										<xsl:text>Five libraries have</xsl:text>
									</xsl:when>
									<xsl:when test="$campus_count = 6">
										<xsl:text>Six libraries have</xsl:text>
									</xsl:when>
									<xsl:when test="$campus_count = 7">
										<xsl:text>Seven libraries have</xsl:text>
									</xsl:when>
									<xsl:when test="$campus_count = 8">
										<xsl:text>Eight libraries have</xsl:text>
									</xsl:when>
									<xsl:when test="$campus_count = 9">
										<xsl:text>Nine libraries have</xsl:text>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="$campus_count" /><xsl:text> libraries have</xsl:text>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:variable>
										
							<xsl:value-of select="$campus_count_word" /><xsl:text> this </xsl:text>
							<xsl:value-of select="php:function('strtolower', string(format))" />
						</p>
				
						<xsl:if test="//holdings/holding">
							<ul>
							<xsl:for-each select="//holdings/holding">
								<xsl:sort select="physicalLocation" />
								<xsl:variable name="oclc_url" select="php:function('urlencode', string(electronicAddress/text))" />
								<li>
									<a href="./?{$language_param}&amp;base={//request/base}&amp;action=bounce&amp;url={$oclc_url}">
										<xsl:value-of select="physicalLocation" />
									</a>
								</li>
							</xsl:for-each>
							</ul>
						</xsl:if>
						
					</div>
					
				</xsl:if>

			</div>
			
			<!-- alternate letter script, like CJK -->

			<xsl:if test="alt_scripts">
				<div class="recordAlternateScript">
					<h2><xsl:value-of select="alt_script_name" /> Information:</h2>
					<xsl:for-each select="alt_scripts/alt_script">
						<p><xsl:value-of select="text()"/></p>
					</xsl:for-each>
				</div>
			</xsl:if>

			<!-- abstract -->
			
			<xsl:if test="abstract or //amazon:EditorialReview/amazon:Content">
				<h2>Summary</h2>
				<div class="recordAbstract">
						<xsl:choose>
					<xsl:when test="abstract">
						<xsl:value-of select="abstract" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="php:function('strip_tags', string(//amazon:EditorialReview/amazon:Content), '&lt;p&gt;,&lt;a&gt;')" disable-output-escaping="yes" />
					</xsl:otherwise>
				</xsl:choose>
				</div>
			</xsl:if>

			<!-- subjects -->
			
			<xsl:if test="../subject_link">
				<div class="recordData">
				<h2>Find other books and material on:</h2>
				<ul>
					<xsl:for-each select="../subject_link">
						<li><a href="{@link}"><xsl:value-of select="text()" /></a></li>
					</xsl:for-each>
				</ul>
				</div>
			</xsl:if>
			
			<!-- toc -->
			
			<xsl:if test="toc">
				<div class="recordData">
					<h2>
					<xsl:choose>

						<xsl:when test="contains(format,'Book')">
							<xsl:text>Chapters:</xsl:text>
						</xsl:when>
						<xsl:otherwise>
							<xsl:text>Contents:</xsl:text>
						</xsl:otherwise>
					</xsl:choose>
					</h2>
					
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
			
			<!-- language -->
			
			<xsl:if test="language">
				<div class="recordData">
				<h2>Language</h2>
				<ul>
					<li><xsl:value-of select="language" /></li>
				</ul>
				</div>
			</xsl:if>
			
			<!-- standard numbers -->
			
			<xsl:if test="standard_numbers">
				<div class="recordData">
				<h2>Standard Numbers</h2>
				<ul>
				<xsl:for-each select="standard_numbers/issn">
					<li>ISSN: <xsl:value-of select="text()" /></li>
				</xsl:for-each>
				<xsl:for-each select="standard_numbers/isbn">
					<li>ISBN: <xsl:value-of select="text()" /></li>
				</xsl:for-each>
				<xsl:for-each select="standard_numbers/gpo">
					<li>GPO Item: <xsl:value-of select="text()" /></li>
				</xsl:for-each>
				<xsl:for-each select="standard_numbers/govdoc">
					<li>Gov Doc: <xsl:value-of select="text()" /></li>
				</xsl:for-each>
				<xsl:for-each select="standard_numbers/oclc">
					<li>OCLC: <xsl:value-of select="text()" /></li>
				</xsl:for-each>
				</ul>
				</div>
			</xsl:if>

			<!-- additional authors -->

			<xsl:if test="authors/author[@additional='true' and @type != 'corporate']">
				<div class="recordData">
				<h2>Additional Authors</h2>
				<ul>
					<xsl:for-each select="authors/author[@additional='true']">
						<li>
							<xsl:choose>
								<xsl:when test="display">
									<xsl:value-of select="display" />							
								</xsl:when>
								<xsl:when test="@type = 'personal'">
									<xsl:value-of select="aufirst" />
									<xsl:text> </xsl:text>
									<xsl:value-of select="auinit" />
									<xsl:text> </xsl:text>
									<xsl:value-of select="aulast" />								
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="aucorp" />
								</xsl:otherwise>
							</xsl:choose>
						</li>
					</xsl:for-each>
				</ul>
				</div>

			</xsl:if>

			<!-- additional titles -->
			
			<xsl:if test="additional_titles">
				<div class="recordData">
				<h2>Additional Titles</h2>
				<ul>
					<xsl:for-each select="additional_titles/additional_title">
						<li><xsl:value-of select="text()" /></li>
					</xsl:for-each>
				</ul>
				</div>
			</xsl:if>

			<!-- series -->
			
			<xsl:if test="series">
				<div class="recordData">
				<h2>Series</h2>
				<ul>
					<xsl:for-each select="series/serie">
						<li><xsl:value-of select="text()" /></li>
					</xsl:for-each>
				</ul>
				</div>
			</xsl:if>

			<!-- notes -->
			
			<xsl:if test="notes">
				<div class="recordData">
				<h2>Additional Notes</h2>
				<ul>
					<xsl:for-each select="notes/note">
						<li><xsl:value-of select="text()" /></li>
					</xsl:for-each>
				</ul>
				</div>
			</xsl:if>

		<xsl:call-template name="additional_full_record_data_supplemental" />
	
	</xsl:for-each>
					
	</div>
	
</xsl:template>

<xsl:template name="sidebar">
	<xsl:call-template name="account_sidebar" />
	<xsl:call-template name="citation" />
</xsl:template>

</xsl:stylesheet>

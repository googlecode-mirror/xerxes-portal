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
    <xsl:if test="results/records/record">
    <div id="sidebar_float">
        <xsl:call-template name="account_sidebar"/>
        
        <div class="folderOutputs box">
    
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
			</div>
    </xsl:if>
    
    <xsl:call-template name="folder_header" />

    
		<xsl:choose>
		<xsl:when test="results/records/record">								
			
			<div class="folderResults">

				<div class="resultsPageOptions">

				

					<div>            
           <xsl:if test="sort_display">
						<span class="resultsSorting">
							sort by:<xsl:text> </xsl:text>
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
						</span>
					</xsl:if>
          
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
          <xsl:variable name="metalib_db_id" select="metalib_id" />
          
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
              <xsl:variable name="link_resolver_allowed" select="not(//database_links/database[@metalib_id = $metalib_db_id]/sfx_suppress = '1')" />
              							
							<xsl:choose>
							<xsl:when test="full_text_bool">
							
								<xsl:call-template name="full_text_links">
									<xsl:with-param name="class">resultsFullTextOption</xsl:with-param>
								</xsl:call-template>
								
							</xsl:when>
							<xsl:when test="$link_resolver_allowed and //fulltext/issn = standard_numbers/issn">
								<a href="{../url_open}" class="resultsFullText" target="{$link_target}">
									<img src="{$base_include}/images/html.gif" alt="full text online" width="16" height="16" border="0" />
                  <xsl:text> </xsl:text>
                  <xsl:copy-of select="$text_link_resolver_available" />
								</a>
							</xsl:when>
							<xsl:when test="$link_resolver_allowed">
								<a href="{../url_open}" class="resultsFullText" target="{$link_target}">
									<img src="{$base_url}/images/sfx.gif" alt="" />
                  <xsl:text> </xsl:text>
                  <xsl:copy-of select="$text_link_resolver_check" />
								</a>
							</xsl:when>
              
              <!-- no fulltext or link resolver? How about original record? -->
              <xsl:when test="links/link[@type='original_record'] and    (//config/show_all_original_record_links = 'true' or //config/original_record_links/database[@metalib_id = $metalib_db_id])">
                <a href="{links/link[@type='original_record' and position()=1]}" class="resultsFullText">
                  <img src="{$base_url}/images/famfamfam/link.png" alt="" />
                  <xsl:text> </xsl:text>
                  <xsl:copy-of select="$text_link_original_record"/>
                </a>
              </xsl:when>              
              <!-- if none of the above, 
                   but we DO have text in the record, tell them so. -->
              <xsl:when test="embeddedText/paragraph">
                  <a href="{$record_link}" class="resultsFulltext">
                    <img src="{$base_url}/images/famfamfam/page_go.png" alt="" />
                    Text in <xsl:value-of select="//config/application_name"/> record
                  </a>
              </xsl:when>
              
							</xsl:choose>
              
              <!-- link to native holdings? -->
              <xsl:if test="links/link[@type='holdings'] and (//config/show_all_holdings_links = 'true' or //config/holdings_links/database[@metalib_id=$metalib_db_id])">
                <span class="resultsAvailableOption">
                  <a href="{links/link[@type='holdings' and position()=1]}" class="resultsFullText">
                      <img src="{$base_url}/images/book.gif" alt="" />
                      <xsl:text> </xsl:text>                    
                      <xsl:copy-of select="$text_link_holdings"/>
                  </a>
                </span>
              </xsl:if>
								
						</div>
						
						<!--
						<div class="folderAvailability">
							<a href="#" class="resultsFullText">
								<img src="{$base_url}/images/edit.gif" alt="" border="0" />
								Edit this record
							 </a>
						</div>
						-->
						
						<div class="folderAvailability">
							<a class="deleteRecord resultsFullText" href="{../url_delete}">
								<img src="{$base_url}/images/delete.gif" alt="" border="0" />
								Delete this record
							 </a>
						</div>
						
						<xsl:if test="$temporarySession != 'true'">
							<xsl:call-template name="tag_input">
								<xsl:with-param name="record" select=".."/>
							</xsl:call-template>
							
						</xsl:if>	
							
					</div>
				</xsl:for-each>
			</div>
						
			<xsl:call-template name="paging_navigation" />
	
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
	
<!-- hidden div that will be used by autocompleter -->
<div class="autocomplete" id="tag_suggestions" style="display:none"></div>
</xsl:template>
</xsl:stylesheet>

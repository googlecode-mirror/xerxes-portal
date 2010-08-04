<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2010 California State University
 version: $Id$
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->
 
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" 
	xmlns:holdings="http://www.loc.gov/standards/iso20775/"  
	exclude-result-prefixes="php holdings">
	
<!-- 	
	TEMPLATE: HOLDINGS LOOKUP
-->

<xsl:template name="holdings_lookup">
	<xsl:param name="record_id" />
	<xsl:param name="isbn" />
	<xsl:param name="oclc" />
	<xsl:param name="type" select="'none'" />
	<xsl:param name="nosave" />
	<xsl:param name="context">record</xsl:param>
	
	<xsl:variable name="id_prefix">
		<xsl:choose>
			<xsl:when test="$record_id != ''">ID:<xsl:value-of select="$record_id" /></xsl:when>
			<xsl:otherwise>missing</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<xsl:variable name="isbn_prefix">
		<xsl:choose>
			<xsl:when test="$isbn != ''">ISBN:<xsl:value-of select="$isbn" /></xsl:when>
			<xsl:otherwise>missing</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<xsl:variable name="oclc_prefix">
		<xsl:choose>
			<xsl:when test="$oclc != ''">OCLC:<xsl:value-of select="$oclc" /></xsl:when>
			<xsl:otherwise>missing</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<xsl:variable name="source" select="//request/source" />
	
	<xsl:choose>
		<xsl:when test="//cached/object[contains(@id,$isbn_prefix) or contains(@id,$oclc_prefix) or contains(@id,$id_prefix)]">
					
			<!-- this record is cached already -->
			
			<xsl:variable name="totalCopies">
				<xsl:choose>
					<xsl:when test="//cached/object[contains(@id,$isbn_prefix) or contains(@id,$oclc_prefix) or contains(@id,$id_prefix)]//holdings:copiesSummary">
						<xsl:value-of select="//cached/object[contains(@id,$isbn_prefix) or contains(@id,$oclc_prefix) or contains(@id,$id_prefix)]//holdings:copiesCount" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>0</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			
			<xsl:variable name="onlineCopies">
				<xsl:choose>
					<xsl:when test="//cached/object[contains(@id,$isbn_prefix) or contains(@id,$oclc_prefix) or contains(@id,$id_prefix)]//holdings:copiesSummary">
						<xsl:value-of select="//cached/object[contains(@id,$isbn_prefix) or contains(@id,$oclc_prefix) or contains(@id,$id_prefix)]//holdings:copiesSummary/holdings:status[holdings:availableFor = '4']/holdings:availableCount" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>0</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			
			<xsl:variable name="printCopies" select="$totalCopies - $onlineCopies" />

			<xsl:variable name="printAvailable">
				<xsl:choose>
					<xsl:when test="//cached/object[contains(@id,$isbn_prefix) or contains(@id,$oclc_prefix) or contains(@id,$id_prefix)]//holdings:copiesSummary">
						<xsl:value-of select="//cached/object[contains(@id,$isbn_prefix) or contains(@id,$oclc_prefix) or contains(@id,$id_prefix)]//holdings:copiesSummary/holdings:status[holdings:availableFor = '1']/holdings:availableCount" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>0</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			
			<xsl:choose>
				<xsl:when test="$type = 'none'">
					<xsl:call-template name="holdings_lookup_none">
						<xsl:with-param name="id" select="$id_prefix" />
						<xsl:with-param name="isbn" select="$isbn_prefix" />
						<xsl:with-param name="oclc" select="$oclc_prefix" />
					</xsl:call-template>			
				</xsl:when>
				<xsl:when test="$type = 'summary'">
					<xsl:call-template name="holdings_lookup_summary">
						<xsl:with-param name="id" select="$id_prefix" />
						<xsl:with-param name="isbn" select="$isbn_prefix" />
						<xsl:with-param name="oclc" select="$oclc_prefix" />
						<xsl:with-param name="nosave" select="$nosave" />
						<xsl:with-param name="onlineCopies" select="$onlineCopies" />
						<xsl:with-param name="printAvailable" select="$printAvailable" />
					</xsl:call-template>			
				</xsl:when>
				<xsl:when test="$type = 'consortium'">
					<xsl:call-template name="holdings_lookup_consortium">
						<xsl:with-param name="id" select="$id_prefix" />
						<xsl:with-param name="isbn" select="$isbn_prefix" />
						<xsl:with-param name="oclc" select="$oclc_prefix" />
						<xsl:with-param name="printAvailable" select="$printAvailable" />
					</xsl:call-template>			
				</xsl:when>
				<xsl:otherwise>
					<xsl:call-template name="holdings_lookup_full">
						<xsl:with-param name="id" select="$id_prefix" />
						<xsl:with-param name="isbn" select="$isbn_prefix" />
						<xsl:with-param name="oclc" select="$oclc_prefix" />
						<xsl:with-param name="printCopies" select="$printCopies" />
						<xsl:with-param name="printAvailable" select="$printAvailable" />
						<xsl:with-param name="context" select="$context" />
					</xsl:call-template>
				</xsl:otherwise>
			</xsl:choose>
	
	
		</xsl:when>

		<xsl:when test="//config/lookup or //worldcat_groups/group[@id = $source]/lookup/address">
					
			<!-- need to get it dynamically with ajax -->
		
			<div id="{$source}:{$record_id}:{$isbn}:{$oclc}:{$type}:{//request/base}" class="availabilityLoad"></div>

		</xsl:when>
		<xsl:otherwise>
			
			<xsl:call-template name="holdings_lookup_none">
				<xsl:with-param name="id" select="$record_id" />
				<xsl:with-param name="isbn" select="$isbn" />
				<xsl:with-param name="oclc" select="$oclc" />
			</xsl:call-template>	
			
		</xsl:otherwise>
	</xsl:choose>

</xsl:template>

<!-- 	
	TEMPLATE: CONSORTIUM LOOKUP ( BRIEF RESULTS )
	For groups with an consortium server lookup
-->

<xsl:template name="holdings_lookup_consortium">
	<xsl:param name="id" />
	<xsl:param name="isbn" />
	<xsl:param name="oclc" />
	<xsl:param name="printAvailable" />
		
	<xsl:choose>
		<xsl:when test="not(//cached/object[contains(@id,$isbn) or contains(@id,$oclc) or contains(@id,$id)])">
			<p><strong style="color:#CC0000">Not found</strong></p>
		</xsl:when>
		<xsl:when test="$printAvailable > 0">
			<p><strong style="color:#009900">Available for borrowing</strong></p>
		</xsl:when>
		<xsl:otherwise>
			<p><strong style="color:#CC0000">No copies available for borrowing</strong></p>

			<xsl:call-template name="ill_option">
				<xsl:with-param name="element">div</xsl:with-param>
				<xsl:with-param name="class">resultsAvailability</xsl:with-param>
				<xsl:with-param name="id"><xsl:value-of select="$id" /></xsl:with-param>
				<xsl:with-param name="oclc"><xsl:value-of select="$oclc" /></xsl:with-param>
				<xsl:with-param name="isbn"><xsl:value-of select="$isbn" /></xsl:with-param>
			</xsl:call-template>

		</xsl:otherwise>
	</xsl:choose>

</xsl:template>

<!-- 	
	TEMPLATE: NO LOOKUP
	For groups that have no look-up enabled
-->

<xsl:template name="holdings_lookup_none">
	<xsl:param name="id" />
	<xsl:param name="isbn" />
	<xsl:param name="oclc" />
	
	<xsl:call-template name="ill_option">
		<xsl:with-param name="element">div</xsl:with-param>
		<xsl:with-param name="class">resultsAvailability</xsl:with-param>
		<xsl:with-param name="id"><xsl:value-of select="$id" /></xsl:with-param>
		<xsl:with-param name="oclc"><xsl:value-of select="$oclc" /></xsl:with-param>
		<xsl:with-param name="isbn"><xsl:value-of select="$isbn" /></xsl:with-param>
	</xsl:call-template>	
		
</xsl:template>

<!-- 	
	TEMPLATE: HOLDINGS LOOKUP SUMMARY
	A summary view of the holdings information
-->

<xsl:template name="holdings_lookup_summary">
	<xsl:param name="id" />
	<xsl:param name="isbn" />
	<xsl:param name="oclc" />
	<xsl:param name="nosave" />
	<xsl:param name="onlineCopies" />
	<xsl:param name="printAvailable" />

	<ul class="worldcatAvailabilitySummary">
	
	<xsl:call-template name="holdings_full_text">
		<xsl:with-param name="element">li</xsl:with-param>
		<xsl:with-param name="class">resultsHoldings</xsl:with-param>
		<xsl:with-param name="id"><xsl:value-of select="$id" /></xsl:with-param>
		<xsl:with-param name="oclc"><xsl:value-of select="$oclc" /></xsl:with-param>
		<xsl:with-param name="isbn"><xsl:value-of select="$isbn" /></xsl:with-param>
	</xsl:call-template>
	
	<xsl:choose>
		<xsl:when test="not(//cached/object[contains(@id,$isbn) or contains(@id,$oclc) or contains(@id,$id)])">
			<xsl:if test="//request/source = 'local'">
				<li class="worldcatAvailabilityMissing"><img src="images/book-out.gif" alt="" />&#160; No Copies Available</li>
			</xsl:if>
			<xsl:call-template name="ill_option">
				<xsl:with-param name="element">li</xsl:with-param>
				<xsl:with-param name="class">resultsHoldings</xsl:with-param>
				<xsl:with-param name="id"><xsl:value-of select="$id" /></xsl:with-param>
				<xsl:with-param name="oclc"><xsl:value-of select="$oclc" /></xsl:with-param>
				<xsl:with-param name="isbn"><xsl:value-of select="$isbn" /></xsl:with-param>
			</xsl:call-template>
		</xsl:when>
		<xsl:otherwise>
			<xsl:variable name="copy_count" select="$onlineCopies + $printAvailable" />
			<li>
				<xsl:choose>
					<xsl:when test="$copy_count = '0'"><img src="images/book-out.gif" alt="" />&#160; No copies available</xsl:when>
					<xsl:when test="$copy_count = '1'"><img src="images/book.gif" alt="" />&#160; 1 copy available</xsl:when>
					<xsl:otherwise><img src="images/book.gif" alt="" />&#160; <xsl:value-of select="$copy_count" /> copies available</xsl:otherwise>
				</xsl:choose>
			</li>
		</xsl:otherwise>
	</xsl:choose>
	</ul>
	
</xsl:template>


<!-- 	
	TEMPLATE: HOLDINGS LOOKUP FULL
	A full table-view of the (print) holdings information, with full-text below
-->

<xsl:template name="holdings_lookup_full">
	<xsl:param name="id" />
	<xsl:param name="isbn" />
	<xsl:param name="oclc" />	
	<xsl:param name="printCopies" />
	<xsl:param name="printAvailable" />
	<xsl:param name="context">record</xsl:param>
	
	<xsl:variable name="group" select="//request/source" />
	<xsl:variable name="consortium">
		<xsl:choose>
			<xsl:when test="//worldcat_groups/group[@id = $group]/lookup/display = 'consortium'">
				<xsl:text>true</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>false</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<xsl:variable name="has_summary">
		<xsl:choose>
			<xsl:when test="//cached/object[contains(@id,$isbn) or contains(@id,$oclc) or contains(@id,$id)]//holdings/holding">
				<xsl:text>1</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>0</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<xsl:choose>
		<xsl:when test="$has_summary = '1'">
		
			<p><strong>Online</strong></p>
			
			<div class="summaryOnlineHolding">
				<xsl:call-template name="holdings_full_text">
					<xsl:with-param name="element">span</xsl:with-param>
					<xsl:with-param name="class">resultsAvailability</xsl:with-param>
					<xsl:with-param name="id"><xsl:value-of select="$id" /></xsl:with-param>
					<xsl:with-param name="oclc"><xsl:value-of select="$oclc" /></xsl:with-param>
					<xsl:with-param name="isbn"><xsl:value-of select="$isbn" /></xsl:with-param>
				</xsl:call-template>
			</div>
		
			<p><strong>Print holdings</strong></p>
		
			<xsl:for-each select="//cached/object[contains(@id,$isbn) or contains(@id,$oclc) or contains(@id,$id)]//holdings/holding">
				<ul class="holdingsSummaryStatement">
					<xsl:for-each select="data">
						<li><xsl:value-of select="@key" />: <xsl:value-of select="@value" /></li>
					</xsl:for-each>
				</ul>
			</xsl:for-each>

			<xsl:if test="$context = 'record'">
			
				<p><strong>Bound volumes</strong></p>
				
				<xsl:call-template name="holdings_item_table">
					<xsl:with-param name="id" select="$id" />
					<xsl:with-param name="isbn" select="$isbn" />
					<xsl:with-param name="oclc" select="$oclc" />
					<xsl:with-param name="consortium" select="$consortium" />
				</xsl:call-template>
			
			</xsl:if>
			
		</xsl:when>
		<xsl:otherwise>
	
			<xsl:if test="$printCopies != '0'">
			
				<xsl:if test="$printAvailable > 0 and $consortium = 'true'">
					<div class="worldcatConsortiumRequest">
						<form action="{//cached/object[contains(@id,$isbn) or contains(@id,$oclc) or contains(@id,$id)]//holdings:resourceIdentifier/holdings:value}" method="get">
							<input type="submit" value="Request this item" />
						</form>
					</div>
				</xsl:if>
				
				<xsl:call-template name="holdings_item_table">
					<xsl:with-param name="id" select="$id" />
					<xsl:with-param name="isbn" select="$isbn" />
					<xsl:with-param name="oclc" select="$oclc" />
					<xsl:with-param name="consortium" select="$consortium" />
				</xsl:call-template>
			
			</xsl:if>
			
			<xsl:call-template name="holdings_full_text">
				<xsl:with-param name="element">span</xsl:with-param>
				<xsl:with-param name="class">resultsAvailability</xsl:with-param>
				<xsl:with-param name="id"><xsl:value-of select="$id" /></xsl:with-param>
				<xsl:with-param name="oclc"><xsl:value-of select="$oclc" /></xsl:with-param>
				<xsl:with-param name="isbn"><xsl:value-of select="$isbn" /></xsl:with-param>
			</xsl:call-template>
		
			<xsl:call-template name="ill_option">
				<xsl:with-param name="element">span</xsl:with-param>
				<xsl:with-param name="class">resultsAvailability</xsl:with-param>
				<xsl:with-param name="oclc"><xsl:value-of select="$oclc" /></xsl:with-param>
				<xsl:with-param name="isbn"><xsl:value-of select="$isbn" /></xsl:with-param>
			</xsl:call-template>
		</xsl:otherwise>
	</xsl:choose>

</xsl:template>


<xsl:template name="holdings_item_table">
	<xsl:param name="id" />
	<xsl:param name="isbn" />
	<xsl:param name="oclc" />
	<xsl:param name="consortium" />
		<div>
			<xsl:attribute name="class">
				<xsl:text>worldcatAvailable</xsl:text>
				<xsl:if test="//request/action = 'record'">
					<xsl:text> worldcatAvailableRecord</xsl:text>
				</xsl:if>
			</xsl:attribute>
			
			<table class="holdingsTable">
			<tr>
				<xsl:if test="$consortium = 'true'">
					<th>Institution</th>
				</xsl:if>
				<th>Location</th>
				<th>Call Number</th>
				<th>Status</th>
			</tr>
			<xsl:for-each select="//cached/object[contains(@id,$isbn) or contains(@id,$oclc) or contains(@id,$id)]//holdings:holding/holdings:holdingsSimple/holdings:copyInformation[not(holdings:electronicLocator)]">
				<tr>
					<xsl:if test="location">
						<td><xsl:value-of select="location" /></td>
					</xsl:if>
					<td><xsl:value-of select="holdings:sublocation" /></td>
					<td><xsl:value-of select="holdings:shelfLocator" /></td>
					<td><xsl:value-of select="holdings:note" /></td>
				</tr>
			</xsl:for-each>
			</table>
		</div>

</xsl:template>

<!-- 	
	TEMPLATE: HOLDINGS FULL TEXT
	just the full-text on a holdings lookup
-->

<xsl:template name="holdings_full_text">
	<xsl:param name="element" />
	<xsl:param name="class" />
	<xsl:param name="id" />
	<xsl:param name="oclc" />
	<xsl:param name="isbn" />
				
	<xsl:for-each select="//cached/object[contains(@id,$isbn) or contains(@id,$oclc) or contains(@id,$id)]//holdings:copyInformation/holdings:electronicLocator">
		<xsl:element name="{$element}">
			<xsl:attribute name="class"><xsl:value-of select="$class" /></xsl:attribute>
			<a href="{holdings:pointer}" class="recordAction" target="" >
				<img src="{$base_include}/images/html.gif" alt="" width="16" height="16" border="0" /> 
				<xsl:choose>
					<xsl:when test="holdings:note">
						<xsl:value-of select="holdings:note" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:copy-of select="$text_records_fulltext_available" />
					</xsl:otherwise>
				</xsl:choose>
			</a>
		</xsl:element>
	</xsl:for-each>
		
</xsl:template>

<!-- 	
	TEMPLATE: ILL OPTION
	just the ill link on a holdings lookup
-->

<xsl:template name="ill_option">
	<xsl:param name="element" />
	<xsl:param name="class" />
	<xsl:param name="id" />
	<xsl:param name="oclc" />
	<xsl:param name="isbn" />
	
	<xsl:variable name="oclc_noprefix">
		<xsl:choose>
			<xsl:when test="substring($oclc,1,5) = 'OCLC:'">
				<xsl:value-of select="substring($oclc, 6)" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$oclc" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<xsl:variable name="source"  select="//request/source|//request/requester"/>
	
	<xsl:variable name="available">
		<xsl:for-each select="/*/cached/object[contains(@id,$isbn) or contains(@id,$oclc) or contains(@id,$id)]//holdings:copiesSummary">
			<xsl:if test="holdings:status[holdings:availableFor = '1']/holdings:availableCount &gt; 0 or 
			holdings:status[holdings:availableFor = '4']/holdings:availableCount &gt; 0">
				<xsl:text>yes</xsl:text>
			</xsl:if>
		</xsl:for-each>
	</xsl:variable>
	
	<xsl:if test="$available = ''">

		<xsl:element name="{$element}">
			<xsl:attribute name="class"><xsl:value-of select="$class" /></xsl:attribute> 
			<a target="{$link_target}" href="{../url_open}" class="recordAction">
				<xsl:choose>
					<xsl:when test="//worldcat_groups/group[@id = $source]/lookup/ill_text">
						<img src="images/ill.gif" alt="" border="0" class="miniIcon linkResolverLink "/>
						<xsl:text> </xsl:text>
						<xsl:value-of select="//worldcat_groups/group[@id = $source]/lookup/ill_text" />
					</xsl:when>
					<xsl:otherwise>
						<img src="images/sfx.gif" alt="" border="0" class="miniIcon linkResolverLink "/>
						<xsl:text> </xsl:text>
						<xsl:text> Check for availability </xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</a>
		</xsl:element>
	</xsl:if>
		
</xsl:template>
		
<!-- 	
	TEMPLATE: WORLDCAT SAVE RECORD
	for worldcat
-->

<xsl:template name="worldcat_save_record">
	<xsl:param name="element" />
	<xsl:param name="class" />
	<xsl:param name="id" />
	
	<xsl:variable name="record_id"><xsl:value-of select="$id" /></xsl:variable>
		
	<xsl:element name="{$element}">
	
		<xsl:attribute name="class">saveRecord <xsl:value-of select="$class" /> recordAction</xsl:attribute>

		<img id="folder_worldcat{$id}" width="17" height="15" alt="" border="0" >
		<xsl:attribute name="src">
			<xsl:choose> 
				<xsl:when test="//request/session/resultssaved[@key = $record_id]">images/folder_on.gif</xsl:when>
				<xsl:otherwise>images/folder.gif</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
		</img>

		<xsl:text> </xsl:text>
		
		<xsl:choose>
		
			<xsl:when test="//request/session/username">

				<a id="link_worldcat:{$id}" href="{../url_save}">
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
						(<xsl:text> </xsl:text>
						<a href="{//navbar/element[@id = 'login']/url}">
							<xsl:copy-of select="$text_results_record_saved_perm" />
						</a>
						<xsl:text> </xsl:text>)
					</span>
				</xsl:if>		
				
		
				<!-- label/tag input for saved records, if record is saved and it's not a temporary session -->
											
				<xsl:if test="//request/session/resultssaved[@key = $record_id] and not(//request/session/role = 'guest' or //request/session/role = 'local')">
					<div class="results_label resultsFullText" id="label_{$record_id}" > 
						<xsl:call-template name="tag_input">
							<xsl:with-param name="record" select="//saved_records/saved[@id = $record_id]" />
							<xsl:with-param name="context" select="'the results page'" />
						</xsl:call-template>	
					</div>
				</xsl:if>
			</xsl:when>
			<xsl:otherwise>
				<a>
				<xsl:attribute name="href"><xsl:value-of select="//navbar/element[@id = 'login']/url" /></xsl:attribute>
					Login to save records
				</a>
			</xsl:otherwise>
		</xsl:choose>
		
	</xsl:element>
		
</xsl:template>

<xsl:template name="worldcat_result">
	
	<xsl:param name="source">local</xsl:param>
	
	<xsl:variable name="isbn" 		select="standard_numbers/isbn[string-length(text()) = 10]" />
	<xsl:variable name="oclc" 		select="standard_numbers/oclc" />
	<xsl:variable name="year" 		select="year" />
	<xsl:variable name="record_id">
		<xsl:if test="//request/base != 'worldcat'">
			<xsl:value-of select="record_id" />
		</xsl:if>
	</xsl:variable>
	
	<xsl:variable name="display">
		<xsl:choose>
			<xsl:when test="//worldcat_groups/group[@id = $source]/lookup/display">
				<xsl:value-of select="//worldcat_groups/group[@id = $source]/lookup/display" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="//config/lookup_display" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<li class="result">
	
		<div class="worldcatBookCover">
			<img src="http://images.amazon.com/images/P/{$isbn}.01.THUMBZZZ.jpg" alt="" class="book-jacket" />
		</div>
	
		<div class="worldcatResult">
		
			<div class="resultsTitle">
				<a href="{../url}" class="resultsTitle">
					<xsl:value-of select="title_normalized" />
				</a>
			</div>
			
			<div class="resultsInfo">
			
				<div class="resultsType">
					<xsl:value-of select="format" />
				</div>

				<div class="resultsAbstract">
				
					<xsl:if test="abstract">
						<div class="worldcatAbstractData">
							<xsl:choose>
								<xsl:when test="string-length(abstract) &gt; 300">
									<xsl:value-of select="substring(summary, 1, 300)" /> . . .
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="abstract" />
								</xsl:otherwise>
							</xsl:choose>
						</div>
					</xsl:if>
					
					<xsl:if test="primary_author">
						<div class="resultsBookSummary">
							By: <xsl:value-of select="primary_author" /><br />
							<xsl:if test="format = 'Book' and publisher">
								<xsl:if test="place">
									<xsl:value-of select="place" />
									<xsl:text>: </xsl:text>									
								</xsl:if>
								<xsl:value-of select="publisher" />
								<xsl:if test="year">
									<xsl:text>, </xsl:text>
									<xsl:value-of select="year" />
								</xsl:if>								
							</xsl:if>								
						</div>				
					</xsl:if>
					
				</div>
				
				<div class="recordActions">
				
					<xsl:call-template name="holdings_lookup">
						<xsl:with-param name="record_id"><xsl:value-of select="$record_id" /></xsl:with-param>
						<xsl:with-param name="isbn"><xsl:value-of select="$isbn" /></xsl:with-param>
						<xsl:with-param name="oclc"><xsl:value-of select="$oclc" /></xsl:with-param>
						<xsl:with-param name="type"><xsl:value-of select="$display" /></xsl:with-param>
						<xsl:with-param name="context">results</xsl:with-param>
					</xsl:call-template>
	
					<xsl:call-template name="worldcat_save_record">
						<xsl:with-param name="element">div</xsl:with-param>
						<xsl:with-param name="class">saveRecord</xsl:with-param>
						<xsl:with-param name="id"><xsl:value-of select="$record_id" /></xsl:with-param>
					</xsl:call-template>
					
				</div>
				<div style="clear:both"></div>	
				
			</div>
		</div>
	</li>

</xsl:template>

<xsl:template name="google_preview">

	<xsl:variable name="isbn" select="//results/records/record/xerxes_record/standard_numbers/isbn" />

	<xsl:variable name="ids">
		<xsl:for-each select="//results/records/record/xerxes_record/standard_numbers/isbn|standard_numbers/oclc">
			<xsl:choose>
				<xsl:when test="name() = 'isbn'">
					<xsl:text>'ISBN:</xsl:text><xsl:value-of select="text()" /><xsl:text>'</xsl:text>
				</xsl:when>
				<xsl:when test="name() = 'oclc'">
					<xsl:text>'OCLC:</xsl:text><xsl:value-of select="text()" /><xsl:text>'</xsl:text>
				</xsl:when>
			</xsl:choose>
			<xsl:if test="following-sibling::isbn|following-sibling::oclc">
				<xsl:text>,</xsl:text>
			</xsl:if>
		</xsl:for-each>
	
	</xsl:variable>
	
	<div class="google_preview">
		<script type="text/javascript" src="http://books.google.com/books/previewlib.js"></script>
		<script type="text/javascript">GBS_insertPreviewButtonPopup([<xsl:value-of select="$ids" />]);</script>
		<noscript><a href="http://books.google.com/books?as_isbn={$isbn}">Check for more information at Google Book Search</a></noscript>
	</div>

</xsl:template>


</xsl:stylesheet>

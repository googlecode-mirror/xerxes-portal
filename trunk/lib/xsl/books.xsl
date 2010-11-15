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
	
	
	<xsl:variable name="temp_text_bound_volumes">Bound volumes</xsl:variable>
	
	
<!-- 	
	TEMPLATE: AVAILABILITY LOOKUP
-->

<xsl:template name="availability_lookup">
	<xsl:param name="record_id" />
	<xsl:param name="isbn" />
	<xsl:param name="oclc" />
	<xsl:param name="type" select="'none'" />
	<xsl:param name="nosave" />
	<xsl:param name="context">results</xsl:param>
		
	<xsl:variable name="source" select="//request/source" />
	
	<xsl:variable name="printAvailable" select="count(items/item[availability=1])" />
	<xsl:variable name="onlineCopies" select="count(links/link[@type != 'none'])" />
	<xsl:variable name="totalCopies" select="$printAvailable + $onlineCopies" />

	<xsl:choose>
	
		<xsl:when test="//config/lookup or //worldcat_groups/group[@id = $source]/lookup/address">
		
			<xsl:choose>		
				<xsl:when test="items or holdings or no_items = '1'">
				
					<!-- item and holdings data already fetched and in the XML response -->
				
					<!-- pick display type -->
				
					<xsl:choose>

						<xsl:when test="holdings">

							<xsl:call-template name="availability_lookup_holdings">
								<xsl:with-param name="context" select="$context" />
							</xsl:call-template>
							
						</xsl:when>
						<xsl:when test="$type = 'summary'">
						
							<xsl:call-template name="availability_lookup_summary">
								<xsl:with-param name="totalCopies" select="$totalCopies" />
								<xsl:with-param name="printAvailable" select="$printAvailable" />
							</xsl:call-template> 
							
						</xsl:when>
						<xsl:otherwise>
						
							<xsl:call-template name="availability_lookup_full">
								<xsl:with-param name="totalCopies" select="$totalCopies" />
							</xsl:call-template>
						
						</xsl:otherwise>
					</xsl:choose>
				
				</xsl:when>
	
				<!-- not here, so need to get it dynamically with ajax -->
		
				<xsl:otherwise>
							
					<div id="{$source}:{$record_id}:{$isbn}:{$oclc}:{$type}:{//request/base}" class="availabilityLoad"></div>
		
				</xsl:otherwise>				
			</xsl:choose>
			
			<!-- check for full-text -->
										
			<xsl:call-template name="availability_full_text">
				<xsl:with-param name="element">span</xsl:with-param>
				<xsl:with-param name="class">resultsAvailability</xsl:with-param>
			</xsl:call-template>
			
		</xsl:when>
		
		<!-- no lookup required, thanks -->
		
		<xsl:otherwise>
			<xsl:call-template name="availability_lookup_none" />	
		</xsl:otherwise>
	</xsl:choose>

</xsl:template>

<!-- 	
	TEMPLATE: NO LOOKUP
	For sources that have no look-up enabled
-->

<xsl:template name="availability_lookup_none">
	
	<xsl:call-template name="ill_option">
		<xsl:with-param name="element">div</xsl:with-param>
		<xsl:with-param name="class">resultsAvailability</xsl:with-param>
	</xsl:call-template>	
		
</xsl:template>

<!-- 	
	TEMPLATE: AVAILABILITY LOOKUP SUMMARY
	A summary view of the holdings information
-->

<xsl:template name="availability_lookup_summary">
	<xsl:param name="totalCopies" />
	<xsl:param name="printAvailable" />	
	
	<ul class="worldcatAvailabilitySummary">
	
	<xsl:choose>
		<xsl:when test="items/item and $printAvailable = '0'">
		
			<li class="worldcatAvailabilityMissing"><img src="images/book-out.png" alt="" />&#160; No Copies Available</li>

			<xsl:call-template name="ill_option">
				<xsl:with-param name="element">li</xsl:with-param>
				<xsl:with-param name="class">resultsHoldings</xsl:with-param>
			</xsl:call-template>

		</xsl:when>
		<xsl:when test="$printAvailable = '1'">
			<li><img src="images/book.png" alt="" />&#160; 1 copy available</li>
		</xsl:when>
		<xsl:when test="$printAvailable &gt; '1'">
			<li><img src="images/book.png" alt="" />&#160; <xsl:value-of select="$printAvailable" /> copies available</li>
		</xsl:when>			
	</xsl:choose>
	
	</ul>
	
</xsl:template>

<!-- 	
	TEMPLATE: AVAILABILITY LOOKUP HOLDINGS
	Display of holdings data for things like journals and newspapers
-->

<xsl:template name="availability_lookup_holdings">
	
	<xsl:param name="context">record</xsl:param>
	
	<xsl:if test="holdings//holding/url">

		<p><strong>Online</strong></p>
		
		<div class="summaryOnlineHolding">
			<xsl:call-template name="availability_full_text">
				<xsl:with-param name="element">span</xsl:with-param>
				<xsl:with-param name="class">resultsAvailability</xsl:with-param>
			</xsl:call-template>
		</div>
	</xsl:if>
	
	<xsl:if test="holdings/holding">

		<p><strong>Print holdings</strong></p>
	
		<xsl:for-each select="holdings/holding">
			<ul class="holdingsSummaryStatement">
				<xsl:for-each select="data">
					<li><xsl:value-of select="@key" />: <xsl:value-of select="@value" /></li>
				</xsl:for-each>
			</ul>
		</xsl:for-each>
		
	</xsl:if>

	<xsl:if test="$context = 'record'">
	
		<xsl:if test="items/item">
	
			<p><strong><xsl:value-of select="$temp_text_bound_volumes" /></strong></p>					
			<xsl:call-template name="availability_item_table" />
			
		</xsl:if>
	
	</xsl:if>
</xsl:template>


<!-- 	
	TEMPLATE: AVAILABILITY LOOKUP FULL
	A full table-view of the (print) holdings information, with full-text below
-->

<xsl:template name="availability_lookup_full">
	<xsl:param name="totalCopies" />

	
	<xsl:if test="count(items/item) != '0'">
		<xsl:call-template name="availability_item_table" />
	</xsl:if>
	
	<xsl:if test="$totalCopies = 0">
		<xsl:call-template name="ill_option">
			<xsl:with-param name="element">span</xsl:with-param>
			<xsl:with-param name="class">resultsAvailability</xsl:with-param>
		</xsl:call-template>
	</xsl:if>
			
</xsl:template>

<!-- 	
	TEMPLATE: AVAILABILITY ITEM TABLE
	Show the items in a table
-->

<xsl:template name="availability_item_table">

	<div>
		<xsl:attribute name="class">
			<xsl:text>worldcatAvailable</xsl:text>
			<xsl:if test="//request/action = 'record'">
				<xsl:text> worldcatAvailableRecord</xsl:text>
			</xsl:if>
		</xsl:attribute>
		
		<table class="holdingsTable">
		<tr>
			<xsl:if test="institution">
				<th>Institution</th>
			</xsl:if>
			<th>Location</th>
			<th>Call Number</th>
			<th>Status</th>
		</tr>
		<xsl:for-each select="items/item">
			<tr>
				<xsl:if test="institution">
					<td><xsl:value-of select="institution" /></td>
				</xsl:if>
				<td><xsl:value-of select="location" /></td>
				<td><xsl:value-of select="callnumber" /></td>
				<td><xsl:value-of select="status" /></td>
			</tr>
		</xsl:for-each>
		</table>
	</div>

</xsl:template>

<!-- 	
	TEMPLATE: AVAILABILITY FULL TEXT
	just the full-text on a holdings lookup
-->

<xsl:template name="availability_full_text">
	<xsl:param name="element" />
	<xsl:param name="class" />
					
	<xsl:for-each select="links/link[@type != 'none']">
		<xsl:element name="{$element}">
			<xsl:attribute name="class"><xsl:value-of select="$class" /></xsl:attribute>
			<a href="{url}" class="recordAction" target="" >
				<img src="{$base_include}/images/html.png" alt="" width="16" height="16" border="0" /> 
				<xsl:choose>
					<xsl:when test="display">
						<xsl:value-of select="display" />
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

	<xsl:variable name="source"  select="//request/source"/>	
	
	<xsl:if test="count(items/item)">
	
		<xsl:element name="{$element}">
			<xsl:attribute name="class"><xsl:value-of select="$class" /></xsl:attribute> 
			<a target="{$link_target}" href="{../url_open}" class="recordAction">
				<xsl:choose>
					<xsl:when test="//worldcat_groups/group[@id = $source]/lookup/ill_text">
						<img src="images/ill.png" alt="" border="0" class="miniIcon linkResolverLink "/>
						<xsl:text> </xsl:text>
						<xsl:value-of select="//worldcat_groups/group[@id = $source]/lookup/ill_text" />
					</xsl:when>
					<xsl:otherwise>
						<img src="{$image_sfx}" alt="" border="0" class="miniIcon linkResolverLink "/>
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
				<xsl:when test="//request/session/resultssaved[@key = $record_id]">images/folder_on.png</xsl:when>
				<xsl:otherwise>images/folder.png</xsl:otherwise>
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
					
					<xsl:if test="authors/author[@type='conference' or @type='corporate' and not(@additional)]">
					<xsl:text> / </xsl:text>
					<xsl:value-of select="authors/author[@type='conference' or @type='corporate' and not(@additional)]" />
					</xsl:if>
					
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
							<xsl:if test="format != 'Journal' and format != 'Newspaper'">
								By: <xsl:value-of select="primary_author" /><br />
							
								<xsl:if test="publisher">
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
					
							</xsl:if>								
						</div>				
					</xsl:if>
					
				</div>
				
				<div class="recordActions">
				
					<xsl:call-template name="availability_lookup">
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

<xsl:template name="sms_option">
	
	<xsl:if test="count(items/item) &gt; 0">
	
		<div id="smsOption" class="resultsAvailability recordAction">

			<img src="images/phone.png" alt="" />
			<xsl:text> </xsl:text>
			<a id="smsLink" href="{../url_sms}">Send location to your phone</a> 
		
		</div>
		
		<div id="sms" style="display:none">
			<xsl:call-template name="sms">
				<xsl:with-param name="header">h2</xsl:with-param>
			</xsl:call-template>
		</div>

	</xsl:if>


</xsl:template>

<xsl:template name="sms">
	<xsl:param name="header">h1</xsl:param>
	
	<xsl:variable name="num_copies" select="count(//items/item)" />
		
	<xsl:element name="{$header}">
		Send title and location to your mobile phone
	</xsl:element>

	<form name="smsForm" action="./" method="get">
	
		<input type="hidden" name="lang" value="{//request/lang}" />
		<input type="hidden" name="base" value="folder" />
		<input type="hidden" name="action" value="sms" />
		<input type="hidden" name="title" value="{title_normalized}" />
		
		<div class="smsProperty">
			<label for="phone">Your phone number: </label>
		</div>
		<div class="smsValue">
			<input type="text" name="phone" id="phone" />
		</div>
		
		<div class="smsProperty">
			<label for="provider">Provider:</label>
		</div>
		<div class="smsValue">
			<select name="provider">
				<option value="">-- choose one --</option>
				
				<xsl:call-template name="sms_input_option">
					<xsl:with-param name="email">txt.att.net</xsl:with-param>
					<xsl:with-param name="text">AT&amp;T / Cingular</xsl:with-param>
				</xsl:call-template>

				<xsl:call-template name="sms_input_option">
					<xsl:with-param name="email">MyMetroPcs.com</xsl:with-param>
					<xsl:with-param name="text">Metro PCS</xsl:with-param>
				</xsl:call-template>

				<xsl:call-template name="sms_input_option">
					<xsl:with-param name="email">messaging.nextel.com</xsl:with-param>
					<xsl:with-param name="text">Nextel</xsl:with-param>
				</xsl:call-template>

				<xsl:call-template name="sms_input_option">
					<xsl:with-param name="email">messaging.sprintpcs.com</xsl:with-param>
					<xsl:with-param name="text">Sprint</xsl:with-param>
				</xsl:call-template>

				<xsl:call-template name="sms_input_option">
					<xsl:with-param name="email">tmomail.net</xsl:with-param>
					<xsl:with-param name="text">T-Mobile</xsl:with-param>
				</xsl:call-template>

				<xsl:call-template name="sms_input_option">
					<xsl:with-param name="email">vtext.com</xsl:with-param>
					<xsl:with-param name="text">Verizon</xsl:with-param>
				</xsl:call-template>

				<xsl:call-template name="sms_input_option">
					<xsl:with-param name="email">vmobl.com</xsl:with-param>
					<xsl:with-param name="text">Virgin</xsl:with-param>
				</xsl:call-template>
				
				<!--
				
				<option value="message.alltel.com">Alltel</option>
				<option value="ptel.net">Powertel</option>
				<option value="tms.suncom.com">SunCom</option>
				<option value="email.uscc.net">US Cellular</option>
				
				-->
				
			</select>
		</div>

		<xsl:if test="$num_copies &gt; 1">
			<h3>Choose one of the copies</h3>
		</xsl:if>
						
		<xsl:for-each select="items/item">
			
			<xsl:variable name="item">
				<xsl:value-of select="location" />
				<xsl:text> </xsl:text>
				<xsl:value-of select="callnumber" />
			</xsl:variable>
			
			<label>
				<xsl:if test="$num_copies &gt; 1">
					<xsl:attribute name="class">smsCopy</xsl:attribute>
				</xsl:if>
				
				<input name="item" value="{$item}">
					<xsl:attribute name="type">
						<xsl:choose>
							<xsl:when test="$num_copies &gt; 1">radio</xsl:when>
							<xsl:otherwise>hidden</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>
					<xsl:if test="position() = 1">
						<xsl:attribute name="checked">checked</xsl:attribute>
					</xsl:if>
				</input>
				<xsl:if test="$num_copies &gt; 1">
					<xsl:text> </xsl:text>
					<xsl:value-of select="$item" />
					<br />
				</xsl:if>
			</label>
			
		</xsl:for-each>
		
		<br />
		
		<input type="submit" value="Send" />
		
		<p class="smsNote">Carrier charges may apply.</p>
		
	</form>

</xsl:template>

<xsl:template name="sms_input_option">
	<xsl:param name="email" />
	<xsl:param name="text" />
	
	<option value="{$email}">
		<xsl:if test="//request/session/user_provider = $email">
			<xsl:attribute name="selected">selected</xsl:attribute>
		</xsl:if>
		<xsl:value-of select="$text" />
	</option>

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

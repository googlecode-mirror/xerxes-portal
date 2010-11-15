<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2010 California State University
 version $Id$
 package: Worldcat
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->
 
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl"
	exclude-result-prefixes="php">
	
	<xsl:variable name="text_worldcat_name">Find Books</xsl:variable>

<xsl:template name="individual_record_pager" />
	
<xsl:template name="module_header">
	
	<xsl:if test="$is_mobile != 1 ">
		<link href="{$base_include}/css/worldcat/worldcat.css?xerxes_version={$xerxes_version}" rel="stylesheet" type="text/css" />	
	</xsl:if>
	
	<script src="{$base_include}/javascript/worldcat/availability.js" language="javascript" type="text/javascript"></script>
	<script src="{$base_include}/javascript/facet-tabs.js" language="javascript" type="text/javascript"></script>
		
</xsl:template>


<xsl:template name="breadcrumb_worldcat">

	<xsl:param name="condition" />
	
	<xsl:variable name="last_search" select="//link_results/@url" />
	
	<xsl:call-template name="breadcrumb_start" />
	
	<xsl:if test="//request/action != 'home'">
		<a href="./?{$language_param}&amp;base={//request/base}"><xsl:value-of select="$text_worldcat_name" /></a> <xsl:copy-of select="$text_breadcrumb_separator" />
	</xsl:if>
		
	<xsl:if test="$condition = '2' and $last_search != ''">
		<a href="{$last_search}">Search Results</a> <xsl:copy-of select="$text_breadcrumb_separator" />	
	</xsl:if>
	
</xsl:template>

<xsl:template name="generic_searchbox_hidden_fields_local">

	<input type="hidden" name="source" value="local" />

</xsl:template>

<xsl:template name="generic_advanced_search_option">

		<div id="worldcatAdvancedMore">
			<xsl:if test="not(//request/session/role) or //request/session/role != 'guest'">
				<a href="./?{$language_param}&amp;base={//request/base}&amp;action=advanced"><xsl:value-of select="$text_searchbox_options_more" /></a>
			</xsl:if>
		</div>	
</xsl:template>

<xsl:template name="generic_advanced_search">

	<input type="hidden" name="advanced" value="true" />
	
	<div id="worldcatAdvancedSearch">

		<fieldset id="searchTerms">
		
			<legend>Search Terms</legend>
			
			<ul>
				<xsl:if test="request/advancedfull or request/query1">
					<li>
						<xsl:text>Search: </xsl:text>
						<xsl:call-template name="field_pulldown">
							<xsl:with-param name="default">kw</xsl:with-param>
							<xsl:with-param name="active"><xsl:value-of select="request/field1" /></xsl:with-param>
							<xsl:with-param name="id">1</xsl:with-param>
						</xsl:call-template> 
						<xsl:text> for </xsl:text><input type="text" name="query1" value="{request/query1}" />
					</li>
				</xsl:if>
				<xsl:if test="request/advancedfull or request/query2">
					<li> 
						<xsl:call-template name="boolean">
							<xsl:with-param name="id">1</xsl:with-param>
						</xsl:call-template>
						<xsl:text> </xsl:text>
						<xsl:call-template name="field_pulldown">
							<xsl:with-param name="default">ti</xsl:with-param>
							<xsl:with-param name="active"><xsl:value-of select="request/field2" /></xsl:with-param>
							<xsl:with-param name="id">2</xsl:with-param>
						</xsl:call-template> 
						<xsl:text> for </xsl:text><input type="text" name="query2"  value="{request/query2}" />
					</li>
				</xsl:if>
				<xsl:if test="request/advancedfull or request/query3">
					<li>
						<xsl:call-template name="boolean">
							<xsl:with-param name="id">2</xsl:with-param>
						</xsl:call-template>
						<xsl:text> </xsl:text>
						<xsl:call-template name="field_pulldown">
							<xsl:with-param name="default">au</xsl:with-param>
							<xsl:with-param name="active"><xsl:value-of select="request/field3" /></xsl:with-param>
							<xsl:with-param name="id">3</xsl:with-param>
						</xsl:call-template> 
						<xsl:text> for </xsl:text><input type="text" name="query3"  value="{request/query3}" />
					</li>
				</xsl:if>
				<xsl:if test="request/advancedfull or request/query4">
					<li>
						<xsl:call-template name="boolean">
							<xsl:with-param name="id">3</xsl:with-param>
						</xsl:call-template>
						<xsl:text> </xsl:text>
						<xsl:call-template name="field_pulldown">
							<xsl:with-param name="default">su</xsl:with-param>
							<xsl:with-param name="active"><xsl:value-of select="request/field4" /></xsl:with-param>
							<xsl:with-param name="id">4</xsl:with-param>
						</xsl:call-template> 
						<xsl:text> for </xsl:text><input type="text" name="query4"  value="{request/query4}" />
					</li>
				</xsl:if>
			</ul>
						
			<xsl:if test="not(request/advancedfull)">
			
				<xsl:if test="request/year or request/mt or request/la">
					<p class="error">Limits have been set</p>
				</xsl:if>
				
				<p>[ <a href="{//advanced_search/@link}">Show full options</a> ]</p>
			
			</xsl:if>


			<div id="searchSubmit">
				<input type="submit" value="Search" />
			</div>
			
		</fieldset>
		
		<xsl:if test="request/advancedfull">
	
			<fieldset id="basicLimits" class="limit">
			
				<legend>Limits (optional)</legend>
			
				<table summary="for layout only">
					<tr>
						<td>Year:</td>
						<td>
						<select name="year_relation">
							<option value="=">
								<xsl:if test="request/year_relation = '='">
									<xsl:attribute name="selected">seleted</xsl:attribute>
								</xsl:if>
								<xsl:text>=</xsl:text>
							</option>
							<option value="&lt;">
								<xsl:if test="request/year_relation = '&#60;'">
									<xsl:attribute name="selected">seleted</xsl:attribute>
								</xsl:if>
								<xsl:text>before</xsl:text>
							</option>
							<option value="&gt;">
								<xsl:if test="request/year_relation = '&#62;'">
									<xsl:attribute name="selected">seleted</xsl:attribute>
								</xsl:if>
								<xsl:text>after</xsl:text>
							</option>
						</select>
						<xsl:text> </xsl:text>
						<input type="text" name="year" value="{//request/year}" />
						<div class="explain">
							( enter a single year, or range of years as YYYY-YYYY )
						</div>
						</td>
					</tr>
					<tr>
						<td>Format:</td>
						<td>
							<select name="mt">
								<option value="">
									<xsl:text>All Formats</xsl:text>
								</option>
								<option value="bks">
									<xsl:if test="contains(request/mt, 'bks')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Book</xsl:text>
								</option>
								<option value="brl">
									<xsl:if test="contains(request/mt, 'brl')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									&#160;&#160;&#160; Braille
								</option>
								<option value="lpt">
									<xsl:if test="contains(request/mt, 'lpt')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									&#160;&#160;&#160; Large print
								</option>
								<option value="vis">
									<xsl:if test="contains(request/mt, 'vis')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Visual Material</xsl:text>
								</option>
								<option value="vca">
									<xsl:if test="contains(request/mt, 'vca')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									&#160;&#160;&#160; Videocassette
								</option>
								<option value="dvv">
									<xsl:if test="contains(request/mt, 'dvv')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									&#160;&#160;&#160; DVD video
								</option>
								<option value="rec">
									<xsl:if test="contains(request/mt, 'rec')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Sound Recording</xsl:text>
								</option>
								<option value="msr">
									<xsl:if test="contains(request/mt, 'msr')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									&#160;&#160;&#160; Music
								</option>
								<option value="nsr">
									<xsl:if test="contains(request/mt, 'nsr')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									&#160;&#160;&#160; Audio book, etc.
								</option>
								<option value="cda">
									<xsl:if test="contains(request/mt, 'cda')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									&#160;&#160;&#160; CD audio
								</option>
								<option value="cas">
									<xsl:if test="contains(request/mt, 'cas')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									&#160;&#160;&#160; Cassette recording
								</option>
								<option value="lps">
									<xsl:if test="contains(request/mt, 'lps')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									&#160;&#160;&#160; LP recording
								</option>
								<option value="sco">
									<xsl:if test="contains(request/mt, 'sco')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Musical Score</xsl:text>
								</option>
								<option value="ser">
									<xsl:if test="contains(request/mt, 'ser')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Journal / Magazine / Newspaper</xsl:text>
								</option>
								<option value="url">
									<xsl:if test="contains(request/mt, 'url')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Internet Resource</xsl:text>
								</option>
								<option value="com">
									<xsl:if test="contains(request/mt, 'com')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Computer File</xsl:text>
								</option>
								<option value="map">
									<xsl:if test="contains(request/mt, 'map')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Map</xsl:text>
								</option>
								<option value="mix">
									<xsl:if test="contains(request/mt, 'mix')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Archival Material</xsl:text>
								</option>
							</select>
						
						</td>
					</tr>
					<tr>
						<td>Content:</td>
						<td>
							<select name="mt">
								<option value="">
									<xsl:text>Any content</xsl:text>
								</option>
								<option value="fic">
									<xsl:if test="contains(request/mt, 'fic')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Fiction</xsl:text>
								</option>
								<option value="-fic">
									<xsl:if test="contains(request/mt, '-fic')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Non-Fiction</xsl:text>
								</option>
								<option value="bio">
									<xsl:if test="contains(request/mt, 'bio')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Biography</xsl:text>
								</option>
								<option value="deg">
									<xsl:if test="contains(request/mt, 'deg')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Thesis or Dissertation</xsl:text>
								</option>
								<option value="cnp">
									<xsl:if test="contains(request/mt, 'cnp')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Conference publication</xsl:text>
								</option>
								<option value="mss">
									<xsl:if test="contains(request/mt, 'mss')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Manuscript</xsl:text>
								</option>
							</select>			
						</td>
					</tr>
					<tr>
						<td>Audience:</td>
						<td>
							<select name="mt">
								<option value="">
									<xsl:text>All audiences</xsl:text>
								</option>
								<option value="-juv">
									<xsl:if test="contains(request/mt, '-juv')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Non-Juvenile</xsl:text>
								</option>
								<option value="juv">
									<xsl:if test="contains(request/mt, 'juv')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Juvenile</xsl:text>
								</option>
								<option value="pre">
									<xsl:if test="contains(request/mt, 'pre')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									&#160;&#160;&#160; Preschool
								</option>
								<option value="pri">
									<xsl:if test="contains(request/mt, 'pri')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									&#160;&#160;&#160; Primary school
								</option>
								<option value="ejh">
									<xsl:if test="contains(request/mt, 'ejh')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									&#160;&#160;&#160; Pre-adolescent
								</option>
								<option value="shs">
									<xsl:if test="contains(request/mt, 'shs')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									&#160;&#160;&#160; Adolescent
								</option>
								<option value="jau">
									<xsl:if test="contains(request/mt, 'jau')">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									&#160;&#160;&#160; Juvenile
								</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>Language:</td>
						<td>
							<select name="la">
								<option value="">
									<xsl:text>All languages</xsl:text>
								</option>
								<option value="ara">
									<xsl:if test="request/la = 'bks'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Arabic</xsl:text>
								</option>
								<option value="bul">
									<xsl:if test="request/la = 'bul'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Bulgarian</xsl:text>
								</option>
								<option value="chi">
									<xsl:if test="request/la = 'chi'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Chinese</xsl:text>
								</option>
								<option value="cze">
									<xsl:if test="request/la = 'cze'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Czech</xsl:text>
								</option>
								<option value="dan">
									<xsl:if test="request/la = 'dan'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Danish</xsl:text>
								</option>
								<option value="dut">
									<xsl:if test="request/la = 'dut'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Dutch</xsl:text>
								</option>
								<option value="eng">
									<xsl:if test="request/la = 'eng'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>English</xsl:text>
								</option>
								<option value="fre">
									<xsl:if test="request/la = 'fre'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>French</xsl:text>
								</option>
								<option value="ger">
									<xsl:if test="request/la = 'ger'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>German</xsl:text>
								</option>
								<option value="gre">
									<xsl:if test="request/la = 'gre'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Greek (modern)</xsl:text>
								</option>
								<option value="heb">
									<xsl:if test="request/la = 'heb'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Hebrew</xsl:text>
								</option>
								<option value="hin">
									<xsl:if test="request/la = 'hin'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Hindi</xsl:text>
								</option>
								<option value="hun">
									<xsl:if test="request/la = 'hun'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Hungarian</xsl:text>
								</option>
								<option value="ind">
									<xsl:if test="request/la = 'ind'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Indonesian</xsl:text>
								</option>
								<option value="ita">
									<xsl:if test="request/la = 'ita'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Italian</xsl:text>
								</option>
								<option value="jpn">
									<xsl:if test="request/la = 'jpn'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Japanese</xsl:text>
								</option>
								<option value="kor">
									<xsl:if test="request/la = 'kor'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Korean</xsl:text>
								</option>
								<option value="lat">
									<xsl:if test="request/la = 'lat'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Latin</xsl:text>
								</option>
								<option value="nor">
									<xsl:if test="request/la = 'nor'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Norwegian</xsl:text>
								</option>
								<option value="per">
									<xsl:if test="request/la = 'per'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Persian (modern)</xsl:text>
								</option>
								<option value="pol">
									<xsl:if test="request/la = 'pol'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Polish</xsl:text>
								</option>
								<option value="por">
									<xsl:if test="request/la = 'por'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Portuguese</xsl:text>
								</option>
								<option value="rum">
									<xsl:if test="request/la = 'rum'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Romanian</xsl:text>
								</option>
								<option value="rus">
									<xsl:if test="request/la = 'rus'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Russian</xsl:text>
								</option>
								<option value="scr">
									<xsl:if test="request/la = 'scr'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Serbo-Croatian (Roman)</xsl:text>
								</option>
								<option value="spa">
									<xsl:if test="request/la = 'spa'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Spanish</xsl:text>
								</option>
								<option value="swe">
									<xsl:if test="request/la = 'swe'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Swedish</xsl:text>
								</option>
								<option value="tha">
									<xsl:if test="request/la = 'tha'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Thai</xsl:text>
								</option>
								<option value="tur">
									<xsl:if test="request/la = 'tur'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Turkish</xsl:text>
								</option>
								<option value="ukr">
									<xsl:if test="request/la = 'ukr'">
										<xsl:attribute name="selected">seleted</xsl:attribute>
									</xsl:if>
									<xsl:text>Ukrainian</xsl:text>
								</option>
							</select>
						</td>
					</tr>
			
				</table>
			
			</fieldset>
		</xsl:if>

	</div>

</xsl:template>

<xsl:template name="boolean">
	
	<xsl:param name="id" />

	<select name="boolean{$id}">
		<option value="and">AND</option>
		<option value="or">OR</option>
		<option value="not">NOT</option>
	</select>

</xsl:template>

<xsl:template name="field_pulldown">
	<xsl:param name="default" />
	<xsl:param name="active" />
	<xsl:param name="id" />
	
	<xsl:variable name="selected">
		<xsl:choose>
			<xsl:when test="$active != ''">
				<xsl:value-of select="$active" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$default" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<select name="field{$id}">
	
		<option value="kw">Keyword</option>
		
		<!-- title -->
		
		<option value="ti">
			<xsl:if test="$selected = 'ti'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>Title</xsl:text>
		</option>
		
		<option value="ti_exact">
			<xsl:if test="$selected = 'ti_exact'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Title (Exact)</xsl:text>
		</option>
		<option value="se">
			<xsl:if test="$selected = 'se'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Series Title</xsl:text>
		</option>
		
		<!-- author -->
		
		<option value="au">
			<xsl:if test="$selected = 'au'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>Author</xsl:text>
		</option>
		
		<option value="au_exact">
			<xsl:if test="$selected = 'au_exact'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Author (Exact)</xsl:text>
		</option>
		
		<option value="cn">
			<xsl:if test="$selected = 'cn'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Conference Name</xsl:text>
		</option>
		
		<option value="cn_exact">
			<xsl:if test="$selected = 'cn_exact'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Conference Name (Exact)</xsl:text>
		</option>
		
		<option value="co">
			<xsl:if test="$selected = 'co'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Corporate Name</xsl:text>
		</option>
		
		<option value="co_exact">
			<xsl:if test="$selected = 'co_exact'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>		
			<xsl:text>&#160;&#160;&#160; Corporate Name (Exact)</xsl:text>
		</option>
		
		<option value="pn">
			<xsl:if test="$selected = 'pn'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Personal Name</xsl:text>
		</option>
		
		<option value="pn_exact">
			<xsl:if test="$selected = 'pn_exact'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Personal Name (Exact)</xsl:text>
		</option>
		
		<!-- subject -->
		
		<option value="su">
			<xsl:if test="$selected = 'su'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>Subject</xsl:text>
		</option>
		
		<option value="su_exact">
			<xsl:if test="$selected = 'su_exact'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Subject (Exact)</xsl:text>
		</option>
		
		
		<!-- why are this not included in the worldcat api (2008-12-15) ? 
		
		<option value="na">
			<xsl:if test="$selected = 'na'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Person</xsl:text>
		</option>
		
		<option value="gc">
			<xsl:if test="$selected = 'gc'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Place</xsl:text>
		</option>
		
		<option value="de">
			<xsl:if test="$selected = 'de'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Topic</xsl:text>
		</option>
		<option value="nc">
			<xsl:if test="$selected = 'nc'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Company or Conference</xsl:text>
		</option>
		<option value="ge">
			<xsl:if test="$selected = 'ge'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Genre/Form</xsl:text>
		</option>
		-->
				
		<!-- numbers -->
		
		<option value="number">
			<xsl:if test="$selected = 'number'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>Identification Numbers</xsl:text>
		</option>
		<option value="dd">
			<xsl:if test="$selected = 'dd'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Dewey Classification Number</xsl:text>
		</option>
		<option value="gn">
			<xsl:if test="$selected = 'gn'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Government Document Number</xsl:text>
		</option>

		<option value="sn">
			<xsl:if test="$selected = 'sn'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; ISBN / ISSN</xsl:text>
		</option>
		
		<!-- not included (2008-12-15) ? 
		
		<option value="nb">
			<xsl:if test="$selected = 'nb'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; ISBN</xsl:text>
		</option>
		<option value="ns">
			<xsl:if test="$selected = 'ns'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; ISSN</xsl:text>
		</option>
		
		-->
		
		<option value="lc">
			<xsl:if test="$selected = 'lc'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; LC Classification Number</xsl:text>
		</option>
		
		<!-- not included (2008-12-15) ? 
		
		<option value="nl">
			<xsl:if test="$selected = 'nl'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; LC Control Number</xsl:text>
		</option>
		-->
		
		<option value="mn">
			<xsl:if test="$selected = 'mn'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; Music Publisher Number</xsl:text>
		</option>
		<option value="no">
			<xsl:if test="$selected = 'no'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>&#160;&#160;&#160; OCLC Number</xsl:text>
		</option>
			
		<!-- others -->
		
		<option value="am">
			<xsl:if test="$selected = 'am'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>Access Method</xsl:text>
		</option>
		<option value="am_exact">
			<xsl:if test="$selected = 'am_exact'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>Access Method (Exact)</xsl:text>
		</option>
		
		<!-- not included (2008-12-15) ? 
		
		<option value="mc">
			<xsl:if test="$selected = 'mc'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>Musical Composition</xsl:text>
		</option>
		
		<option value="mc_exact">
			<xsl:if test="$selected = 'mc_exact'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>Musical Composition (Exact)</xsl:text>
		</option>
		
		-->
		
		<option value="nt">
			<xsl:if test="$selected = 'nt'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>Notes/Comments</xsl:text>
		</option>
		<option value="pb">
			<xsl:if test="$selected = 'pb'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>Publisher</xsl:text>
		</option>
		<option value="pl">
			<xsl:if test="$selected = 'pl'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>Place of Publication</xsl:text>
		</option>
	
	</select>

</xsl:template>


</xsl:stylesheet>

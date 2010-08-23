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
 copyright: 2009 California State University
 version: $Id$
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	
	<!-- currently used to parse multilingual database descriptions -->
	<xsl:import href="list-tokenizer.xsl" />

	<!-- text labels are defined in a seperate place -->
	<xsl:include href="labels/eng.xsl" />

<!-- 
	GLOBAL VARIABLES
	Configuration values used throughout the templates
-->

	<!-- version used to prevent css caching, and possibly other places to advertise version -->
	
	<xsl:variable name="xerxes_version" select="//config/xerxes_version" />
	
	<xsl:variable name="base_url"		select="//base_url" />
	<xsl:variable name="app_name"		select="//config/application_name" />
	<xsl:variable name="rewrite" 		select="//config/rewrite" />
	<xsl:variable name="search_limit"	select="//config/search_limit" />
	<xsl:variable name="link_target"	select="//config/link_target" />
	<xsl:variable name="link_target_databases" select="//config/link_target_databases" />

	<xsl:variable name="text_collection_default_new_name" select="//config/default_collection_name" />
	<xsl:variable name="text_collection_default_new_section_name" select="//config/default_collection_section_name" />
	<xsl:variable name="is_mobile">
		<xsl:choose>
			<xsl:when test="//request/session/is_mobile = '1'">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<!-- these have defaults here and in config.xml for backwards-compatability on older configs -->
	
	<xsl:variable name="document">
		<xsl:choose>
			<xsl:when test="//config/document">
				<xsl:value-of select="//config/document" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>doc3</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<xsl:variable name="template">
		<xsl:choose>
			<xsl:when test="//config/template">
				<xsl:value-of select="//config/template" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>yui-t6</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<xsl:variable name="show_db_detail_search">
		<xsl:choose>
			<xsl:when test="//config/show_db_detail_search">
				<xsl:value-of select="//config/show_db_detail_search" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>true</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<xsl:variable name="databases_searchable"	select="//config/database_list_searchable" />
	
	<xsl:variable name="xerxes_language" select="//config/xerxes_language" />
	<!--xsl:variable name="xerxes_language">
		<xsl:choose>
			<xsl:when test="//config/xerxes_language">
				<xsl:value-of select="//config/xerxes_language" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>XXXeng</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable-->
	
	<xsl:variable name="db_description_multilingual">
		<xsl:choose>
			<xsl:when test="//config/db_description_multilingual">
				<xsl:value-of select="//config/db_description_multilingual" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>false</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<!-- which description in order is our xerxes_language? -->
	<xsl:variable name="xerxes_language_position">
		<xsl:call-template name="find-item-in-list">
			<xsl:with-param name="list">
				<xsl:value-of select="$db_description_multilingual" />
			</xsl:with-param>
			<xsl:with-param name="delimiter">,</xsl:with-param>
			<xsl:with-param name="find-item"><xsl:value-of select="$xerxes_language" /></xsl:with-param>
		</xsl:call-template>
	</xsl:variable>
	
	<xsl:variable name="categories_num_columns">
		<xsl:choose>
			<xsl:when test="//config/categories_num_columns">
				<xsl:value-of select="//config/categories_num_columns" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>3</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<xsl:variable name="base_include" select="$base_url" />
		
	<xsl:variable name="app_mini_icon_url"><xsl:value-of select="$base_url" />/images/famfamfam/page_find.png</xsl:variable>
	
	<xsl:variable name="global_advanced_mode" 
		select="(//request/metasearch_input_mode = 'advanced') or 
		( //results/search/pair[@position = '2']/query != '' ) or 
		//results/search/pair[@position ='1']/field = 'ISSN' or 
		//results/search/pair[@position ='1']/field = 'ISBN' or 
		//results/search/pair[@position ='1']/field = 'WYR'" />


	<xsl:variable name="temporarySession">
		<xsl:if test="//request/session/role = 'guest' or //request/session/role = 'local'">
			<xsl:text>true</xsl:text>
		</xsl:if>	
	</xsl:variable>
	
	<!-- extra content to include in the HTML 'head' section -->
	<xsl:variable name="text_extra_html_head_content" />
	

<!-- 	
	TEMPLATE: SURROUND
	This is the master template that defines the overall design for the application; place
	here the header, footer and other design elements which all pages should contain.
-->

<xsl:template name="surround">
	<xsl:param name="surround_template"><xsl:value-of select="$template" /></xsl:param>
	<xsl:param name="sidebar" />

	<html lang="eng">
	<head>
	<title><xsl:value-of select="//config/application_name" />: <xsl:call-template name="title" /></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<xsl:call-template name="header_refresh" />	
	<base href="{$base_include}/" />
	<xsl:call-template name="css_include" />
	<xsl:call-template name="header" />	
	</head>
	<body>
	<xsl:if test="request/action = 'subject' or request/action = 'categories'">
		<xsl:attribute name="onLoad">if (document.forms.form1) if  (document.forms.form1.query)  document.forms.form1.query.focus()</xsl:attribute>
	</xsl:if>
	
	<div class="ada">
		<xsl:if test="not(request/session/ada)">
			<xsl:variable name="return_url" select="php:function('urlencode', string(request/server/request_uri))" />
			<a href="{$base_url}/?base=databases&amp;action=accessible&amp;return={$return_url}">
				<xsl:copy-of select="$text_ada_version" /> 
			</a>
		</xsl:if>
	</div>

	<div>
		<xsl:choose>
			<xsl:when test="$is_mobile = 1">
				<xsl:attribute name="class">mobile</xsl:attribute>
			</xsl:when>
			<xsl:otherwise>
				<xsl:attribute name="id"><xsl:value-of select="$document" /></xsl:attribute>
				<xsl:attribute name="class"><xsl:value-of select="$surround_template" /></xsl:attribute>
			</xsl:otherwise>
		</xsl:choose>
		<div id="hd">
			<xsl:choose>
				<xsl:when test="$is_mobile = '1'">
					<xsl:call-template name="mobile_header" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:call-template name="header_div" />
				</xsl:otherwise>
			</xsl:choose>
			<div id="breadcrumb">
				<div class="trail">
					<xsl:call-template name="breadcrumb" />
				</div>
			</div>
		</div>
		<div id="bd">
			<div id="yui-main">
				<div class="yui-b">
					<xsl:if test="string(//session/flash_message)">
						<xsl:call-template name="message_display"/>
					</xsl:if>
					
					<xsl:call-template name="main" />
				</div>
			</div>
			
			<xsl:if test="$sidebar != 'none' and $is_mobile != '1'">
				<xsl:call-template name="sidebar_wrapper" />
			</xsl:if>

		</div>
		<div id="ft">
			<xsl:choose>
				<xsl:when test="$is_mobile = '1'">
					<xsl:call-template name="mobile_footer" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:call-template name="footer_div" />
				</xsl:otherwise>
			</xsl:choose>
		</div>
	</div>
	
	<xsl:if test="//config/google_analytics">

		<xsl:variable name="google_analytics" select="//config/google_analytics" />
		
		<script type="text/javascript">
			var gaJsHost = (("https:" == document.location.protocol) ?
			"https://ssl." : "http://www.");
			document.write(unescape("%3Cscript src='" + gaJsHost +
			"google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
		</script>
		<script type="text/javascript">
			var pageTracker = _gat._getTracker("{$google_analytics}");
			pageTracker._initData();
			pageTracker._trackPageview();
		</script>
		
	</xsl:if>
		
	</body>
	</html>
	
</xsl:template>

<!-- 
	TEMPLATE: CSS_INCLUDE 
-->

<xsl:template name="css_include">

	<link href="{$base_include}/css/reset-fonts-grids.css?xerxes_version={$xerxes_version}" rel="stylesheet" type="text/css" />
	<link href="{$base_include}/css/xerxes-blue.css?xerxes_version={$xerxes_version}" rel="stylesheet" type="text/css" />
		
	<xsl:choose>
		<xsl:when test="$is_mobile = '1'">
		
			<!-- mobile devices get their own stylesheet (to neutralize the main one, plus  it's own
			 definitions), and a local override stylesheet -->
			 
			<link href="{$base_include}/css/xerxes-mobile.css?xerxes_version={$xerxes_version}" rel="stylesheet" type="text/css" />
			<link href="{$base_include}/css/local-mobile.css?xerxes_version={$xerxes_version}" rel="stylesheet" type="text/css" />
			
			<!-- this is necessary for the iPhone to not start out with a zoomed view, other 
			browsers should ignore -->
			
			<meta name="viewport" content="width=360" />
		</xsl:when>
		<xsl:otherwise>
			
			<!-- local override for the main stylesheet -->
			<link href="{$base_include}/css/local.css?xerxes_version={$xerxes_version}" rel="stylesheet" type="text/css" />	
			
		</xsl:otherwise>
	</xsl:choose>

	<link href="{$base_include}/css/xerxes-print.css?xerxes_version={$xerxes_version}" rel="stylesheet" type="text/css" media="print" />

</xsl:template>

<!-- 
	TEMPLATE: MESSAGE_DISPLAY
	A generic way to display a message to the user in any page, usually
	used for non-ajax version of completion status messages.
-->

<xsl:template name="message_display">
	<div id="message_display">
		<xsl:copy-of select="//session/flash_message"/>
	</div>
</xsl:template>


<!-- 	
	TEMPLATES THAT SHOULD BE OVERRIDEN IN PAGES OR LOCAL INCLUDES.XSL
	Defined here in case they are not, so as not to stop the proceeedings
-->

<xsl:template name="header_div" />
<xsl:template name="footer_div" />
<xsl:template name="page_name" />
<xsl:template name="breadcrumb" />
<xsl:template name="sidebar" />
<xsl:template name="sidebar_additional" />
<xsl:template name="module_header" />
<xsl:template name="additional_record_links" />
<xsl:template name="additional_brief_record_data" />

<!--
	TEMPLATE: SIDEBAR WRAPPER
	This defines the overarching sidebar element.  Pages normally will use sidebar template, which 
	defines the content, but if a page can call this template to change the _structure_ of the 
	sidebar as well
-->

<xsl:template name="sidebar_wrapper">
	<div class="yui-b">
		<div id="sidebar">
			<xsl:call-template name="sidebar" />
			<xsl:call-template name="sidebar_additional" />
		</div>
	</div>
</xsl:template>


<!-- 
	TEMPLATE: BREADCRUMB START
	The start of the breadcrumb trail, which can include links to the library or campus
	website.  Also here we break out the Xerxes 'home' link in case some section of the
	application (my saved records, for example) that might not want to be conceptually
	seperate
-->

<xsl:template name="breadcrumb_start">

	<xsl:if test="not(request/base = 'databases' and request/action ='categories')">
		<a href="{$base_url}"><xsl:value-of select="$text_databases_category_pagename" /></a> <xsl:copy-of select="$text_breadcrumb_seperator" />
	</xsl:if>

</xsl:template>

<!-- 
	TEMPLATE: TITLE
	the title that appears in the browser window.  this is assumed to be the 
	page name, unless the page overrides it
-->

<xsl:template name="title">
	<xsl:call-template name="page_name" />
</xsl:template>

<!-- 
	TEMPLATE: BREADCRUMBS DATABASES
-->

<xsl:template name="breadcrumb_databases">
	<xsl:param name="condition" />
	
	<xsl:call-template name="breadcrumb_start" />
	
	<xsl:choose>
		<xsl:when test="$condition = 2">
			<a href="{//category/url}"><xsl:value-of select="//category/@name" /></a> <xsl:copy-of select="$text_breadcrumb_seperator" />
		</xsl:when>
		<xsl:when test="$condition = 3">
			<a href="{//embed_info/direct_url}"><xsl:value-of select="//database/title_display" /></a> <xsl:copy-of select="$text_breadcrumb_seperator" />
		</xsl:when>
		<xsl:when test="$condition = 4">
			<a href="{//navbar/element[@id='database_list']}"><xsl:value-of select="$text_databases_az_pagename" /></a> <xsl:copy-of select="$text_breadcrumb_seperator" />
		</xsl:when>
	</xsl:choose>

</xsl:template>

<!-- 
	TEMPLATE: BREADCRUMBS METASEARCH
-->

<xsl:template name="breadcrumb_metasearch">
	<xsl:param name="condition" />
	
	<xsl:call-template name="breadcrumb_start" />
	
	<a href="{results/search/context_url}"><xsl:value-of select="results/search/context" /></a> 
	<xsl:copy-of select="$text_breadcrumb_seperator" />
	
	<xsl:choose>
		<xsl:when test="$condition = 2">
			<a href="{//resultset_link}"><xsl:copy-of select="$text_results_breadcrumb" /></a> <xsl:copy-of select="$text_breadcrumb_seperator" />
		</xsl:when>
		<xsl:when test="$condition = 3">
			<a href="{//request/return}"><xsl:copy-of select="$text_results_breadcrumb" /></a> <xsl:copy-of select="$text_breadcrumb_seperator" />
		</xsl:when>

	</xsl:choose>

</xsl:template>

<!-- 
	TEMPLATE: BREADCRUMBS FOLDER
-->

<xsl:template name="breadcrumb_folder">
	<xsl:param name="condition" />
		
	<xsl:call-template name="breadcrumb_start" />
	
	<xsl:choose>
		<xsl:when test="$condition != 1">
			<a href="{//navbar/element[@id='saved_records']}"><xsl:copy-of select="$text_header_savedrecords" /></a>
			<xsl:copy-of select="$text_breadcrumb_seperator" />
		</xsl:when>
	</xsl:choose>

</xsl:template>


<!-- 
	TEMPLATE: BREADCRUMB COLLECTIONS
-->

<xsl:template name="breadcrumb_collection">
	<xsl:param name="condition">1</xsl:param>
		
	<xsl:call-template name="breadcrumb_start" />
	
	<xsl:if test="not(category/@is_default_collection = 'yes')">
		<a href="{navbar/element[@id='saved_collections']/url}"><xsl:copy-of select="$text_header_collections"/></a> 
		<xsl:copy-of select="$text_breadcrumb_seperator" />	
	</xsl:if>

	<xsl:if test="$condition = 2">
		<a href="{category/url}"><xsl:value-of select="category/@name"/></a> <xsl:copy-of select="$text_breadcrumb_seperator" />
	</xsl:if>	

</xsl:template>


<!-- 	
	TEMPLATE: SEARCH BOX
	Search box that appears in the 'hits' and 'results' page, as well as databases_subject.xsl. 
-->

<xsl:template name="search_box">
	
	<xsl:param name="full_page_url" select="//request/server/request_uri"/>
	
	<xsl:choose>
		<xsl:when test="$is_mobile = '1'">
			<xsl:call-template name="mobile_metalib_search_box" />
		</xsl:when>
		<xsl:otherwise>
			<xsl:call-template name="metalib_search_box">
				<xsl:with-param name="full_page_url" select="$full_page_url"/>
			</xsl:call-template>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<!-- 	
	TEMPLATE: METALIB SEARCH BOX
	This one used for regular web browsers, not mobile
-->

<xsl:template name="metalib_search_box">

	<xsl:param name="full_page_url" />
		
	<!-- "base" url used for switching search modes. Defaults to just our current url, but for embed purposes 
	may be provided differently. -->

	<!-- split contents into seperate template to make partial AJAX loading easier -->
	
	<div class="raisedBox searchBox">
	
		<!-- pull out any already existing query entries -->
		
		<xsl:variable name="query" select="//results/search/pair[@position = '1']/query" />
		<xsl:variable name="query2" select="//results/search/pair[@position = '2']/query" />
		
		<xsl:variable name="find_operator" select="//results/search/operator[@position = '1']" />
		
		<xsl:variable name="field" select="//results/search/pair[@position ='1']/field"/>
		<xsl:variable name="field2" select="//results/search/pair[2]/field"/>
		
		<xsl:variable name="advanced_mode" select="$global_advanced_mode" />
		
		<div class="searchLabel">
			<label for="field"><xsl:copy-of select="$text_searchbox_search" /></label>
		</div>
		
		<div class="searchInputs">
		
			<xsl:call-template name="metasearch_input_pair">
				<xsl:with-param name="field_selected" select="$field" />
				<xsl:with-param name="query_entered" select="$query" />
				<xsl:with-param name="advanced_mode" select="$advanced_mode" />
			</xsl:call-template>

			<!-- advanced search stuff is output even if we are in simple mode, but with display:none. 
			Javascriptiness may easily toggle without reload that way. -->
			
			<label for="find_operator1" class="find_operator1label ada">
				<xsl:if test="not($advanced_mode)">
					<xsl:attribute name="style">display:none;</xsl:attribute>
				</xsl:if>
				<xsl:copy-of select="$text_searchbox_ada_boolean" />
			</label>
			
			<xsl:text>&nbsp;</xsl:text>

			<select id="find_operator1" class="find_operator1" name="find_operator1">
				<xsl:if test="not($advanced_mode)">
					<xsl:attribute name="style">display:none;</xsl:attribute>
				</xsl:if>
				<option value="AND">
					<xsl:if test="$find_operator = 'AND'">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
					<xsl:copy-of select="$text_searchbox_boolean_and" />
				</option>
				<option value="OR">
					<xsl:if test="$find_operator = 'OR'">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
				<xsl:copy-of select="$text_searchbox_boolean_or" />
				</option>
				<option value="NOT">
					<xsl:if test="$find_operator = 'NOT'">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
				<xsl:copy-of select="$text_searchbox_boolean_without" />
				</option>
			</select>
		
			<div class="searchBox_advanced_newline">
				<xsl:if test="not($advanced_mode)">
					<xsl:attribute name="style">display:none;</xsl:attribute>
				</xsl:if>
			</div>
			
			<label for="field2" class="ada field2label">
				<xsl:if test="not($advanced_mode)">
					<xsl:attribute name="style">display:none;</xsl:attribute>
				</xsl:if>
				<xsl:copy-of select="$text_searchbox_search" />
			</label>
			
			<span class="searchBox_advanced_pair">
				<xsl:if test="not($advanced_mode)">
					<xsl:attribute name="style">display:none;</xsl:attribute>
				</xsl:if>
				<xsl:call-template name="metasearch_input_pair">
					<xsl:with-param name="field_selected" select="$field2" />
					<xsl:with-param name="query_entered" select="$query2" />
					<xsl:with-param name="advanced_mode" select="true()" />
					<xsl:with-param name="input_name_suffix" select="2" />
				</xsl:call-template>
				<xsl:text>&nbsp;</xsl:text>
			</span>
			<input class="searchbox_submit" type="submit" name="Submit" value="{$text_searchbox_go}" />
		</div>
		
		<xsl:if test="results/search/spelling != ''">
			<xsl:variable name="spell_url" select="results/search/spelling_url" />
			<p class="spellSuggest error"><xsl:copy-of select="$text_searchbox_spelling_error" />
			<a href="{$spell_url}"><xsl:value-of select="//spelling" /></a></p>
		</xsl:if>
	
		<div class="metasearch_input_toggle">
			<xsl:choose>
			<xsl:when test="$advanced_mode">
				<a class="searchBox_toggle">
				<xsl:attribute name="href">
					<xsl:value-of select="php:functionString('Xerxes_Framework_Request::setParamInUrl', $full_page_url, 'metasearch_input_mode', 'simple')"/>
				</xsl:attribute>
				<xsl:copy-of select="$text_searchbox_options_fewer" />
				</a>
			</xsl:when>
			<xsl:otherwise>
				<a class="searchBox_toggle">
				<xsl:attribute name="href">
					<xsl:value-of select="php:functionString('Xerxes_Framework_Request::setParamInUrl', $full_page_url, 'metasearch_input_mode', 'advanced')"/>
				</xsl:attribute>
				<xsl:copy-of select="$text_searchbox_options_more" />
				</a>
			</xsl:otherwise>
		</xsl:choose>
		</div>
	</div>
	
	<xsl:for-each select="//base_info">
		<xsl:if test="base_001">
			<input type="hidden" name="database" value="{base_001}" />
		</xsl:if>
	</xsl:for-each>
	
</xsl:template>

<!--
	TEMPLATE: METASEARCH INPUT PAIR
	Two search box form
-->

<xsl:template name="metasearch_input_pair">
	<xsl:param name="field_selected" />
	<xsl:param name="query_entered" />
	<xsl:param name="advanced_mode" select="false" />
	<xsl:param name="input_name_suffix" select ="''" />
	
	<select id="field{$input_name_suffix}" class="field{$input_name_suffix}" name="field{$input_name_suffix}">
		<option value="WRD"><xsl:copy-of select="$text_searchbox_field_keyword" /></option>
		<option value="WTI">
		<xsl:if test="$field_selected = 'WTI'">
			<xsl:attribute name="selected">seleted</xsl:attribute>
		</xsl:if>
		<xsl:copy-of select="$text_searchbox_field_title" />
		</option>
		<option value="WAU">
		<xsl:if test="$field_selected = 'WAU'">
			<xsl:attribute name="selected">selected</xsl:attribute>
		</xsl:if>
		<xsl:copy-of select="$text_searchbox_field_author" />
		</option>
		<option value="WSU">
		<xsl:if test="$field_selected = 'WSU'">
			<xsl:attribute name="selected">selected</xsl:attribute>
		</xsl:if>
		<xsl:copy-of select="$text_searchbox_field_subject" />
		</option>
		
		<!-- Include advanced mode options? We don't just try to hide,
		doesn't work in IE, javascript will need to actually add/remove. -->
		
		<xsl:if test="$advanced_mode">
			<option value="ISSN">
			<xsl:if test="$field_selected = 'ISSN'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:copy-of select="$text_searchbox_field_issn" />
			</option>
			<option value="ISBN">
			<xsl:if test="$field_selected = 'ISBN'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:copy-of select="$text_searchbox_field_isbn" />
			</option>
			<option value="WYR">
			<xsl:if test="$field_selected = 'WYR'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:copy-of select="$text_searchbox_field_year" />
			</option>
		</xsl:if>
	</select>
	<xsl:text> </xsl:text><label for="query{$input_name_suffix}"><xsl:copy-of select="$text_searchbox_for" /></label><xsl:text> </xsl:text>
	<input id="query{$input_name_suffix}" class="query{$input_name_suffix}" name="query{$input_name_suffix}" type="text" size="32" value="{$query_entered}" />
	
</xsl:template>

<!--
	TEMPLATE: TAG INPUT
	tab/label input form used to enter labels/tags for saved record, on both folder page and search results
	page (for saved records only) one of record (usually) or id (unusually) are required. 
	parameter: record  =>  XSL node representing a savedRecord with a child <id> and optional children <tags>
	parameter: id => pass a string id instead of a record in nodeset. Used for the 'template' form for ajax 
	label input adder. 
-->

<xsl:template name="tag_input">
	<xsl:param name="record" select="." />
	<xsl:param name="id" select="$record/id" /> 
	<xsl:param name="context">the saved records page</xsl:param>

	<div class="folderLabels recordAction" id="tag_input_div-{$id}">
		<form action="./" method="get" class="tags">
		
			<!-- note that if this event is fired with ajax, the javascript changes
			the action element here to 'tags_edit_ajax' so the server knows to display a 
			different view, which the javascript captures and uses to updates the totals above. -->
			
			<input type="hidden" name="base" value="folder" />
			<input type="hidden" name="action" value="tags_edit" />
			<input type="hidden" name="record" value="{$id}" />
			<input type="hidden" name="context" value="{$context}" />
			
			<xsl:variable name="tag_list">
				<xsl:for-each select="$record/tag">
					<xsl:value-of select="text()" />
					<xsl:if test="following-sibling::tag">
						<xsl:text>, </xsl:text>
					</xsl:if>
				</xsl:for-each>
			</xsl:variable>
			
			<input type="hidden" name="tagsShaddow" id="shadow-{$id}" value="{$tag_list}" />
			
			<label for="tags-{$id}"><xsl:copy-of select="$text_records_tags" /></label>
			
			<input type="text" name="tags" id="tags-{$id}" class="tagsInput" value="{$tag_list}" />			
			<xsl:text> </xsl:text>
			<input id="submit-{$id}" type="submit" name="submitButton" value="Update" class="tagsSubmit" />
		</form>
	</div>
	
</xsl:template>


<!--
	TEMPLATE: SUBJECT DATABASES LIST
	used to list databases, generally on a search form, on databases_subject.xsl,
	and embed_subject.xsl 
-->

<xsl:template name="subject_databases_list">
	<xsl:param name="should_show_checkboxes" select="true()" />
	<!-- specific subcategory only? Default to false meaning, no, all subcats. -->
	<xsl:param name="show_only_subcategory" select="false()" />
	
	<xsl:for-each select="category/subcategory[(not($show_only_subcategory ))
		or ($show_only_subcategory = '') or (@id = $show_only_subcategory)]">
	
		<fieldset class="subjectSubCategory">
		<legend><xsl:value-of select="@name" /></legend>
			
			<!-- if the current session can't search this resource, should we show a lock icon? 
			We show lock icons for logged in with account users, on campus users, and guest users. 
			Not for off campus not logged in users, because they might be able to search more 
			resources than we can tell now. --> 
				
			<xsl:variable name="should_lock_nonsearchable" select=" (//request/authorization_info/affiliated = 'true' 
				or //request/session/role = 'guest')" />
			
			<xsl:variable name="subcategory" select="position()" />

			<ul class="databaseSectionList">
			<xsl:for-each select="database">
				<li class="databaseSectionItem">
				
				<xsl:variable name="id_meta" select="metalib_id" />
				
				<!-- how many database checkboxes were displayed in this subcategory, before now?
					Used for seeing if we've reached maximum for default selected dbs. Depends on 
					if we're locking non-searchable or not. -->
					
				<xsl:variable name="prev_checkbox_count">
				<xsl:choose>
					<xsl:when test="$should_lock_nonsearchable">
					<xsl:value-of select="count(preceding-sibling::database[searchable_by_user = '1'])" />
					</xsl:when>
					<xsl:otherwise>
					<xsl:value-of select="count(preceding-sibling::database[searchable = '1'])" />
					</xsl:otherwise>
				</xsl:choose>
				</xsl:variable>
				
				<!-- Show a checkbox, a disabled checkbox, or a lock icon. If it's a checkbox, default it to checked or not. -->
				
				<xsl:choose>
				<xsl:when test="not($should_show_checkboxes)">
					<xsl:text> </xsl:text>
				</xsl:when>
				<xsl:when test="searchable = 1">
					<xsl:choose>
						<xsl:when test="$should_lock_nonsearchable	and searchable_by_user != '1'" >
							<!-- if we have a logged in user (or a registered guest), but they can't search this, show them a lock. -->			
							<img src="{$base_url}/images/lock.png" alt="{$text_databases_subject_hint_restricted}" title="{$text_databases_subject_hint_restricted}" />
						</xsl:when>
						<xsl:otherwise>
							<!-- if no user logged in, or user logged in and they can
							search this, show them a checkbox. -->
							<xsl:element name="input">
								<xsl:attribute name="name">database</xsl:attribute>
								<xsl:attribute name="id"><xsl:value-of select="../@id" />_<xsl:value-of select="metalib_id" /></xsl:attribute>
								<xsl:attribute name="value"><xsl:value-of select="metalib_id" /></xsl:attribute>
								<xsl:attribute name="type">checkbox</xsl:attribute>
								<xsl:if test="$subcategory = 1 and $prev_checkbox_count &lt; //config/search_limit">
									<xsl:attribute name="checked">checked</xsl:attribute>
								</xsl:if>
								<xsl:attribute name="class">subjectDatabaseCheckbox</xsl:attribute>
							</xsl:element>
						</xsl:otherwise>
					</xsl:choose>
					<xsl:text> </xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<img src="{$base_url}/images/link-out.gif" alt="{$text_databases_subject_hint_restricted}" title="{$text_databases_subject_hint_native_only}"/>
					<xsl:text> </xsl:text>
				</xsl:otherwise>
				</xsl:choose>
				
				<span class="subjectDatabaseTitle">
					<xsl:choose>
						<xsl:when test="not($should_lock_nonsearchable and searchable_by_user != '1')">
							<a title="{$text_databases_subject_hint_direct_search} {title_display}" target="{$link_target_databases}">
							<xsl:attribute name="href"><xsl:value-of select="xerxes_native_link_url" /></xsl:attribute>
								<xsl:value-of select="title_display" />
							</a>
							<!-- label that is hidden from normal graphical browsers, but 
							available for screen readers or other machine
							processing. -->
							<label for="{../@id}_{metalib_id}" style="position: absolute; top: -1000px;" class="ada databaseSectionItemLabel">
								<xsl:value-of select="title_display" />
							</label>
						</xsl:when>
						<xsl:otherwise>
							<a title="{$text_databases_subject_hint_direct_search} {title_display}">
							<xsl:attribute name="href"><xsl:value-of select="xerxes_native_link_url" /></xsl:attribute>
								<xsl:value-of select="title_display" />
							</a>
						</xsl:otherwise>
					</xsl:choose>
					<xsl:text> </xsl:text>					
				</span>
					
				<span class="subjectDatabaseInfo">
					<a  title="{$text_databases_subject_hint_more_info_about} {title_display}">
					<xsl:attribute name="href"><xsl:value-of select="url" /></xsl:attribute>
					<img src="images/info.gif" alt="{$text_databases_subject_hint_more_info_about} {title_display}">
						<xsl:attribute name="src"><xsl:value-of select="//config/base_url" />/images/info.gif</xsl:attribute>
					</img>
					</a>
					<xsl:text> </xsl:text>
				</span>
				
				<xsl:if test="group_restriction">
					<span class="subjectDatabaseRestriction">
						<xsl:call-template name="db_restriction_display" />
					</span>
				</xsl:if>
				
				</li>
			</xsl:for-each>
			</ul>
		</fieldset>
	</xsl:for-each>
	
</xsl:template>

<!-- 
	TEMPLATE: DATABASES SEARCH BOX
	Search box that appears sometimes on databases_alphabetical.xsl. May
	appear other places eventually.
-->

<xsl:template name="databases_search_box">
		
	<form method="GET" action="./">
		<div id="databasesSearch" class="raisedBox">
			<input type="hidden" name="base" value="databases" />
			<input type="hidden" name="action" value="find" />
			
			<label for="query"><xsl:copy-of select="$text_databases_az_search" /></label> 
			
			<input id="query" name="query" type="text" size="32">
				<xsl:attribute name="value"><xsl:value-of select="request/query" /></xsl:attribute>
			</input>
			<xsl:text> </xsl:text>
			<input type="submit" value="{$text_searchbox_go}" />
		</div>		
	</form>
	
</xsl:template>

<!-- 
	TEMPLATE: DB RESTRICTION DISPLAY
 	Show access rights for db, including group restrictions. Either pass in a parameter, or else it assumes that
	a <database> node is the XSL current() node. 
-->

<xsl:template name="db_restriction_display">
	<xsl:param name="database" select="current()" />

	<xsl:variable name="group_restrictions" select="$database/group_restriction" />
	
	<xsl:if test="$group_restrictions">
		<xsl:copy-of select="$text_databases_access_available" />
	</xsl:if>
	
	<xsl:for-each select="$group_restrictions">
		<xsl:value-of select="@display_name" />
		<xsl:choose>
			<xsl:when test="count(following-sibling::group_restriction) = 1">
				<xsl:text> </xsl:text><xsl:copy-of select="$text_databases_access_group_and" /><xsl:text> </xsl:text>
			</xsl:when>
			<xsl:when test="count(following-sibling::group_restriction) > 1">
			,<xsl:text> </xsl:text>
			</xsl:when>
		</xsl:choose>
	</xsl:for-each>
	<xsl:if test="$group_restrictions">
	<xsl:text>  </xsl:text><xsl:copy-of select="$text_databases_access_users" />
	</xsl:if>
</xsl:template>


<!-- 	
	TEMPLATE: FOLDER BRIEF RESULTS
	VERY Brief results list that appears on the export options pages.
-->

<xsl:template name="folder_brief_results">

	<xsl:variable name="username" 	select="request/session/username" />
	
	<!-- <xsl:call-template name="folder_export_options" /> -->
	
	<fieldset>
		<legend><xsl:copy-of select="$text_folder_records_export" /></legend>
		
			<input type="button" id="clear_databases" value="clear all" />

			<ul id="folder_output_results">
			<xsl:for-each select="results/records/record">
				<li>
					<input type="checkbox" name="record" value="{id}" id="record-{id}" checked="checked" />
					<label for="record-{id}">
						<a href="{url_full}"><xsl:value-of select="title" /></a><br />
						<xsl:value-of select="author" /> / 
						<xsl:call-template name="text_results_format">
							<xsl:with-param name="format" select="format" />
						</xsl:call-template> / 
						<xsl:value-of select="year" />
					</label>
				</li>
			</xsl:for-each>
			</ul>
	</fieldset>
	
</xsl:template>


<!-- 
	TEMPLATE: FOLDER HEADER 
	Sets the name of the folder area, dynamically based on roles.
-->

<xsl:template name="folder_header">

	<xsl:variable name="return" 	select="php:function('urlencode', string(request/server/request_uri))" />
	
	<h1><xsl:call-template name="folder_header_label" /></h1>
				
	<!-- @todo make this a singletext label variable -->
	
	<xsl:if test="request/session/role = 'local'">
		<p class="temporary_login_note"><xsl:copy-of select="$text_folder_login_temp" /></p>
	</xsl:if>
	
	<xsl:call-template name="folder_header_limit" />

</xsl:template>

<!-- 
	TEMPLATE: FOLDER HEADER LIMIT
	Shows a selected 'tag' or format limit across the my saved records pages, including exports
-->

<xsl:template name="folder_header_limit">
	
	<xsl:if test="request/label">
		<h2>
			<a href="./?base=folder"><img src="{$base_url}/images/delete.gif" alt="remove limit" /></a>
			<xsl:copy-of select="$text_folder_limit_tag" /><xsl:text>: </xsl:text><xsl:value-of select="request/label" />
		</h2>
	</xsl:if>
	
	<xsl:if test="request/type">
		<h2>
			<a href="./?base=folder"><img src="{$base_url}/images/delete.gif" alt="remove limit" /></a>
			<xsl:copy-of select="$text_folder_limit_format" /><xsl:text>: </xsl:text><xsl:value-of select="request/type" />
		</h2>
	</xsl:if>

</xsl:template>

<!-- 
	TEMPLATE: FOLDER HEADER LABEL
	Whether this is 'temporary' or 'my' saved records
-->

<xsl:template name="folder_header_label">
	<xsl:choose>
		<xsl:when test="$temporarySession = 'true'">
			<xsl:copy-of select="$text_folder_header_temporary" />
		</xsl:when>
		<xsl:otherwise>
			<xsl:copy-of select="$text_header_savedrecords" />
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<!-- 
	TEMPLATE: TAGS DISPLAY
	used by a couple of pages in the folder area for displaying tags
-->

<xsl:template name="tags_display">
	
	<h2><xsl:copy-of select="$text_folder_options_tags" /></h2>
	<ul>
	<xsl:for-each select="tags/tag">
		<li>
		<xsl:choose>
			<xsl:when test="@label = //request/label">
				<strong><xsl:value-of select="@label" /></strong> ( <xsl:value-of select="@total" /> )
			</xsl:when>
			<xsl:otherwise>
				<a href="{@url}"><span class="label_list_item"><xsl:value-of select="@label" /></span></a> ( <xsl:value-of select="@total" /> )
			</xsl:otherwise>
		</xsl:choose>
		</li>
	</xsl:for-each>
	</ul>
	
</xsl:template>


<!-- 	
	TEMPLATE: RESULTS RETURN
	provides a return mechanism on the saved records page to get back to the results
-->

<xsl:template name="results_return">

	<xsl:variable name="back" 	select="request/session/saved_return" />
	
	<xsl:if test="contains(request/session/saved_return,'hits') or 
			contains(request/session/saved_return,'results') or 
			contains(request/session/saved_return,'facet') or
			contains(request/session/saved_return,'record')">

		<div class="folderReturn">
			<img src="{$base_include}/images/back.gif" alt="" />
			<span class="folderReturnText">
				<a href="{$back}"><xsl:copy-of select="$text_folder_return" /></a>
			</span>
		</div>
		
	</xsl:if>

</xsl:template>

<!-- 	
	TEMPLATE: HEADER REFRESH
	Refresh the page on the metasearch hits progress
-->

<xsl:template name="header_refresh">

	<!-- metasearch refresh -->
	<!-- automated refresh, unless this is opera mobile or screen reader -->
	
	<xsl:if test="$is_mobile = 1 and not(contains(//server/http_user_agent,'Opera')) and not(request/session/ada)">
		<xsl:if test="request/action = 'hits' and results/progress &lt; 10">
			<meta http-equiv="refresh" content="6" />
		</xsl:if>
	</xsl:if>


</xsl:template>

<!--
	TEMPLATE: FULL TEXT LINKS
	Constructs proxied links for full-text links in the results, folder, and full record
	Assumes that you call from inside a xerxes_record element
-->

<xsl:template name="full_text_links">
	<xsl:param name="class" />
	
	<xsl:variable name="database_code" select="metalib_id" />
	
	<xsl:for-each select="links/link[@type != 'none' and @type != 'original_record' and @type != 'holdings']">
		
		<div class="{$class}">
		
			<xsl:variable name="url">
				<xsl:if test="url">
					<xsl:value-of select="php:function('urlencode', string(url))" />
				</xsl:if>
			</xsl:variable>
			
			<a>
				<xsl:attribute name="href">
					<xsl:value-of select="$base_url" /><xsl:text>/</xsl:text>
					<xsl:text>./?base=databases&amp;action=proxy</xsl:text>
					<xsl:text>&amp;database=</xsl:text><xsl:value-of select="$database_code" />
					<xsl:choose>
						<xsl:when test="$url != ''">
							<xsl:text>&amp;url=</xsl:text><xsl:value-of select="$url" />
						</xsl:when>
						<xsl:otherwise>
							<xsl:for-each select="param">
								<xsl:text>&amp;param=</xsl:text>
								<xsl:value-of select="@field" />
								<xsl:text>=</xsl:text>
								<xsl:value-of select="text()" />
							</xsl:for-each>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:attribute>
				
				<xsl:attribute name="class">recordAction <xsl:value-of select="@type"/></xsl:attribute>
				<xsl:attribute name="target"><xsl:value-of select="$link_target" /></xsl:attribute>
			
				<xsl:choose>
					<xsl:when test="@type = 'pdf'">
						<img src="{$base_include}/images/pdf.gif" alt="" width="16" height="16" border="0" class="miniIcon fullTextLink pdf"/>
						<xsl:text> </xsl:text>
						<xsl:copy-of select="$text_records_fulltext_pdf" />
					</xsl:when>
					<xsl:when test="@type = 'html'">
						<img src="{$base_include}/images/html.gif" alt="" width="16" height="16" border="0" class="miniIcon fullTextLink html"/>
						<xsl:text> </xsl:text>
						<xsl:copy-of select="$text_records_fulltext_html" />
					</xsl:when>
					<xsl:otherwise>
						<img src="{$base_include}/images/html.gif" alt="" width="16" height="16" border="0" class="miniIcon fullTextLink unknown"/>
						<xsl:text> </xsl:text>
						<xsl:copy-of select="$text_records_fulltext_available" />
					</xsl:otherwise>
				</xsl:choose>
			</a>
		
		</div>
		
	</xsl:for-each>
</xsl:template>

<!-- 
	TEMPLATE: RECORD LINK
	generates a holding or link_native single link, you supply the type. 
	Call from an XSL context where ./ is a xerxes_record
-->
	 
<xsl:template name="record_link">
	<xsl:param name="type" />
	<xsl:param name="class">recordAction <xsl:value-of select="$type"/></xsl:param>
	<xsl:param name="text" select="$type"/>
	<xsl:param name="img_src"/>
	
	<xsl:if test="links/link[@type=$type]">
		<xsl:variable name="encoded_direct_url">
			<xsl:value-of select="php:function('urlencode', string(links/link[@type=$type]))" />
		</xsl:variable>
	
		<!-- send through proxy action for possible proxying -->
		
		<a class="{$class}">
			<xsl:attribute name="href">
				<xsl:value-of select="$base_url" /><xsl:text>/</xsl:text>
				<xsl:text>./?base=databases&amp;action=proxy</xsl:text>
				<xsl:text>&amp;database=</xsl:text><xsl:value-of select="metalib_id" />
				<xsl:text>&amp;url=</xsl:text><xsl:value-of select="$encoded_direct_url" />
			</xsl:attribute>
		
			<xsl:if test="$img_src">      
				<img src="{$img_src}" alt="" class="miniIcon {$type}Link"/>
			</xsl:if>
			
			<xsl:text> </xsl:text>
			<xsl:copy-of select="$text"/>
		</a>
	</xsl:if>
	
</xsl:template>

<!--
	TEMPLATE: MY ACCOUNT SIDEBAR
	links to login/out, my saved records, and other personalization features
-->

<xsl:template name="account_sidebar">
	<div id="account" class="box">
		<h2><xsl:copy-of select="$text_header_myaccount" /></h2>
		<ul>
			<li id="login_option">
				<xsl:choose>
					<xsl:when test="//request/session/role and //request/session/role != 'local'">
						<a id="logout">
						<xsl:attribute name="href"><xsl:value-of select="//navbar/element[@id = 'logout']/url" /></xsl:attribute>
							<xsl:copy-of select="$text_header_logout" />
						</a>
					</xsl:when>
					<xsl:otherwise>
						<a id="login">
						<xsl:attribute name="href"><xsl:value-of select="//navbar/element[@id = 'login']/url" /></xsl:attribute>
							<xsl:copy-of select="$text_header_login" />
						</a>
					</xsl:otherwise>
				</xsl:choose>
			</li>
		
			<li id="my_saved_records" class="sidebarFolder">
				<img name="folder" width="17" height="15" border="0" id="folder" alt="">
					<xsl:attribute name="src">
					<xsl:choose>
					<xsl:when test="//navbar/element[@id='saved_records']/@numSessionSavedRecords &gt; 0"><xsl:value-of select="$base_include" />/images/folder_on.gif</xsl:when>
					<xsl:otherwise><xsl:value-of select="$base_include"/>/images/folder.gif</xsl:otherwise>
					</xsl:choose>
					</xsl:attribute>
				</img>
				<xsl:text> </xsl:text>
				<a>
				<xsl:attribute name="href"><xsl:value-of select="//navbar/element[@id='saved_records']/url" /></xsl:attribute>
					<xsl:copy-of select="$text_header_savedrecords" />
				</a>
			</li>
			
			<xsl:if test="//navbar/element[@id='saved_collections']">
				<li id="my_databases" class="sidebarFolder">
					<img src="{$base_include}/images/folder.gif" width="17" height="15" border="0" alt=""/><xsl:text> </xsl:text>
					<a href="{//navbar/element[@id='saved_collections']/url}"><xsl:copy-of select="$text_header_collections"/></a>
				</li>
			</xsl:if>
		</ul>
	</div>
</xsl:template>

<!--
	TEMPLATE: COLLECTIONS SIDEBAR
	This sidebar shows a list of collections, and has a form to create a new collection. 
	(user-created subject). It is shown on collection-relate pages.
-->

<xsl:template name="collections_sidebar">
	<div id="collections" class="box">
		<h2><xsl:copy-of select="$text_header_my_collections" /></h2>
		<p><xsl:copy-of select="$text_header_my_collections_explain" /></p>
		<ul>
		<!-- don't list the default collection here, that's presented differently. -->
		<xsl:for-each select="/*/userCategories/category[name != /*/config/default_collection_name]">
			<li>
				<xsl:choose>
					<xsl:when test="//request/base = 'collections' and //request/action = 'subject' and //request/subject = normalized">
						<!-- already looking at it, don't make it a link. -->
						<strong><xsl:value-of select="name"/></strong>
					</xsl:when>
					<xsl:otherwise>
						<a href="{url}"><xsl:value-of select="name"/></a>
					</xsl:otherwise>
				</xsl:choose>
			</li>
		</xsl:for-each>
		</ul>
		
		<form method="GET" action="./">
		<input type="hidden" name="base" value="collections"/>
		<input type="hidden" name="action" value="new"/>
		<input type="hidden" name="username" value="{//request/username}"/>
		
		<input type="hidden" name="new_subcategory_name" value="{$text_collection_default_new_section_name}"/>
		
		<p><label for="new_subject_name"><xsl:copy-of select="$text_header_my_collections_new" /></label></p>
		<input type="text" id="new_subject_name" name="new_subject_name"/><xsl:text> </xsl:text><input type="submit" name="add" value="{$text_header_my_collections_add}" />
		
		</form>
	</div>
</xsl:template>

<!-- 
	TEMPLATE: SNIPPET SIDEBAR
	Link to generate the snippet. Only shown for user-generated
  subjects if subject is public. 
-->

<xsl:template name="snippet_sidebar">
	<xsl:if test="not(/*/category/@owned_by_user) or /*/category/@published = 1">
		<div id="snippet" class="box">
			<h2><xsl:copy-of select="$text_header_embed" /></h2>
	
			<ul>
			<xsl:if test="request/base = 'databases' and (request/action = 'subject' or request/action ='metasearch')">
				<xsl:variable name="subject" select="//category/@normalized" />
				<li> <a href="./?base=embed&amp;action=gen_subject&amp;subject={$subject}"><xsl:copy-of select="$text_header_snippet_generate_subject" /></a> </li>
			</xsl:if>
			
			<xsl:if test="request/base = 'databases' and request/action = 'database'">
				<xsl:variable name="id" select="//database[1]/metalib_id" />
				<li> <a href="./?base=embed&amp;action=gen_database&amp;id={$id}"><xsl:copy-of select="$text_header_snippet_generate_database" /></a> </li>
			</xsl:if>
			
			<xsl:if test="request/base = 'collections' and (request/action = 'subject' or request/action = 'edit_form')">
				<li> <a href="./?base=collections&amp;action=gen_embed&amp;username={//category[1]/@owned_by_user}&amp;subject={//category[1]/@normalized}"><xsl:copy-of select="$text_header_snippet_generate_collection" /></a> </li>
			</xsl:if>
			
			</ul>
		</div>
	</xsl:if>
</xsl:template>

<!--
	TEMPLATE: SESSION AUTH INFO
	Displays a user's authorization crednetials from login and IP.  Useful especially if you are using Metalib 
	usergroup/secondary affiliation access. jrochkind likes to display it on the front page in a sidebar.
-->

<xsl:template name="session_auth_info">
	<div id="sessionAuthInfo" class="box">

		<xsl:choose>
			<xsl:when test="//request/authorization_info/affiliated[@user_account = 'true']">
				<h2 class="sessionLoggedIn">Welcome, <xsl:value-of select="//session/user_properties[@key = 'username']" />.</h2>	 
			</xsl:when>
			<xsl:otherwise>
				<h2 class="sessionLoggedOut">Welcome</h2>	
			</xsl:otherwise>
		</xsl:choose>
		
		<div class="sessionAuthSection">
			<xsl:choose>
				<xsl:when test="//request/authorization_info/group[@user_account = 'true']">
					<h3>Your Affiliation: </h3>
					<ul>
						<xsl:for-each select="//request/authorization_info/group[@user_account = 'true']">
							<li><xsl:value-of select="@display_name" /></li>
						</xsl:for-each>
					</ul>
				</xsl:when>
				<xsl:when test="//session/role = 'guest'">
					<h3>Your Affiliation: Guest</h3>
				</xsl:when>
			</xsl:choose>
		</div>
		
		<div class="sessionAuthSection">
			<h3>Your Location: </h3>
			<ul>
			<xsl:choose>
				<xsl:when test="//request/authorization_info/group[@ip_addr = 'true']">
					<xsl:for-each select="//request/authorization_info/group[@ip_addr = 'true']">
						<li><xsl:value-of select="@display_name" /></li>
					</xsl:for-each>	
				</xsl:when>			
				<xsl:when test="//request/authorization_info/affiliated[@ip_addr = 'true']">
					<li>On-campus</li>
				</xsl:when>
				<xsl:otherwise>
					<li><strong>Off</strong> campus</li>
				</xsl:otherwise>
			</xsl:choose>
			</ul>
		</div>

	</div>
</xsl:template>


<!-- 
	TEMPLATE PAGING NAVIGATION	
	Provides the visual display for moving through a set of results
-->

<xsl:template name="paging_navigation">

	<xsl:if test="//pager/page">
		<div class="resultsPager">

			<ul class="resultsPagerList">
			<xsl:for-each select="//pager/page">
				<li>
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
									<xsl:copy-of select="$text_results_next" />
								</xsl:when>
								<xsl:otherwise>
									<xsl:attribute name="class">resultsPagerLink</xsl:attribute>
								</xsl:otherwise>
							</xsl:choose>
							<xsl:call-template name="text_results_sort_options">
								<xsl:with-param name="option" select="text()" />
							</xsl:call-template>
						</a>
					</xsl:otherwise>
				</xsl:choose>
				</li>
			</xsl:for-each>
			</ul>
		</div>
	</xsl:if>

</xsl:template>

<!-- 
	TEMPLATE: FACETS
	Control the display and configuration of facets in the metasearch results 
-->

<xsl:template name="facets">

	<xsl:variable name="group" 				select="request/group" />
	<xsl:variable name="this_result_set"	select="request/resultset" />

	<xsl:if test="//cluster_facet and results/database = 'Top Results'">
		
		<div id="facets" class="box">
			<h2><xsl:copy-of select="$text_header_facets" /></h2>
			<xsl:for-each select="//cluster_facet[@name != 'DATABASE']">
			
				<xsl:variable name="name" select="@name" />
				
				<xsl:if test="//cluster_facet[@name = $name]/node[node_no_of_docs > 2 and @name != 'Other']">
				
					<xsl:variable name="facet_number" select="@position" />
					
					<h3><xsl:call-template name="text_facet_group" /></h3>
					
					<ul>
					
					<xsl:choose>
						<xsl:when test="@name != 'DATE'">
							<xsl:for-each select="node[node_no_of_docs > 2 and @name != 'Other' and @name != 'Target not returning the record']">
								
								<xsl:call-template name="facet_display">
									<xsl:with-param name="group" select="$group" />
									<xsl:with-param name="this_result_set" select="$this_result_set" />
									<xsl:with-param name="facet_number" select="$facet_number" />
								</xsl:call-template>
							</xsl:for-each>
						</xsl:when>
						<xsl:when test="@name = 'DATE'">
							<xsl:for-each select="node[node_no_of_docs > 2 and @name != 'Other' and @name != 'Target not returning the record']">
								<xsl:sort select="@name" order="descending" />
								<xsl:call-template name="facet_display">
									<xsl:with-param name="group" select="$group" />
									<xsl:with-param name="this_result_set" select="$this_result_set" />
									<xsl:with-param name="facet_number" select="$facet_number" />
								</xsl:call-template>
							</xsl:for-each>
						</xsl:when>
					
					</xsl:choose>
					
					</ul>
				</xsl:if>
			</xsl:for-each>
		</div>
	</xsl:if>
	
</xsl:template>

<!-- 
	TEMPLATE: FACET DISPLAY
	A utility template for the 'facets' tempalte above
-->

<xsl:template name="facet_display">

	<xsl:param name="group" />
	<xsl:param name="this_result_set" />
	<xsl:param name="facet_number" />
	<xsl:param name="facet_return" />

	<xsl:variable name="node_pos" select="@position" />
	
	<xsl:if test="@node_level = 1">
		<li>
		<xsl:choose>
			<xsl:when test="//request/node = $node_pos and //request/facet = $facet_number">
				<strong><xsl:value-of select="@name" /></strong> ( <xsl:value-of select="node_no_of_docs" /> )
			</xsl:when>
			<xsl:otherwise>
				<a href="{url}"><xsl:value-of select="@name" /></a>
		 		(&nbsp;<xsl:value-of select="node_no_of_docs" />&nbsp;)
			</xsl:otherwise>
		</xsl:choose>
		</li>
		
	</xsl:if>

</xsl:template>

<!-- 
	TEMPLATE: BRIEF RESULTS
	deprecated, used in the metasearch and folder brief results pages
-->

<xsl:template name="brief_results">

	<ul id="results">
	
	<xsl:for-each select="//records/record/xerxes_record">
		<xsl:call-template name="brief_result_article">
			<xsl:with-param name="result_set" select="result_set" />
			<xsl:with-param name="record_number" select="record_number" />		
		</xsl:call-template>
	</xsl:for-each>
	
	</ul>
	
</xsl:template>

<!-- 
	TEMPLATE: BRIEF RESULT ARTICLE
	display of results geared toward articles (or really any non-book display)
-->

<xsl:template name="brief_result_article">
	
		<xsl:param name="result_set" />
		<xsl:param name="record_number" />
		
		<!-- peer reviewed calculated differently in folder and metasearch -->
		
		<xsl:variable name="refereed">
			<xsl:choose>
				<xsl:when test="refereed = 1 and not(contains(format,'Review'))">
					<xsl:text>true</xsl:text>
				</xsl:when>
				<xsl:when test="../refereed = 1 and not(contains(format,'Review'))">
					<xsl:text>true</xsl:text>
				</xsl:when>
				<xsl:when test="//refereed/issn = standard_numbers/issn and not(contains(format,'Review'))">
					<xsl:text>true</xsl:text>
				</xsl:when>
			</xsl:choose>
		</xsl:variable>
		
		<xsl:variable name="record_id">
			<xsl:value-of select="$result_set" />:<xsl:value-of select="$record_number" />
		</xsl:variable>
		
		<li class="result">
			
			<xsl:variable name="title">
				<xsl:choose>
					<xsl:when test="title_normalized != ''">
						<xsl:value-of select="title_normalized" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:copy-of select="$text_results_no_title" />
					</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			
			<div class="resultsTitle">
				<a href="{../url_full}"><xsl:value-of select="$title" /></a>
			</div>
			
			<div class="resultsInfo">
			
				<div class="resultsType">
					<xsl:call-template name="text_results_format">
						<xsl:with-param name="format" select="format" />
					</xsl:call-template>
					
					<xsl:call-template name="text_results_language" />
					
					<!-- peer reviewed -->
					
					<xsl:if test="$refereed = 'true'">
						<xsl:text> </xsl:text><img src="images/refereed_hat.gif" width="20" height="14" alt="" />
						<xsl:text> </xsl:text><xsl:copy-of select="$text_results_refereed" />
					</xsl:if>
				</div>
				
				<div class="resultsAbstract">
				
					<xsl:choose>
						<xsl:when test="summary_type = 'toc'">
							<xsl:value-of select="$text_record_summary_toc" /><xsl:text>: </xsl:text>
						</xsl:when>
						<xsl:when test="summary_type = 'subjects'">
							<xsl:value-of select="$text_record_summary_subjects" /><xsl:text>: </xsl:text>
						</xsl:when>					
					</xsl:choose>
				
					<xsl:choose>
						<xsl:when test="string-length(summary) &gt; 300">
							<xsl:value-of select="substring(summary, 1, 300)" /> . . .
						</xsl:when>
						<xsl:when test="summary">
							<xsl:value-of select="summary" />
						</xsl:when>
						
						<!-- @todo remove this after we make sure this feature is truly gone -->
						<!-- take from embedded text, if available -->
						
						<xsl:when test="embeddedText">
							<xsl:variable name="usefulContent" select="embeddedText/paragraph[ string-length(translate(text(), '- ', '')) &gt; 20]" />
							<xsl:value-of select="substring($usefulContent, 1, 300)" />
							<xsl:if test="string-length($usefulContent) &gt; 300">. . . </xsl:if>
						</xsl:when>
					</xsl:choose>
				</div>
				
				<xsl:if test="primary_author">
					<span class="resultsAuthor">
						<strong><xsl:copy-of select="$text_results_author" />: </strong><xsl:value-of select="primary_author" />
					</span>
				</xsl:if>
				
				<xsl:if test="year">
					<span class="resultsYear">
						<strong><xsl:copy-of select="$text_results_year" />: </strong>
						<xsl:value-of select="year" />
					</span>
				</xsl:if>
				
				<xsl:if test="journal or journal_title">
					<span class="resultsPublishing">
						<strong><xsl:copy-of select="$text_results_published_in" />: </strong>
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
				
				<xsl:call-template name="additional_brief_record_data" />
				
				<div class="recordActions">
					
					<xsl:call-template name="full_text_options" />
					
					<xsl:call-template name="additional_record_links" />
					
					<xsl:choose>
						
						<!-- @todo: give saved record area it's own template -->
						
						<xsl:when test="/folder">
						
							<div class="folderAvailability deleteRecord">
								<a class="recordAction deleteRecord" href="{../url_delete}">
									<img src="{$base_url}/images/delete.gif" alt="" border="0" class="miniIcon deleteRecordLink"/>
									<xsl:text> </xsl:text>
									<xsl:copy-of select="$text_results_record_delete" />
								 </a>
							</div>
							
							<xsl:if test="$temporarySession != 'true'">
								<xsl:call-template name="tag_input">
									<xsl:with-param name="record" select=".."/>
								</xsl:call-template>
							</xsl:if>						
						
						</xsl:when>
						<xsl:otherwise>
						
							<!-- save facility in search results area -->
							
							<div id="saveRecordOption_{$result_set}_{$record_number}" class="recordAction saveRecord">
								<img id="folder_{$result_set}{$record_number}"	width="17" height="15" alt="" border="0" class="miniIcon saveRecordLink">
								<xsl:attribute name="src">
									<xsl:choose> 
										<xsl:when test="//request/session/resultssaved[@key = $record_id]">images/folder_on.gif</xsl:when>
										<xsl:otherwise>images/folder.gif</xsl:otherwise>
									</xsl:choose>
								</xsl:attribute>
								</img>
								
								<xsl:text> </xsl:text>
								<a id="link_{$result_set}:{$record_number}" href="{../url_save_delete}">
									<!-- 'saved' class used as a tag by ajaxy stuff -->
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
										(<xsl:text> </xsl:text><a href="{//navbar/element[@id = 'login']/url}">
											<xsl:copy-of select="$text_results_record_saved_perm" />
										</a><xsl:text> </xsl:text>)
									</span>
								</xsl:if>
							</div>
							
							<!-- label/tag input for saved records, if record is saved and it's not a temporary session -->
							
							<xsl:if test="//request/session/resultssaved[@key = $record_id] and $temporarySession != 'true'">
								<div id="label_{$result_set}:{$record_number}" > 
									<xsl:call-template name="tag_input">
										<xsl:with-param name="record" select="//saved_records/saved[@id = $record_id]" />
										<xsl:with-param name="context">the results page</xsl:with-param>
									</xsl:call-template>	
								</div>
							</xsl:if>

						</xsl:otherwise>

					</xsl:choose>					
				</div>
			</div>
		</li>

</xsl:template>

<xsl:template name="full_text_options">

	<xsl:variable name="metalib_db_id" 	select="metalib_id" />
	<xsl:variable name="link_resolver_allowed" select="not(//database_links/database[@metalib_id = $metalib_db_id]/sfx_suppress = '1')" />

	<!-- holdings (to catalog)  -->
	
	<xsl:if test="links/link[@type='holdings'] and (//config/show_all_holdings_links = 'true' or //config/holdings_links/database[@metalib_id=$metalib_db_id])">
			<xsl:call-template name="record_link">
				<xsl:with-param name="type">holdings</xsl:with-param>
				<xsl:with-param name="text" select="$text_link_holdings"/>
				<xsl:with-param name="img_src" select="concat($base_url, '/images/book.gif')"/>
			</xsl:call-template>
	</xsl:if>
	
	<xsl:choose>
	
		<!-- native full-text -->
	
		<xsl:when test="full_text_bool">
			
			<xsl:call-template name="full_text_links"/>							
				
		</xsl:when>
		
		<!-- link resolver -->
		
		<xsl:when test="$link_resolver_allowed and (subscription = 1 or //fulltext/issn = standard_numbers/issn)">
				<a href="{../url_open}&amp;fulltext=1" target="{$link_target}" class="recordAction linkResolverLink">
					<img src="{$base_include}/images/html.gif" alt="" width="16" height="16" border="0" class="miniIcon linkResolverLink"/>
					<xsl:text> </xsl:text>
					<xsl:copy-of select="$text_link_resolver_available" />
				</a>
		</xsl:when>
		
		<xsl:when test="$link_resolver_allowed">
				<a href="{../url_open}" target="{$link_target}" class="recordAction linkResoverLink">
					<img src="{$base_url}/images/sfx.gif" alt="" class="miniIcon linkResolverLink "/>
					<xsl:text> </xsl:text>
					<xsl:copy-of select="$text_link_resolver_check" />
				</a>
		</xsl:when>
		
		<!-- if no direct link or link resolver, do we have an original record link? -->
		
		<xsl:when test="links/link[@type='original_record'] and (//config/show_all_original_record_links = 'true' or //config/original_record_links/database[@metalib_id = $metalib_db_id])">
			<xsl:call-template name="record_link">
			<xsl:with-param name="type">original_record</xsl:with-param>
			<xsl:with-param name="text" select="$text_link_original_record"/>
			<xsl:with-param name="img_src" select="concat($base_url,'/images/famfamfam/link.png')"/>
			</xsl:call-template>
		</xsl:when>
		
		<!-- @todo remove this 
		if none of the above, but we DO have text in the record, tell them so. -->
		
		<xsl:when test="embeddedText/paragraph">
			<a href="{../url_full}" class="recordAction textLink">
			<img src="{$base_url}/images/famfamfam/page_go.png" alt="" class="miniIcon textLink"/>
				Text in record
			</a>
		</xsl:when>
	</xsl:choose>
	
</xsl:template>

<!-- 
	TEMPLATE: HIDDEN TAG LAYERS
	These are used in the metasearch results (but not folder results because it already has some of these) 
	and record pages for the auto-complete tag input
-->

<xsl:template name="hidden_tag_layers">
	
	<div id="tag_suggestions" class="autocomplete" style="display:none;"></div>

	<div id="template_tag_input" class="results_label" style="display:none;">
		<xsl:call-template name="tag_input">
			<xsl:with-param name="id">template</xsl:with-param>
		</xsl:call-template> 
	</div>

	<div id="labelsMaster" class="folderOutput" style="display: none">
		<xsl:call-template name="tags_display" />
	</div>
	
	<xsl:call-template name="safari_tag_fix" />
	
</xsl:template>


<!-- 
	TEMPLATE: SAFARI TAG FIX
	This hidden iframe essentially thwarts the Safari backforward cache so that
	tags don't get wacky
-->

<xsl:template name="safari_tag_fix">
	
	<xsl:if test="contains(//server/http_user_agent,'Safari')">
	
		<iframe style="height:0px;width:0px;visibility:hidden" src="about:blank">
			<!-- this frame prevents back-forward cache for safari -->
		</iframe>
		
	</xsl:if>

</xsl:template>

<!-- 
	TEMPLATE: SUBCATEGORIES SIDEBAR
	Display subcategories that have designated for the sidebar, with special handling
	of librarians
-->

<xsl:template name="subcategories_sidebar">

	<xsl:for-each select="sidebar/subcategory">
		
		<div class="box">
			<h2><xsl:value-of select="@name" /></h2>
			<ul>
			<xsl:for-each select="database">
				<li>
					<xsl:choose>
						<xsl:when test="type = 'Librarian'">
							<xsl:attribute name="class">subjectLibrarian</xsl:attribute>

							<xsl:if test="library_contact">
                            	<xsl:variable name="image_url" select="php:function('urlencode', string(library_contact))" />
								<div class="librarianPicture">
									<img src="./?base=databases&amp;action=librarian-image&amp;url={$image_url}" alt="{title_display}" />
								</div>
							</xsl:if>

							<div class="librarianTitle">
								<xsl:choose>
									<xsl:when test="link_native_home != ''">
										<a href="{xerxes_native_link_url}"><xsl:value-of select="title_display" /></a>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="title_display" />
									</xsl:otherwise>								
								</xsl:choose>
							</div>

							<dl>
								<xsl:if test="library_email">
									<div>
										<dt><xsl:copy-of select="$text_databases_subject_librarian_email" /></dt>
										<dd><xsl:call-template name="text_databases_subject_librarian_email_value" /></dd>
									</div>
								</xsl:if>
								<xsl:if test="library_telephone">
									<div>
										<dt><xsl:copy-of select="$text_databases_subject_librarian_telephone" /></dt>
										<dd><xsl:value-of select="library_telephone" /></dd>
									</div>
								</xsl:if>
								<xsl:if test="library_fax">
									<div>
										<dt><xsl:copy-of select="$text_databases_subject_librarian_fax" /></dt>
										<dd><xsl:value-of select="library_fax" /></dd>
									</div>
								</xsl:if>
								<xsl:if test="library_address">
									<div>
										<dt><xsl:copy-of select="$text_databases_subject_librarian_address" /></dt>
										<dd><xsl:value-of select="library_address" /></dd>
									</div>
								</xsl:if>
							</dl>
						</xsl:when>
						<xsl:otherwise>
							<a>
								<xsl:attribute name="href"><xsl:value-of select="xerxes_native_link_url" /></xsl:attribute>
								<xsl:value-of select="title_display" />
							</a>
						</xsl:otherwise>
					</xsl:choose>		
				</li>
			</xsl:for-each>
			</ul>
		</div>
		
	</xsl:for-each>

</xsl:template>

<!-- 
	TEMPLATE: MOBILE HEADER
	A special (slimmed-down) header to use when displaying for a mobile device
-->

<xsl:template name="mobile_header" >

	<div style="background-color: #336699; padding: 10px; padding-bottom: 2px;">
		<a href="{$base_url}" style="color: #fff; font-weight: bold; text-decoration:none">
			<xsl:value-of select="$app_name" />
		</a>
	</div>

</xsl:template>

<!-- 
	TEMPLATE: MOBILE FOOTER
	A special (slimmed-down) footer to use when displaying for a mobile device
-->

<xsl:template name="mobile_footer" />

<!-- 
	TEMPLATE: MOBILE METALIB SEARCH BOX
	A special (slimmed-down) search box, with metalib added hidden fields
-->

<xsl:template name="mobile_metalib_search_box">
	<xsl:variable name="search_query" select="//results/search/pair[@position = '1']/query" />
	<xsl:call-template name="mobile_search_box">
		<xsl:with-param name="query" select="$search_query" />
	</xsl:call-template>

	<xsl:for-each select="//base_info">
		<xsl:if test="base_001">
			<input type="hidden" name="database" value="{base_001}" />
		</xsl:if>
	</xsl:for-each>
		
</xsl:template>


<!-- 
	TEMPLATE: MOBILE SEARCH BOX
	Just the search box and go itself, sued for mobile
-->

<xsl:template name="mobile_search_box">
	<xsl:param name="query" />
	
	<div style="margin-bottom: 1em">
		<input type="text" name="query" value="{$query}" />
		<xsl:text> </xsl:text>
		<input class="searchbox_submit" type="submit" name="Submit" value="{$text_searchbox_go}" />
	</div>
	
</xsl:template>


<xsl:template name="generic_sort_bar">

	<xsl:choose>
		<xsl:when test="results/total = '0'">
			<xsl:call-template name="generic_no_hits" />
		</xsl:when>
		<xsl:otherwise>

			<div id="sort">
				<div class="yui-gd">
					<div class="yui-u first">
						<xsl:copy-of select="$text_metasearch_results_summary" />
					</div>
					<div class="yui-u">
						<xsl:choose>
							<xsl:when test="//sort_display">
								<div id="sortOptions">
									<xsl:copy-of select="$text_results_sort_by" /><xsl:text>: </xsl:text>
									<xsl:for-each select="//sort_display/option">
										<xsl:choose>
											<xsl:when test="@active = 'true'">
												<strong><xsl:value-of select="text()" /></strong>
											</xsl:when>
											<xsl:otherwise>
												<xsl:variable name="link" select="@link" />
												<a href="{$link}">
													<xsl:value-of select="text()" />
												</a>
											</xsl:otherwise>
										</xsl:choose>
										<xsl:if test="following-sibling::option">
											<xsl:text> | </xsl:text>
										</xsl:if>
									</xsl:for-each>
								</div>
							</xsl:when>
							<xsl:otherwise>&#160;</xsl:otherwise>
						</xsl:choose>
					</div>
				</div>
			</div>
		</xsl:otherwise>
	</xsl:choose>

</xsl:template>

<xsl:template name="generic_no_hits">

	<p class="error"><xsl:value-of select="$text_metasearch_hits_no_match" /></p>

</xsl:template>

<xsl:template name="generic_searchbox">

	<form action="./" method="get">

		<input type="hidden" name="base" value="{//request/base}" />
		<input type="hidden" name="action" value="search" />
		
		<xsl:call-template name="generic_searchbox_hidden_fields_local" />

		<xsl:if test="request/sortkeys">
			<input type="hidden" name="sortKeys" value="{request/sortkeys}" />
		</xsl:if>

	<xsl:choose>
		<xsl:when test="$is_mobile = '1'">
			<xsl:call-template name="generic_searchbox_mobile" />
		</xsl:when>
		<xsl:otherwise>
			<xsl:call-template name="generic_searchbox_full" />
		</xsl:otherwise>
	</xsl:choose>

	</form>	
	
</xsl:template>


<xsl:template name="generic_searchbox_mobile">

	<xsl:variable name="search_query" select="//request/query" />
	<xsl:call-template name="mobile_search_box">
		<xsl:with-param name="query" select="$search_query" />
	</xsl:call-template>
		
</xsl:template>

<xsl:template name="generic_searchbox_full">

	<xsl:choose>
		<xsl:when test="request/advanced or request/advancedfull">
			<xsl:call-template name="generic_advanced_search" />
		</xsl:when>
		<xsl:otherwise>
			<xsl:call-template name="generic_simple_search" />			
		</xsl:otherwise>
	</xsl:choose>

</xsl:template>

<xsl:template name="generic_simple_search">

	<xsl:variable name="query"	select="request/query" />
	
	<div class="raisedBox searchBox">

		<div class="searchLabel">
			<label for="field">Search</label><xsl:text> </xsl:text>
		</div>
		
		<div class="searchInputs">

			<select id="field" name="field">
				
				<xsl:for-each select="config/basic_search_fields/field">
				
					<xsl:variable name="internal">
						<xsl:choose>
							<xsl:when test="@id"><xsl:value-of select="@id" /></xsl:when>
							<xsl:otherwise><xsl:value-of select="@internal" /></xsl:otherwise>
						</xsl:choose>
					</xsl:variable>
				
					<option value="{$internal}">
					<xsl:if test="//request/field = $internal">
						<xsl:attribute name="selected">seleted</xsl:attribute>
					</xsl:if>
					<xsl:value-of select="@public" />
					</option>
					
				</xsl:for-each>
			</select>
			
			<xsl:text> </xsl:text><label for="query"><xsl:value-of select="$text_searchbox_for" /></label><xsl:text> </xsl:text>
			
			<input id="query" name="query" type="text" size="32" value="{$query}" /><xsl:text> </xsl:text>
			
			<input type="submit" name="Submit" value="GO" />
		
		</div>
		
		<xsl:if test="results/spelling != ''">
			<p class="spellSuggest error">Did you mean: <a href="{results/spelling/@url}"><xsl:value-of select="results/spelling" /></a></p>
		</xsl:if>	
		
		<xsl:call-template name="generic_advanced_search_option" />
		
	</div>

</xsl:template>


<xsl:template name="generic_advanced_search_option" />
<xsl:template name="generic_advanced_search" />
<xsl:template name="generic_searchbox_hidden_fields_local" />


<!-- 	
	TEMPLATE: HEADER
	header content, such as Javascript functions that should appear on specific page.
-->

<xsl:template name="header">

	<!--opensearch autodiscovery -->
	
	<xsl:if test="category/subcategory">
		<xsl:variable name="subject_name" select="//category[1]/@name" />
		<xsl:variable name="subject_id" select="//category[1]/@normalized" />
		<link rel="search"
			type="application/opensearchdescription+xml" 
			href="{$base_url}?base=databases&amp;action=subject-opensearch&amp;subject={$subject_id}"
			title="{$app_name} {$subject_name} search" />
	</xsl:if>
	
	<!-- exclude javascript for ada (because it messes with screen readers) and mobile devices (makes loading faster) -->

	<xsl:if test="not(request/session/ada) and $is_mobile = 0">
	
		<script src="{$base_include}/javascript/onload.js" language="javascript" type="text/javascript"></script>
		<script src="{$base_include}/javascript/prototype.js" language="javascript" type="text/javascript"></script>
		<script src="{$base_include}/javascript/scriptaculous/scriptaculous.js" language="javascript" type="text/javascript"></script>
		
		<!-- fancy message display -->
		
		<script src="{$base_include}/javascript/message_display.js" language="javascript" type="text/javascript"></script>
		
		<!-- controls the adding and editing of tags -->
		
		<script src="{$base_include}/javascript/tags.js" language="javascript" type="text/javascript"></script>
		
		
		<script src="{$base_include}/javascript/toggle_metasearch_advanced.js" language="javascript" type="text/javascript"></script>
		
		<!-- controls the saving and tracking of saved records -->
		
		<script type="text/javascript">
			
			// change numSessionSavedRecords to numSavedRecords if you prefer the folder icon to change
			// if there are any records at all in saved records. Also fix initial display in navbar.
			
			numSavedRecords = parseInt('0<xsl:value-of select="navbar/element[@id='saved_records']/@numSessionSavedRecords" />', 10);
			isTemporarySession = <xsl:choose><xsl:when test="$temporarySession = 'true'">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>
		</script>
		
		<script src="{$base_include}/javascript/save.js" language="javascript" type="text/javascript"></script>
		
		<script language="javascript" type="text/javascript">
			var dateSearch = "<xsl:value-of select="results/search/date" />";
			var xerxes_iSearchable = "<xsl:value-of select="$search_limit" />";
		</script>
		
		<!-- add behaviors to edit collection dialog, currently just delete confirm -->
		<script src="{$base_include}/javascript/collections.js" language="javascript" type ="text/javascript"></script>
	
		<!-- umlaut content on record detail page, when so configured -->
		<xsl:if test="//config/umlaut_base and (( request/base='metasearch' and request/action = 'record' ) or (request/base='folder' and request/action = 'full'))">
          <!-- only if this database does NOT have openurl link generation
               suppressed. If it does, we can do nothing. This duplicates
               code in records.xsl, sorry. -->
      <xsl:variable name="db_metalib_id" select="//records/record[1]/xerxes_record/metalib_id" />
      <xsl:variable name="link_resolver_allowed" select="not(//database_links/database[@metalib_id = $db_metalib_id]/sfx_suppress) or //database_links/database[@metalib_id = $db_metalib_id]/sfx_suppress != '1'" />
			
			<xsl:if test="$link_resolver_allowed">
        <!-- have umlaut set up up functions for js magic -->
        <script type="text/javascript" src="{//config/umlaut_base}/javascripts/embed/umlaut-embed-func.js"></script>
        
        <!-- Now call our script that will call umlaut magic with local
             xerxes display logic. First need to set a couple of dynamically
             generated parameters in js global vars, so xerxes js can get it.-->      			
        <script language="javascript" type="text/javascript">
          openurl_kev_co = '<xsl:value-of select="//records/record[1]/openurl_kev_co"/>'; 
          umlaut_base = '<xsl:value-of select="//config/umlaut_base"/>';          
          
          if (typeof(jsDisplayConstants) == "undefined" ) {
             jsDisplayConstants = new Array(); 
          }
          jsDisplayConstants['link_resolver_name'] = '<xsl:copy-of select="$text_link_resolver_name"/>';
          jsDisplayConstants['link_resolver_load_message'] = '<xsl:copy-of select="$text_link_resolver_load_msg"/>';
          jsDisplayConstants['link_resolver_direct_link_prefix'] = '<xsl:copy-of select="$text_link_resolver_direct_link_prefix"/>';
        </script>
        <script language="javascript" type="text/javascript" src="{$base_include}/javascript/umlaut_record_detail.js"/>
      </xsl:if>
		</xsl:if>
		
	</xsl:if>
  	
	<xsl:call-template name="module_header" />
	
	<xsl:copy-of select="$text_extra_html_head_content" />

</xsl:template>


<!--
	#############################
	#                           #
	#   DEPRECATED TEMPLATES    #
	#                           #
	#############################
-->

<xsl:template name="metasearch_options" />
<xsl:template name="title_old" />
<xsl:template name="folder_export_options" />
<xsl:template name="categories_sidebar" />
<xsl:template name="categories_sidebar_alt" />

</xsl:stylesheet>

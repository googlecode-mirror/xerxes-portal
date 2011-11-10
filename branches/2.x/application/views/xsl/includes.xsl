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
 version: $Id: includes.xsl 1623 2011-01-21 23:23:59Z dwalker@calstate.edu $
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	
	<!-- 
		TEXT LABLES
		These are variables that define the labels of the system
	-->
	
	<xsl:include href="labels/eng.xsl" />

	<!-- 
		GLOBAL VARIABLES
		Configuration values used throughout the application
	-->
	
	<xsl:variable name="base_url" select="//config/base_url" />
	
	<xsl:variable name="xerxes_version" select="//config/xerxes_version" />

	<xsl:variable name="link_target" select="//config/link_target" />

	<xsl:variable name="is_mobile">
		<xsl:choose>
			<xsl:when test="//request/session/is_mobile = '1'">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<xsl:variable name="temporarySession">
		<xsl:if test="//request/session/role = 'guest' or //request/session/role = 'local'">
			<xsl:text>true</xsl:text>
		</xsl:if>	
	</xsl:variable>
	
	<!-- extra content to include in the HTML 'head' section -->
	
	<xsl:variable name="text_extra_html_head_content" />

	<!-- 
		LANGUAGE VARIABLES
		Things that only helix84 understands ;-)
	-->
	
	<xsl:variable name="language_param">
		<xsl:if test="//request/lang">
			<xsl:text>lang=</xsl:text><xsl:value-of select="//request/lang" />
		</xsl:if>
	</xsl:variable>
	
	<xsl:variable name="default_language">
		<xsl:value-of select="//config/languages/language[position()=1]/@code" />
	</xsl:variable>
	
	<xsl:variable name="language">
		<xsl:choose>
			<xsl:when test="//request/lang and //request/lang != ''"> 
				<!-- @todo: allow only languages defined in //config/languages/language[@code] -->
				<xsl:value-of select="//request/lang" />
			</xsl:when>
			<xsl:when test="$default_language"> <!-- if it's defined, use it -->
				<xsl:value-of select="$default_language" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>eng</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<!--
		For languages other than English, will add "_code" suffix, where code is language code.
		This can be used to define language-dependent CSS classes, e.g. for buttons.
		If you wish to turn this off, just define <xsl:variable name="language_suffix" /> in your local includes.xsl
		
		XSLT example: <a href='example.html' class='myclass{$language_suffix}'></a>
		CSS example:
			.myclass {
				background-image:url('english-label.png')
			}
			.myclass_ger {
				background-image:url('german-label.png')
			}
	-->
	
	<xsl:variable name="language_suffix">
		<xsl:choose>
			<xsl:when test="$language != 'eng'">
				<xsl:text>_</xsl:text><xsl:value-of select="$language" />
			</xsl:when>
			<xsl:otherwise>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<xsl:variable name="locale">
		<xsl:value-of select="//config/languages/language[@code=$language]/@locale" />
	</xsl:variable>
	
	<xsl:variable name="rfc1766">
		<xsl:choose>
			<xsl:when test="$locale = '' or $locale = 'C'">
				<xsl:text>en</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="substring-before($locale, '_')" />
				<!--
				Code to generate RFC 1766 subcode (e.g. en-US, pt-BR, ...), if it ever becomes necessary in Xerxes
				
				<xsl:variable name="rfc1766temp"><xsl:value-of select="substring-before($locale, '.')" /></xsl:variable>
				<xsl:variable name="rfc1766sub"><xsl:value-of select="substring-after($rfc1766temp, '_')" /></xsl:variable>
				<xsl:if test="$rfc1766sub">
					<xsl:text>-</xsl:text>
					<xsl:value-of select="$rfc1766sub" />
				</xsl:if>
				-->
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	

	<!-- 	
		TEMPLATE: SURROUND
		This is the master template that defines the overall design for the application; place
		here the header, footer and other design elements which all pages should contain.
	-->
	
	<xsl:template name="surround">
		<xsl:param name="surround_template"><xsl:value-of select="//config/template" /></xsl:param>
		<xsl:param name="sidebar" />
	
		<html lang="{$rfc1766}">
	
		<xsl:call-template name="surround-head" />

		<body>
		
		<div class="ada">
			<xsl:if test="not(request/session/ada)">
				<a href="{navbar/accessible_link}">
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
					<xsl:attribute name="id"><xsl:value-of select="//config/document" /></xsl:attribute>
					<xsl:attribute name="class"><xsl:value-of select="$surround_template" /></xsl:attribute>
				</xsl:otherwise>
			</xsl:choose>
	
			<!-- The main content is split into subtemplates to make customiztion of parts easier -->
			
			<xsl:call-template name="surround-hd" />
	
			<xsl:call-template name="surround-bd">
				<xsl:with-param name="sidebar"><xsl:value-of select="$sidebar" /></xsl:with-param>
			</xsl:call-template>
	
			<xsl:call-template name="surround-ft" />
	
		</div>
		
		</body>
		</html>
		
	</xsl:template>
	
	<xsl:template name="surround-head">
		<head>
		<title><xsl:value-of select="//config/application_name" />: <xsl:call-template name="title" /></title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<base href="{$base_url}/" />
		
		<!-- css -->
		<xsl:call-template name="css_include" />
		
		<!-- javascript: only when not ada or mobile -->
		<xsl:if test="not(request/session/ada) and $is_mobile = 0">
			<xsl:call-template name="javascript_include" />
		</xsl:if>
		
		<!-- header content added by module -->
		<xsl:call-template name="module-header" />	
		
		<!-- and by local implementation -->
		<xsl:copy-of select="$text_extra_html_head_content" />
		
		<!-- good junk -->
		<xsl:call-template name="surround-google-analytics" />
		</head>
	</xsl:template>
	
	<!-- 
		TEMPLATE: surround-hd
		page header
	-->
	<xsl:template name="surround-hd">
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
	</xsl:template>
	
	<!-- 
		TEMPLATE: surround-bd
		page body - main content
	-->
	<xsl:template name="surround-bd">
		<xsl:param name="sidebar" />
	
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
	</xsl:template>
	
	<!-- 
		TEMPLATE: surround-ft
		page footer
	-->
	<xsl:template name="surround-ft">
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
	</xsl:template>
	
	<!-- 
		TEMPLATE: surround-google-analytics
		Google analytics script
	-->
	<xsl:template name="surround-google-analytics">
		<xsl:if test="//config/google_analytics">
			<script type="text/javascript">
				var _gaq = _gaq || [];
				_gaq.push(['_setAccount', '<xsl:value-of select="//config/google_analytics"/>']);
				_gaq.push(['_trackPageview']);
	
				(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
				})();
			</script>
		</xsl:if>
	</xsl:template>
	
	<!-- 
		TEMPLATE: CSS INCLUDE 
	-->
	
	<xsl:template name="css_include">
	
		<link href="css/reset-fonts-grids.css?xerxes_version={$xerxes_version}" rel="stylesheet" type="text/css" />
		<link href="css/xerxes-blue.css?xerxes_version={$xerxes_version}" rel="stylesheet" type="text/css" />
			
		<xsl:choose>
			<xsl:when test="$is_mobile = '1'">
			
				<!-- mobile devices get their own stylesheet (to neutralize the main one, plus it's own
				 definitions), and a local override stylesheet -->
				 
				<link href="css/xerxes-mobile.css?xerxes_version={$xerxes_version}" rel="stylesheet" type="text/css" />
				<link href="css/local-mobile.css?xerxes_version={$xerxes_version}" rel="stylesheet" type="text/css" />
				
				<!-- this is necessary for the iPhone to not start out with a zoomed view, other 
				browsers should ignore -->
				
				<meta name="viewport" content="width=360" />
			</xsl:when>
			<xsl:otherwise>
				
				<!-- local override for the main stylesheet -->
				<link href="css/local.css?xerxes_version={$xerxes_version}" rel="stylesheet" type="text/css" />	
				
			</xsl:otherwise>
		</xsl:choose>
		
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
		Defined here in case they are not, so as not to stop the proceedings
	-->
	
	<xsl:template name="header_div" />
	<xsl:template name="footer_div" />
	<xsl:template name="page_name" />
	<xsl:template name="breadcrumb" />
	<xsl:template name="sidebar" />
	<xsl:template name="sidebar_additional" />
	<xsl:template name="module-header" />
	
	<!--
		TEMPLATE: SIDEBAR WRAPPER
		This defines the overarching sidebar element.  Pages normally will use sidebar template, which 
		defines the content, but if a page can call this template to change the _structure_ of the 
		sidebar as well
	-->
	
	<xsl:template name="sidebar_wrapper">
		<div class="yui-b">
			<div class="sidebar">
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
		separate
	-->
	
	<xsl:template name="breadcrumb_start">
		
	</xsl:template>
	
	<!-- 
		TEMPLATE: TITLE
		the title that appears in the browser window.  This is assumed to be the 
		page name, unless the page overrides it
	-->
	
	<xsl:template name="title">
		<xsl:call-template name="page_name" />
	</xsl:template>
	
	<!-- 
		TEMPLATE: MOBILE HEADER
		A special (slimmed-down) header to use when displaying for a mobile device
	-->
	
	<xsl:template name="mobile_header" >
	
		<div style="background-color: #336699; padding: 10px; padding-bottom: 2px;">
			<a href="{$base_url}" style="color: #fff; font-weight: bold; text-decoration:none">
				<xsl:value-of select="$text_app_name" />
			</a>
		</div>
	
	</xsl:template>
	
	<!-- 
		TEMPLATE: MOBILE FOOTER
		A special (slimmed-down) footer to use when displaying for a mobile device
	-->
	
	<xsl:template name="mobile_footer" />
	
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
							<xsl:attribute name="href"><xsl:value-of select="//navbar/logout_link" /></xsl:attribute>
								<xsl:copy-of select="$text_header_logout" />
							</a>
						</xsl:when>
						<xsl:otherwise>
							<a id="login">
							<xsl:attribute name="href"><xsl:value-of select="//navbar/login_link" /></xsl:attribute>
								<xsl:copy-of select="$text_header_login" />
							</a>
						</xsl:otherwise>
					</xsl:choose>
				</li>
			
				<li id="my_saved_records" class="sidebarFolder">
					<xsl:call-template name="img_save_record">
						<xsl:with-param name="id">folder</xsl:with-param>
						<xsl:with-param name="test" select="//navbar/element[@id='saved_records']/@numSessionSavedRecords &gt; 0" />
					</xsl:call-template>
					<xsl:text> </xsl:text>
					<a>
					<xsl:attribute name="href"><xsl:value-of select="//navbar/my_account_link" /></xsl:attribute>
						<xsl:copy-of select="$text_header_savedrecords" />
					</a>
				</li>
				
			</ul>
		</div>
	</xsl:template>

	<!--
		TEMPLATE: JAVASCRIPT
	-->
	
	<xsl:template name="javascript_include">
			
		<xsl:call-template name="jslabels" />
	
		<script src="javascript/jquery/jquery-1.6.2.min.js" language="javascript" type="text/javascript"></script>
		
		<script src="javascript/results.js" language="javascript" type="text/javascript"></script>

	</xsl:template>
		
	<!-- 	
		TEMPLATE: JSLABELS
		maps text from the i18n label files into a Javascript array, so we can use them in external js files
	-->
	
	<xsl:template name="jslabels">
	
		<script language="javascript" type="text/javascript" src="{navbar/labels_link}"></script> 
	
	</xsl:template>
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
<!--
	#############################
	#                           #
	#      IMAGE TEMPLATES      #
	#                           #
	#############################
-->

<xsl:variable name="app_mini_icon_url">images/famfamfam/page_find.png</xsl:variable>
<xsl:variable name="image_sfx">images/sfx.gif</xsl:variable>
<xsl:variable name="img_src_original_record">images/famfamfam/link.png</xsl:variable>
<xsl:variable name="img_src_holdings">images/book.gif</xsl:variable>
<xsl:variable name="img_src_chain">images/famfamfam/link.png</xsl:variable>

<xsl:template name="img_databases_az_hint_info">
	<img alt="{$text_databases_az_hint_info}" title="{$text_databases_az_hint_info}" src="images/info.gif" class="iconInfo miniIcon">
	</img>
</xsl:template>

<xsl:template name="img_databases_az_hint_searchable">
	<img alt="{$text_databases_az_hint_searchable}" title="{$text_databases_az_hint_searchable}" 
		class="iconSearchable miniIcon" src="images/famfamfam/magnifier.png"/>
</xsl:template>

<xsl:template name="img_refereed">
	<img src="images/refereed_hat.gif" width="20" height="14" alt="" />
</xsl:template>

<xsl:template name="img_save_record">
	<xsl:param name="id" />
	<xsl:param name="class" />
	<xsl:param name="alt" />
	<xsl:param name="test" />
	<img id="{$id}" name="{$id}" width="17" height="15" alt="{$alt}" border="0" class="{$class}">
		<xsl:attribute name="src">
			<xsl:choose> 
				<xsl:when test="$test">images/folder_on.gif</xsl:when>
				<xsl:otherwise>images/folder.gif</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
	</img>
</xsl:template>

<xsl:template name="img_delete">
	<xsl:param name="alt" />
	<xsl:param name="title" />
	<xsl:param name="class" />
	<img src="images/delete.gif" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_facet_remove">
	<xsl:param name="alt" />
	<xsl:param name="title" />
	<xsl:param name="class" />
	<img src="images/facet-remove.png" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_holdings">
	<xsl:param name="alt" />
	<xsl:param name="title" />
	<xsl:param name="class" />
	<img src="images/book.gif" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_results_hint_remove_limit">
	<xsl:param name="alt" select="$text_results_hint_remove_limit" />
	<xsl:param name="title" />
	<xsl:param name="class" />
	<img src="images/delete.gif" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_az_info">
	<xsl:param name="alt" select="$text_results_hint_remove_limit" />
	<xsl:param name="title" />
	<xsl:param name="class" />
	<img src="images/info.gif" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_info_about">
	<xsl:param name="alt" />
	<xsl:param name="title" />
	<xsl:param name="class" />
	<img src="images/info.gif" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_embed_info">
	<xsl:param name="alt">info</xsl:param>
	<xsl:param name="title" />
	<xsl:param name="class" />
	<img src="images/info.gif" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_book_not_available">
	<xsl:param name="alt" />
	<xsl:param name="title" />
	<xsl:param name="class" />
	<img src="images/book-out.gif" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_format_pdf">
	<xsl:param name="alt" />
	<xsl:param name="title" />
	<xsl:param name="class" />
	<img src="images/pdf.gif" width="16" height="16" border="0" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_format_html">
	<xsl:param name="alt" />
	<xsl:param name="title" />
	<xsl:param name="class" />
	<img src="images/html.gif" width="16" height="16" border="0" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_format_unknown">
	<xsl:param name="alt" />
	<xsl:param name="title" />
	<xsl:param name="class" />
	<img src="images/html.gif" width="16" height="16" border="0" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_databases_subject_hint_restricted">
	<xsl:param name="alt" select="$text_databases_subject_hint_restricted" />
	<xsl:param name="title" select="$text_databases_subject_hint_restricted" />
	<xsl:param name="class" />
	<img src="images/lock.gif" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_grey_checkbox">
	<xsl:param name="alt" select="$text_databases_subject_hint_restricted" />
	<xsl:param name="title" select="$text_databases_subject_hint_native_only" />
	<xsl:param name="class" />
	<img src="images/link-out.gif" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_back">
	<xsl:param name="alt" />
	<xsl:param name="title" />
	<xsl:param name="class" />
	<img src="images/back.gif" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_ill">
	<xsl:param name="alt" />
	<xsl:param name="title" />
	<xsl:param name="class" />
	<img src="images/ill.gif" border="0" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_phone">
	<xsl:param name="alt" />
	<xsl:param name="title" />
	<xsl:param name="class" />
	<img src="images/phone.png" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_search">
	<xsl:param name="alt" />
	<xsl:param name="title" />
	<xsl:param name="class" />
	<img src="images/famfamfam/magnifier.png" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

<xsl:template name="img_add">
	<xsl:param name="alt" />
	<xsl:param name="title" />
	<xsl:param name="class" />
	<img src="images/famfamfam/add.png" alt="{$alt}" title="{$title}" class="{$class}" />
</xsl:template>

</xsl:stylesheet>

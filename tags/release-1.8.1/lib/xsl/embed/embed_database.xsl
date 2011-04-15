<?xml version="1.0" encoding="UTF-8"?>

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
	
	<xsl:include href="../includes.xsl" />  
	
	<xsl:output method="html" />
	
	<xsl:template match="/*">
		
		<!-- default true -->
		
		<xsl:variable name="disp_show_info_link" select="not(request/disp_show_info_link = 'false')"/>
		<xsl:variable name="disp_show_desc" select="not( request/disp_show_desc = 'false' )" />
		
		<!-- default false -->
		<xsl:variable name="disp_embed_css" select="request/disp_embed_css = 'true'"/>
		
		<!-- default 'ALL' -->
		<xsl:variable name="disp_show_desc_lang">
			<xsl:choose>
				<xsl:when test="not(request/disp_show_desc_lang)">ALL</xsl:when>
				<xsl:otherwise><xsl:value-of select="request/disp_show_desc_lang" /></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		
		<!-- if it's a partial page and we want to include CSS anyway, do it.-->
		<xsl:if test="$disp_embed_css">
			<xsl:call-template name="disp_embed_css" />
		</xsl:if>
		
		<xsl:for-each select="/*/databases/database[1]">
		
			<div class="xerxes_outer_wrapper">
			<div class="alphaTitle">
				
				<span class="heading">
				<a>
					<xsl:attribute name="href"><xsl:value-of select="xerxes_native_link_url" /></xsl:attribute>
					<xsl:value-of select="title_display" />
				</a>
				</span>
				 
				<xsl:if test="$disp_show_info_link">
					<xsl:text> </xsl:text>
					<span class="xerxes_db_info">
					<a>
						<xsl:attribute name="href"><xsl:value-of select="url" /></xsl:attribute>
						<xsl:call-template name="img_embed_info">
							<xsl:with-param name="class">embedInfo</xsl:with-param>
						</xsl:call-template>
					</a>
					</span>
				</xsl:if>
			</div>
			
			<xsl:if test="$disp_show_desc">
				<div class="alphaDescription">
					<xsl:call-template name="show_db_description">
						<xsl:with-param name="description_language" select="$disp_show_desc_lang" />
					</xsl:call-template>
				</div>
			</xsl:if>
			
			</div>
		</xsl:for-each>
	</xsl:template>
	
	
	<xsl:template name="disp_embed_css">
		<!-- First a way that's technically HTML illegal (style tag in body)
			but works: -->
		<style type="text/css">
			@import url(<xsl:value-of select="$base_include"/>/css/xerxes.css);
		</style>
		
		<!-- now a way that's legal, but requires javascript, and won't
		take effect until page is completely loaded. Oh well, that's
		why we did it the illegal way too. Need prototype. 
		
		Turns out this seems to cause problems with page-load time. Not
		worth the standards compliance. Left here as an example for
		those who think it is. 
		
		<script src="{$base_include}/javascript/prototype-1.6.0.2.js" type="text/javascript"></script>
		
		<script type="text/javascript">
		//Event.observe is Prototype
		Event.observe(window, 'load', function() {
			var objCSS = document.createElement('link')
			objCSS.rel = 'stylesheet';
			objCSS.href = 'http://testbox.mse.jhu.edu/xerxes/css/xerxes.css';
			objCSS.type = 'text/css';
			var objHead = document.getElementsByTagName('head');
			objHead[0].appendChild(objCSS);
		});
		</script> -->
	</xsl:template>
	
</xsl:stylesheet>

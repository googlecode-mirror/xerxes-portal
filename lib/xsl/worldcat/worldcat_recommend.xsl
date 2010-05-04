<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2007 California State University
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
<xsl:import href="worldcat.xsl" />

<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround">
		<xsl:with-param name="surround_template">none</xsl:with-param>
		<xsl:with-param name="sidebar">none</xsl:with-param>
	</xsl:call-template>
</xsl:template>

<xsl:template name="breadcrumb">
	<xsl:call-template name="breadcrumb_worldcat" />
	Interlibrary Loan
</xsl:template>

<xsl:template name="page_name">
	<xsl:value-of select="//records/record/xerxes_record/title_normalized" />
</xsl:template>

<xsl:template name="main">

	<!-- similar books -->
	
	<h1><xsl:value-of select="results/records/record/xerxes_record/title_normalized" /></h1>
		
	<div style="padding: 10px; display: table; margin: 40px; margin-top: 20px; font-size: 130%; border: 1px solid #ccc;">
		<a target="_blank" href="{../url_open}">
			Place interlibrary loan request
		</a>
	</div>

	
	<xsl:if test="//recommendations">
		
		<h2>. . . OR consider these other titles, available in the library:</h2>
		
		<ul id="results" style="margin-top: 3em">
		
			<xsl:for-each select="//recommendations/records/record/xerxes_record">
				<xsl:call-template name="worldcat_result" />
			</xsl:for-each>
		
		</ul>
			
	</xsl:if>


</xsl:template>

</xsl:stylesheet>

<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2009 California State University
 version 1.5
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
-->
 
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">

<!-- Header -->

<xsl:template name="header_div" >

	<div style="background-color: #336699; padding: 20px; padding-bottom: 2px;">
		<a href="{$base_url}" style="color: #fff; font-weight: bold; font-size: 150%; font-family: Arial, Helvetica, sans-serif; text-decoration:none">
			<xsl:value-of select="//config/application_name" />
		</a>
	</div>

</xsl:template>



<!-- Footer -->

<xsl:template name="footer_div" >
	
</xsl:template>

<!-- 
	Override categories_sidebar if you'd like to put something in the sidebar on the home page. 

	session_auth_info provides an example of giving the user their login/authentication details.
	The 'additional options' list below also provides a link to the a-z database list.
-->

<!--
	
<xsl:template name="categories_sidebar">
		
		<xsl:call-template name="session_auth_info" />
	
		<h2>Additional Options</h2>
		<ul>
			<li>
				<a>
				<xsl:attribute name="href"><xsl:value-of select="navbar/element[@id='database_list']/url" /></xsl:attribute>
				Database List (A-Z)
				</a>
			</li>
			<li>Ask a Librarian</li>
			<li>Example</li>
			<li>Another Example</li>
		</ul>
	
</xsl:template>

-->

</xsl:stylesheet>

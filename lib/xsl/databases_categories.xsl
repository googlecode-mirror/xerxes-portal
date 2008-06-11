<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2007 California State University
 version: 1.1
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl">
<xsl:include href="includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

<xsl:template match="/knowledge_base">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="search_limit" select="config/search_limit" />
	
	<!-- a single category added to the page is assumed to be the quicksearch -->
	
	<xsl:variable name="quick_search_category" select="category/@name" />
	
	<div style="padding: 20px;">
		
    <div id="main_content">
	
		<xsl:if test="$quick_search_category != ''">
			<form action="./" method="get">
				<input type="hidden" name="base" value="metasearch" />
				<input type="hidden" name="action" value="search" />
				<input type="hidden" name="context" value="{$quick_search_category}" />
				<input type="hidden" name="context_url" value="{$base_url}" />
				<input type="hidden" name="field" value="WRD" />        
				<input type="hidden" name="subject">
					<xsl:attribute name="value"><xsl:value-of select="category/@normalized" /></xsl:attribute>
				</input>

				<div id="categories_quicksearch">
					<h2><xsl:value-of select="$quick_search_category" /></h2>
					<p>Search a handful of our most popular databases.</p>	  	
					<div id="search">
						<div class="searchBox">
							<label for="category">Search</label> <xsl:text>: </xsl:text>
							<input id="query" name="query" type="text" size="30" /><xsl:text> </xsl:text>
							<input name="submit" type="submit" value="GO" />
						</div>
					</div>
				</div>
			</form>
		</xsl:if>
		
    
		<div class="categories">

        
			<h2>Search by Subject</h2>
			<p>Search databases specific to your area of study.</p>
			<xsl:variable name="total" select="count(categories/category)" />
			
			<table class="categoriesTable">
			<tr valign="top">
			<td>
				<ul>
				<xsl:for-each select="categories/category[@position &lt;= ( $total * .33 )]">
					<xsl:variable name="normalized" select="normalized" />
					<li><a href="{url}"><xsl:value-of select="name" /></a></li>
				</xsl:for-each>
				</ul>
			</td>
			<td>
				<ul>
				<xsl:for-each select="categories/category[@position &gt; ( $total * .33 ) and @position &lt; ( $total * .66 )]">
					<xsl:variable name="normalized" select="normalized" />
					<li><a href="{url}"><xsl:value-of select="name" /></a></li>
				</xsl:for-each>
				</ul>
			</td>
			<td>
				<ul>
				<xsl:for-each select="categories/category[@position &gt;= ( $total * .66 )]">
					<xsl:variable name="normalized" select="normalized" />
					<li><a href="{url}"><xsl:value-of select="name" /></a></li>
				</xsl:for-each>
				</ul>
			</td>
			</tr>
			</table>
			
		</div>
    </div>
    
    <div id="sidebar" class="categories_sidebar">
		<xsl:call-template name="categories_sidebar" />
    </div>	
    
	</div>

	
</xsl:template>
</xsl:stylesheet>


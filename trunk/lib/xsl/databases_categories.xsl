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

<!-- Should the full metasearch form be used, instead a smaller
     simpler one? Defaults to a simpler one, override to false()
     in local xsl includes.xsl or database_categories.xsl if you
     want the full one. -->
	 
<xsl:variable name="homepage_use_simple_search" select="true()" />

<xsl:template match="/knowledge_base">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="search_limit" select="config/search_limit" />
	
	<!-- a single category added to the page is assumed to be the quicksearch -->
	
	<xsl:variable name="quick_search_category" select="category/@name" />
	
	<div class="homePage">
		
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
						<p><xsl:copy-of select="$text_databases_category_quick_desc" /></p>
						<div id="search">
							<xsl:choose>
								<xsl:when test="$homepage_use_simple_search">
									<div class="searchBox">
										<label for="query">Search</label> <xsl:text>: </xsl:text>
										<input id="query" name="query" type="text" size="30" /><xsl:text> </xsl:text>
										<input name="submit" type="submit" value="GO" />
									</div>
								</xsl:when>
								<xsl:otherwise>
									<xsl:call-template name="search_box" />
								</xsl:otherwise>
							</xsl:choose>
						</div>
					</div>
				</form>
			</xsl:if>
			
			<!-- <div id="sidebar_alt" class="categories_sidebar">				
        
			</div> -->
      
			
			<div class="categories">            
				<h2><xsl:copy-of select="$text_databases_category_subject" /></h2>
				<p><xsl:copy-of select="$text_databases_category_subject_desc" /></p>


        
				<table class="categoriesTable">
				<tr valign="top">
          <xsl:call-template name="loop_columns" />
				</tr>
				</table>
				
			</div>
		</div>
		
		<div id="sidebar" class="categories_sidebar">
      <xsl:call-template name="account_sidebar" />
			<xsl:call-template name="categories_sidebar" />
      <!-- deprecated, only one kind of sidebar on databases_categories now.-->
      <xsl:call-template name="categories_sidebar_alt" />
		</div>
	
	</div>
	
</xsl:template>

<!-- A recursively called looping template for dynamically determined
     number of columns. -->
<xsl:template name="loop_columns">
  <xsl:param name="num_columns" select="$categories_num_columns"/>
  <xsl:param name="iteration_value" select="1"/>
   <!--
   This template produces the following logic
   for ($i = $initial-value; $i<=$maxount; ($i = $i + 1))
   {
      // print column
   }
   -->
   
  <xsl:variable name="total" select="count(categories/category)" />
  <xsl:variable name="numRows" select="ceiling($total div $num_columns)"/>
 
   
  <xsl:if test="$iteration_value &lt;= $num_columns">
      <!-- stuff to print -->
				<td>
					<ul>
          <xsl:for-each select="categories/category[@position &gt; ($numRows * ($iteration_value -1)) and @position &lt;= ( $numRows * $iteration_value )]">					
						<xsl:variable name="normalized" select="normalized" />
						<li><a href="{url}"><xsl:value-of select="name" /></a></li>
					</xsl:for-each>
					</ul>
				</td>      
      
 			<xsl:call-template name="loop_columns">
 				<xsl:with-param name="num_columns" select="$num_columns"/>
 				<xsl:with-param name="iteration_value"  select="$iteration_value+1"/>
 			</xsl:call-template>
  </xsl:if>
 	</xsl:template>


</xsl:stylesheet>


<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">

  <xsl:import href="../includes.xsl" />  
  
  <xsl:output method="html" />
  
  <xsl:template match="/*">
    <xsl:variable name="disp_show_title" select="not(request/disp_show_title = 'false')" />
    <xsl:variable name="disp_show_search" select="not(request/disp_show_search = 'false')" />
    <xsl:variable name="disp_show_subcategories" select="not(request/disp_show_subcategories = 'false')" />
    <xsl:variable name="disp_embed_css" select="request/disp_embed_css"/>
    <xsl:variable name="disp_only_subcategory" select="request/disp_only_subcategory" />

      <!-- if it's a partial page and we want to include CSS anyway, do it.-->
      <xsl:if test="$disp_embed_css">
        <xsl:call-template name="disp_embed_css" />
      </xsl:if>
    
    <!-- we need this js for the database maximum limit. -->
    <script language="javascript" type="text/javascript">
      var iSearchable = "<xsl:value-of select="//config/search_limit" />";
    </script>
    <script src="{$base_url}/javascript/save.js" language="javascript" type="text/javascript" />
      
    <div id="xerxes_outer_wrapper">
      <form name="form1" method="get" action="{$base_url}/" class="metasearchForm">
        <input type="hidden" name="base" value="metasearch" />
        <input type="hidden" name="action" value="search" />
        <input type="hidden" name="context">
           <xsl:attribute name="value"><xsl:value-of select="//category/@name"/></xsl:attribute>
        </input>
        <input type="hidden" name="context_url">
          <xsl:attribute name="value"><xsl:value-of select="//category/url" /></xsl:attribute>
        </input>
        
        <div id="searchArea">
        
        <xsl:if test="$disp_show_title">
          <div class="heading">
              <h3 class="xerxes_heading"><xsl:value-of select="//category/@name" /></h3>
          </div>
        </xsl:if>
        
        <xsl:if test="$disp_show_search">    
          <div id="search">  
            <xsl:call-template name="search_box">
              <!-- if they switch search modes in embedded mode, and don't have
                   ajax available, send them to the full subject page -->
               <xsl:with-param name="full_page_url"><xsl:value-of select="/*/category/url"/></xsl:with-param>
            </xsl:call-template>
          </div>
        </xsl:if>
        
        <xsl:choose>
          <xsl:when test="$disp_show_subcategories">
            <div class="subjectDatabases">
              <xsl:call-template name="subject_databases_list">
                <xsl:with-param name="should_show_checkboxes" select="$disp_show_search" />
                <xsl:with-param name="show_only_subcategory" select="$disp_only_subcategory" />
                
              </xsl:call-template>
      
            </div>
          </xsl:when>
          <xsl:otherwise>
                    
            <!-- tell the controller the subject/category to use to find
                 dbs to search. -->
            <input type="hidden" name="subject">
              <xsl:attribute name="value"><xsl:value-of select="request/subject" /></xsl:attribute>
            </input>
          </xsl:otherwise>
        </xsl:choose>
        </div>
      </form>
    </div>
  </xsl:template>
  
  
  <xsl:template name="disp_embed_css">
    <!-- First a way that's technically HTML illegal (style tag in body) but works: --></xsl:template>

  
</xsl:stylesheet>

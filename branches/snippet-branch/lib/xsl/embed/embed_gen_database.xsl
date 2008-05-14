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
	xmlns:php="http://php.net/xsl"
  xsl:extension-element-prefixes="php">    
  
<xsl:include href="../includes.xsl" />

<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="main">

<!-- js to update example immediately -->
<xsl:call-template name="embed_js_update" />

<div id="headingBox">
    <h2><xsl:call-template name="page_name" /></h2>
    <p>Include a snippet for this database on your own web page.</p>
 </div>

 <div id="snippet_generator">
    
    <div id="snippet_option_column">
  
    <form method="GET" id="generator">
      <input type="hidden" name="base" id="base" value="embed" />
      <input type="hidden" name="action" id="action" value="gen_database" />
      <input type="hidden" name="id">
         <xsl:attribute name="value"><xsl:value-of select="request/id" /></xsl:attribute>
      </input>
  
      <fieldset id="options">
        <legend>Options</legend>
        <p>How would you like the included snippet to look?</p>
        <table>
    
          <tr class="optionRow">
          <td><h4><label for="disp_show_search">Show database description?</label></h4></td> 
          <td><select name="disp_show_desc" id="disp_show_search">
            <option value="true">
            <xsl:if test="request/disp_show_desc = 'true'">
              <xsl:attribute name="selected">selected</xsl:attribute>
            </xsl:if>
            yes
            </option>
            <option value="false">
            <xsl:if test="request/disp_show_desc = 'false'">
              <xsl:attribute name="selected">selected</xsl:attribute>
            </xsl:if>      
            no
            </option>
          </select>    
          </td>
          </tr>
    
          <tr class="optionRow">
          <td><h4><label for="disp_show_info_link">Show info button?</label></h4></td> 
          <td><select name="disp_show_info_link" id="disp_show_info_link">
            <option value="true">
            <xsl:if test="request/disp_show_info_link = 'true'">
              <xsl:attribute name="selected">selected</xsl:attribute>
            </xsl:if>
            yes
            </option>
            <option value="false">
            <xsl:if test="request/disp_show_info_link = 'false'">
              <xsl:attribute name="selected">selected</xsl:attribute>
            </xsl:if>      
            no
            </option>
          </select>
          </td>
          </tr>
            
        </table>
        <p><input type="submit" value="refresh" /></p>      
      </fieldset>

      <fieldset id="embedding_instructions">
        <legend>Include Instructions</legend>
        <p>You have several options for including. </p>
        <h3>1. Server side include url</h3>
        <p class="optionInfo">Preferred method of inclusion if your web page environment or content management system supports any kind of server-side include.</p>  
    
        <textarea id="direct_url_content" class="displayTextbox" readonly="yes"><xsl:value-of select="embed_info/embed_direct_url" /></textarea> 
    
        <h3>2. Javascript widget</h3>
        <p class="optionInfo">Should work in any web environment that allows javascript, but viewers' browsers must support javascript.</p>
        <textarea id="js_widget_content" class="displayTextbox" readonly="yes">
          <script type="text/javascript" charset="utf-8" >
            <xsl:attribute name="src"><xsl:value-of select="embed_info/embed_js_call_url"/></xsl:attribute>
          </script>        
        
        </textarea>
    
        <h3>3. HTML Source </h3>
        <p class="optionInfo">Last resort. If this is your only option, you can embed this HTML source directly into your own web page. However, as JHSearch configuration or features change, your included snippet may not see any changes, and may even stop working. Use with care.</p>
        <a target="_blank"  id="view_source_link" class="optionInfo">
        <xsl:attribute name="href" >
        <xsl:value-of select="embed_info/embed_direct_url" />
        <xsl:text>&amp;format=source</xsl:text>
        </xsl:attribute>
        View snippet source
        </a>
        
      </fieldset>    
    </form>

  </div>
  
    <fieldset id="example">
    <legend>Example</legend>
    <div id="example_container">
    <div id="example_content">
    <xsl:value-of disable-output-escaping="yes" select="php:functionString('getEmbedContent', embed_info/embed_direct_url)"  />
    </div>
    </div>
    </fieldset>
  
</div>

</xsl:template>
</xsl:stylesheet>

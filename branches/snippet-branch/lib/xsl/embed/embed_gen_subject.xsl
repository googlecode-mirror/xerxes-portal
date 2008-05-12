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

  <!-- Use prototype to have fancy fancy auto-update of example when
       form changes. -->
  <script type="text/javascript">
    function updateExample() {
      var query_string = Form.serialize($("generator"));
      var complete_url = '<xsl:value-of select="embed_info/raw_embedded_action_url" />&amp;disp_embed=true&amp;' + query_string;
      
      // Update the content
      new Ajax.Updater('example_content',complete_url, 
      { method:'get'});
      
      // Update our instruction urls
      $("direct_url_content").update( complete_url );
      // Sorry about weird double-escaping, in an XSLT->js world, that's
      // how it goes sometimes. 
      $("js_widget_content").update('&amp;lt;script type="text/javascript" charset="utf-8" src="' + complete_url + '&amp;disp_embed_js=true" &amp;gt;&amp;lt;/script&amp;gt;');
      $("view_source_link").href = complete_url + "&amp;format=source";
    }
    Event.observe(window, 'load', function() {
      new Form.EventObserver($("generator"), updateExample);
    });
  </script>
  


    
 <div id="headingBox">
    <h2><xsl:call-template name="page_name" /></h2>
    <p>Include a snippet for this subject on your own web page.</p>
 </div>

 <div id="snippet_generator">
    
    <div id="snippet_option_column">
  
    <form method="GET" id="generator" action="./embed/gen_subject">
      <input type="hidden" name="subject">
         <xsl:attribute name="value"><xsl:value-of select="request/subject" /></xsl:attribute>
      </input>
  
      <fieldset id="options">
        <legend>Options</legend>
        <p>How would you like the included snippet to look?</p>
        <table>
        
          <tr class="optionRow">
          <td><h4><label for="disp_show_title">Show title?</label></h4></td>
          <td><select id="disp_show_title" name="disp_show_title">
            <option value="true">
            <xsl:if test="request/disp_show_title = 'true'">
              <xsl:attribute name="selected">selected</xsl:attribute>
            </xsl:if>
            yes
            </option>
            <option value="false">
            <xsl:if test="request/disp_show_title = 'false'">
              <xsl:attribute name="selected">selected</xsl:attribute>
            </xsl:if>      
            no
            </option>
          </select>
          </td>
          </tr>
    
          <tr class="optionRow">
          <td><h4><label for="disp_show_search">Show search box?</label></h4></td> 
          <td><select name="disp_show_search" id="disp_show_search">
            <option value="true">
            <xsl:if test="request/disp_show_search = 'true'">
              <xsl:attribute name="selected">selected</xsl:attribute>
            </xsl:if>
            yes
            </option>
            <option value="false">
            <xsl:if test="request/disp_show_search = 'false'">
              <xsl:attribute name="selected">selected</xsl:attribute>
            </xsl:if>      
            no
            </option>
          </select>    
          </td>
          </tr>
    
          <tr class="optionRow">
          <td><h4><label for="disp_show_subcategories">Show database listing?</label></h4></td> 
          <td><select name="disp_show_subcategories" id="disp_show_subcategories">
            <option value="true">
            <xsl:if test="request/disp_show_subcategories = 'true'">
              <xsl:attribute name="selected">selected</xsl:attribute>
            </xsl:if>
            yes
            </option>
            <option value="false">
            <xsl:if test="request/disp_show_subcategories = 'false'">
              <xsl:attribute name="selected">selected</xsl:attribute>
            </xsl:if>      
            no
            </option>
          </select>
          </td>
          </tr>
    
          <tr class="optionRow">
          <td><h4><label for="disp_embed_css">Include Stylesheet in embed?</label></h4><p class="optionInfo">If you have the technical capability, it's preferable to define CSS styles yourself in your external context for the classes and elements used in the embedded content. Including Stylesheet in embed works imperfectly.</p></td>
          <td><select id="disp_embed_css" name="disp_embed_css">
            <option value="true">
            <xsl:if test="request/disp_embed_css = 'true'">
              <xsl:attribute name="selected">selected</xsl:attribute>
            </xsl:if>
            yes
            </option>
            <option value="false">
            <xsl:if test="request/disp_embed_css = 'false'">
              <xsl:attribute name="selected">selected</xsl:attribute>
            </xsl:if>      
            no
            </option>
          </select></td>
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

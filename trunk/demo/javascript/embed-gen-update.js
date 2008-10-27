 <!-- 
  //Use prototype to have fancy fancy auto-update of example snippet on snippet 
  // generation pages, whenoptions in form are changed. Assumes ids for various
  // page elements, look at existing examples or code. 
 
  //Including page NEEDS to set global-scope js variable that this code uses:
  
  // 1) Required variable "snip_base_url", a base complete url (with hostname)
  //    used to generate the snippet in question. 
  // 2) OPTIONAL variable "snip_noscript_content", used to supply some noscript
  //    content to generated javascript widget for snippet. 
 
     function updateExample() {
      
        var query_hash = Form.serialize($("generator"), true);
        // Remove base and action, they point to ourselves, the generator.            
        delete query_hash["base"];
        delete query_hash["action"];
        //prototype Hash converts back to a query string. 
        var query_string = new Hash(query_hash).toQueryString ();
        
        //var complete_url = '<xsl:value-of select="embed_info/raw_embedded_action_url" />&amp;disp_embed=true&amp;' + query_string;
        var complete_url = snip_base_url + '&amp;disp_embed=true&amp;' + query_string;
        
;
        
        // Update the content
        new Ajax.Updater('example_content', complete_url, 
        { method:'get'});
        
    
        // Update our instruction urls
        $("direct_url_content").update( complete_url );
        

        
        js_widget_content = 
          '&lt;script type="text/javascript" charset="utf-8" src="' + complete_url + 
          '&amp;format=embed_html_js" &gt;&lt;/script&gt;';
        //if we have a snip_noscript_content var, add that on. 
        if ( typeof(snip_noscript_content) != "undefined") {
            js_widget_content += 
              '\n&lt;noscript&gt;'+ snip_noscript_content + '&lt;/noscript&gt;';
        }
        
        
        // Don't use prototype update, because it will execute the
        // js that we want as source!
        $("js_widget_content").inner_html = js_widget_content;
        

        
        $("view_source_link").href = complete_url + "&amp;format=text";
        
  
      }
      Event.observe(window, 'load', function() {
        new Form.EventObserver($("generator"), updateExample);
      });
 // -->

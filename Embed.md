## Introduction ##

In the sidebar of the Xerxes subject and database detail pages you'll see an option to embed that page in an external website.

Even if you have very little control over the external website you will be embedding the snippet, this can work reasonably well.

But if you DO have control over the external website, there are a few things you can do to make it work even better.

In the example below, replace `http://yourxerxes.university.edu/` with your particular base path to Xerxes, which depending on how you've set up xerxes, might have another path component too, e.g.: `http://xerxes.university.edu/xerxes`


## Styling the look ##

The subject snippet generator lets you decide whether to include CSS styles with your snippet. However, this is off by default because the way it works is a bit wonky, it's a last resort.

Better, if you have control over the host page and are not scared of HTML (or have a web admin you can share this info with who meets those criteria), is to link on the host page to the proper CSS files to make the embedded snippets look just like they do in Xerxes.

In the 'head' section of your HTML, include a 'link' element to the Xerxes stylesheet:

```
 <link href="http://yourxerxes.university.edu/css/xerxes-embeddable.css"  rel="stylesheet" type="text/css">
```

If you'd like to customize the look and feel on your particular host page, the best way is to include that line, then AFTER that line, include some more style definitions over-riding certain aspects there.

Even if you have multiple Xerxes embed snippets on a page, you only need to include this content once on your page.

## Activating toggle via javascript ##

You'll notice in Xerxes, if the browser has javascript enabled, clicking on 'more options' on a search box adds more options without a page reload. But in your embedded content on a host page, clicking 'more options' just sends you to a Xerxes page. Also in Xerxes, you'll get some javascript to make sure you can't select more databases than are allowed.

Would you like these features  on your host page?  Simply reference some Xerxes javascript files in the 'head' section of your page.

```

<script  src="http://yourxerxes.university.edu/javascript/onload.js" language="javascript" type="text/javascript"></script>

<script  src="http://yourxerxes.university.edu/javascript/prototype.js" language="javascript" type="text/javascript"></script>

<script  src="http://yourxerxes.university.edu/javascript/toggle_metasearch_advanced.js" language="javascript" type="text/javascript"></script>

<script  src="http://yourxerxes.university.edu/javascript/save.js" language="javascript" type="text/javascript"></script>

```

If you don't have access to the 'head' portion of the page, this should also work in the 'body'.

Even if you have multiple Xerxes embed snippets on a page, you only need to include this content once on your page.

The prototype link is of course only necessary if your page doesn't already include prototype.  `toggle_metasearch_advanced.js` will give you the more options stuff. save.js will give you the maximum db checking.  And `onload.js` is necessary to set things up that xerxes js expects.
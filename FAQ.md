

## Xerxes seems to produce fewer hits than Metalib. Is there a limit to the search results? ##

No.  But one difference between Xerxes and Metalib lies in the _presentation_ of the total number of hits.

In Xerxes the 'Top Results' (merged set) displays the total number of records that have actually been _fetched_ and merged by Metalib (usually 30 from each database).  Metalib, on the other hand, shows that number and the total number of possible hits, saying something like '80 fetched of 690', for example.

## Can Xerxes fetch more records for the merged set? ##

No.  Unfortunately, we've not been able to reach a satisfactory solution for this in Xerxes.  Currently, we offer no equivalent to Metalib's 'fetch more' function.

We firmly believe that the _way_ Metalib performs this operation, by fetching more records and then _re-sorting_ the entire result set, so users are forced to make their way back through results they've already seen, is entirely confusing.

ELUNA and the Metalib X-Server customers worked with Ex Libris for two years to see if Metalib could _append_ the newly fetched records to the end of the merged results, which would greatly reduce the confusion.  But Metalib's current architecture is such that that this is not possible.

That being said, the _need_ to fetch more records is often mitigated by the fact that users don't often go beyond the first few pages of results.  They usually modify their search somewhat and start again before even reaching the end.  So a workaround to the problem has not been a pressing feature.

## Can users save records in multiple folders? ##

Yes.  However, in Xerxes we use a 'tagging' metaphor rather than a 'folder' metaphor for organizing records.

We like tagging as an organizing idiom a bit better than folders because it allows users to create and assign records to a group in a single action.  A lot of users, interestingly, use the tag field as a kind of pseudo-notes field in Xerxes, too.

## Does Xerxes work with other link resolvers besides SFX? ##

Yes, in fact, it should work better than in Metalib.

The regular link resolver button stuff is the same, of course.  That's the beauty of OpenURL, it works with all link resolvers.

But, whereas Metalib uses SFX-specific technology for looking-up full-text availability, Xerxes uses the Google Scholar export instead, which all major links resolvers support.

So, even though, as a matter of habit, we reference "SFX" in the documentation, there's nothing SFX-specific in Xerxes.

## I get this error: Could not construct link to full-text ##

For this, you just need to make sure all of your Ebsco databases have a link-to-native-record entry in the IRD.  Like this one for Academic Search Premiere:

> `http://search.ebscohost.com/login.aspx?direct=true&an=$001&db=a9h&scope=site`

. . . just swap 'a9h' for the right database code.

Xerxes really should handle this more gracefully -- it's a known issue -- but better all around if you add those link syntaxes.
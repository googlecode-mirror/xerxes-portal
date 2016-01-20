Xerxes should run on IIS without any problems.  Although the application is initially developed on Apache, it does not (in it's basic configuration) require any Apache-specific modules. Here are some notes that you might find useful, however, if you're setting up Xerxes on IIS.

## Installing PHP 5 on IIS 5/6 ##

There are a lot of bad installation tutorials on the web for PHP and IIS.  I find this one on [Installing PHP 5 on IIS in 5 simple steps](http://www.iis-aid.com/articles/how_to_guides/installing_php_5_on_iis_in_5_simple_steps) to be the best.

## Setting index.php as default content page ##

For Xerxes URLs to resolve correctly, you will need to tell IIS to treat files with the name 'index.php' as a 'default content page'.

On Windows 2003 with IIS 6:

  * Open up the Internet Information Services (IIS) Manager console.
  * Right click on the 'Web sites' folder and choose 'Properties' (or choose a specific website within that folder if Xerxes and PHP are only set-up on that one sub-site)
  * In the screen that appears, click the 'Documents' tab
  * Make sure 'enable default content page' is checked
  * Click the 'add' button, and enter 'index.php' (without quotes)
  * Hit okay.


## Security with config.xml ##

IIS will, by default, serve the demo/config/config.xml file over the web, exposing your passwords and other sensitive information.

You can prevent this from happening by restricting remote access to the file in the IIS console manager. On Windows 2003 with IIS 6:

  * Open up the Internet Information Services (IIS) Manager console.
  * Click on 'Web Sites' and navigate down to the Xerxes 'demo' folder (you may have called this folder something different)
  * Click on the 'config' folder, right-click on 'config.xml' and choose 'Properties'.
  * In the screen that appears, click on the 'File Security Tab'
  * Under 'Authentication and access control' (the two hands shaking) click the 'Edit' button
  * Un-check the option for 'enable anonymous access' and hit okay
  * A dialog box will appear warning that all access is now denied to that file, hit 'Yes' because this precisely what we want to do
  * Hit okay

If you go back to demo/config/config.xml in your browser, and you should now get a 'forbidden' error message
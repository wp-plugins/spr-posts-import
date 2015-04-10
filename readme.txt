=== Plugin Name ===
Contributors: sprise
Donate link: http://www.sprisemedia.com/
Tags: posts, migration, import, move site
Requires at least: 4.1
Tested up to: 4.1
Stable tag: .1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Move your blog posts to a new site with one click.

== Description ==

Migrate posts across Wordpress installs. Optionally, limit your import to certain categories of your site.

Your posts will be imported along with:

* Images (added to your Media Library and src tags will be corrected)
* Authors
* Tags
* Categories
* Comments

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

== Installation ==

Upload to the plugins directory and activate. The SPR Posts Import admin link will appear under the Plugins admin nav.

== Frequently Asked Questions ==

= Will **all** the images, authors, tags, categories, and comments from my old site be imported? =

If you give specific categories then only the posts within those categories will be imported, and any assets (images, authors, etc) that do not apply to those posts will be skipped. If you do not enter any categories (no restrictions) then all assets related to your posts will be imported. This may not include all assets from your old site - authors with 0 posts would not be included, for example.

= How many posts can I import using this plugin? =

This plugin was designed to import thousands of posts in one sitting, and be adjustable to your server's capabilities. You will choose a number of posts to import, and if you check the box for Automated Full Import the plugin will keep going until all of your posts have been gone over. You may need to do some testing to find the number of posts that your server can handle. If the plugin dies (ie page stops loading, out of memory, etc) then you will need to choose a smaller number of posts to import. For this reason it is paramount that you take a backup of your new site before beginning the import process.

= Will this plugin correct all links to my old site? =

No, with this post importer your *images* will be loaded into your new site's Media Library and relinked within your post body. You will need to correct any other links by hand.

= Will this plugin copy my custom fields or other plugin data? =

No, only standard Wordpress post data (and other assets described above) will be imported.

= Can I import other custom post types? =

No, at this time only Post items are imported. Pages and all other post types will be ignored.


== Screenshots ==

1. This is what the import form and file analysis look like.
2. Instructions are beneath the import form.

== Changelog ==

= 0.1 =
* First version of SPR Posts Import.

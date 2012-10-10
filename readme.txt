=== Thesis Cache(r) ===
Contributors: mpvanwinkle77
Tags: performance, Thesis, caching
Requires at least: 3.0
Tested up to: 3.4.1
Stable tag: 1.2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Thesis Cache(r) is designed to integrate WordPress object caching with your thesis theme by giving you page-by-page cache control. 

== Description ==

Thesis Cache(r) works with any and all object-caching plugins including W3 Total Cache. If no persistent object cache is in use it will install a basic file-based object cache. Thesis Cache(r) gives each post/page/post-type object a meta box to select whether a page is cached, if so for how long, and whether or not to also cache the sidebars on the page. 

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the `thesis-cacher` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
Note: If ThesisCache(r) needs to install an object cache for you it may prompt you to temporarily make wp-content writable.

== Frequently Asked Questions ==

= When is the cache cleared =

Pages cached with ThesisCache(r) will only be cleared under the following conditions. 
1). The cache file expires
2). The full object cache is manually cleared
3). A specific page/post is updated
4). A comment is left on a specific page/post

== Screenshots ==


== Changelog ==

= 1.2 =
* Initial version

=1.2.1=
* Bugfix for plugin directory

=1.2.2=
* Remove object-cache.php upon deactivation, but only if it is the object-cache.php installed by the plugin.

== Upgrade Notice ==

= 1.2.2 =
Added deactivation logic: Remove object-cache.php upon deactivation, but only if it is the object-cache.php installed by the plugin.

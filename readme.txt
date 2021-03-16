=== Dynamic User Directory Exclude User Filter ===

Contributors: Sarah Giles
Donate link: http://sgcustomwebsolutions.com/wordpress-plugin-development/
Requires: Dynamic User Directory 1.4.8 or later
WP Version Tested up to: 5.4.2
Stable tag: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Extends the Dynamic User Directory plugin by allowing you to configure rules for filtering users out of the directory. 

== Installation ==

OPTION 1
1. Install the ZIP file via the WordPress Plugins page

OPTION 2
1. Extract the dynamic-user-directory-exclude-user-filter folder from the ZIP file. 
2. Copy the whole dynamic-user-directory-exclude-user-filter folder into your plugin directory.
3. Activate the plugin through the 'Plugins' screen in WordPress.
4. Visit the DUD plugin settings page to configure the Exclude User Filter settings.

== Changelog ==

= 1.0 =
- 6/26/19
- First public release.

= 1.1 =
- 7/1/19
- Fixed: When using the Custom Sort Field, Exclude User Filter was not returning the category links for display when the directory is subsorted on display name.

= 1.2 =
- 5/29/20
- New Feature: Added a "Performance Improvement" checkbox to the Exclude User Filter settings. This will speed up page load time for directories with a high volume (1000+) of users.

= 1.3 =
- 6/17/20
- New Feature: Added a new MemberPress checkbox on the DUD settings page for the Exclude User Filter add-on: "Show users if they have at least one subscription that is NOT selected for hiding." This lets you show users with multiple subscriptions if at least one of those subscriptions should be shown.
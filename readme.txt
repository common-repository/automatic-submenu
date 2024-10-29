=== Automatic Submenu for Categories & Pages ===
Contributors: rallisf1
Donate link: https://www.paypal.me/rallisf1
Tags: menu, submenu, children, posts, pages
Requires at least: 3.1
Requires PHP: >= 5.4
Tested up to: 4.8.3
Stable tag: trunk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically append children posts and pages as submenu items in the frontend

== Description ==

Ever wanted to be able to automatically have category and pages children automatically added to your menus? I'm sure I did and was disappointed to not find something that dead simple that works and doesn't mess up with the theme or other plugins.

Now bear with me on this one as it is my first WP plugin for like a decade and the first ever to be shared so i kept it quite basic. 

How it works:

*   On each menu item that is either a category or a page you get an option to automatically append their children as a submenu
*   You get to decide how many children to show and how they're gonna be ordered (Title or Date)
*   And that's it, all you have to do is create content and never worry about your menu again.
*   As promised, it doesn't mess with the theme or other plugins, it just injects the children found in the corresponding place of the nav menu array when that is triggered in the frontend

== Installation ==

Plain and simple

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Appearance->Menu and config each menu item as you like


== Frequently Asked Questions ==

= Is there a limit on the submenus I can have the children automatically populated =

No, you can have it enabled on whatever category or page manu you like, no matter how many or how deep they are in your menu structure

= I saved my menu but I don't see the children added in the menu editor =

Yes that is correct, the children are shown only in the front-end of your website else you would have problems when saving your menu.

= Can I change the ordering of the added children regarding manually added submenu items? =

No, currently you cannot. All automatic children will be appended (added to the end) of the submenu. Leave a request on the forum if you would like this feature.

= Can I use this plugin with custom post types and taxonomies? =

No, currently you cannot. It works with pages and posts assigned to a category. Leave a request on the forum if you would like this feature.

= Can i use this plugin along with another menu altering plugin (e.g. megamenu) ? =

Yes, all this plugin does is inject items to the nav menu array for the front-end. It doesn't affect the rendering of the menu. Nonetheless; I cannot guarantee the behaviour of third-party menu plugins which rely on custom post types other than nav_menu_item.

== Screenshots ==

1. How it looks like in the menu editor

== Changelog ==

= 1.0 =
* Initial release

== Code hacks ==

There is no settings page for the plugin but you can change a couple default behaviours from the code itself

= Changing the default maximum children number when the field is empty =

`$item->automatic_max = 5;` just change the number at line 220

= Bring only direct children of Pages and not all ascendants =

`$children = get_pages( array( 'child_of' => $item->object_id, 'number' => $item->automatic_max, 'sort_column' => 'post_'.$ordering[0], 'sort_order' => strtoupper( $ordering[1] ) ) );` just change 'child_of' to 'parent' at line 226

Upcoming Features:

Due to lack of time i will implement the following features upon demand. Please post your requests in the forum.

* Settings Page
* Submenu items ordering
* Support for custom post types and taxonomies

Credits:

* I have used the menu walker created by zviryatko (https://plugins.trac.wordpress.org/browser/menu-image/trunk/menu-image.php)
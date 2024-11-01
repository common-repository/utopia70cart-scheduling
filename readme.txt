=== J.W.Cart Scheduler ===
Contributors: utopiamech
Donate link: https://apps.jw.org/DONATE
Tags: scheduler,scheduling,magazine cart scheduler,Jehovah's Witnesses,public witnessing cart,literature cart scheduling
Requires at least: 4.9.7
Tested up to: 4.9.8
Stable tag: 4.9.8
Requires PHP: 5.6.24
License: GPL version 2
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Displays cart locations and schedules for Jehovah's Witnesses Literature carts, allowing members to log in and reserve times.

== Description ==

The J.W. Cart Scheduler displays hourly schedules for various days of the week, and for multiple locations where public witnessing carts are displayed. Admin members set up locations, which are displayed via shortcodes on pages or posts. Visitors can log in and pick available times to reserve the cart for a specific location.

Some features:
* For privacy, schedules are not displayed unless visitor is logged in.
* Multiple locations can be set up, each with their own schedule and display (WordPress Admin level)
* Members can reserve time slots and edit their times (WordPress Subscriber level)
* Assistants can also log in and cancel/edit anyone's time slots (WordPress Contributor level)
* Flexible timetable allows time slots to be active weekly (for example, starting later on a Sunday) and at specific dates (such as starting later on January 1st) 

For further information and instructions please see the plugin support page at http://www.utopiamechanicus.com/wp-plugin-j-w-cart-scheduler/ 

Please note: This plugin is developed independently of JW.ORG and the Watch Tower Bible and Tract Society of Pennsylvania, and is provided simply to aid in cart scheduling,

== Installation ==

To install the plugin and get it working follow these steps:

1. Upload the plugin files to the "/wp-content/plugins/plugin-name" directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Go to the J.W.Cart Scheduler menu option to view the carts, and follow the directions to enter a location or two to start.
4. Set up a blog page, one for each location, and give it a generic title, like "Location 1" or "Location 2" (Page/Post titles are visible even when not logged in, so should be generic for privacy) 
5. At a minimum, you will want to display a schedule for the specific location on each page, like this: **[u70cal location='2']** where the '2' is the location number; in this case, the schedule for location 2 will be displayed. 
6. Assign cart staff by creating new Users in WordPress, assigning them Subscriber level or Contributor level. When logged in, members at Subscriber level will be able to reserve slots for any location, and cancel their own reservations. At Contributor level, they will be able to do the same, as well as cancel anyone's timeslots, and edit comments. Admin level members will also have access to the "J.W.Cart Scheduler" menus. 
7. View the "More Info" tab for details on using the other shortcodes, which can be used to display member info, a login form (if not yet logged in), or entire blocks of text that are visible only if logged in, or alternatively only if not logged in. 

== Frequently Asked Questions ==

= Should I use pages or posts? =

For most blogs/themes, pages are will look better and are more suitable for permanent schedules.

= How do I group the pages? =

One option is to create a main page with links to all the location pages, and pass that URL out to visitors. From there, they can visit all pages.

= Why don't I name my locations on my pages? =

It's up to you, but if you name a location on a web page, it will be visible to everyone visiting that page, logged in or not. For privacy reasons you may wish to avoid that.

= How do I show/hide information to visitors? =

Various shortcodes can provide text for visitors not logged in, and different text for those not logged in. For example, this page text will display a welcome for visitors along with a login form. Once logged in, visitors are  welcomed by name, and can view the schedule for location 3.
     [u70block loggedin='1']
     Welcome [u70disp type='username']
     [u70disp type='title' location='3']: [u70disp type='description' location='3']
     [u70cal location='3']
     [/u70block]
     [u70block loggedin='0']
     Welcome - please log in to view the schedules.
     [u70disp type='loginform']
     [/u70block]

= Can members get a summary? =

Another shortcode gives a summary for a few days, indicating which carts and which hours they have reserved. This code will show a seven-day summary for all carts reserved by the logged-in visitor:
     [u70block loggedin='1']
     Welcome [u70disp type='username']
     [u70disp type='reservations' dayoffset='0' daylength='7' ]
     [/u70block]
     [u70block loggedin='0']
     Welcome - please log in to view the schedules.
     [u70disp type='loginform']
     [/u70block]

= How do I add members? =

Use the WordPress Users menu, and "Add New". Alternately, if you have a large number to add, you can use a bulk registration plugin, such as "Import users from CSV with meta" by codection

== Screenshots ==

* No screenshots

== Changelog ==

= 0.91 =
* First release

== Upgrade Notice ==

* First release
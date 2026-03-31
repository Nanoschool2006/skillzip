=== WooCommerce Integration for Edwiser Bridge ===
Contributors: WisdmLabs
Tags: WordPress, Moodle, Courses, Users, Synchronization, Sell Courses, Learning Management System, LMS, LMS Integration, Moodle WordPress, WordPress Moodle, WP Moodle, WooCommerce, WooCommerce Subscriptions, WooCommerce Product Bundles
Requires at least: 4.7
Tested up to: 6.0.2
Stable tag: 2.2.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html


WooCommerce Integration for Edwiser Bridge allows users to sell Moodle courses from a WooCommerce store on WordPress.


== Description ==

WooCommerce Integration is an extension for Edwiser Bridge and allows users to sell their Moodle courses from a WooCommerce store on WordPress. Below is the full list of features of the WooCommerce Integration extension.

= Sell Courses with WooCommerce =
The extensions provides an option to sell Moodle courses synchronized with a WordPress website effortlessly.

= Create / Update Courses as Products =
Courses Imported from Moodle can be created as products using the Synchronization Option for WooCommerce Products. Previously created products will be updated using the same option.

= Sell Courses as Bundled Products =
Now no need to buy a separate extension to fulfil the bundled courses requirement. The WooCommerce Moodle integration provides an option to bundle multiple courses and sell them as a product.

= Sell Courses as Subscription =
Want to sell Moodle courses as Subscription products? The WooCommerce Integration for Edwiser Bridge supports the WooCommerce Subscriptions plugin which makes selling subscription courses possible.

= Link Courses from Product Page =
Courses can be linked with products from the products page by adding a new product and selecting the course you want to link with that product

= Unenrollment on Cancellation =
On cancellation or refund, the user gets unenrolled from the courses in question following which access to the courses is also revoked.

= Publish Synchronized Products =
All courses synchronized as products can be published at once using the Synchronization Option for WooCommerce Products making the process an absolutely effortless one.

= Translation Ready =
Need to display the WooCommerce Moodle Integration in a language of your choice? Include the necessary translation files to the plugin and you are good to go!

= Hooks and Filters =
Various hooks and filters have been included which make the add-on easily customizable and developer friendly.

== Installation ==

= Minimum Requirements = 
* WordPress 4.7.0 or higher
* WooCommerce 2.3.0 or higher

= Automatic Installation =

* Upon purchasing the WooCommerce Integration Extension for Edwiser Bridge, an email containing the purchase receipt, download link, and license key will be sent to your registered email id. 
* You can download the extension using the download link provided.
* Go to the 'Plugins' menu from the dashboard.
* Click on the 'Add New' button on this page.
* Now click on the 'Upload Plugin' button and upload the WooCommerce Integration extension file that you downloaded in the first step.
* Click on the 'Install Now' button once you have located and uploaded the plugin.
* On successful installation click the 'Activate Plugin' link to activate the plugin.

=  Manual Installation  = 
* Download the WooCommerce Integration extension from the download link provided in the purchase email.
* Now unzip and upload the folder using the FTP application of your choice.
* The extension can then be activated by navigating to the Plugins menu in the admin dashboard.


== Frequently Asked Questions ==

= Which version of WordPress does the WooCommerce Integration extension work with? =
WooCommerce Integration extension requires at least WordPress version 4.7.0 and has been tested up to version 5.7.

= Which version of WooCommerce does the WooCommerce Integration extension work with? =
WooCommerce Integration for Edwiser Bridge works with WooCommerce 2.3.0 or higher.

= Are there any prerequisites for the installation of the extension? =
WooCommerce Integration is an extension of the Edwiser Bridge plugin. Hence the Edwiser Bridge plugin will have to be installed on your website before you get started. You can download your free copy of Edwiser Bridge from wordpress.org

Take a look at the link below to see the full list of questions which will help you around the WooCommerce Integration Extension.
<a href = "https://edwiser.org/bridge/extensions/woocommerce-integration/#FAQ">Frequently Asked Questions</a>

== Changelog ==
= 2.2.3 =
* Fix - single product purchase enrolment issue

= 2.2.2 =
* Feature : Added functionality to purchase courses for someone else.
* Feature : Added already enrolled tag in the course when the user is already enrolled in courses.
* Tweak : Arranged associated courses in alphabetical order.
* Tweak : Coding standards improvements.
* Tweak : Sync updated course name on the WooCommerce product on product sync.
* Tweak : PHP 8.0 compatibility.
* Fix : Fixed non-completed subscriptions cancelled were unenrolling/suspending user enrolment issues.

= 2.2.1 =
* Tweak: deprocated custom fields
* Fix: Fixed email was not sent when the user registered via checkout/my-account

= 2.2.0 =
* Fix: Fixed course access expiry date for subscription renewal.
* Fix: Empty Request for user creation during the checkout process.
* Fix: Duplicate class and function parameter mismatch.

= 2.1.8 =
* Fix: Added compatibility to PHP 8.0.

= 2.1.7 =
* Fix: Solved custom fields synchronization Notices.
* Fix: Solved single click checkout page issue when selected page is cart.
* Fix: Solved Single page checkout token and login links issue.
* Tweak: Added support to On hold woocommerce subscription status.


= 2.1.6 =
* Fix: Solved custom fields synchronization issue from checkout page.
* Fix: Solved Cart page checkout button issue when woo-int is activated.
* Fix: Purchaser was getting enrolled when bulk purchase product is purchased, solved Bulk Purchase compatibility issue. 


= 2.1.5 =
* Feture: Added functionality to add custom fields on checkout page.
* Feture: Added functionality to add custom fields on Woocommerce My account page.
* Feture: Added functionality to add custom fields on Edwiser Bridge User account page.



= 2.1.4 =
* Feture: Compatibility with WordPress 5.7.
* Fix: After product Synchronization Bulk purchase settings were getting removed/disabled.

= 2.1.3 =
* Tweak: Removed use of ABSPATH constant.

= 2.1.2 =
* Fix - Adding fix for woocommerce my account page my courses tab 404 issue.


= 2.1.1 =
* Tweak: Removed constant dependency from Edwiser Bridge and used actual value.

= 2.1.0 =
* Feature - Functionality to create account on Moodle side from woocommerce My-account page.
* Feature - Functionality to update woocommerce My-account page fields on Moodle side.
* Feature - Added My Courses tab in the woocommerce my-account page.
* Tweak - FUnctionality to not perform any action on the on=hold and pending cancellation status of subscription.
* Fix - Bulk purchase products are also mentioned in the enrollment email if the normal course associated product is purchased with Bulk product so fixed this issue.


= 2.0.1 =
* Fix - Solved fatal error on the plugin installation.
* Fix - Subscriptions was unable to send the enrollment email so solved it.


= 2.0.0 =
* Feature - Added Woocommerce Membership support.
* Feature - User will get enrolled in all the courses associated to the products which are associated to the Membership on the purchase of Membership from the product.
* Feature - On membership activation user will get enrolled to the courses associated to the products which are associated to the membership.
* Feature - On membership suspend user will get suspended to the courses associated to the products which are associated to the membership.
* Feature - On membership expiration user will get unenrolled or suspended or nothing will happen depending on the setting provided in the woo-int tab.
* Feature - On membership cancellation user will get unenrolled or suspended or nothing will happen depending on the setting provided in the woo-int tab.
* Fix - Email was sent if the product is bulk purchased enabled product from backend but purchased without enabling the bulk purchase, fixed this issue.
* Fix - Plugin was giving fatal on activation if the edwiser bridge was not enabled, fixed this issue.
* Fix - Notice removed from the products page.


= 1.1.9 =
* Fix - Fixed count issue on courses listing page.
* Fix - Added comma(,) to seperate multiple courses in associated courses column.
* Fix - Fixed the issue of course enrollment email not sending after order completion.

= 1.1.8 =
* Feature - Added setting in Woocommerce-Integration to show associated courses in the single product page.
* Fix - Fixed table layout issue shown on the products list at the backend for associated courses section.
* Fix - User was created at moodle even if the product doesn't have any associated courses, fixed this issue.
* Fix - Fixed the variable product issue.
* Fix - Solved the issue of refund functionality for the subscription.



= 1.1.7 =
* Fix - Fixed a bug which was coming on completing Woocommerce orders manually.

= 1.1.6 =
* Feature - Choose an action to perform on subscription expiration (Unenroll, Suspend or Do Nothing).
* Feature - Display Woocommerce orders on edwiser bridge user account template page.
* Fix - Associated course getting removed on quick edit.

= 1.1.5 =
* Tweak - UPdated licensing template
* Fix - Fixed WooCommerce guest checkout setting disabling issue.


= 1.1.4 =
* Feature - Setting for Redirect users to My Courses Page from WooCommerce Thank You Page.
* Tweak - Updated Licensing code.
* Tweak - Compatibility with WooCommerce 3.0.0



= 1.1.3=
* Feature - Redirect users to My Courses Page from WooCommerce Thank You Page.
* Feature - WooCommerce Grouped Product compatibility.
* Feature - Checkbox `Unenroll from purchased courses?` on dashboard order refund section.
* Feature - One click checkout.
* Feature - New shortcode `[bridge_woo_single_cart_checkout]` to render single cart checkout page.
* Tweak - Show confirmation warning while trashing courses.
* Tweak - Compatibility with major update of WooCommerce Subscriptions 2.0.0.
* Tweak - Show associated courses as link.
* Tweak - Licensing code.
* Fix - User not getting enrolled when they purchases variable product.
* Fix - Plugin not working for Simple and Variable Subscriptions.
* Fix - Translation ready.
* Fix - Trashed courses are still visible in dashboard product edit page courses dropdown and on single product pages.
* Fix - Courses does not save properly on dashboard product edit page.
* Fix - Courses to products synchronization not working properly.


= 1.1.2=
* Feature - Single product variation.
* Feature - Added a hook to support "Bulk Purchase" Add-on.

= 1.1.1=
* Fix - Fixed a bug that was conflicting with WooCommerce Subscriptions.

= 1.1 =
* Tweak - Refactored & optimized whole plugin codebase using tools like PHPCS & PHPMD.
* Tweak - WooCommerce Integration license key presented inside the "licenses" tab in Edwiser Bridge.

= 1.0.4 =
* Fixed the table creation issue in this update.

= 1.0.3 =
* Sync Course categories as Product categories
* Assign Product categories to respective Products as per courses & course categories. 

= 1.0.2 =
* Separate "Products" tab in Synchronization.
* Virtual and Downloadable options enabled by default in product page.

= 1.0.1 =
* Plugin compatibility with PHP 5.2 and above.

= 1.0 =
* Plugin Launched

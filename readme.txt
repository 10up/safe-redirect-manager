=== Safe Redirect Manager ===
Contributors: tlovett1, tollmanz, taylorde, 10up, jakemgold, danielbachhuber, VentureBeat
Tags: http redirects, redirect manager, url redirection, safe http redirection, multisite redirects, redirects
Requires at least: 3.1
Tested up to: 5.0
Stable tag: trunk

Safely and easily manage your website's HTTP redirects.

== Description ==

Safe Redirect Manager is a HTTP redirect manager for WordPress. An easy-to-use UI allows you to redirect locations to new URL's with the HTTP status codes of your choosing. This plugin works great with multisite.

[Fork the plugin on GitHub.](https://github.com/10up/safe-redirect-manager)

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

== Changelog ==

= 1.9.1 =
* Fix SQL injection bug opened up by SQL search functionality.

= 1.9 =
* Add redirect notes feature.
* Fix PHP 7.2 errors
* Instantiate classes in main file instead of individual files for improved testability.
* Add filters for request path and redirect path
* Add filter to only apply redirects on 404

= 1.8 =
* Improved escaping
* Custom redirect capability
* Code refactor
* Fix root redirect in sub directory bug
* Fix broken html

= 1.7.8 (Dec. 16, 2015) =
* Fix SQL injection bug and no search terms warning

= 1.7.7 (Jun. 18, 2015) =
* Make default redirect status filterable
* Add composer.json
* Fix delete capability on redirect post type

= 1.7.6 (Feb. 13, 2015) =
* Use home_url() instead of site_url(). Props [swalkinshaw](https://github.com/swalkinshaw)
* Don't redirect if redirect to location is invalid. Props [vaurdan](https://github.com/vaurdan)
* Redirection plugin importer. Props [eugene-manuilov](https://github.com/eugene-manuilov)

= 1.7.5 (Sept. 9, 2014) =
* Don't always lowercase matched parts in redirect to replace. Props[francescolaffi](https://github.com/francescolaffi)
* Plugin icon/banner

= 1.7.4 (Sept. 5, 2014) =
* Fix case sensitivity redirection bug.
* Add more unit tests

= 1.7.3 (Aug. 26, 2014) =
* Check if the global $wp_query is null before using get_query_var. Props [cmmarslender](https://github.com/cmmarslender)
* Unit tests
* Making _x translatable text work. Props [lucspe](https://github.com/lucspe)

= 1.7.2 (Feb. 10, 2014) =
* Added French translation. Props [jcbrebion](https://github.com/jcbrebion).
* Bug fix: Don't perform redirects in the admin. Props [joshbetz](https://github.com/joshbetz).
* Bug fix: Prevent duplicate GUIDs. Props [danblaker](https://github.com/danblaker).

= 1.7.1 (Dec. 12, 2013) =
* Add 307 redirect status code. Thanks [lgedeon](https://github.com/lgedeon)
* Plugin textdomain should be loaded on init
* Add status code labels to creation dropdown. Thanks Chancey Mathews

= 1.7 (Apr. 6, 2013) =
* Return redirect_from on get_permalink. Thanks [simonwheatley](https://github.com/simonwheatley)
* Allow for regex replacement in from/to redirects
* Add Slovak translation. Thanks [Branco Radenovich](http://webhostinggeeks.com/blog)
* Notice fixed in filter_admin_title

= 1.6 (Dec. 11, 2012) =
* Bulk delete redirects from the Manage Redirects screen
* wp-cli coverage including subcommands for creating, deleting, and listing redirects, and importing .htaccess files

= 1.5 (Nov. 7 2012) =
* Regular expressions allowed in redirects
* New filter 'srm_registered_redirects' allows you to conditionally unset redirects based on context, user permissions, etc. Thanks [jtsternberg](https://github.com/jtsternberg) for the pull request.

= 1.4.2 (Oct. 17, 2012) =
* Disable redirect loop checking by default. You can filter srm_check_for_possible_redirect_loops to enable it.
* Only return published redirects in update_redirect_cache. - bug fix

= 1.4.1 (Oct. 11, 2012) =
* Refresh cache after create_redirect call - bug fix
* Refresh cache after save_post is called - bug fix
* Chop off "pre-WP" path from requested path. This allows the plugin to work on WP installations in sub-directories - bug fix

= 1.4 (Oct. 9, 2012) =
* Use the '*' wildcard at the end of your match value to configure a wildcard redirect. Use the same symbol at the end of your redirect to value in order to have the matched value be appended to the end of the redirect. Thanks [prettyboymp](https://github.com/prettyboymp) for the pull request
* Change default request-matching behavior to be case-insensitive. This can be modified using the 'srm_case_insensitive_redirects' filter.
* Include an informational 'X-Safe-Redirect-Manager' header when a redirect occurs. Thanks [simonwheatley](https://github.com/simonwheatley) for the pull request

= 1.3 =
* safe-redirect-manager.php - Globalize SRM class for use in themes/plugins/scripts. Added create_redirect method to make importing easier.

= 1.2 =
*   safe-redirect-manager.php - manage_options capabilitiy required to use redirect manager, remove checkbox column, hide view switcher, fix search feature, hide privacy stuff for bulk edit

= 1.1 =
*   safe-redirect-manager.php - plugin_url() used properly, is_plugin_page function

= 1.0 =
*   Plugin released

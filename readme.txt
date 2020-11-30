=== Safe Redirect Manager ===
Contributors: tlovett1, tollmanz, taylorde, 10up, jakemgold, danielbachhuber, VentureBeat
Tags: http redirects, redirect manager, url redirection, safe http redirection, multisite redirects, redirects
Requires at least: 4.6
Tested up to: 5.5.3
Stable tag: 1.10.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Safely and easily manage your website's HTTP redirects.

== Description ==

Easily and safely manage your site's redirects the WordPress way. There are many redirect plugins available. Most of them store redirects in the options table or in custom tables. Most of them provide tons of unnecessary options. Some of them have serious performance implications (404 error logging). Safe Redirect Manager stores redirects as Custom Post Types. This makes your data portable and your website scalable. Safe Redirect Manager is built to handle enterprise level traffic and is used on major publishing websites. The plugin comes with only what you need following the WordPress mantra, decisions not options. Actions and filters make the plugin very extensible.

[Fork the plugin on GitHub.](https://github.com/10up/safe-redirect-manager)

== Installation ==

Install the plugin in WordPress. You can download a [zip via GitHub](https://github.com/10up/safe-redirect-manager/archive/trunk.zip) and upload it using the WordPress plugin uploader ("Plugins" > "Add New" > "Upload Plugin").

== Configuration ==

There are no overarching settings for this plugin. To manage redirects, navigate to the administration panel ("Tools" > "Safe Redirect Manager").

Each redirect contains a few fields that you can utilize:

=== "Redirect From" ===
This should be a path relative to the root of your WordPress installation. When someone visits your site with a path that matches this one, a redirect will occur. If your site is located at `http://example.com/wp/` and you wanted to redirect `http://example.com/wp/about` to `http://example.com`, your "Redirect From" would be `/about`.

Clicking the "Enable Regex" checkbox allows you to use regular expressions in your path. There are many [great tutorials](http://www.regular-expressions.info) on regular expressions.

You can also use wildcards in your "Redirect From" paths. By adding an `*` at the end of a URL, your redirect will match any request that starts with your "Redirect From". Wildcards support replacements. This means if you have a wildcard in your from path that matches a string, you can have that string replace a wildcard character in your "Redirect To" path. For example, if your "Redirect From" is `/test/*`, your "Redirect To" is `http://google.com/*`, and the requested path is `/test/string`, the user would be redirect to `http://google.com/string`.

=== "Redirect To" ===
This should be a path (i.e. `/test`) or a URL (i.e. `http://example.com/wp/test`). If a requested path matches "Redirect From", they will be redirected here. "Redirect To" supports wildcard and regular expression replacements.

=== "HTTP Status Code" ===
[HTTP status codes](http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html) are numbers that contain information about a request (i.e. whether it was successful, unauthorized, not found, etc). You should almost always use either 302 (temporarily moved) or 301 (permanently moved).

*Note:*

* Redirects are cached using the Transients API. Cache busts occur when redirects are added, updated, and deleted so you shouldn't be serving stale redirects.
* By default the plugin only allows at most 250 redirects to prevent performance issues. There is a filter `srm_max_redirects` that you can utilize to up this number.
* "Redirect From" and requested paths are case insensitive by default.
* Developers can use `srm_additional_status_codes` filter to add status codes if needed.

== Changelog ==

= 1.10.0 =
* **Added:** `410 Gone` status code to the list of HTTP status codes and `srm_additional_status_codes` to add additional status codes ([@dinhtungdu](https://profiles.wordpress.org/dinhtungdu/), [@helen](https://profiles.wordpress.org/helen), [@PopVeKind](https://profiles.wordpress.org/popvekind/)).
* **Added:** Option to ignore query parameters, previous behaviour still available via the new `srm_match_query_params` filter (props [@bradleyt](https://profiles.wordpress.org/bradleyt/), [@dinhtungdu](https://profiles.wordpress.org/dinhtungdu/)).
* **Added:** Extracts redirect matching logic from `maybe_redirect` to `match_redirect` method, plus `srm_match_redirect` function to expose matching redirect logic to themes and plugins (props [@nicholas_io](https://profiles.wordpress.org/nicholas_io/), [@dinhtungdu](https://profiles.wordpress.org/dinhtungdu/)).
* **Added:** Redirect Post ID to response headers where a redirect rule is invoked (props [@jamesmorrison](https://profiles.wordpress.org/jamesmorrison/), [@dinhtungdu](https://profiles.wordpress.org/dinhtungdu/)).
* **Added:** Banner and icon images (props [@lea10up](https://profiles.wordpress.org/lea10up/)).
* **Added:** Documentation and unit test updates (props [@noplanman](https://profiles.wordpress.org/noplanman/), [@dinhtungdu](https://profiles.wordpress.org/dinhtungdu/), [@kevinbrands](https://profiles.wordpress.org/kevinbrands/), [@jeffpaul](https://profiles.wordpress.org/jeffpaul/), [@davidegreenwald](https://profiles.wordpress.org/davidegreenwald/), [@barryceelen](https://profiles.wordpress.org/barryceelen/)).
* **Fixed:** Use proper hook for setting up `SRM_Redirect` (props [@dinhtungdu](https://profiles.wordpress.org/dinhtungdu/), [@icaleb](https://profiles.wordpress.org/icaleb/)).
* **Fixed:** Regression related to wildcard matching (props [@amyevans](https://github.com/amyevans), [@dinhtungdu](https://profiles.wordpress.org/dinhtungdu/), [@jeffreybetts](https://github.com/jeffreybetts)).
* **Fixed:** Missing `order` column in CSV import WP-CLI command (props [@barryceelen](https://profiles.wordpress.org/barryceelen/)).
* **Security:** Bump `lodash` from 4.17.15 to 4.17.19 (props [@dependabot](https://github.com/dependabot)).

= 1.9.3 =
* **Changed:** Allow for escaped values on `_redirect_rule_from`, useful when importing regex (props [@raymondware](https://profiles.wordpress.org/raymondware)).
* **Changed:** Check `current_user_can` cap later to prevent the notice being thrown during Jetpack sitemap cron event runs (props [@rebeccahum](https://profiles.wordpress.org/rebasaurus)).
* **Changed:** Updated tests and documentation (props [@adamsilverstein](https://profiles.wordpress.org/adamsilverstein), [@jeffpaul](https://profiles.wordpress.org/jeffpaul), [@helen](https://profiles.wordpress.org/helen)).
* **Changed:** Check correct meta field when updating notes (props [@lucymtc](https://profiles.wordpress.org/lucymtc), [@adamsilverstein](https://profiles.wordpress.org/adamsilverstein)).
* **Changed:** Bump WordPress version "tested up to" 5.3 (props [@jeffpaul](https://profiles.wordpress.org/jeffpaul)).
* **Fixed:** Update the logic for wildcard matching to properly match URLs with query parameters (props [@adamsilverstein](https://profiles.wordpress.org/adamsilverstein), [@mslinnea](https://profiles.wordpress.org/linsoftware).
* **Security:** Bump lodash from 4.17.11 to 4.17.15 (props [@dependabot](https://github.com/dependabot)).

= 1.9.2 =
* Fix CLI list function name for PHP 5

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
*   safe-redirect-manager.php - manage_options capability required to use redirect manager, remove checkbox column, hide view switcher, fix search feature, hide privacy stuff for bulk edit

= 1.1 =
*   safe-redirect-manager.php - plugin_url() used properly, is_plugin_page function

= 1.0 =
*   Plugin released

=== Safe Redirect Manager ===
Contributors:      10up, tlovett1, tollmanz, taylorde, jakemgold, danielbachhuber, VentureBeat, jeffpaul
Tags:              http redirects, redirect manager, url redirection, safe http redirection, multisite redirects, redirects
Requires at least: 5.7
Tested up to:      6.2
Requires PHP:      7.4
Stable tag:        2.0.1
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

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
* By default the plugin only allows at most 1000 redirects to prevent performance issues. There is a filter `srm_max_redirects` that you can utilize to up this number.
* "Redirect From" and requested paths are case insensitive by default.
* Developers can use `srm_additional_status_codes` filter to add status codes if needed.
* Rules set with 403 and 410 status codes are handled by applying the HTTP status code and render the default WordPress `wp_die` screen with an optional message.
* Rules set with a 404 status code will apply the status code and render the 404 template.

== Changelog ==

= 2.0.1 - 2023-06-01 =
* **Fixed:** Ensure our E2E tests run (props [@Sidsector9](https://github.com/Sidsector9), [@iamdharmesh](https://github.com/iamdharmesh) via [#318](https://github.com/10up/safe-redirect-manager/pull/318)).
* **Fixed:** Ensure the `message` array key exists before we use it (props [@dkotter](https://github.com/dkotter), [@ocean90](https://github.com/ocean90), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#319](https://github.com/10up/safe-redirect-manager/pull/319)).
* **Fixed:** Resolve deprecation notices in PHP 8.1 and later (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@dkotter](https://github.com/dkotter) via [#322](https://github.com/10up/safe-redirect-manager/pull/322)).

= 2.0.0 - 2023-05-31 =
**Note that this version bumps the PHP minimum from 5.6 to 7.4 and the WordPress minimum from 4.6 to 5.7.**

* **Added:** Handling of 403, 404, and 410 status codes (props [@nateconley](https://github.com/nateconley), [@cadic](https://github.com/cadic), [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9), [@helen](https://github.com/helen), [@dinhtungdu](https://github.com/dinhtungdu), [@dustinrue](https://github.com/dustinrue), [@ciprianimike](https://github.com/ciprianimike), [@jeffpaul](https://github.com/jeffpaul), [@aosmichenko](https://github.com/aosmichenko), [@okadots](https://github.com/okadots) via [#300](https://github.com/10up/safe-redirect-manager/pull/300)).
* **Added:** Support for adding notes when importing redirects (props [@barryceelen](https://github.com/barryceelen), [@cadic](https://github.com/cadic), [@jayedul](https://github.com/jayedul) via [#277](https://github.com/10up/safe-redirect-manager/pull/277)).
* **Added:** "Build release zip" GitHub Action (props [@iamdharmesh](https://github.com/iamdharmesh), [@cadic](https://github.com/cadic), [@faisal-alvi](https://github.com/faisal-alvi) via [#293](https://github.com/10up/safe-redirect-manager/pull/293)).
* **Added:** GitHub Action summary added Cypress test report (props [@jayedul](https://github.com/jayedul), [@peterwilsoncc](https://github.com/peterwilsoncc), [@iamdharmesh](https://github.com/iamdharmesh) via [#314](https://github.com/10up/safe-redirect-manager/pull/314)).
* **Added:** - Dependency review Github action (props [@jeffpaul](https://github.com/jeffpaul), [@Sidsector9](https://github.com/Sidsector9) via [#317](https://github.com/10up/safe-redirect-manager/pull/317)).
* **Changed:** Bumped PHP minimum supported version from 5.6 to 7.4 (props [@csloisel](https://github.com/csloisel), [@dkotter](https://github.com/dkotter), [@vikrampm1](https://github.com/vikrampm1) via [#289](https://github.com/10up/safe-redirect-manager/pull/289)).
* **Changed:** Bumped WordPress minimum supported version from 4.6 to 5.7 (props [@csloisel](https://github.com/csloisel), [@dkotter](https://github.com/dkotter), [@vikrampm1](https://github.com/vikrampm1) via [#289](https://github.com/10up/safe-redirect-manager/pull/289)).
* **Changed:** Bumped PHPCS compat script to use 7.4 as test version (props [@csloisel](https://github.com/csloisel), [@dkotter](https://github.com/dkotter), [@vikrampm1](https://github.com/vikrampm1) via [#289](https://github.com/10up/safe-redirect-manager/pull/289)).
* **Changed:** Bumped WordPress "test up to" version 6.2 (props [@csloisel](https://github.com/csloisel), [@jayedul](https://github.com/jayedul) via [#290](https://github.com/10up/safe-redirect-manager/pull/290), [#310](https://github.com/10up/safe-redirect-manager/pull/310)).
* **Changed:** Cypress integration migrated from 9.5.2 to 11.2.0 (props [@jayedul](https://github.com/jayedul), [@cadic](https://github.com/cadic), [@Sidsector9](https://github.com/Sidsector9), [@iamdharmesh](https://github.com/iamdharmesh) via [#295](https://github.com/10up/safe-redirect-manager/pull/295)).
* **Changed:** Run E2E tests on the ZIP generated by "Build release zip" GitHub Action (props [@jayedul](https://github.com/jayedul), [@cadic](https://github.com/cadic), [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#306](https://github.com/10up/safe-redirect-manager/pull/306), [#311](https://github.com/10up/safe-redirect-manager/pull/311)).
* **Changed:** Status code dropdown is now sorted numerically (props [@norcross](https://github.com/norcross), [@Sidsector9](https://github.com/Sidsector9) via [#307](https://github.com/10up/safe-redirect-manager/pull/307)).
* **Removed:** PHP versions < 7.4 from phpunit tests (props [@csloisel](https://github.com/csloisel), [@dkotter](https://github.com/dkotter), [@vikrampm1](https://github.com/vikrampm1) via [#289](https://github.com/10up/safe-redirect-manager/pull/289)).
* **Fixed:** Check non-active multisite directory against the main site redirects (props [@phpbits](https://github.com/phpbits), [@dinhtungdu](https://github.com/dinhtungdu), [@ciprianimike](https://github.com/ciprianimike), [@gsarig](https://github.com/gsarig), [@Sidsector9](https://github.com/Sidsector9), [@davidegreenwald](https://github.com/davidegreenwald), [@turtlepod](https://github.com/turtlepod) via [#248](https://github.com/10up/safe-redirect-manager/pull/248)).
* **Fixed:** Regex redirects without leading `/` are buggy (props [@dhanendran](https://github.com/dhanendran), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#279](https://github.com/10up/safe-redirect-manager/pull/279)).
* **Fixed:** Issue with `srm_additional_status_codes` filter hook (props [@Sidsector9](https://github.com/Sidsector9), [@faisal-alvi](https://github.com/faisal-alvi) via [#312](https://github.com/10up/safe-redirect-manager/pull/312)).
* **Security:** Bump `got` from 10.7.0 to 11.8.5 (props [@dependabot](https://github.com/apps/dependabot), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#286](https://github.com/10up/safe-redirect-manager/pull/286)).
* **Security:** Bump `@wordpress/env` from 4.9.0 to 5.3.0 (props [@dependabot](https://github.com/apps/dependabot), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#286](https://github.com/10up/safe-redirect-manager/pull/286)).
* **Security:** Bump `simple-git` from 3.9.0 to 3.16.0 (props [@dependabot](https://github.com/apps/dependabot), [@Sidsector9](https://github.com/Sidsector9), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#294](https://github.com/10up/safe-redirect-manager/pull/294), [#302](https://github.com/10up/safe-redirect-manager/pull/302)).
* **Security:** Bump `http-cache-semantics` from 4.1.0 to 4.1.1 (props [@dependabot](https://github.com/apps/dependabot), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#305](https://github.com/10up/safe-redirect-manager/pull/305)).

= 1.11.1 - 2022-09-28 =
* **Added:** Indicate plugin as the source of redirects (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@Sidsector9](https://github.com/Sidsector9) via [#281](https://github.com/10up/safe-redirect-manager/pull/281)).

= 1.11.0 - 2022-06-27 =
* **Added:** Detect duplicate rules for the same 'redirect from' value (props [@adamsilverstein](https://github.com/adamsilverstein), [@dhanendran](https://github.com/dhanendran), [@hrkhal](https://github.com/hrkhal), [@jeffpaul](https://github.com/jeffpaul), [@lukaspawlik](https://github.com/lukaspawlik), [@sanketio](https://github.com/sanketio), [@Sidsector9](https://github.com/Sidsector9) via [#171](https://github.com/10up/safe-redirect-manager/pull/171)).
* **Added:** PHP 8 compatibility (props [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#264](https://github.com/10up/safe-redirect-manager/pull/264)).
* **Added:** E2E Tests with Cypress (props [@iamdharmesh](https://github.com/iamdharmesh), [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter) via [#262](https://github.com/10up/safe-redirect-manager/pull/262), [#273](https://github.com/10up/safe-redirect-manager/pull/273)).
* **Added:** Dependency security scanning (props [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#268](https://github.com/10up/safe-redirect-manager/pull/268)).
* **Changed:** Default number of redirects in readme files (props [@grappler](https://github.com/grappler) via [#259](https://github.com/10up/safe-redirect-manager/pull/259)).
* **Changed:** Bump WordPress "tested up to" version 6.0 (props [@jeffpaul](https://github.com/jeffpaul), [@sudip-10up](https://github.com/sudip-10up), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#260](https://github.com/10up/safe-redirect-manager/pull/260), [#270](https://github.com/10up/safe-redirect-manager/pull/270)).
* **Fixed:** Unit tests by adding `PHPUnit-Polyfills` library (props [@iamdharmesh](https://github.com/iamdharmesh), [@Sidsector9](https://github.com/Sidsector9) via [#257](https://github.com/10up/safe-redirect-manager/pull/257)).
* **Security:** Bump `minimist` from 1.2.5 to 1.2.6 (props [@dependabot](https://github.com/apps/dependabot) via [#265](https://github.com/10up/safe-redirect-manager/pull/265)).

= 1.10.1 - 2021-12-16 =
* **Added:** Formatting options to `wp safe-redirect-manager list` command (props [@dinhtungdu](https://profiles.wordpress.org/dinhtungdu/), [@TheLastCicada](https://profiles.wordpress.org/thelastcicada/)).
* **Changed:** Increased redirect limits from 250 to 1,000 (props [@sultann](https://profiles.wordpress.org/manikmist09/), [@dinhtungdu](https://profiles.wordpress.org/dinhtungdu/), [@jilltilt](https://github.com/jilltilt), [@yeevy](https://github.com/yeevy)).
* **Changed:** Bump WordPress version "tested up to" 5.8 (props [@jeffpaul](https://profiles.wordpress.org/jeffpaul/), [@ankitguptaindia](https://profiles.wordpress.org/ankit-k-gupta/), [@phpbits](https://profiles.wordpress.org/phpbits/)).
* **Fixed:** Required parameter following optional deprecated message in PHP 8 (props [@vinkla](https://profiles.wordpress.org/vinkla/), [@dinhtungdu](https://profiles.wordpress.org/dinhtungdu/)).
* **Fixed:** Edge case when redirecting a URL with parameters where `$parsed_requested_path['path']` does not always exist (props [@dinhtungdu](https://profiles.wordpress.org/dinhtungdu/), [@davidmondok](https://profiles.wordpress.org/davidmondok/)).
* **Fixed:** Formatting fix to prevent npm install error (props [@phpbits](https://profiles.wordpress.org/phpbits/)).
* **Security:** Bump `minimist` from 0.0.8 to 1.2.5 (props [@dependabot](https://github.com/dependabot)).
* **Security:** Bump `lodash` from 4.17.19 to 4.17.21 (props [@dependabot](https://github.com/dependabot)).

= 1.10.0 - 2020-12-03 =
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

= 1.9.3 - 2019-11-20 =
* **Changed:** Allow for escaped values on `_redirect_rule_from`, useful when importing regex (props [@raymondware](https://profiles.wordpress.org/raymondware)).
* **Changed:** Check `current_user_can` cap later to prevent the notice being thrown during Jetpack sitemap cron event runs (props [@rebeccahum](https://profiles.wordpress.org/rebasaurus)).
* **Changed:** Updated tests and documentation (props [@adamsilverstein](https://profiles.wordpress.org/adamsilverstein), [@jeffpaul](https://profiles.wordpress.org/jeffpaul), [@helen](https://profiles.wordpress.org/helen)).
* **Changed:** Check correct meta field when updating notes (props [@lucymtc](https://profiles.wordpress.org/lucymtc), [@adamsilverstein](https://profiles.wordpress.org/adamsilverstein)).
* **Changed:** Bump WordPress version "tested up to" 5.3 (props [@jeffpaul](https://profiles.wordpress.org/jeffpaul)).
* **Fixed:** Update the logic for wildcard matching to properly match URLs with query parameters (props [@adamsilverstein](https://profiles.wordpress.org/adamsilverstein), [@mslinnea](https://profiles.wordpress.org/linsoftware).
* **Security:** Bump lodash from 4.17.11 to 4.17.15 (props [@dependabot](https://github.com/dependabot)).

= 1.9.2 - 2018-11-27 =
* Fix CLI list function name for PHP 5

= 1.9.1 - 2018-11-26 =
* Fix SQL injection bug opened up by SQL search functionality.

= 1.9 - 2018-04-03 =
* Add redirect notes feature.
* Fix PHP 7.2 errors
* Instantiate classes in main file instead of individual files for improved testability.
* Add filters for request path and redirect path
* Add filter to only apply redirects on 404

= 1.8 - 2017-12-08 =
* Improved escaping
* Custom redirect capability
* Code refactor
* Fix root redirect in sub directory bug
* Fix broken html

= 1.7.8 - 2015-12-16 =
* Fix SQL injection bug and no search terms warning

= 1.7.7 - 2015-06-18 =
* Make default redirect status filterable
* Add composer.json
* Fix delete capability on redirect post type

= 1.7.6 - 2015-02-13 =
* Use home_url() instead of site_url(). Props [swalkinshaw](https://github.com/swalkinshaw)
* Don't redirect if redirect to location is invalid. Props [vaurdan](https://github.com/vaurdan)
* Redirection plugin importer. Props [eugene-manuilov](https://github.com/eugene-manuilov)

= 1.7.5 - 2014-09-09 =
* Don't always lowercase matched parts in redirect to replace. Props[francescolaffi](https://github.com/francescolaffi)
* Plugin icon/banner

= 1.7.4 - 2014-09-05 =
* Fix case sensitivity redirection bug.
* Add more unit tests

= 1.7.3 - 2014-08-26 =
* Check if the global $wp_query is null before using get_query_var. Props [cmmarslender](https://github.com/cmmarslender)
* Unit tests
* Making _x translatable text work. Props [lucspe](https://github.com/lucspe)

= 1.7.2 - 2014-02-10 =
* Added French translation. Props [jcbrebion](https://github.com/jcbrebion).
* Bug fix: Don't perform redirects in the admin. Props [joshbetz](https://github.com/joshbetz).
* Bug fix: Prevent duplicate GUIDs. Props [danblaker](https://github.com/danblaker).

= 1.7.1 - 2013-12-12 =
* Add 307 redirect status code. Thanks [lgedeon](https://github.com/lgedeon)
* Plugin textdomain should be loaded on init
* Add status code labels to creation dropdown. Thanks Chancey Mathews

= 1.7 - 2013-04-06 =
* Return redirect_from on get_permalink. Thanks [simonwheatley](https://github.com/simonwheatley)
* Allow for regex replacement in from/to redirects
* Add Slovak translation. Thanks [Branco Radenovich](http://webhostinggeeks.com/blog)
* Notice fixed in filter_admin_title

= 1.6 - 2012-12-11 =
* Bulk delete redirects from the Manage Redirects screen
* wp-cli coverage including subcommands for creating, deleting, and listing redirects, and importing .htaccess files

= 1.5 - 2012-11-07 =
* Regular expressions allowed in redirects
* New filter 'srm_registered_redirects' allows you to conditionally unset redirects based on context, user permissions, etc. Thanks [jtsternberg](https://github.com/jtsternberg) for the pull request.

= 1.4.2 - 2012-10-17 =
* Disable redirect loop checking by default. You can filter srm_check_for_possible_redirect_loops to enable it.
* Only return published redirects in update_redirect_cache. - bug fix

= 1.4.1 - 2012-10-11 =
* Refresh cache after create_redirect call - bug fix
* Refresh cache after save_post is called - bug fix
* Chop off "pre-WP" path from requested path. This allows the plugin to work on WP installations in sub-directories - bug fix

= 1.4 - 2012-10-09 =
* Use the '*' wildcard at the end of your match value to configure a wildcard redirect. Use the same symbol at the end of your redirect to value in order to have the matched value be appended to the end of the redirect. Thanks [prettyboymp](https://github.com/prettyboymp) for the pull request
* Change default request-matching behavior to be case-insensitive. This can be modified using the 'srm_case_insensitive_redirects' filter.
* Include an informational 'X-Safe-Redirect-Manager' header when a redirect occurs. Thanks [simonwheatley](https://github.com/simonwheatley) for the pull request

= 1.3 - 2012-09-19 =
* safe-redirect-manager.php - Globalize SRM class for use in themes/plugins/scripts. Added create_redirect method to make importing easier.

= 1.2 - 2012-09-01 =
*   safe-redirect-manager.php - manage_options capability required to use redirect manager, remove checkbox column, hide view switcher, fix search feature, hide privacy stuff for bulk edit

= 1.1 - 2012-08-28 =
*   safe-redirect-manager.php - plugin_url() used properly, is_plugin_page function

= 1.0 - 2012-08-27 =
*   Plugin released

== Upgrade Notice ==

= 2.0.0 =
Note that this version bumps the PHP minimum from 5.6 to 7.4 and the WordPress minimum from 4.6 to 5.7.

=== Safe Redirect Manager ===
Contributors:      10up, tlovett1, tollmanz, taylorde, jakemgold, danielbachhuber, jeffpaul
Tags:              http redirects, redirect manager, url redirection, safe http redirection, multisite redirects
Requires at least: 6.9
Tested up to:      7.1
Stable tag:        2.3.0
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Safely manage your website's HTTP redirects.

== Description ==

Safely manage your site's redirects the WordPress way. There are many redirect plugins available. Most of them store redirects in the options table or in custom tables. Most of them provide tons of unnecessary options. Some of them have serious performance implications (404 error logging). Safe Redirect Manager stores redirects as Custom Post Types. This makes your data portable and your website scalable. Safe Redirect Manager is built to handle enterprise level traffic and is used on major publishing websites. The plugin comes with only what you need following the WordPress mantra, decisions not options. Actions and filters make the plugin very extensible.

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
* Browsers heavily cache 301 (permanently moved) redirects. It's recommended to test your permanent redirects using the 302 (temporarily moved) status code before changing them to 301 permanently moved.

=== Developer Documentation ===

Safe Redirect Manager includes a number of actions and filters developers can make use of. These are documented on the [Safe Redirect Manager developer documentation](http://10up.github.io/safe-redirect-manager/) micro-site.

== Frequently Asked Questions ==

= Where do I report security bugs found in this plugin? =

Please report security bugs found in the source code of the Safe Redirect Manager plugin through the [Patchstack Vulnerability Disclosure  Program](https://patchstack.com/database/vdp/3fab514f-46ce-4b71-ab53-450228f73bde).  The Patchstack team will assist you with verification, CVE assignment, and notify the developers of this plugin.

== Screenshots ==

1. List of Redirect rules under Tools > Safe Redirect Manager
2. Edit view of a Redirect rule
3. Bulk Edit view of multiple Redirect rules
4. Bulk Edit to enable Force HTTPs

== Upgrade Notice ==

= 2.3.0 =
This is a security release, it is recommended to upgrade immediately.

== Changelog ==

= 2.3.0 - 2026-09-21 =

**Security**

- Resolve GHSA-xxq8-pr9f-w3c6 (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [GHSA-xxq8-pr9f-w3c6 ](https://github.com/10up/safe-redirect-manager/security/advisories/GHSA-xxq8-pr9f-w3c6)).

**Added**

- Export redirect rules as CSV or JSON from the admin redirect list table (props [@nhrrob](https://github.com/nhrrob), [@thrijith](https://github.com/thrijith), [@peterwilsoncc](https://github.com/peterwilsoncc), [@dkotter](https://github.com/dkotter) via [#452](https://github.com/10up/safe-redirect-manager/pull/452), [#469](https://github.com/10up/safe-redirect-manager/pull/469)).
- Run a `current_user_can` check when we validate a URL to ensure the user has proper permissions (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#464](https://github.com/10up/safe-redirect-manager/pull/464)).

**Changed**

- Bump WordPress tested-up-to version 7.1 (props [@Rishabh-fueled](https://github.com/Rishabh-fueled), [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc), [@phpbits](https://github.com/phpbits), [@zamanq](https://github.com/zamanq) via [#418](https://github.com/10up/safe-redirect-manager/pull/418), [#429](https://github.com/10up/safe-redirect-manager/pull/429), [#444](https://github.com/10up/safe-redirect-manager/pull/444), [#458](https://github.com/10up/safe-redirect-manager/pull/458)).
- Bump WordPress minimum supported version to 6.9 (props [@Rishabh-fueled](https://github.com/Rishabh-fueled), [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#419](https://github.com/10up/safe-redirect-manager/pull/419), [#461](https://github.com/10up/safe-redirect-manager/pull/461)).
- Update NPM dependencies via `npm audit fix` (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@dkotter](https://github.com/dkotter) via [#434](https://github.com/10up/safe-redirect-manager/pull/434)).

**Fixed**

- Prevent duplicate redirect detection from flagging own post as a duplicate (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@dkotter](https://github.com/dkotter) via [#438](https://github.com/10up/safe-redirect-manager/pull/438)).
- PHP 8.4 deprecation warnings (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#440](https://github.com/10up/safe-redirect-manager/pull/440)).
- Prevent wildcard redirects from dropping the leading slash when the source and destination bases do not include a trailing slash (props [@thisismyurl](https://github.com/thisismyurl), [@thrijith](https://github.com/thrijith), [@dkotter](https://github.com/dkotter), [@earthlingdavey](https://github.com/earthlingdavey) via [#453](https://github.com/10up/safe-redirect-manager/pull/453)).

= 2.2.2 - 2025-02-05 =

* **Added:** Add author ID as a new, optional argument to the `srm_create_redirect` function. If passed, will associate this author ID to the newly created redirect (props [@norcross](https://github.com/norcross), [@dkotter](https://github.com/dkotter) via [#408](https://github.com/10up/safe-redirect-manager/pull/408)).
* **Fixed:** Fix a few typos (props [@szepeviktor](https://github.com/szepeviktor), [@jeffpaul](https://github.com/jeffpaul) via [#407](https://github.com/10up/safe-redirect-manager/pull/407)).

= 2.2.1 - 2024-11-13 =

* **Changed:** Bump WordPress "tested up to" version 6.7 (props [@sudip-md](https://github.com/sudip-md), [@jeffpaul](https://github.com/jeffpaul), [@mehidi258](https://github.com/mehidi258) via [#403](https://github.com/10up/safe-redirect-manager/pull/403)).
* **Changed:** Bump WordPress minimum supported version to 6.5 (props [@sudip-md](https://github.com/sudip-md), [@jeffpaul](https://github.com/jeffpaul), [@mehidi258](https://github.com/mehidi258) via [#403](https://github.com/10up/safe-redirect-manager/pull/403)).
* **Fixed:** Prevent undefined property warnings when searching redirects (props [@chermant](https://github.com/chermant), [@Sidsector9](https://github.com/Sidsector9), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#400](https://github.com/10up/safe-redirect-manager/pull/400)).
* **Fixed:** Ensure the add new button shows proper text (props [[@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#404](https://github.com/10up/safe-redirect-manager/pull/404)).

= 2.2.0 - 2024-09-19 =

* **Added:** Option to Quick Edit and Bulk Edit redirect's https status and force https meta (props [@dhanendran](https://github.com/dhanendran), [@ravinderk](https://github.com/ravinderk), [@faisal-alvi](https://github.com/faisal-alvi), [@dkotter](https://github.com/dkotter), [@qasumitbagthariya](https://github.com/qasumitbagthariya), [@mehul0810](https://github.com/mehul0810), [@espellcaste](https://github.com/espellcaste) via [#350](https://github.com/10up/safe-redirect-manager/pull/350)).
* **Added:** Screenshots for WP.org plugin page (props [@faisal-alvi](https://github.com/faisal-alvi), [@jeffpaul](https://github.com/jeffpaul), [@iamdharmesh](https://github.com/iamdharmesh) via [#394](https://github.com/10up/safe-redirect-manager/pull/394)).
* **Changed:** Bump WordPress "tested up to" version 6.6 (props [@ankitguptaindia](https://github.com/ankitguptaindia), [@sudip-md](https://github.com/sudip-md) via [#386](https://github.com/10up/safe-redirect-manager/pull/386)).
* **Changed:** Bump WordPress minimum supported version from 6.3 to 6.4 (props [@ankitguptaindia](https://github.com/ankitguptaindia), [@sudip-md](https://github.com/sudip-md) via [#386](https://github.com/10up/safe-redirect-manager/pull/386)).
* **Changed:** Update documentation (props [@szepeviktor](https://github.com/szepeviktor), [@jeffpaul](https://github.com/jeffpaul), [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#384](https://github.com/10up/safe-redirect-manager/pull/384), [#388](https://github.com/10up/safe-redirect-manager/pull/388), [#391](https://github.com/10up/safe-redirect-manager/pull/391)).
* **Fixed:** Allows use of full URLs as redirect targets when using absolute URLs (props [@benlk](https://github.com/benlk), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#395](https://github.com/10up/safe-redirect-manager/pull/395)).
* **Security:** Bump `braces` from 3.0.2 to 3.0.3 (props [@dependabot](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi) via [#383](https://github.com/10up/safe-redirect-manager/pull/383)).
* **Security:** Bump `jsdoc` from 3.6.11 to 4.0.3 (props [@dependabot](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi) via [#383](https://github.com/10up/safe-redirect-manager/pull/383)).

= Earlier versions =
For the changelog of earlier versions, please refer to [the changelog on github.com](https://github.com/10up/safe-redirect-manager/blob/develop/CHANGELOG.md).

== Upgrade Notice ==

= 2.3.0 =
This version bumps the WordPress minimum from 6.5 to 6.9.

= 2.2.1 =
This version bumps the WordPress minimum from 6.4 to 6.5.

= 2.2.0 =
This version bumps the WordPress minimum from 6.3 to 6.4.

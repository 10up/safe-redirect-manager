=== Safe Redirect Manager ===
Contributors:      10up, tlovett1, tollmanz, taylorde, jakemgold, danielbachhuber, VentureBeat, jeffpaul
Tags:              http redirects, redirect manager, url redirection, safe http redirection, multisite redirects, redirects
Requires at least: 5.7
Tested up to:      6.4
Requires PHP:      7.4
Stable tag:        2.1.1
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

= 2.1.1 - 2024-01-08 =
* **Added:** Support for the WordPress.org plugin preview (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#357](https://github.com/10up/safe-redirect-manager/pull/357)).
* **Added:** `phpcs:ignore` on the now safe `ini_set()` (props [@philipjohn](https://github.com/philipjohn), [@ravinderk](https://github.com/ravinderk) via [#355](https://github.com/10up/safe-redirect-manager/pull/355)).
* **Changed:** Bump `Cypress` from 13.0.0 to 13.1.0, `@10up/cypress-wp-utils` from 0.1.0 to 0.2.0, `@wordpress/env` from 5.3.0 to 8.7.0, `cypress-mochawesome-reporter` from 3.4.0 to 3.5.1 and `node-wp-i18n` from 1.2.5 to 1.2.7 (props [@iamdharmesh](https://github.com/iamdharmesh), [@ravinderk](https://github.com/ravinderk) via [#349](https://github.com/10up/safe-redirect-manager/pull/349)).
* **Changed:** Bump WordPress "tested up to" version 6.4 (props [@qasumitbagthariya](https://github.com/qasumitbagthariya), [@jeffpaul](https://github.com/jeffpaul) via [#353](https://github.com/10up/safe-redirect-manager/pull/353), [#354](https://github.com/10up/safe-redirect-manager/pull/354)).
* **Changed:** Validate and sanitize a superglobal before using it (props [@jspellman814](https://github.com/jspellman814), [@ravinderk](https://github.com/ravinderk) via [#356](https://github.com/10up/safe-redirect-manager/pull/356)).
* **Fixed:** Ensure text can be translated (props [@alexclassroom](https://github.com/alexclassroom), [@iamdharmesh](https://github.com/iamdharmesh) via [#351](https://github.com/10up/safe-redirect-manager/pull/351)).

= 2.1.0 - 2023-09-07 =
* **Added:** WP-CLI command `export` to export redirects into a CSV file. (props [@zamanq](https://github.com/zamanq), [@jeffpaul](https://github.com/jeffpaul), [@Sidsector9](https://github.com/Sidsector9) via [#299](https://github.com/10up/safe-redirect-manager/pull/299)).
* **Added:** Admin settings to set custom redirect protocol per route (props [@tlovett1](https://github.com/tlovett1), [@danielbachhuber](https://github.com/danielbachhuber), [@benoitchantre](https://github.com/benoitchantre), [@jayedul](https://github.com/jayedul), [@Sidsector9](https://github.com/Sidsector9) via [#301](https://github.com/10up/safe-redirect-manager/pull/301)).
* **Added:** Autocomplete to the "Redirect To" field (props [@tlovett1](https://github.com/tlovett1), [@bmarshall511](https://github.com/bmarshall511), [@ravinderk](https://github.com/ravinderk) via [#325](https://github.com/10up/safe-redirect-manager/pull/325)).
* **Added:** Allow existing import records to be updated instead of skipped (props [@retlehs](https://github.com/retlehs), [@bmarshall511](https://github.com/bmarshall511), [@dkotter](https://github.com/dkotter) via [#329](https://github.com/10up/safe-redirect-manager/pull/329)).
* **Added:** Check for minimum required PHP version before loading the plugin (props [@kmgalanakis](https://github.com/kmgalanakis), [@iamdharmesh](https://github.com/iamdharmesh), [@Sidsector9](https://github.com/Sidsector9), [@vikrampm1](https://github.com/vikrampm1), [@dkotter](https://github.com/dkotter) via [#340](https://github.com/10up/safe-redirect-manager/pull/340)).
* **Added:** Repo Automator GitHub Action (props [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul) via [#348](https://github.com/10up/safe-redirect-manager/pull/348)).
* **Changed:** Bump [Support Level](https://github.com/10up/safe-redirect-manager#support-level) from `Active` to `Stable` (props [@jeffpaul](https://github.com/jeffpaul), [@Sidsector9](https://github.com/Sidsector9), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#303](https://github.com/10up/safe-redirect-manager/pull/303)).
* **Changed:** Bump WordPress "tested up to" version 6.3 (props [@github-actions](https://github.com/apps/github-actions), [@kmgalanakis](https://github.com/kmgalanakis), [@iamdharmesh](https://github.com/iamdharmesh) via [#339](https://github.com/10up/safe-redirect-manager/pull/339)).
* **Changed:** Reduced the number of queries in half by removing `post_status` property from redirects data (props [@tlovett1](https://github.com/tlovett1), [@pdclark](https://github.com/pdclark), [@mehul0810](https://github.com/mehul0810), [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter), [@nateconley](https://github.com/nateconley), [@sksaju](https://github.com/sksaju), [@ravinderk](https://github.com/ravinderk) via [#326](https://github.com/10up/safe-redirect-manager/pull/326)).
* **Changed:** Rule editor always shows raw redirect target (props [@tbenyon](https://github.com/tbenyon), [@jeffpaul](https://github.com/jeffpaul), [@trainoasis](https://github.com/trainoasis), [@jayedul](https://github.com/jayedul), [@dkotter](https://github.com/dkotter) via [#330](https://github.com/10up/safe-redirect-manager/pull/330), [#333](https://github.com/10up/safe-redirect-manager/pull/333)).
* **Changed:** Include links to redirects that cause redirect loops/chains in the warning (props [@joshbetz](https://github.com/joshbetz), [@tlovett1](https://github.com/tlovett1), [@Sidsector9](https://github.com/Sidsector9), [@ravinderk](https://github.com/ravinderk) via [#341](https://github.com/10up/safe-redirect-manager/pull/341)).
* **Changed:** Set the default value for the `srm_check_for_possible_redirect_loops` filter to `true` (props [@joshbetz](https://github.com/joshbetz), [@tlovett1](https://github.com/tlovett1), [@Sidsector9](https://github.com/Sidsector9), [@ravinderk](https://github.com/ravinderk) via [#341](https://github.com/10up/safe-redirect-manager/pull/341)).
* **Changed:** Added a back link to the admin notices when a post is saved or updated (props [@szepeviktor](https://github.com/szepeviktor), [@tlovett1](https://github.com/tlovett1), [@bmarshall511](https://github.com/bmarshall511), [@iamdharmesh](https://github.com/iamdharmesh) via [#328](https://github.com/10up/safe-redirect-manager/pull/328)).
* **Changed:** Missing Docblocks for filter hooks (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@jayedul](https://github.com/jayedul), [@dkotter](https://github.com/dkotter), [@faisal-alvi](https://github.com/faisal-alvi), [@iamdharmesh](https://github.com/iamdharmesh) via [#313](https://github.com/10up/safe-redirect-manager/pull/313)).
* **Fixed:** `auto_detect_line_endings` deprecation warning in PHP 8.1 and above (props [@dhewer](https://github.com/dhewer), [@jayedul](https://github.com/jayedul), [@Sidsector9](https://github.com/Sidsector9), [@ravinderk](https://github.com/ravinderk) via [#327](https://github.com/10up/safe-redirect-manager/pull/327)).
* **Fixed:** Only show public post types in the autocomplete "Redirect To" field (props [@ravinderk](https://github.com/ravinderk), [@bmarshall511](https://github.com/bmarshall511), [@dkotter](https://github.com/dkotter) via [#332](https://github.com/10up/safe-redirect-manager/pull/332)).
* **Fixed:** Slow performance during redirect chain/loop detection (props [@tlovett1](https://github.com/tlovett1), [@danielbachhuber](https://github.com/danielbachhuber), [@Sidsector9](https://github.com/Sidsector9), [@ravinderk](https://github.com/ravinderk), [@iamdharmesh](https://github.com/iamdharmesh) via [#336](https://github.com/10up/safe-redirect-manager/pull/336)).
* **Security:** Bump `semver` from 7.3.8 to 7.5.4 (props [@dependabot](https://github.com/apps/dependabot), [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9) via [#334](https://github.com/10up/safe-redirect-manager/pull/334), [#342](https://github.com/10up/safe-redirect-manager/pull/342)).
* **Security:** Bump `tough-cookie` from 2.5.0 to 4.1.3 (props [@dependabot](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi) via [#337](https://github.com/10up/safe-redirect-manager/pull/337)).
* **Security:** Bump `@cypress/request` from 2.88.10 to 3.0.0 (props [@dependabot](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi), [@ravinderk](https://github.com/ravinderk) via [#337](https://github.com/10up/safe-redirect-manager/pull/337), [#343](https://github.com/10up/safe-redirect-manager/pull/343)).
* **Security:** Bump `cypress` from 11.2.0 to 13.0.0 (props [@dependabot](https://github.com/apps/dependabot), [@ravinderk](https://github.com/ravinderk) via [#343](https://github.com/10up/safe-redirect-manager/pull/343)).

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

= Earlier versions =
For the changelog of earlier versions, please refer to [the changelog on github.com](https://github.com/10up/safe-redirect-manager/blob/develop/CHANGELOG.md#1111---2022-09-28).

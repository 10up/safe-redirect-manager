# Safe Redirect Manager

> A WordPress plugin to safely and easily manage your website's HTTP redirects.

[![Support Level](https://img.shields.io/badge/support-active-green.svg)](#support-level) [![Build Status](https://travis-ci.org/10up/safe-redirect-manager.svg?branch=develop)](https://travis-ci.org/10up/safe-redirect-manager) [![Release Version](https://img.shields.io/github/release/10up/safe-redirect-manager.svg)](https://github.com/10up/safe-redirect-manager/releases/latest) ![WordPress tested up to version](https://img.shields.io/badge/WordPress-v5.5.3%20tested-success.svg) [![GPLv2 License](https://img.shields.io/github/license/10up/safe-redirect-manager.svg)](https://github.com/10up/safe-redirect-manager/blob/develop/LICENSE.md)

## Purpose

Easily and safely manage your site's redirects the WordPress way. There are many redirect plugins available. Most of
them store redirects in the options table or in custom tables. Most of them provide tons of unnecessary options. Some
of them have serious performance implications (404 error logging). Safe Redirect Manager stores redirects as Custom
Post Types. This makes your data portable and your website scalable. Safe Redirect Manager is built to handle enterprise
level traffic and is used on major publishing websites. The plugin comes with only what you need following the
WordPress mantra, decisions not options. Actions and filters make the plugin very extensible.

## Installation

Install the plugin in WordPress. You can download a
[zip via GitHub](https://github.com/10up/safe-redirect-manager/archive/master.zip) and upload it using the WordPress
plugin uploader ("Plugins" > "Add New" > "Upload Plugin").

## Configuration

There are no overarching settings for this plugin. To manage redirects, navigate to the administration panel ("Tools" > "Safe Redirect Manager").

Each redirect contains a few fields that you can utilize:

#### "Redirect From"
This should be a path relative to the root of your WordPress installation. When someone visits your site with a path
that matches this one, a redirect will occur. If your site is located at `http://example.com/wp/` and you wanted to redirect `http://example.com/wp/about` to `http://example.com`, your "Redirect From" would be `/about`.

Clicking the "Enable Regex" checkbox allows you to use regular expressions in your path. There are many
[great tutorials](http://www.regular-expressions.info) on regular expressions.

You can also use wildcards in your "Redirect From" paths. By adding an `*` at the end of a URL, your redirect will
match any request that starts with your "Redirect From". Wildcards support replacements. This means if you have a
wildcard in your from path that matches a string, you can have that string replace a wildcard character in your
"Redirect To" path. For example, if your "Redirect From" is `/test/*`, your "Redirect To" is
`http://google.com/*`, and the requested path is `/test/string`, the user would be redirect to `http://google.com/string`.

#### "Redirect To"
This should be a path (i.e. `/test`) or a URL (i.e. `http://example.com/wp/test`). If a requested path matches
"Redirect From", they will be redirected here. "Redirect To" supports wildcard and regular expression replacements.

#### "HTTP Status Code"
[HTTP status codes](http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html) are numbers that contain information about
a request (i.e. whether it was successful, unauthorized, not found, etc). You should almost always use either 302 (temporarily moved) or 301 (permanently moved).

*Note:*

* Redirects are cached using the Transients API. Cache busts occur when redirects are added, updated, and deleted
so you shouldn't be serving stale redirects.
* By default the plugin only allows at most 250 redirects to prevent performance issues. There is a filter
`srm_max_redirects` that you can utilize to up this number.
* "Redirect From" and requested paths are case insensitive by default.
* Developers can use `srm_additional_status_codes` filter to add status codes if needed.

## Filters

### Redirect loops detection

By default redirect loop detection is disabled. To prevent redirect loops you can filter `srm_check_for_possible_redirect_loops`.

```php
add_filter( 'srm_check_for_possible_redirect_loops', '__return_true' );
```

### Only redirect if 404 occurs

By default every matched URL is redirected. To only redirect matched but not found URLs (i.e., 404 pages), use `srm_redirect_only_on_404`.

```php
add_filter( 'srm_redirect_only_on_404', '__return_true' );
```

## CLI commands

The following WP-CLI commands are supported by Safe Redirect Manager:

* **`wp safe-redirect-manager list`**

    List all of the currently configured redirects.

* **`wp safe-redirect-manager create <from> <to> [<status-code>] [<enable-regex>] [<post-status>]`**

    Create a redirect. `<from>` and `<to>` are required parameters.

	* `<from>`: Redirect from path. Required.

	* `<to>`: Redirect to path. Required.

	* `<status-code>`: HTTP Status Code. Optional. Default to `302`.

	* `<enable-regex>`: Whether to enable Regular expression. Optional. Default to `false`.

	* `<post-status>`: The status of the redirect. Optional. Default to `publish`.

	**Example:** `wp safe-redirect-manager create /about-us /contact-us 301`

* **`wp safe-redirect-manager delete <id>`**

    Delete a redirect by `<id>`.

* **`wp safe-redirect-manager update-cache`**

    Update the redirect cache.

* **`wp safe-redirect-manager import <file> [--source=<source-column>] [--target=<target-column>] [--regex=<regex-column>] [--code=<code-column>]  [--order=<order-column>]`**

    Imports redirects from a CSV file.

    * `<file>`: Path to one or more valid CSV file for import. This file should contain redirection from and to URLs, regex flag and HTTP redirection code. Here is the example table:

        | source                     | target             | regex | code | order |
        |----------------------------|--------------------|-------|------|-------|
        | /legacy-url                | /new-url           | 0     | 301  | 0     |
        | /category-1                | /new-category-slug | 0     | 302  | 1     |
        | /tes?t/[0-9]+/path/[^/]+/? | /go/here           | 1     | 302  | 3     |
        | ...                        | ...                | ...   | ...  | ...   |

        _You can also use exported redirects from "Redirection" plugin, which you can download here: /wp-admin/tools.php?page=redirection.php&sub=modules_

    * `--source`: Header title for source ("from" URL) column mapping.

    * `--target`: Header title for target ("to" URL) column mapping.

    * `--regex`: Header title for regex column mapping.

    * `--code`: Header title for code column mapping.

    * `--order`: Header title for order column mapping.

* **`wp safe-redirect-manager import-htaccess <file>`**

    Import .htaccess file redirects.

## Development

#### Setup
Follow the configuration instructions above to setup the plugin. We recommend developing the plugin locally in an
environment such as [WP Local Docker](https://github.com/10up/wp-local-docker).

#### Testing
Within the terminal change directories to the plugin folder. Initialize your unit testing environment by running the
following command:

```bash
bash bin/install-wp-tests.sh database username password host version
```

Run the plugin tests:
```bash
phpunit
```

#### Issues
If you identify any errors or have an idea for improving the plugin, please
[open an issue](https://github.com/10up/safe-redirect-manager/issues?state=open).

## Translations
Safe Redirect Manager is available in English and other languages.  A listing of those languages and instructions for translating the plugin into other languages is available on [Translating WordPress](https://translate.wordpress.org/projects/wp-plugins/safe-redirect-manager/).  Many thanks to the [contributors on the translation teams](https://translate.wordpress.org/projects/wp-plugins/safe-redirect-manager/contributors/)!

## Support Level

**Active:** 10up is actively working on this, and we expect to continue work for the foreseeable future including keeping tested up to the most recent version of WordPress.  Bug reports, feature requests, questions, and pull requests are welcome.

## Changelog

A complete listing of all notable changes to Safe Redirect Manager are documented in [CHANGELOG.md](https://github.com/10up/safe-redirect-manager/blob/develop/CHANGELOG.md).

## Contributing

Please read [CODE_OF_CONDUCT.md](https://github.com/10up/safe-redirect-manager/blob/develop/CODE_OF_CONDUCT.md) for details on our code of conduct, [CONTRIBUTING.md](https://github.com/10up/safe-redirect-manager/blob/develop/CONTRIBUTING.md) for details on the process for submitting pull requests to us, and [CREDITS.md](https://github.com/10up/safe-redirect-manager/blob/develop/CREDITS.md) for a listing of maintainers of, contributors to, and libraries used by Safe Redirect Manager.

## Like what you see?

<p align="center">
<a href="http://10up.com/contact/"><img src="https://10updotcom-wpengine.s3.amazonaws.com/uploads/2016/10/10up-Github-Banner.png" width="850"></a>
</p>

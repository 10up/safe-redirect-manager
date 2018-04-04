Safe Redirect Manager [![Build Status](https://travis-ci.org/10up/safe-redirect-manager.svg?branch=master)](https://travis-ci.org/10up/safe-redirect-manager)
==============

A WordPress plugin to safely and easily manage your website's HTTP redirects.

<p align="center">
<a href="http://10up.com/contact/"><img src="https://10updotcom-wpengine.s3.amazonaws.com/uploads/2016/10/10up-Github-Banner.png" width="850"></a>
</p>

## Purpose

Easily and safely manage your site's redirects the WordPress way. There are many redirect plugins available. Most of
them store redirects in the options table or in custom tables. Most of them provide tons of unnecessary options. Some
of them have serious performance implications (404 error logging). Safe Redirect Manager stores redirects as Custom
Post Types. This makes your data portable and your website scalable. Safe Redirect Manager is built to handle enterprise
level traffic and is used on major publishing websites. The plugin comes with only what you need following the
WordPress mantra, decisions not options. Actions in filters make the plugin very extensible.

## Installation

Install the plugin in WordPress. You can download a
[zip via Github](https://github.com/10up/safe-redirect-manager/archive/master.zip) and upload it using the WP
plugin uploader.

## Non-English Usage
Safe Redirect Manager is available in English, French, and Slovak. Instructions for translating the plugin into other
languages are below.

## Configuration

There are no overarching settings for this plugin. To manager redirects navigate to the administration panel. Within
the main menu, click "Tools" > "Safe Redirect Manager".

Each redirect contains a few fields that you can utilize:

#### "Redirect From"
This should be a path relative to the root of your WordPress installation. When someone visits your site with a path
that matches this one, a redirect will occur. If your site is located at ```http://example.com/wp/``` and you wanted to redirect `http://example.com/wp/about` to `http://example.com`, your "Redirect From" would be `/about`.

Clicking the "Enable Regex" checkbox allows you to use regular expressions in your path. There are many
[great tutorials](http://www.regular-expressions.info) on regular expressions.

You can also use wildcards in your "Redirect From" paths. By adding an `*` at the end of a URL, your redirect will
match any request that starts with your "Redirect From". Wildcards support replacements. This means if you have a
wildcard in your from path that matches a string, you can have that string replace a wildcard character in your
"Redirect To" path. For example, if your "Redirect From" is `/test/*`, your "Redirect To" is
`http://google.com/*`, and the requested path is `/test/string`, the user would be redirect to `http://google.com/string`.

#### "Redirect To"
This should be a path i.e. `/test` or a URL i.e. `http://example.com/wp/test`. If a requested path matches
"Redirect From", they will be redirected here. "Redirect To" supports wildcard and regular expression replacements.

#### "HTTP Status Code"
[HTTP status codes](http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html) are numbers that contain information about
a request i.e. whether it was successful, unauthorized, not found, etc. You should almost always use either 302,
temporarily moved, or 301, permanently moved.

*Note:*

* Redirects are cached using the Transients API. Cache busts occur when redirects are added, updated, and deleted
so you shouldn't be serving stale redirects.
* By default the plugin only allows at most 250 redirects to prevent performance issues. There is a filter
`srm_max_redirects` that you can utilize to up this number.
* "Redirect From" and requested paths are case insensitive by default.

## Redirect loops

By default redirect loop detection is disabled. To prevent redirect loops you can filter `srm_check_for_possible_redirect_loops`.

```php
add_filter( 'my_srm_redirect_loop_filter', '__return_true' );
```

## Development

#### Setup
Follow the configuration instructions above to setup the plugin. I recommend developing the plugin locally in an
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

Safe Redirect Manager [![Build Status](https://travis-ci.org/tlovett1/Safe-Redirect-Manager.svg?branch=master)](https://travis-ci.org/tlovett1/Safe-Redirect-Manager)
==============

A WordPress plugin to safely and easily manage your website's HTTP redirects.

## Purpose

Easily and safely manage your site's redirects the WordPress way. There are many redirect plugins available. Most of
them store redirects in the options table or in custom tables. Most of them provide tons of unnecessary options. Some
of them have serious performance implications (404 error logging). Safe Redirect Manager stores redirects as Custom
Post Types. This makes your data portable and your website scalable. Safe Redirect Manager is built to handle enterprise
level traffic and is used on major publishing websites. The plugin comes with only what you need following the
WordPress mantra decisions not options. Actions in filters make the plugin very extensible.

## Installation

Install the plugin in WordPress. You can download a
[zip via Github](https://github.com/tlovett1/safe-redirect-manager/archive/master.zip) and upload it using the WP
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
that matches this one, a redirect will occur. If your site is located at ```http://example.com/wp/``` and you wanted to
redirect ```http://example.com/wp/about``` to ```http://example.com```, your "Redirect From" would be ```/about```.

Clicking the "Enable Regex" checkbox allows you to use regular expressions in your path. There are many
[great tutorials](http://www.regular-expressions.info) on regular expressions.

You can also use wildcards in your "Redirect From" paths. By adding an ```*``` at the end of a URL, your redirect will
match any request that starts with your "Redirect From". Wildcards support replacements. This means if you have a
wildcard in your from path that matches a string, you can have that string replace a wildcard character in your
"Redirect To" path. For example, if your "Redirect From" is ```/test/*```, your "Redirect To" is
```http://google.com/*```, and the requested path is ```/test/string```, the user would be redirect to ```http://google.com/string```.

#### "Redirect To"
This should be a path i.e. ```/test``` or a URL i.e. ```http://example.com/wp/test```. If a requested path matches
"Redirect From", they will be redirected here. "Redirect To" supports wildcard and regular expression replacements.

#### "HTTP Status Code"
[HTTP status codes](http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html) are numbers that contain information about
a request i.e. whether it was successful, unauthorized, not found, etc. You should almost always use either 302,
temporarily moved, or 301, permanently moved.

*Note:*

* Redirects are cached using the Transients API. Cache busts occur when redirects are added, updated, and deleted
so you shouldn't be serving stale redirects.
* By default the plugin only allows at most 150 redirects to prevent performance issues. There is a filter
```srm_max_redirects``` that you can utilize to up this number.
* "Redirect From" and requested paths are case insensitive by default.

## Development

#### Setup
Follow the configuration instructions above to setup the plugin. I recommend developing the plugin locally in an
environment such as [Varying Vagrant Vagrants](https://github.com/Varying-Vagrant-Vagrants/VVV).

#### Translation
Safe Redirect Manager has a [.pot file](https://github.com/tlovett1/Safe-Redirect-Manager/blob/master/languages/safe-redirect-manager.pot)
containing strings ready for translation. You can use a program like [POedit](http://poedit.net) to generate .po/.mo
files for your language.

#### Testing
Within the terminal change directories to the plugin folder. Initialize your unit testing environment by running the
following command:

For VVV users:
```
bash bin/install-wp-tests.sh wordpress_test root root localhost latest
```

For VIP Quickstart users:
```
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

where:

* wordpress_test is the name of the test database (all data will be deleted!)
* root is the MySQL user name
* root is the MySQL user password (if you're running VVV). Blank if you're running VIP Quickstart.
* localhost is the MySQL server host
* latest is the WordPress version; could also be 3.7, 3.6.2 etc.

Run the plugin tests:
```
phpunit
```

#### Issues
If you identify any errors or have an idea for improving the plugin, please
[open an issue](https://github.com/tlovett1/safe-redirect-manager/issues?state=open).
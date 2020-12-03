# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased] - TBD

## [1.10.0] - 2020-12-03
### Added
- `410 Gone` status code to the list of HTTP status codes and `srm_additional_status_codes` to add additional status codes ([@dinhtungdu](https://github.com/dinhtungdu), [@helen](https://github.com/helen), [@PopVeKind](https://github.com/PopVeKind) via [#215](https://github.com/10up/safe-redirect-manager/pull/215)).
- Option to ignore query parameters, previous behaviour still available via the new `srm_match_query_params` filter (props [@braders](https://github.com/braders), [@dinhtungdu](https://github.com/dinhtungdu) via [#196](https://github.com/10up/safe-redirect-manager/pull/196)).
- Extracts redirect matching logic from `maybe_redirect` to `match_redirect` method, plus `srm_match_redirect` function to expose matching redirect logic to themes and plugins (props [@nicholasio](https://github.com/nicholasio), [@dinhtungdu](https://github.com/dinhtungdu) via [#198](https://github.com/10up/safe-redirect-manager/pull/198)).
- Redirect Post ID to response headers where a redirect rule is invoked (props [@jamesmorrison](https://github.com/jamesmorrison), [@dinhtungdu](https://github.com/dinhtungdu) via [#218](https://github.com/10up/safe-redirect-manager/pull/218)).
- Banner and icon images (props [@lea10up](https://github.com/lea10up) via [#209](https://github.com/10up/safe-redirect-manager/pull/209)).
- Documentation and unit test updates (props [@noplanman](https://github.com/noplanman), [@dinhtungdu](https://github.com/dinhtungdu), [@noesteijver](https://github.com/noesteijver), [@jeffpaul](https://github.com/jeffpaul), [@davidegreenwald](https://github.com/davidegreenwald), [@barryceelen](https://github.com/barryceelen) via [#195](https://github.com/10up/safe-redirect-manager/pull/195), [#200](https://github.com/10up/safe-redirect-manager/pull/200), [#207](https://github.com/10up/safe-redirect-manager/pull/207), [#210](https://github.com/10up/safe-redirect-manager/pull/210), [#211](https://github.com/10up/safe-redirect-manager/pull/211), [#213](https://github.com/10up/safe-redirect-manager/pull/213), [#219](https://github.com/10up/safe-redirect-manager/pull/219), [#224](https://github.com/10up/safe-redirect-manager/pull/224)).

### Fixed
- Use proper hook for setting up `SRM_Redirect` (props [@dinhtungdu](https://github.com/dinhtungdu), [@WPprodigy](https://github.com/WPprodigy) via [#203](https://github.com/10up/safe-redirect-manager/pull/203)).
- Regression related to wildcard matching (props [@amyevans](https://github.com/amyevans), [@dinhtungdu](https://github.com/dinhtungdu), [@jeffreybetts](https://github.com/jeffreybetts) via [#217](https://github.com/10up/safe-redirect-manager/pull/217)).
- Missing `order` column in CSV import WP-CLI command (props [@barryceelen](https://github.com/barryceelen) via [#220](https://github.com/10up/safe-redirect-manager/pull/220)).

### Security
- Bump `lodash` from 4.17.15 to 4.17.19 (props [@dependabot](https://github.com/dependabot) via [#214](https://github.com/10up/safe-redirect-manager/pull/214)).

## [1.9.3] - 2019-11-20
### Changed
- Allow for escaped values on `_redirect_rule_from`, useful when importing regex (props [@raymondware](https://github.com/raymondware) via [#163](https://github.com/10up/safe-redirect-manager/pull/163))
- Check `current_user_can` cap later to prevent the notice being thrown during Jetpack sitemap cron event runs (props [@rebeccahum](https://github.com/rebeccahum) via [#178](https://github.com/10up/safe-redirect-manager/pull/178))
- Updated tests and documentation (props [@adamsilverstein](https://github.com/adamsilverstein), [@jeffpaul](https://github.com/jeffpaul), [@helen](https://github.com/helen) via [#173](https://github.com/10up/safe-redirect-manager/pull/173), [#179](https://github.com/10up/safe-redirect-manager/pull/179), [#180](https://github.com/10up/safe-redirect-manager/pull/180), [#181](https://github.com/10up/safe-redirect-manager/pull/181), [#184](https://github.com/10up/safe-redirect-manager/pull/184), [#190](https://github.com/10up/safe-redirect-manager/pull/190), [#192](https://github.com/10up/safe-redirect-manager/pull/192))
- Check correct meta field when updating notes (props [@lucymtc](https://github.com/lucymtc), [@adamsilverstein](https://github.com/adamsilverstein) via [#154](https://github.com/10up/safe-redirect-manager/pull/154), [#185](https://github.com/10up/safe-redirect-manager/pull/185))
- Bump WordPress version "tested up to" 5.3 (props [@jeffpaul](https://github.com/jeffpaul) via [#193](https://github.com/10up/safe-redirect-manager/pull/193))

### Fixed
- Update the logic for wildcard matching to properly match URLs with query parameters (props [@adamsilverstein](https://github.com/adamsilverstein), [@mslinnea](https://github.com/mslinnea) via [#182](https://github.com/10up/safe-redirect-manager/pull/182))

### Security
- Bump lodash from 4.17.11 to 4.17.15 (props [@dependabot](https://github.com/dependabot) via [#183](https://github.com/10up/safe-redirect-manager/pull/183))

## [1.9.2] - 2018-11-27
### Fixed
- CLI list function name for PHP 5

## [1.9.1] - 2018-11-26
### Fixed
- SQL injection bug opened up by SQL search functionality

## [1.9] - 2018-04-03
### Added
- Redirect notes feature
- Filters for request path and redirect path
- Filter to only apply redirects on 404

### Changed
- Instantiate classes in main file instead of individual files for improved testability

### Fixed
- PHP 7.2 errors

## [1.8] - 2017-12-08
### Added
- Custom redirect capability

### Changed
- Improved escaping
- Code refactor

### Fixed
- Root redirect in sub directory bug
- Broken html

## [1.7.8] - 2015-12-16
### Fixed
- SQL injection bug and no search terms warning

## [1.7.7] - 2015-06-18
### Added
- `composer.json` file

### Changed
- Make default redirect status filterable

### Fixed
- Delete capability on redirect post type

## [1.7.6] - 2015-02-13
### Added
- Redirection plugin importer. Props [@eugene-manuilov](https://github.com/eugene-manuilov)

### Changed
- Use `home_url()` instead of `site_url()`. Props [@swalkinshaw](https://github.com/swalkinshaw)
- Don't redirect if redirect to location is invalid. Props [@vaurdan](https://github.com/vaurdan)

## [1.7.5] - 2014-09-09
### Added
- Plugin icon/banner

### Changed
- Don't always lowercase matched parts in redirect to replace. Props [@francescolaffi](https://github.com/francescolaffi)

## [1.7.4] - 2014-09-05
### Added
- More unit tests

### Fixed
- Case sensitivity redirection bug.

## [1.7.3] - 2014-08-26
### Added
- Unit tests

### Changed
- Check if the global `$wp_query` is null before using `get_query_var`. Props [@cmmarslender](https://github.com/cmmarslender)

### Fixed
- Making `_x` translatable text work. Props [@lucspe](https://github.com/lucspe)

## [1.7.2] - 2014-02-10
### Added
- French translation. Props [@jcbrebion](https://github.com/jcbrebion).

### Fixed
- Don't perform redirects in the admin. Props [@joshbetz](https://github.com/joshbetz).
- Prevent duplicate GUIDs. Props [@danblaker](https://github.com/danblaker).

## [1.7.1] - 2013-12-12
### Added
- 307 redirect status code. Thanks [@lgedeon](https://github.com/lgedeon)
- Status code labels to creation dropdown. Thanks [@chanceymathews](https://github.com/chanceymathews)

### Changed
- Plugin textdomain should be loaded on init

## [1.7] - 2013-04-06
### Added
- Allow for regex replacement in from/to redirects
- Slovak translation. Thanks [Branco Radenovich](http://webhostinggeeks.com/blog)

### Changed
- Return `redirect_from` on `get_permalink`. Thanks [@simonwheatley](https://github.com/simonwheatley)

### Fixed
- Notice in `filter_admin_title`

## [1.6] - 2012-12-11
### Added
- Bulk delete redirects from the Manage Redirects screen
- wp-cli coverage including subcommands for creating, deleting, and listing redirects, and importing `.htaccess` files

## [1.5] - 2012-11-07
### Added
- Regular expressions allowed in redirects
- New filter `srm_registered_redirects` allows you to conditionally unset redirects based on context, user permissions, etc. Thanks [@jtsternberg](https://github.com/jtsternberg) for the pull request.

## [1.4.2] - 2012-10-17
### Changed
- Disable redirect loop checking by default. You can filter `srm_check_for_possible_redirect_loops` to enable it.

### Fixed
- Only return published redirects in `update_redirect_cache`.

## [1.4.1] - 2012-10-11
### Fixed
- Refresh cache after `create_redirect` call
- Refresh cache after `save_post` is called
- Chop off "pre-WP" path from requested path. This allows the plugin to work on WP installations in sub-directories.

## [1.4] - 2012-10-09
### Added
- Use the `*` wildcard at the end of your match value to configure a wildcard redirect. Use the same symbol at the end of your redirect to value in order to have the matched value be appended to the end of the redirect. Thanks [@prettyboymp](https://github.com/prettyboymp) for the pull request
- Include an informational `X-Safe-Redirect-Manager` header when a redirect occurs. Thanks [@simonwheatley](https://github.com/simonwheatley) for the pull request

### Changed
- Default request-matching behavior to be case-insensitive. This can be modified using the `srm_case_insensitive_redirects` filter.

## [1.3] - 2012-09-19
### Added
- Globalize SRM class for use in `themes/plugins/scripts`
- `create_redirect` method to make importing easier

## [1.2] - 2012-09-01
### Added
- `manage_options` capability required to use redirect manager

### Changed
- Hide view switcher
- Hide privacy stuff for bulk edit

### Removed
- Checkbox column

### Fixed
- Search feature

## [1.1] - 2012-08-28
### Added
- `is_plugin_page` function

### Fixed
- `plugin_url()` used properly

## [1.0] - 2012-08-27
- Plugin released

[Unreleased]: https://github.com/10up/safe-redirect-manager/compare/trunk...develop
[1.10.0]: https://github.com/10up/safe-redirect-manager/compare/1.9.3...1.10.0
[1.9.3]: https://github.com/10up/safe-redirect-manager/compare/1.9.2...1.9.3
[1.9.2]: https://github.com/10up/safe-redirect-manager/compare/1.9.1...1.9.2
[1.9.1]: https://github.com/10up/safe-redirect-manager/compare/1.9...1.9.1
[1.9]: https://github.com/10up/safe-redirect-manager/compare/93ffb3a...1.9
[1.8]: https://github.com/10up/safe-redirect-manager/compare/1.7.8...93ffb3a
[1.7.8]: https://github.com/10up/safe-redirect-manager/compare/1.7.7...1.7.8
[1.7.7]: https://github.com/10up/safe-redirect-manager/compare/1.7.6...1.7.7
[1.7.6]: https://github.com/10up/safe-redirect-manager/compare/1.7.5...1.7.6
[1.7.5]: https://github.com/10up/safe-redirect-manager/compare/1.7.4...1.7.5
[1.7.4]: https://github.com/10up/safe-redirect-manager/compare/1.7.3...1.7.4
[1.7.3]: https://github.com/10up/safe-redirect-manager/compare/1.7.2...1.7.3
[1.7.2]: https://github.com/10up/safe-redirect-manager/compare/a74c801...1.7.2
[1.7.1]: https://github.com/10up/safe-redirect-manager/compare/43f5f81...a74c801
[1.7]: https://github.com/10up/safe-redirect-manager/compare/1.6...43f5f81
[1.6]: https://github.com/10up/safe-redirect-manager/compare/24e7900...1.6
[1.5]: https://github.com/10up/safe-redirect-manager/compare/9ae98d9...24e7900
[1.4.2]: https://github.com/10up/safe-redirect-manager/compare/817dc34...9ae98d9
[1.4.1]: https://github.com/10up/safe-redirect-manager/compare/1.4...817dc34
[1.4]: https://github.com/10up/safe-redirect-manager/compare/862eafe...1.4
[1.3]: https://github.com/10up/safe-redirect-manager/compare/4f5349b...862eafe
[1.2]: https://github.com/10up/safe-redirect-manager/compare/4286e15...4f5349b
[1.1]: https://github.com/10up/safe-redirect-manager/compare/7d15b16...4286e15
[1.0]: https://github.com/10up/safe-redirect-manager/commit/7d15b16

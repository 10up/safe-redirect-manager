# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased] - TBD

## [2.1.1] - 2024-01-08
### Added
- Support for the WordPress.org plugin preview (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#357](https://github.com/10up/safe-redirect-manager/pull/357)).
- `phpcs:ignore` on the now safe `ini_set()` (props [@philipjohn](https://github.com/philipjohn), [@ravinderk](https://github.com/ravinderk) via [#355](https://github.com/10up/safe-redirect-manager/pull/355)).

### Changed
- Bump `Cypress` from 13.0.0 to 13.1.0, `@10up/cypress-wp-utils` from 0.1.0 to 0.2.0, `@wordpress/env` from 5.3.0 to 8.7.0, `cypress-mochawesome-reporter` from 3.4.0 to 3.5.1 and `node-wp-i18n` from 1.2.5 to 1.2.7 (props [@iamdharmesh](https://github.com/iamdharmesh), [@ravinderk](https://github.com/ravinderk) via [#349](https://github.com/10up/safe-redirect-manager/pull/349)).
- Bump WordPress "tested up to" version 6.4 (props [@qasumitbagthariya](https://github.com/qasumitbagthariya), [@jeffpaul](https://github.com/jeffpaul) via [#353](https://github.com/10up/safe-redirect-manager/pull/353), [#354](https://github.com/10up/safe-redirect-manager/pull/354)).
- Validate and sanitize a superglobal before using it (props [@jspellman814](https://github.com/jspellman814), [@ravinderk](https://github.com/ravinderk) via [#356](https://github.com/10up/safe-redirect-manager/pull/356)).

### Fixed
- Ensure text can be translated (props [@alexclassroom](https://github.com/alexclassroom), [@iamdharmesh](https://github.com/iamdharmesh) via [#351](https://github.com/10up/safe-redirect-manager/pull/351)).

## [2.1.0] - 2023-09-07
### Added
- WP-CLI command `export` to export redirects into a CSV file (props [@zamanq](https://github.com/zamanq), [@jeffpaul](https://github.com/jeffpaul), [@Sidsector9](https://github.com/Sidsector9) via [#299](https://github.com/10up/safe-redirect-manager/pull/299)).
- Admin settings to set custom redirect protocol per route (props [@tlovett1](https://github.com/tlovett1), [@danielbachhuber](https://github.com/danielbachhuber), [@benoitchantre](https://github.com/benoitchantre), [@jayedul](https://github.com/jayedul), [@Sidsector9](https://github.com/Sidsector9) via [#301](https://github.com/10up/safe-redirect-manager/pull/301)).
- Autocomplete to the "Redirect To" field (props [@tlovett1](https://github.com/tlovett1), [@bmarshall511](https://github.com/bmarshall511), [@ravinderk](https://github.com/ravinderk) via [#325](https://github.com/10up/safe-redirect-manager/pull/325)).
- Allow existing import records to be updated instead of skipped (props [@retlehs](https://github.com/retlehs), [@bmarshall511](https://github.com/bmarshall511), [@dkotter](https://github.com/dkotter) via [#329](https://github.com/10up/safe-redirect-manager/pull/329)).
- Check for minimum required PHP version before loading the plugin (props [@kmgalanakis](https://github.com/kmgalanakis), [@iamdharmesh](https://github.com/iamdharmesh), [@Sidsector9](https://github.com/Sidsector9), [@vikrampm1](https://github.com/vikrampm1), [@dkotter](https://github.com/dkotter) via [#340](https://github.com/10up/safe-redirect-manager/pull/340)).
- Repo Automator GitHub Action (props [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul) via [#348](https://github.com/10up/safe-redirect-manager/pull/348)).

### Changed
- Bump [Support Level](https://github.com/10up/safe-redirect-manager#support-level) from `Active` to `Stable` (props [@jeffpaul](https://github.com/jeffpaul), [@Sidsector9](https://github.com/Sidsector9), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#303](https://github.com/10up/safe-redirect-manager/pull/303)).
- Bump WordPress "tested up to" version 6.3 (props [@github-actions](https://github.com/apps/github-actions), [@kmgalanakis](https://github.com/kmgalanakis), [@iamdharmesh](https://github.com/iamdharmesh) via [#339](https://github.com/10up/safe-redirect-manager/pull/339)).
- Reduced the number of queries in half by removing `post_status` property from redirects data (props [@tlovett1](https://github.com/tlovett1), [@pdclark](https://github.com/pdclark), [@mehul0810](https://github.com/mehul0810), [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter), [@nateconley](https://github.com/nateconley), [@sksaju](https://github.com/sksaju), [@ravinderk](https://github.com/ravinderk) via [#326](https://github.com/10up/safe-redirect-manager/pull/326)).
- Rule editor always shows raw redirect target (props [@tbenyon](https://github.com/tbenyon), [@jeffpaul](https://github.com/jeffpaul), [@trainoasis](https://github.com/trainoasis), [@jayedul](https://github.com/jayedul), [@dkotter](https://github.com/dkotter) via [#330](https://github.com/10up/safe-redirect-manager/pull/330), [#333](https://github.com/10up/safe-redirect-manager/pull/333)).
- Include links to redirects that cause redirect loops/chains in the warning (props [@joshbetz](https://github.com/joshbetz), [@tlovett1](https://github.com/tlovett1), [@Sidsector9](https://github.com/Sidsector9), [@ravinderk](https://github.com/ravinderk) via [#341](https://github.com/10up/safe-redirect-manager/pull/341)).
- Set the default value for the `srm_check_for_possible_redirect_loops` filter to `true` (props [@joshbetz](https://github.com/joshbetz), [@tlovett1](https://github.com/tlovett1), [@Sidsector9](https://github.com/Sidsector9), [@ravinderk](https://github.com/ravinderk) via [#341](https://github.com/10up/safe-redirect-manager/pull/341)).
- Added a back link to the admin notices when a post is saved or updated (props [@szepeviktor](https://github.com/szepeviktor), [@tlovett1](https://github.com/tlovett1), [@bmarshall511](https://github.com/bmarshall511), [@iamdharmesh](https://github.com/iamdharmesh) via [#328](https://github.com/10up/safe-redirect-manager/pull/328)).
- Missing Docblocks for filter hooks (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@jayedul](https://github.com/jayedul), [@dkotter](https://github.com/dkotter), [@faisal-alvi](https://github.com/faisal-alvi), [@iamdharmesh](https://github.com/iamdharmesh) via [#313](https://github.com/10up/safe-redirect-manager/pull/313)).

### Fixed
- `auto_detect_line_endings` deprecation warning in PHP 8.1 and above (props [@dhewer](https://github.com/dhewer), [@jayedul](https://github.com/jayedul), [@Sidsector9](https://github.com/Sidsector9), [@ravinderk](https://github.com/ravinderk) via [#327](https://github.com/10up/safe-redirect-manager/pull/327)).
- Only show public post types in the autocomplete "Redirect To" field (props [@ravinderk](https://github.com/ravinderk), [@bmarshall511](https://github.com/bmarshall511), [@dkotter](https://github.com/dkotter) via [#332](https://github.com/10up/safe-redirect-manager/pull/332)).
- Slow performance during redirect chain/loop detection (props [@tlovett1](https://github.com/tlovett1), [@danielbachhuber](https://github.com/danielbachhuber), [@Sidsector9](https://github.com/Sidsector9), [@ravinderk](https://github.com/ravinderk), [@iamdharmesh](https://github.com/iamdharmesh) via [#336](https://github.com/10up/safe-redirect-manager/pull/336)).

### Security
- Bump `semver` from 7.3.8 to 7.5.4 (props [@dependabot](https://github.com/apps/dependabot), [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9) via [#334](https://github.com/10up/safe-redirect-manager/pull/334), [#342](https://github.com/10up/safe-redirect-manager/pull/342)).
- Bump `tough-cookie` from 2.5.0 to 4.1.3 (props [@dependabot](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi) via [#337](https://github.com/10up/safe-redirect-manager/pull/337)).
- Bump `@cypress/request` from 2.88.10 to 3.0.0 (props [@dependabot](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi), [@ravinderk](https://github.com/ravinderk) via [#337](https://github.com/10up/safe-redirect-manager/pull/337), [#343](https://github.com/10up/safe-redirect-manager/pull/343)).
- Bump `cypress` from 11.2.0 to 13.0.0 (props [@dependabot](https://github.com/apps/dependabot), [@ravinderk](https://github.com/ravinderk) via [#343](https://github.com/10up/safe-redirect-manager/pull/343)).

## [2.0.1] - 2023-06-01
### Fixed
- Ensure our E2E tests run (props [@Sidsector9](https://github.com/Sidsector9), [@iamdharmesh](https://github.com/iamdharmesh) via [#318](https://github.com/10up/safe-redirect-manager/pull/318)).
- Ensure the `message` array key exists before we use it (props [@dkotter](https://github.com/dkotter), [@ocean90](https://github.com/ocean90), [@vena](https://github.com/vena), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#319](https://github.com/10up/safe-redirect-manager/pull/319)).
- Resolve deprecation notices in PHP 8.1 and later (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@dkotter](https://github.com/dkotter) via [#322](https://github.com/10up/safe-redirect-manager/pull/322)).

## [2.0.0] - 2023-05-31
**Note that this version bumps the PHP minimum from 5.6 to 7.4 and the WordPress minimum from 4.6 to 5.7.**

### Added
- Handling of 403, 404, and 410 status codes (props [@nateconley](https://github.com/nateconley), [@cadic](https://github.com/cadic), [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9), [@helen](https://github.com/helen), [@dinhtungdu](https://github.com/dinhtungdu), [@dustinrue](https://github.com/dustinrue), [@ciprianimike](https://github.com/ciprianimike), [@jeffpaul](https://github.com/jeffpaul), [@aosmichenko](https://github.com/aosmichenko), [@okadots](https://github.com/okadots) via [#300](https://github.com/10up/safe-redirect-manager/pull/300)).
- Support for adding notes when importing redirects (props [@barryceelen](https://github.com/barryceelen), [@cadic](https://github.com/cadic), [@jayedul](https://github.com/jayedul) via [#277](https://github.com/10up/safe-redirect-manager/pull/277)).
- "Build release zip" GitHub Action (props [@iamdharmesh](https://github.com/iamdharmesh), [@cadic](https://github.com/cadic), [@faisal-alvi](https://github.com/faisal-alvi) via [#293](https://github.com/10up/safe-redirect-manager/pull/293)).
- GitHub Action summary added Cypress test report (props [@jayedul](https://github.com/jayedul), [@peterwilsoncc](https://github.com/peterwilsoncc), [@iamdharmesh](https://github.com/iamdharmesh) via [#314](https://github.com/10up/safe-redirect-manager/pull/314)).
- Dependency review Github action (props [@jeffpaul](https://github.com/jeffpaul), [@Sidsector9](https://github.com/Sidsector9) via [#317](https://github.com/10up/safe-redirect-manager/pull/317)).

### Changed
- Bumped PHP minimum supported version from 5.6 to 7.4 (props [@csloisel](https://github.com/csloisel), [@dkotter](https://github.com/dkotter), [@vikrampm1](https://github.com/vikrampm1) via [#289](https://github.com/10up/safe-redirect-manager/pull/289)).
- Bumped WordPress minimum supported version from 4.6 to 5.7 (props [@csloisel](https://github.com/csloisel), [@dkotter](https://github.com/dkotter), [@vikrampm1](https://github.com/vikrampm1) via [#289](https://github.com/10up/safe-redirect-manager/pull/289)).
- Bumped PHPCS compat script to use 7.4 as test version (props [@csloisel](https://github.com/csloisel), [@dkotter](https://github.com/dkotter), [@vikrampm1](https://github.com/vikrampm1) via [#289](https://github.com/10up/safe-redirect-manager/pull/289)).
- Bumped WordPress "test up to" version 6.2 (props [@csloisel](https://github.com/csloisel), [@jayedul](https://github.com/jayedul) via [#290](https://github.com/10up/safe-redirect-manager/pull/290), [#310](https://github.com/10up/safe-redirect-manager/pull/310)).
- Cypress integration migrated from 9.5.2 to 11.2.0 (props [@jayedul](https://github.com/jayedul), [@cadic](https://github.com/cadic), [@Sidsector9](https://github.com/Sidsector9), [@iamdharmesh](https://github.com/iamdharmesh) via [#295](https://github.com/10up/safe-redirect-manager/pull/295)).
- Run E2E tests on the ZIP generated by "Build release zip" GitHub Action (props [@jayedul](https://github.com/jayedul), [@cadic](https://github.com/cadic), [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#306](https://github.com/10up/safe-redirect-manager/pull/306), [#311](https://github.com/10up/safe-redirect-manager/pull/311)).
- Status code dropdown is now sorted numerically (props [@norcross](https://github.com/norcross), [@Sidsector9](https://github.com/Sidsector9) via [#307](https://github.com/10up/safe-redirect-manager/pull/307)).

### Removed
- PHP versions < 7.4 from phpunit tests (props [@csloisel](https://github.com/csloisel), [@dkotter](https://github.com/dkotter), [@vikrampm1](https://github.com/vikrampm1) via [#289](https://github.com/10up/safe-redirect-manager/pull/289)).

### Fixed
- Check non-active multisite directory against the main site redirects (props [@phpbits](https://github.com/phpbits), [@dinhtungdu](https://github.com/dinhtungdu), [@ciprianimike](https://github.com/ciprianimike), [@gsarig](https://github.com/gsarig), [@Sidsector9](https://github.com/Sidsector9), [@davidegreenwald](https://github.com/davidegreenwald), [@turtlepod](https://github.com/turtlepod) via [#248](https://github.com/10up/safe-redirect-manager/pull/248)).
- Regex redirects without leading `/` are buggy (props [@dhanendran](https://github.com/dhanendran), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#279](https://github.com/10up/safe-redirect-manager/pull/279)).
- Issue with `srm_additional_status_codes` filter hook (props [@Sidsector9](https://github.com/Sidsector9), [@faisal-alvi](https://github.com/faisal-alvi) via [#312](https://github.com/10up/safe-redirect-manager/pull/312)).

### Security
- Bump `got` from 10.7.0 to 11.8.5 (props [@dependabot](https://github.com/apps/dependabot), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#286](https://github.com/10up/safe-redirect-manager/pull/286)).
- Bump `@wordpress/env` from 4.9.0 to 5.3.0 (props [@dependabot](https://github.com/apps/dependabot), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#286](https://github.com/10up/safe-redirect-manager/pull/286)).
- Bump `simple-git` from 3.9.0 to 3.16.0 (props [@dependabot](https://github.com/apps/dependabot), [@Sidsector9](https://github.com/Sidsector9), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#294](https://github.com/10up/safe-redirect-manager/pull/294), [#302](https://github.com/10up/safe-redirect-manager/pull/302)).
- Bump `http-cache-semantics` from 4.1.0 to 4.1.1 (props [@dependabot](https://github.com/apps/dependabot), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#305](https://github.com/10up/safe-redirect-manager/pull/305)).

## [1.11.1] - 2022-09-28
### Added
- Indicate plugin as the source of redirects (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@Sidsector9](https://github.com/Sidsector9) via [#281](https://github.com/10up/safe-redirect-manager/pull/281)).

## [1.11.0] - 2022-06-27
### Added
- Detect duplicate rules for the same 'redirect from' value (props [@adamsilverstein](https://github.com/adamsilverstein), [@dhanendran](https://github.com/dhanendran), [@hrkhal](https://github.com/hrkhal), [@jeffpaul](https://github.com/jeffpaul), [@lukaspawlik](https://github.com/lukaspawlik), [@sanketio](https://github.com/sanketio), [@Sidsector9](https://github.com/Sidsector9) via [#171](https://github.com/10up/safe-redirect-manager/pull/171)).
- PHP 8 compatibility (props [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#264](https://github.com/10up/safe-redirect-manager/pull/264)).
- E2E Tests with Cypress (props [@iamdharmesh](https://github.com/iamdharmesh), [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter) via [#262](https://github.com/10up/safe-redirect-manager/pull/262), [#273](https://github.com/10up/safe-redirect-manager/pull/273)).
- Dependency security scanning (props [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#268](https://github.com/10up/safe-redirect-manager/pull/268)).

### Changed
- Default number of redirects in readme files (props [@grappler](https://github.com/grappler) via [#259](https://github.com/10up/safe-redirect-manager/pull/259)).
- Bump WordPress "tested up to" version 6.0 (props [@jeffpaul](https://github.com/jeffpaul), [@sudip-10up](https://github.com/sudip-10up), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#260](https://github.com/10up/safe-redirect-manager/pull/260), [#270](https://github.com/10up/safe-redirect-manager/pull/270)).

### Fixed
- Unit tests by adding `PHPUnit-Polyfills` library (props [@iamdharmesh](https://github.com/iamdharmesh), [@Sidsector9](https://github.com/Sidsector9) via [#257](https://github.com/10up/safe-redirect-manager/pull/257)).

### Security
- Bump `minimist` from 1.2.5 to 1.2.6 (props [@dependabot](https://github.com/apps/dependabot) via [#265](https://github.com/10up/safe-redirect-manager/pull/265)).

## [1.10.1] - 2021-12-16
### Added
- Formatting options to `wp safe-redirect-manager list` command (props [@dinhtungdu](https://github.com/dinhtungdu), [@TheLastCicada](https://github.com/TheLastCicada) via [#238](https://github.com/10up/safe-redirect-manager/pull/238)).

### Changed
- Increased redirect limits from 250 to 1,000 (props [@sultann](https://github.com/sultann), [@dinhtungdu](https://github.com/dinhtungdu), [@jilltilt](https://github.com/jilltilt), [@yeevy](https://github.com/yeevy) via [#242](https://github.com/10up/safe-redirect-manager/pull/242)).
- Bump WordPress version "tested up to" 5.8 (props [@jeffpaul](https://github.com/jeffpaul), [@ankitguptaindia](https://github.com/ankitguptaindia), [@phpbits](https://github.com/phpbits) via [#233](https://github.com/10up/safe-redirect-manager/pull/233), [#235](https://github.com/10up/safe-redirect-manager/pull/235), [#252](https://github.com/10up/safe-redirect-manager/pull/252)).

### Fixed
- Required parameter following optional deprecated message in PHP 8 (props [@vinkla](https://github.com/vinkla), [@dinhtungdu](https://github.com/dinhtungdu) via [#231](https://github.com/10up/safe-redirect-manager/pull/231)).
- Edge case when redirecting a URL with parameters where `$parsed_requested_path['path']` does not always exist (props [@dinhtungdu](https://github.com/dinhtungdu), [@davidmondok](https://github.com/davidmondok) via [#246](https://github.com/10up/safe-redirect-manager/pull/246), [#247](https://github.com/10up/safe-redirect-manager/pull/247)).
- Formatting fix to prevent npm install error (props [@phpbits](https://github.com/phpbits) via [#249](https://github.com/10up/safe-redirect-manager/pull/249)).

### Security
- Bump `minimist` from 0.0.8 to 1.2.5 (props [@dependabot](https://github.com/dependabot) via [#250](https://github.com/10up/safe-redirect-manager/pull/250)).
- Bump `lodash` from 4.17.19 to 4.17.21 (props [@dependabot](https://github.com/dependabot) via [#251](https://github.com/10up/safe-redirect-manager/pull/251)).

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
[2.1.1]: https://github.com/10up/safe-redirect-manager/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/10up/safe-redirect-manager/compare/2.0.1...2.1.0
[2.0.1]: https://github.com/10up/safe-redirect-manager/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/10up/safe-redirect-manager/compare/1.11.1...2.0.0
[1.11.1]: https://github.com/10up/safe-redirect-manager/compare/1.11.0...1.11.1
[1.11.0]: https://github.com/10up/safe-redirect-manager/compare/1.10.1...1.11.0
[1.10.1]: https://github.com/10up/safe-redirect-manager/compare/1.10.0...1.10.1
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

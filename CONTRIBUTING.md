# Contributing and Maintaining

First, thank you for taking the time to contribute!

The following is a set of guidelines for contributors as well as information and instructions around our maintenance process.  The two are closely tied together in terms of how we all work together and set expectations, so while you may not need to know everything in here to submit an issue or pull request, it's best to keep them in the same document.

## Ways to contribute

Contributing isn't just writing code - it's anything that improves the project.  All contributions are managed right here on GitHub.  Here are some ways you can help:

### Reporting bugs

If you're running into an issue, please take a look through [existing issues](/issues) and [open a new one](/issues/new) if needed.  If you're able, include steps to reproduce, environment information, and screenshots/screencasts as relevant.

### Suggesting enhancements

New features and enhancements are also managed via [issues](/issues).

### Pull requests

Pull requests represent a proposed solution to a specified problem.  They should always reference an issue that describes the problem and contains discussion about the problem itself.  Discussion on pull requests should be limited to the pull request itself, i.e. code review.

For more on how 10up writes and manages code, check out our [10up Engineering Best Practices](https://10up.github.io/Engineering-Best-Practices/).

## Workflow

The `develop` branch is the development branch which means it contains the next version to be released.  `stable` contains the current latest release and `master` contains the corresponding stable development version.  Always work on the `develop` branch and open up PRs against `develop`.

## Release instructions

1. Starting from `develop`, cut a release branch named `release/X.Y.Z` for your changes.
2. Version bump: Bump the version number in `safe-redirect-manager.php` and `readme.txt` if it does not already reflect the version being released.
3. Changelog: Add/update the changelog in both `readme.txt` and `CHANGELOG.md`.
4. Check to be sure any new files/paths that are unnecessary in the production version are included in `.distignore`.
5. Readme updates: Make any other readme changes as necessary.  `CHANGELOG.md` and `README.md` are geared toward GitHub and `readme.txt` contains WordPress.org-specific content.  The two are slightly different.
6. Merge: Make a non-fast-forward merge from `develop` to `master` (`git checkout master && git merge --no-ff develop`).
7. Release: Create a [new release](https://github.com/10up/safe-redirect-manager/releases/new), naming the tag and the release with the new version number. Paste the changelog from `CHANGELOG.md` into the body of the release and include a link to the closed issues on the milestone (e.g. `https://github.com/10up/safe-redirect-manager/milestone/2?closed=1`).  Close the milestone.
8. SVN: Wait for the [GitHub Action](https://github.com/10up/safe-redirect-manager/actions) to finish deploying to the WordPress.org repository.  If all goes well, users with SVN commit access for that plugin will receive an emailed diff of changes.
9. Check WordPress.org: Ensure that the changes are live on [https://wordpress.org/plugins/safe-redirect-manager/](https://wordpress.org/plugins/safe-redirect-manager/). This may take a few minutes.
10. Edit the [X.Y.Z milestone](https://github.com/10up/safe-redirect-manager/milestone/#) with release date (in the `Due date (optional)` field) and link to GitHub release (in the `Description` field), then close `X.Y.Z` milestone.
11. If any open issues or PRs which were milestoned for `X.Y.Z` do not make it into the release, update their milestone to `X.Y.Z+1`, `X.Y+1.0`, `X+1.0.0` or `Future Release`.

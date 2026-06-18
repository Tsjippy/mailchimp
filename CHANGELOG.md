# Changelog
## [Unreleased] - yyyy-mm-dd

### Added

### Changed

### Fixed

### Updated

## [10.2.0] - 2026-06-18


### Changed
- hook and filter name update
- prefix all hooks with plugin name

## [10.1.9] - 2026-06-15


## [10.1.8] - 2026-06-15


## [10.1.7] - 2026-06-15


## [10.1.6] - 2026-06-15


## [10.1.5] - 2026-06-13


### Changed
- html parsing

### Fixed
- shared code loader
- activation hook
- use correct shortcodes on auto created pages

## [10.1.4] - 2026-06-11


### Added
- user, post and rest_meta prefixing

### Changed
- prefixed post metas and shortcodes

### Fixed
- prefix meta_query

## [10.1.3] - 2026-06-09


### Added
- shared functionality loader

### Changed
- comply to coding standards
- code layout
- _ to -
- namespaced all constants
- sanitize all posts and get vars

### Fixed
- spacing problem
- foreach error
- space before dot bug
- error in mailchimp lib

## [10.1.2] - 2026-06-03


### Added
- echo escaping

### Changed
- gmdate vs date

## [10.1.1] - 2026-06-01


### Changed
- merged hooks.md into readme.md

## [10.1.0] - 2026-06-01


### Changed
- loading libraries is now done in shared-functionality plugin

## [10.0.9] - 2026-05-30


### Changed
- do not store get_plugin_data in global variable

## [10.0.8] - 2026-05-29


### Added
- wp_unslash

## [10.0.7] - 2026-05-17


### Added
- deactivation hook

## [10.0.6] - 2026-05-14


### Changed
- date( to gmdate(

## [10.0.5] - 2026-05-12


### Changed
- permission callback for rest api

## [10.0.4] - 2026-05-11


## [10.0.3] - 2026-05-11


### Changed
- implemented new verifyNonce function
- moved css to css file

## [10.0.2] - 2026-05-04


## [10.0.1] - 2026-05-03


### Changed
- removed the redirection at activation as it is done by the share plugin
- use shared github workflows

## [10.0.0] - 2026-05-01


### Added
- redirection to settings page on plugin activation

### Changed
- implemented wp_get_environment_type(
- module to plugin  
- lib updates
- exclude .vscode from releases
- updated github workflow versions

## [8.2.8] - 2026-01-30


### Added
- mailchimp indicator to the content hook

### Changed
- composer updated

## [8.2.7] - 2025-11-21


### Added
- support for Local

## [8.2.6] - 2025-11-04


### Changed
- clearer data attributes

## [8.2.5] - 2025-10-30


### Changed
- use new family class

## [8.2.4] - 2025-10-13


### Changed
- classnames

### Fixed
- issue with unnecesary mailchimp post creation
- bugs

## [8.2.3] - 2025-09-22


### Changed
- mailchimp campaign in iframe

## [8.2.2] - 2025-08-13


### Changed
- redirect *|ARCHIVE|* keyword to post url

### Fixed
- multiple campaign ids per post

## [8.2.1] - 2025-08-12


### Added
- error message when nor from address set

### Changed
- show all mailchimp fields if mailchimp id is set

## [8.2.0] - 2025-08-08


### Fixed
- missing brackets in name

## [8.1.9] - 2025-05-08


### Fixed
- send mainchimp

## [8.1.8] - 2025-03-05


### Fixed
- bug in segment ids

## [8.1.7] - 2025-02-13


### Changed
- module hooks now include module slug

## [8.1.6] - 2025-02-11


### Changed
- sim_module_updated filter to new format
- use site date and time format

## [8.1.5] - 2025-02-09


### Fixed
- issue when campaign does not exist anymore

## [8.1.4] - 2025-02-06


### Added
- campaigns overview
- campaign delete form

### Fixed
- tags display
- block

## [8.1.2] - 2025-02-04


### Added
- async sending of mailchimp campaigns

## [8.1.1] - 2025-02-04


### Fixed
- mailchimp block segment ids not saved

## [8.1.0] - 2025-02-03


### Added
- audience overview

### Fixed
- add or remove tags

## [8.0.9] - 2025-01-17


### Fixed
- url problems

## [8.0.8] - 2025-01-17


### Added
- video preview in mails

## [8.0.7] - 2024-12-13


### Fixed
- e-mail send display
- mailing from backend

## [8.0.6] - 2024-11-20


## [8.0.5] - 2024-10-17


### Fixed
- bug in autoloader

## [8.0.4] - 2024-10-17


### Added
- link processing & autoconvert links to video

### Updated
- readme

## [8.0.3] - 2024-10-11


### Changed
- redering of asset urls

## [8.0.2] - 2024-10-09


### Added
- show direct url to mailchimp campaign when editing a mailchimp post

## [8.0.1] - 2024-10-07


### Changed
- updated deps
- updated hooks

## [8.0.0] - 2024-10-04


## [8.0.0] - 2024-10-03

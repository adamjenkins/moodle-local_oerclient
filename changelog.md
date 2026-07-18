# Changelog

All notable changes to this project are documented in this file, in
[Keep a Changelog](https://keepachangelog.com/) format.

## [0.1.0] - 2026-07-18

### Added

- "Share to OER Exchange" secondary-nav link (Hooks API) → share wizard
  (course or activity, license, metadata).
- `share_upload_task` adhoc task: sanitized backup (`users=false`) + upload
  + publish to the Exchange.
- Site registration (`register.php`) and personal account-linking handshake
  (`connect_callback.php`).
- Catalogue browse (`browse.php`) and resource preview
  (`resource_preview.php`) with structure preview + required-plugin
  disclosure against locally installed plugins.
- Synchronous import via `restore_controller` + post-import localization
  checklist.
- GDPR privacy provider.

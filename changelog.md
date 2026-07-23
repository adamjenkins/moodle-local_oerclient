# Changelog

All notable changes to this project are documented in this file, in
[Keep a Changelog](https://keepachangelog.com/) format.

## [0.1.1] - 2026-07-23

### Added

- Share status page reports the resource's live state on the Exchange
  (status, first published, last updated, visible/hidden, downloads,
  imports) via the new `local_oerexchange_get_share_status` service, plus an
  **Update the shared copy** button that re-uploads the course as it stands
  now. The catalogue entry, its link, its reviews and its first-published
  date are all preserved.
- "View on the Exchange" links on the browse cards and the resource preview.
  Neither page previously offered any route to the canonical public page.
- Japanese translations for `createdby`, `downloadbutton` and `typedata`.

### Changed

- A resource the Exchange refuses (hidden or deleted by its author) now shows
  "no longer available on the Exchange" rather than the far side's raw
  exception text.

### Fixed

- **Sharing was broken for ordinary teachers**, working only for admins and
  managers. `run_backup()` forced the backup's `users` setting to false
  unconditionally, and `base_setting::set_value()` throws for any user
  lacking `moodle/backup:userinfo` — which `editingteacher` does not have by
  default — even when setting it to the value it is already locked to.
- The fix for that was fail-open at the other end: an admin can lock
  `backup_general_users` **on** in the site backup defaults, leaving it true
  and locked for anyone who does hold `backup:userinfo`, which would have
  published a backup full of real user data. Sharing now refuses instead.
- "Update the shared copy" would have created a duplicate catalogue entry:
  `find_existing_resource_id()` only matched *other* shares still marked
  published, and updating requeues the share with its status reset.
- Updating no longer fails with a meaningless core exception when the source
  course or activity has been deleted; the button is replaced by an
  explanation, and the published copy is left alone.

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

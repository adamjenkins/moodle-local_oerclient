# Changelog

All notable changes to this project are documented in this file, in
[Keep a Changelog](https://keepachangelog.com/) format.

## [1.0.1] - 2026-07-29

### Changed

- The camp release-publishing workflow now uses the registry's current
  tokenless template (OIDC trusted publishing, camp-tools v0.2.35). The
  previous template pinned camp-tools v0.2.25, whose index-entry schema
  predates the registry's `source-repo-id` field, so publication of v1.0.0
  could not succeed. No change to the plugin itself.

## [1.0.0] - 2026-07-29

First stable release. `$plugin->maturity` is now `MATURITY_STABLE`.

### Added

- Cover-image thumbnails on the catalogue: `browse.php` leads each card with
  the resource's cover as served by the Exchange, and `resource_preview.php`
  shows the same cover beside the structure preview.
- `local_oerclient\local\cover_image`, with a neutral default panel of the
  same size for resources that have no cover, so cards stay aligned.

### Security

- Every cover-image URL is passed through `clean_param(..., PARAM_URL)` before
  it reaches an `src`. These URLs arrive over the network from the Exchange,
  and `html_writer` only escapes attributes — it does not vet schemes. Same
  distrust already applied to Exchange-supplied download and profile URLs.

### Fixed

- `share_status.php` reports an upload the Exchange rejected, using the
  Exchange's own reason (`versionstatus`/`versionerror` from
  `local_oerexchange_get_share_status`). The Exchange acknowledges a publish
  before it validates the file, so a share could sit here marked published
  while the Exchange had refused it, with the reason readable only on the
  Exchange's moderation page — which a teacher on this site cannot see.

## [0.1.3] - 2026-07-27

### Security

- TLS verification and core's outbound-request security guard are ON by
  default for every Exchange connection. Previous releases shipped
  `verify => false` + `ignoresecurity => true` unconditionally, so all
  site/personal tokens travelled over unverifiable TLS on any install; the
  development-harness case now opts in via a new default-off
  `acceptinvalidcerts` setting whose description says exactly what it
  gives up.
- `import_manager::download()` refuses download URLs that are not on the
  configured Exchange origin (scheme/host/port) — the URL arrives inside
  the Exchange's own response and could otherwise point this server at
  internal-network hosts.
- Creator profile URLs and data-resource download URLs received from the
  Exchange are cleaned with `PARAM_URL` before becoming links, closing a
  `javascript:` href vector open to a malicious/compromised Exchange.
- Guests are refused on browse, preview and the account-linking callback
  (a guest "personal" link row would be shared by every guest session),
  and the one-time link code is no longer echoed into the page URL.

### Fixed

- Privacy provider: the Exchange is now declared with
  `add_external_location_link()` (it is an external system, not a Moodle
  subsystem — the old declaration was a dead no-op with an empty field
  list), and the metadata/export now cover every personal-data column of
  all three tables (summaries, tags, course ids, error messages, import
  records).
- `$plugin->requires` corrected from Moodle 4.5 to 5.0 (2025041400): the
  plugin depends on `core\navigation\navigation_node`, which only exists
  since 5.0, so the old floor permitted installs that fatal on every
  course page.
- `share.php` no longer risks a PHP error on a stale cmid in its render
  path; `register.php` rejects a contact address that email-cleaning
  reduced to empty; `exchange_client::call()` returns an empty array (not
  a TypeError) on a scalar JSON response; the backup controller is
  destroyed on every exit path of `run_backup()`.

### Changed

- Downloads stream to disk (`sink`) instead of buffering whole `.mbz`
  files in memory; the import-result checklist parameter is typed
  `PARAM_BASE64`; Exchange transport errors are translatable strings;
  share/register/browse form fields gained proper label associations; the
  schema declares foreign keys (user/course relations) with an upgrade
  step adding the missing indexes.

## [0.1.2] - 2026-07-23

### Fixed

- **Every course-type import failed.** `import_manager::import()` called
  `create_course()` and `fulldelete()` without requiring `course/lib.php` and
  `lib/filelib.php`. PHPUnit's bootstrap loads all of core, so both were
  always defined under test and never in a real request; the `fulldelete()`
  failure inside the `finally` then masked the original error.
- **Imported courses landed visible to students**, despite being created with
  `visible => 0` and a post-import checklist promising otherwise — a course
  restore writes the backup's own course settings over the target's.
  Visibility is re-asserted after the restore, guarded to courses the import
  itself created, so importing an activity into a pre-existing course never
  alters that course's visibility. The regression test asserts the state
  after `restore_into()`, since the state before it was the misleading one.

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

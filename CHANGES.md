# Release notes — 1.0.5

> **Draft.** More work is going into this release. Before tagging: remove this
> note, add the remaining entries, set the date on the `[1.0.5]` heading in
> `changelog.md`, and bump `$plugin->version` if any code changed after
> `2026080200`.

## The share status page keeps up with the share

Sharing a course to the Exchange happens in the background: your site builds a
sanitized backup, uploads it, and records the result. The status page used to
show whichever of those stages was current at the moment you opened it, and
gave no sign that it would ever say anything different — so the only way to
find out how a share had gone was to keep pressing reload.

It now shows the stage as a progress indicator — queued, building a sanitized
backup, uploading, published — and advances it in place as the work happens. As
soon as the share finishes, the page shows the outcome by itself: the link to
the resource on the Exchange, or the reason it failed.

The indicator counts stages rather than bytes, deliberately. Your browser never
uploads anything here — the backup is built and sent by your Moodle site — so
there is no transfer to measure, and a percentage would be invented.

## Under the hood

- This plugin's first `db/services.php`, declaring the AJAX-only
  `local_oerclient_get_share_state`. It reads one row of the caller's own share
  and makes no call to the Exchange, so a teacher watching the page does not
  become a repeating request against another institution's server.
- New AMD module `local_oerclient/share_status`.
- The status page's own rules are unchanged: you see your own shares, and an
  administrator can see any of them.

## Checks run for this release

`scripts/verify-gates` (proving each gate fires on known-bad input) followed by
`scripts/phpcs-ci` — clean; `local_moodlecheck` — clean (docblock/signature
consistency only); PHPUnit — 88 tests, 201 assertions, all passing. The stage
indicator was verified in a browser against a live site, advancing through the
stages and revealing the outcome without a reload.

# Release notes — 1.0.0

The first stable release. The plugin is declared `MATURITY_STABLE`: the share
wizard, the registration and account-linking handshake, browsing, importing via
`restore_controller`, and the share-status/update path have all been exercised
end to end against a real Exchange, and their interfaces are now considered
settled.

One change lands with it. **Catalogue listings now show cover images.**
`browse.php` leads each card with the resource's cover-image thumbnail as
served by the Exchange, and `resource_preview.php` shows the same cover beside
the structure preview. A resource with no cover gets a neutral panel of the
same size, so cards stay aligned either way.

Every image URL involved arrives over the network from the Exchange and is
passed through `clean_param(..., PARAM_URL)` before it reaches an `src` — the
same distrust this plugin already applies to Exchange-supplied download and
profile URLs.

Verified before release: 47 PHPUnit tests green, phpcs and moodlecheck clean,
and a live end-to-end run confirming that sharing a course, and sharing a
single activity, both reach the Exchange carrying no student data — no
`users.xml`, no enrolments, no submissions, grades or forum posts, and no
`userid` anywhere in the uploaded backup.

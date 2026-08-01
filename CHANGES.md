# Release notes — 1.0.3

## Multilingual titles and descriptions now display correctly

On the browse and resource-preview pages, a resource whose title or summary
was written with the multilang filter showed the raw
`<span lang="en" class="multilang">…</span>` markup as visible text instead of
the language you are reading in. Those values were HTML-escaped for safety but
never passed through this site's text filters. Titles, section and activity
names in the structure preview, and creator names are all filtered now.

The resource summary additionally used to be flattened to plain text, so
nothing in it could be formatted or auto-linked. It now renders as formatted
content — through Moodle's HTML cleaner, because the text arrives over the
network from another Moodle site and is treated as untrusted.

**Site requirement:** for titles and other short strings, Moodle only runs the
multilang filter when that filter is set to apply to *content and headings*
rather than content alone (Site administration → Plugins → Filters → Manage
filters). Summaries are filtered either way.

## Security

The "import into course" menu on the resource preview page listed your
courses' names without escaping them, because Moodle's `html_writer::select()`
does not escape option labels. A course whose name contained HTML could
therefore inject markup into that page for anyone who could see the course in
that menu. Course names are now filtered and escaped like every other name on
the page.

Links inside a resource summary received from an Exchange now open in a new
tab with a `noreferrer` relationship, so a link placed by a hostile or
compromised Exchange cannot see which page on your site the visitor came from.

No database changes; no action required after upgrading beyond the usual
`admin/cli/upgrade.php`.

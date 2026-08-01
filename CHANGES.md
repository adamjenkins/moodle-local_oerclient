# Release notes — 1.0.4

## Licence codes now match the Exchange, and you can choose the style

A resource's licence code was shown in capitals here — `CC-SA-4.0` — while the
Exchange showed the same resource as `cc-sa-4.0`. This plugin was the only one
in the suite that changed the text, so the two never agreed. The code is now
displayed exactly as the Exchange sends it, on the browse and resource-preview
pages and in the Dashboard block.

A new setting, **Show licence codes in capitals**, chooses between the two
styles. It is on by default, so codes still read `CC-SA-4.0` — but the capitals
are now applied with CSS rather than by rewriting the text. That means text you
copy from a page matches what the Exchange actually holds, and a screen reader
reads the code out rather than spelling out capital letters.

If you would rather control this from your theme than with the setting, the
codes are wrapped in `.oer-licence-name`, and
`.oer-licence-name--upper { text-transform: unset; }` in your theme's Raw SCSS
overrides the plugin.

No database changes; no action required after upgrading beyond the usual
`admin/cli/upgrade.php`.

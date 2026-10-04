# Release notes — 1.0.6

Declare Moodle 5.3 support. The plugin now declares itself supported on
Moodle 5.0 through 5.3. No code changes were needed for 5.3. Its
`composer.json` now admits Moodle 5.3 too (`>=5.0 <5.4`), so the plugin can
be installed with Composer on a 5.3 site.

The distribution ZIP now includes the plugin's `tests/` directory.

No database or capability changes. No action is required after upgrading.

# Release notes — 1.0.9

Security release.

- Imported activities now arrive hidden. An activity imported into an existing course used to
  keep the visibility it had in the backup, so students could see content written on another
  site before anyone here had looked at it. The result page and the post-import checklist say
  what was hidden. HTML in the imported activities' descriptions and in page and label content is
  cleaned after the restore, in course imports as well.
- The import capability (local/oerclient:import) is now flagged as an XSS risk as well as a spam
  risk, since it brings content from other sites into courses.
- "Update the shared copy" no longer queues a second upload while one is still being prepared or
  uploaded, which could create duplicate catalogue entries.
- Deleting a user now deletes their link to the Exchange, and with it their Exchange token.
- The developer setting "Accept invalid TLS certificates" now relaxes only certificate checks.
  Allowing an Exchange on a private network address is a separate new setting, "Allow an Exchange
  on a private network address". Sites that had the old setting on get the new one turned on by the upgrade, so they
  keep reaching their Exchange.

Capability change: local/oerclient:import gains RISK_XSS. No database schema changes.

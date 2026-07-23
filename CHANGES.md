# Release notes — 0.1.1

The share status page now reports what the Exchange actually holds — first
published, last updated, visible or hidden, downloads and imports — and
offers **Update the shared copy**, which re-uploads the course as it stands
now and replaces the published file without creating a duplicate catalogue
entry.

Sharing is fixed for ordinary teachers. It previously worked only for
admins and managers, and it now refuses outright rather than publishing user
data when a site's backup defaults force user data into every backup.

Browse and preview pages link to the canonical resource page on the
Exchange, and a resource its author has hidden or deleted reports that
plainly instead of surfacing a raw error.

# Release notes — 0.1.0

Initial alpha release: share-from-course wizard (whole course or single
activity), sanitized backup + upload adhoc task, account-linking handshake,
catalogue browse, resource preview with structure/required-plugin
disclosure, synchronous import via `restore_controller`, and a post-import
localization checklist.

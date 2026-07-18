# local_oerclient

The client plugin for the **OER Exchange** platform. Install on a teacher's
own Moodle site to share courses/activities to a central Exchange
(`local_oerexchange`), browse its catalogue, and import resources with a
post-import localization checklist.

## What it does

- **Share**: a "Share to OER Exchange" link on every course's secondary
  navigation (added via the 4.4+ Hooks API) opens a wizard (whole course or
  a single activity, license, metadata). Sharing queues an adhoc task that
  builds a sanitized (`users=false`) backup and uploads it to the Exchange.
- **Identity**: one-time site registration (admin), then a per-teacher
  account-linking handshake (`connect_callback.php`) that mints a personal
  Exchange token — see `local_oerexchange`'s README for the full handshake.
- **Browse/import**: `browse.php` calls the Exchange's search API;
  `resource_preview.php` shows the structure preview and required-plugin
  disclosure before importing via `restore_controller`.

## Configuration

Site administration > Plugins > Local plugins > OER Client:

1. Set **Exchange URL**.
2. Open **Register with the Exchange**, enter a contact email, submit.
3. Once an Exchange admin approves the site, paste the emailed token into
   **Site token**.
4. Each teacher who wants to share/review links their own account via the
   "Link my Exchange account" button on `/local/oerclient/index.php`.

## Requirements

- Moodle 5.0–5.2 (`$plugin->supported`).

## License

GPL-3.0-or-later, see [LICENSE](LICENSE).

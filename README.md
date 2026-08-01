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
- **Browse/import**: `browse.php` calls the Exchange's search API and leads
  each catalogue card with the resource's cover-image thumbnail as served by
  the Exchange (a neutral panel of the same size where a resource has no
  cover, so cards stay aligned); `resource_preview.php` shows the same cover
  alongside the structure preview and required-plugin disclosure before
  importing via `restore_controller`. Every image URL the Exchange supplies
  is passed through `clean_param(..., PARAM_URL)` first — nothing a remote
  Exchange sends is trusted verbatim. Data resources
  (`type = 'data'` — glossaries, question banks, and other generic files) show
  a **Download** button instead of the import form. A course created by an
  import is **hidden from students** until you have reviewed it and made it
  visible; importing an activity into a course you already had never changes
  that course's visibility.
- **Share status**: the share status page reports what the Exchange actually
  holds for each of your shares — status, first published, last updated,
  visible or hidden, downloads and imports — and offers **Update the shared
  copy**, which re-uploads the course as it stands now and replaces the
  published file without creating a duplicate catalogue entry. A share whose
  source course or activity has since been deleted says so instead. If the
  Exchange **refused** the upload — most often because the backup contained
  user data — the page says so in the Exchange's own words, rather than
  leaving the share looking published. (Sharing from here always strips user
  data, so this is mainly a safety net for backups uploaded on the Exchange
  directly.)

## Configuration

Site administration > Plugins > Local plugins > OER Client:

1. Set **Exchange URL**.
2. Open **Register with the Exchange**, enter a contact email, submit.
3. Once an Exchange admin approves the site, paste the emailed token into
   **Site token**.
4. Each teacher who wants to share/review links their own account via the
   "Link my Exchange account" button on `/local/oerclient/index.php`.

Connections to the Exchange verify TLS certificates and respect Moodle's
outbound-request security checks. **Accept invalid TLS certificates**
(`acceptinvalidcerts`, off by default) exists only for development rigs
with self-signed certificates on private networks — never enable it on a
production site; it lets a network attacker read every token this plugin
sends.

**Show licence codes in capitals** (`uppercaselicencenames`, on by default)
chooses whether a resource's licence code reads `CC-SA-4.0` or `cc-sa-4.0`
when browsing and previewing the catalogue, and in the Dashboard block. It is
a display setting: the capitals are applied with CSS, so the licence is
stored, sent and compared exactly as received either way. A theme can override
it with `.oer-licence-name--upper { text-transform: unset; }`.

## Requirements

- Moodle 5.0–5.2 (`$plugin->supported`).

## License

GPL-3.0-or-later, see [LICENSE](LICENSE).

# OER Client — User Documentation

`local_oerclient` is installed on **your own Moodle site** and connects it to
a central OER Exchange (`local_oerexchange`, installed elsewhere). It lets
you share courses or activities to the Exchange's public catalogue, browse
what other teachers have shared, and import resources into your own site.

For architecture and implementation notes, see the platform design
documentation (`dev-docs/oer-platform/DESIGN.md` in the development
workspace) — this document is for people *using* the plugin, not developing
it.

## Contents

- [For site administrators: connecting to an Exchange](#for-site-administrators-connecting-to-an-exchange)
- [For teachers: linking your account](#for-teachers-linking-your-account)
- [For teachers: sharing a course or activity](#for-teachers-sharing-a-course-or-activity)
- [For teachers: browsing and importing](#for-teachers-browsing-and-importing)
- [The post-import localization checklist](#the-post-import-localization-checklist)
- [Privacy and data handling](#privacy-and-data-handling)
- [Troubleshooting](#troubleshooting)

## For site administrators: connecting to an Exchange

This is a one-time setup step, done once per Moodle site.

1. Open **Site administration ▸ Plugins ▸ Local plugins ▸ OER Client** and
   set **Exchange URL** to the Exchange site you want to connect to (e.g.
   `https://oer-exchange.example`).
2. Open **Site administration ▸ Plugins ▸ Local plugins ▸ OER Client ▸
   Register with the Exchange**. Enter a contact email and submit. This
   sends a registration request to the Exchange — nothing is usable yet.
3. **Wait for approval.** An administrator on the Exchange side reviews and
   approves the request (this is a manual, human step on their end — there
   is no fixed turnaround time). Once approved, a **site token** is emailed
   to the contact address you gave.
4. Paste that token into **Site token** on the same settings page. Your
   site is now connected — teachers can share and import.

Two capabilities control who can do what once connected:
`local/oerclient:share` (share a course/activity — granted to
Editing teacher and Manager by default) and `local/oerclient:import`
(import a resource — same defaults).

## For teachers: linking your account

Sharing and reviewing on the Exchange are attributed to a personal Exchange
account, separate from your login on this site (though the linking process
reuses your Exchange login/signup, which may itself be a familiar account if
you've used the Exchange directly before).

Visit `/local/oerclient/index.php` and click **Link my Exchange account**.
You'll be taken to the Exchange to log in or create an account there, then
returned here automatically — this only needs to be done once. Until you've
linked an account, sharing is unavailable (browsing and importing are not
affected).

## For teachers: sharing a course or activity

1. Open the course you want to share. **Share to OER Exchange** is not a
   top-level tab — click **More ▾** in the course's secondary navigation
   (you need editing rights in the course) and it's the last item in the
   menu that opens.
2. Fill in the wizard:
   - **Title** — prefilled from the course/activity name; edit as needed.
   - **Summary** — describe what it's for and who it's aimed at.
   - **Language**, **tags** — help other teachers find it.
   - **License** — a Creative Commons license (or public domain);
     required.
3. Click **Share it**. This is not instant — behind the scenes, your site
   builds a sanitized backup (with **no student or personal data included**,
   regardless of what the course itself contains) and uploads it to the
   Exchange. You'll land on a status page that updates as it progresses:
   *queued → building a sanitized backup → uploading to the Exchange →
   published* (or *failed*, with an error message, if something went
   wrong). Once published, a link to view it on the Exchange appears.
4. Sharing a **single activity** instead of the whole course works the same
   way: open the activity, click **More ▾** in its own secondary navigation,
   and **Share to OER Exchange** is the last item there too. The wizard's
   **Title** field prefills with the activity's own name (not the course
   name) so you can tell you're sharing the activity, not the whole course.

Re-sharing an already-shared course (after you've updated it) adds a new
version to the same catalogue entry rather than creating a duplicate.

## For teachers: browsing and importing

1. Open **Browse OER Exchange** (from `/local/oerclient/index.php` or the
   course navigation) and search the catalogue exactly as you would on the
   Exchange itself.
2. Click a resource to preview it: license, structure (sections and
   activities), and whether any required contrib plugins are already
   installed on your own site or would be missing.
3. Click **Import**:
   - **Whole course**: creates a brand-new course on your site (hidden by
     default, so you can review it before students see it), imports the
     content into it, and takes you to the localization checklist.
   - **Single activity**: choose which of your courses to import it into
     (only courses where you have import rights are listed), then it's
     added to that course.
4. Importing runs the restore synchronously — you'll see a normal Moodle
   "processing" wait, then land on the checklist page. Large or unusual
   backups can occasionally fail a Moodle restore precheck; if that
   happens you'll see an error rather than a half-imported course.

## The post-import localization checklist

Every import ends on a short checklist of things worth reviewing before you
use the resource with students — these are generic reminders, not
site-specific warnings:

- **Dates**: activity due dates and the course start date came from the
  original course and likely need updating for your own term.
- **Names and references**: check activity content for any names,
  institutions, or contact details that came with the original.
- **Visibility**: imported *courses* are hidden by default specifically so
  you can review them first — make the course visible when you're ready.
- **Grading**: check that grade items and scales match your own gradebook
  setup, especially if you use a different grading scheme than the original
  author.

## Privacy and data handling

This plugin stores: your link to your personal Exchange account (including
the token that lets it act as you there), a record of what you've shared
and its status, and a record of what you've imported and into which course.
All of this is covered by the plugin's GDPR privacy provider — use Moodle's
own **Site administration ▸ Users ▸ Privacy and policies ▸ Data requests**
flow to export or delete it.

Sharing never uploads student or other personal data — every shared backup
is built with user data explicitly excluded, and the Exchange independently
verifies this server-side before publishing (defense in depth: your site
sanitizes, the Exchange checks again).

## Troubleshooting

- **"Share to OER Exchange" link doesn't appear** — you need editing rights
  in the course, and this site must be connected to an Exchange (see
  above).
- **Share status stuck at "queued"** — this step runs on your site's
  scheduled tasks (cron); if cron isn't running regularly, sharing will be
  delayed. Contact your site administrator.
- **Share failed** — the status page shows the error. Common causes: you
  haven't linked your Exchange account yet, or the backup exceeded the
  Exchange's maximum accepted size.
- **Import button is missing or disabled** — for single activities, you
  need import rights in at least one course; if none are listed, ask your
  administrator for access to an appropriate course.
- **"Not installed — will be skipped" next to a required plugin** — that
  activity type isn't available on your site. You can still import; that
  specific activity will simply not appear in the result. Install the
  plugin first if you need it.

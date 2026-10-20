# Release notes — 1.0.8

- After an import, the result page now shows the checklist of what to adapt before students see
  the course (activity dates, names and contact details, the hidden course, grade items). It had
  never been shown: it travelled in the page address as a value that Moodle's parameter cleaning
  silently discarded.
- The "installed" and "missing" plugin badges on a resource preview are readable: the missing
  one was white on orange (1.95:1). Both now take their text colour from the theme.
- A plugin icon now appears beside the plugin on Site administration, Plugins, Plugins overview.
- The camp listing (`.camp/listing.yml`) now carries screenshots with captions. They are
  excluded from the plugin ZIP.

No database or capability changes.

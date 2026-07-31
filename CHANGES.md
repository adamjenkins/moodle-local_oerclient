# Release notes — 1.0.2

The Exchange is now the sole authority on which licences a share may use.

- The "Share to OER Exchange" form's licence field is populated from the
  list of licences the Exchange currently accepts, instead of this site's
  own licence configuration.
- A submitted licence is checked again against the Exchange's list when the
  share is queued, so a licence the Exchange no longer accepts is rejected
  even if it was offered a moment earlier.
- If the Exchange cannot be reached when the form loads, the last
  successfully confirmed list of licences is used and the teacher is told
  it may be out of date, rather than the form silently falling back to
  this site's own licences or failing outright.
- Licence shortnames (for example `CC-SA-4.0`) now display in upper case on
  the catalogue browse page and the resource preview page, matching how
  they are written everywhere else.
- A handful of displayed strings ("License" → "Licence") now use
  International English spelling, matching Moodle core's own convention for
  user-facing prose. No string keys or Japanese strings changed.

No database or capability changes. No action is required after upgrading.

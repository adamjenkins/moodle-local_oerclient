# Release notes — 0.1.2

Fixes importing, which was broken in two ways that hid each other.

Importing a course failed outright with an undefined-function error, and a
second failure inside the cleanup path replaced that error with a more
confusing one before it could be reported.

Imported courses also landed **visible to students**, despite the code
creating them hidden and the post-import checklist promising they would be
hidden until reviewed: restoring a course writes the backup's own settings
over the new course's. Visibility is now re-asserted after the restore.
Importing an activity into a course you already had never changes that
course's visibility.

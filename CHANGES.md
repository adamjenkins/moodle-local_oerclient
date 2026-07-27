# Release notes — 0.1.3

Security-review hardening of everything that crosses the network.

Connections to the Exchange now verify TLS certificates and respect
Moodle's outbound-request security checks by default. Earlier releases
disabled both unconditionally (a leftover of the development harness's
self-signed certificates), which let any network attacker read every token
this plugin sends; development rigs can opt back in with the new,
default-off, clearly-marked "Accept invalid TLS certificates" setting.
Download URLs offered by the Exchange are refused unless they point at the
configured Exchange host, profile/download links from the Exchange pass a
URL-scheme whitelist before rendering, and large downloads stream to disk
instead of transiting memory.

The privacy declaration now tells the whole truth: the Exchange is
declared as the external system it is, and every personal-data column of
the three tables (share summaries and tags, course ids, error messages,
import records) is listed and exported. Also: the installation floor is
corrected to Moodle 5.0 (a 4.5 install would fatal on every course page),
guest access is refused on the browse/preview/link pages, the one-time
link code no longer lands in the page URL, and the schema gains its
missing foreign keys and indexes.

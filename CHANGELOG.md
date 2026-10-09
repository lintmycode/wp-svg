# Changelog

## 1.0.0 — 2026-10-08

First release, as a regular plugin (`nitida/wp-svg`, activated per site) so
SVG uploads stay a per-site choice. Briefly tagged earlier the same day as the
mu-plugin `nitida/wp-mu-svg`; that tag was withdrawn before any site used it.
Replaces the hand-copied `svg-upload-support.php` (9 sites),
whose six-tag blocklist let event attributes, `javascript:` links and remote
references through. Every SVG upload and sideload is now rewritten by
enshrined/svg-sanitize, with remote references removed. Tested against script,
onload, javascript: href, remote `<use>`, a clean logo and a non-XML file.

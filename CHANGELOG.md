# Changelog

## 1.0.0 — 2026-10-08

First release. Replaces the hand-copied `svg-upload-support.php` (9 sites),
whose six-tag blocklist let event attributes, `javascript:` links and remote
references through. Every SVG upload and sideload is now rewritten by
enshrined/svg-sanitize, with remote references removed. Tested against script,
onload, javascript: href, remote `<use>`, a clean logo and a non-XML file.

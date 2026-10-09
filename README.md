# nitida/wp-svg

WordPress plugin, installed with Composer and **activated per site**, that
allows SVG uploads
and runs every one through an allowlist sanitizer
([enshrined/svg-sanitize](https://github.com/darylldoyle/svg-sanitizer), the
library WordPress VIP and Safe SVG use) before WordPress stores it.

## Why not the old `svg-upload-support.php`

That file (hand-copied to 9 sites from the `wp-docker-bedrock` starter)
refused an SVG only if it contained `<script`, `<object`, `<embed`, `<iframe`,
`<form` or `<input`. Event attributes (`<svg onload=…>`), `javascript:` links
and external `<use>` references all passed. An SVG opened by its URL runs on
the site's own origin, so anyone with `upload_files` could plant stored XSS
against an administrator. This plugin rewrites the file instead of looking for
bad words, and refuses anything it can't parse.

## Behaviour

- A regular plugin, not a mu-plugin: SVG uploads are a per-site choice.
  Active = SVG allowed and sanitized; inactive = no SVG uploads at all.
- `svg` is added to the allowed upload types only while the sanitizer class
  is present. No sanitizer, no SVG uploads.
- Browser uploads and sideloads (REST, `media_sideload_image`, imports) are
  sanitized in place before WordPress moves the file. Remote references are
  removed.
- The media library grid gets a "full" size for SVGs so tiles aren't blank.
- Nothing else. GPX or other types a site needs stay in that site's repo.

## Install (Bedrock)

```bash
composer config repositories.wp-svg vcs https://github.com/lintmycode/wp-svg.git
composer require nitida/wp-svg:^1.0
```

`composer/installers` puts it in `web/app/plugins/wp-svg/` (already ignored by
Bedrock). In the same commit **delete `svg-upload-support.php`** from
`mu-plugins/`: it adds the same mime type and keeps its weaker check running.

Activation lives in the database, so after each deploy that brings it in:

```bash
wp plugin activate wp-svg
```

Until then the site has no SVG uploads (the old mu-plugin is gone), which is
the safe failure.

SVGs already in the library were never sanitized. They are usually logos
uploaded by staff, but on a site with many uploaders, re-upload or check them.

## Verify

Upload this as `test.svg` through Media → Add New. It should upload, and the
stored file should have no `onload`:

```xml
<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><rect width="10" height="10"/></svg>
```

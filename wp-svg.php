<?php
/**
 * Plugin Name: nitida — SVG Uploads
 * Description: Allows SVG uploads to the media library and sanitizes every one, so an uploaded SVG cannot carry script.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: nitida
 * License: MIT
 *
 * A regular plugin, not a mu-plugin, on purpose: SVG uploads are a per-site choice, so a
 * site turns them on by activating this and off by deactivating it. Installed as a
 * Composer package (type wordpress-plugin) into web/app/plugins/wp-svg/.
 * enshrined/svg-sanitize comes in through the site's own composer install and Bedrock's
 * root autoloader.
 *
 * Replaces the hand-copied svg-upload-support.php (on 9 sites as of 2026-10-08). That file
 * rejected an SVG only if it contained one of six tag names (`<script`, `<iframe`...), so
 * `<svg onload="...">`, `<a href="javascript:...">` or a `<use>` pointing at another file all
 * went through. An SVG opened by URL runs its script on the site's own origin, so any
 * account with upload_files (editors, authors, shop managers) could plant stored XSS
 * against an administrator. Here the file is rewritten by an allowlist sanitizer before
 * WordPress moves it into uploads; if it can't be parsed, the upload is refused.
 */

namespace Nitida\Svg;

defined('ABSPATH') || exit;

// Normally autoloaded by the site's vendor/; a standalone checkout (tests, a non-Bedrock
// site that installs this package on its own) carries its own vendor/.
if (! class_exists(\enshrined\svgSanitize\Sanitizer::class) && is_readable(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

add_filter('upload_mimes', function (array $mimes): array {
    // Without the sanitizer, allowing SVG would reopen the hole this plugin exists to close.
    if (class_exists(\enshrined\svgSanitize\Sanitizer::class)) {
        $mimes['svg'] = 'image/svg+xml';
    }
    return $mimes;
});

/**
 * Rewrites an uploaded SVG in place with its sanitized markup, or sets the upload error.
 * Runs on both browser uploads and sideloads (media_sideload_image, REST, WP-CLI import).
 *
 * @param array $file Upload array from $_FILES / the sideload, before WordPress moves it.
 */
function sanitize_upload(array $file): array
{
    $is_svg = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)) === 'svg';
    if (! $is_svg || ! empty($file['error']) || empty($file['tmp_name'])) {
        return $file;
    }

    if (! class_exists(\enshrined\svgSanitize\Sanitizer::class)) {
        $file['error'] = __('SVG uploads are disabled: the SVG sanitizer is not installed.');
        return $file;
    }

    $dirty = file_get_contents($file['tmp_name']);
    $sanitizer = new \enshrined\svgSanitize\Sanitizer();
    // Drops <use>/href pointing outside the file: external references can leak the
    // visitor's IP and pull in content the sanitizer never saw.
    $sanitizer->removeRemoteReferences(true);
    $clean = $dirty === false ? false : $sanitizer->sanitize($dirty);

    if ($clean === false || trim($clean) === '') {
        $file['error'] = __('This SVG could not be read safely and was not uploaded.');
        return $file;
    }

    file_put_contents($file['tmp_name'], $clean);
    $file['size'] = strlen($clean);
    return $file;
}
add_filter('wp_handle_upload_prefilter', __NAMESPACE__ . '\\sanitize_upload');
add_filter('wp_handle_sideload_prefilter', __NAMESPACE__ . '\\sanitize_upload');

// Media library grid: SVGs have no generated sizes, so give the JS a "full" size pointing at
// the file itself, otherwise the tile renders blank.
add_filter('wp_prepare_attachment_for_js', function (array $response): array {
    if (($response['mime'] ?? '') !== 'image/svg+xml') {
        return $response;
    }
    $response['image'] = ['src' => $response['url'], 'width' => 150, 'height' => 150];
    $response['sizes'] = [
        'full' => ['url' => $response['url'], 'width' => 150, 'height' => 150, 'orientation' => 'landscape'],
    ];
    return $response;
});

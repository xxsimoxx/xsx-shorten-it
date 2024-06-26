![Shorten It banner](images/banner-1544x500.png)

# Shorten It
Plugin for [ClassicPress](https://www.classicpress.net/).

[![ClassicPress Directory Coding Standard checks.](https://github.com/xxsimoxx/xsx-shorten-it/actions/workflows/cpcs.yml/badge.svg)](https://github.com/xxsimoxx/xsx-shorten-it/actions/workflows/cpcs.yml)

### Create short link for your posts, your affiliates or your social content.
- Create URL like mysite.com/fb to point to longer internal links or external links like instagram videos, external services or partners.
- Generate QR codes for those links with this plugin (using [php-qrcode](https://github.com/splitbrain/php-qrcode)).
- Keep track of how many times those links are used (without tracking data).

## Example
Path: /fbv

Destination: https://www.facebook.com/cris.vardamak/videos/1327994840668572

means that if you connect to *https://educatorecinofilo.dog/fbv* you will be redirected to *https://www.facebook.com/cris.vardamak/videos/1327994840668572*.

## Conflicts

If redirects are not working properly there may be a conflict with plugins using `template_redirect` hook.
This hook is used often by SEO plugins to redirect non existing pages.
Take a look at your SEO plugin's settings.

## Screenshots
![Shorten It screenshot](images/screenshot-1.jpg)



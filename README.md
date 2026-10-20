# Ki-Score TYPO3 extension

TYPO3 extension to integrate kiscore.ai, AI bot detection and analysis.

## Installation

```bash
composer require tpwd/typo3-kiscore
```

## Configuration

Requires extension 2.1.0 or later. In the KIscore dashboard, copy the
connection key for your website. Edit the site's configuration in TYPO3, paste
it into **KIscore connection key**, and save.

The extension splits the key internally and authenticates tracking requests.
Missing or malformed keys send no data. Existing installations with separate
`kiscore_site_id` and `kiscore_tracking_secret` remain supported; a supplied
connection key takes precedence.

Keep the key out of versioned `config/sites/*/config.yaml` by setting the private
hosting variable `KISCORE_CONNECTION_KEY` and entering
`%env(KISCORE_CONNECTION_KEY)%` in the connection key field:

```yaml
kiscore_connection_key: '%env(KISCORE_CONNECTION_KEY)%'
```

The variable is available to the TYPO3 PHP process and is scoped to this site
by the YAML reference. Multiple sites may reference different variable names.
After changing the environment, restart PHP and flush TYPO3's configuration cache.

## Features

- Tracks all frontend requests (including cached pages)
- Sends user agent, referer, client IP and the page path with its `utm_*`
  parameters to kiscore.ai; other query parameters stay on your server, as
  they can hold tokens or email addresses
- Recognises visits referred by AI assistants via referer or `utm_source`
- Compatible with TYPO3 12, 13 and 14
- Minimal performance impact (short timeouts, errors never reach the visitor)

## Tests

This extension ships with a minimal PHPUnit setup to help increase software quality.

- Install dev dependencies:
  ```bash
  composer install
  ```
- Run the unit tests:
  ```bash
  composer test
  ```
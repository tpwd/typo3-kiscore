# Ki-Score TYPO3 extension

TYPO3 extension to integrate kiscore.ai, AI bot detection and analysis.

## Installation

```bash
composer require tpwd/typo3-kiscore
```

## Configuration

The extension tracks all frontend requests and sends them to kiscore.ai. It
only sends data once both values below are set; the Ki-Score API rejects
requests without a matching tracking secret.

### Site ID and tracking secret

1. In the Ki-Score dashboard, open Traffic > Integration and copy the Site ID
   and the tracking secret
2. Go to the TYPO3 Site Configuration module and edit your site configuration
3. Enter both values in the fields "Ki-Score Site ID" and
   "Ki-Score Tracking Secret"
4. Save the configuration

To keep the secret out of `config/sites/*/config.yaml`, reference an
environment variable instead:

```yaml
kiscore_site_id: '%env(KISCORE_SITE_ID)%'
kiscore_tracking_secret: '%env(KISCORE_TRACKING_SECRET)%'
```

## Features

- Tracks all frontend requests (including cached pages)
- Sends user agent, referer, client IP and the page path with its `utm_*`
  parameters to kiscore.ai; other query parameters stay on your server, as
  they can hold tokens or email addresses
- Recognises visits referred by AI assistants via referer or `utm_source`
- Compatible with TYPO3 12 and 13
- Minimal performance impact (short timeouts, errors never reach the visitor)

## Example

![Ki-Score Site ID configuration in TYPO3 Site Configuration](Documentation/Images/Settings-Site-ID.png)

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
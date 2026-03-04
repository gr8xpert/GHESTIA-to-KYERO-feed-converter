# GHESTIA to KYERO Feed Converter

A PHP tool that automatically converts property feeds from **Ghestia** format to **Kyero v3** XML format.

## Features

- Downloads property feed from Ghestia FTP automatically
- Converts to Kyero v3 XML format
- Property type mapping (apartment, villa, penthouse, etc.)
- Feature extraction (pool, air conditioning, heating, etc.)
- Energy rating conversion
- Image handling (up to 50 images per property)
- Update history logging
- Web-based status dashboard

## Installation

1. Upload all files to your web server (e.g., `/kyero/` folder)

2. Create the `feeds` directory:
   ```
   mkdir feeds
   chmod 755 feeds
   ```

3. Edit `config.php` with your settings:
   - Ghestia FTP credentials
   - Agent information
   - Security key

4. Test the update:
   ```
   https://yourdomain.com/kyero/update.php?key=YOUR_SECURITY_KEY
   ```

5. Set up a daily cron job (Plesk Scheduled Task):
   - Task type: "Fetch a URL"
   - URL: `https://yourdomain.com/kyero/update.php?key=YOUR_SECURITY_KEY`
   - Schedule: Daily

## Files

| File | Description |
|------|-------------|
| `config.php` | Configuration (FTP credentials, agent info) |
| `GhestiaToKyero.php` | Converter class |
| `update.php` | Update trigger script |
| `index.php` | Status dashboard |
| `logs.php` | Update history viewer |
| `debug.php` | Debugging tool |
| `.htaccess` | Protects config file |

## URLs

| URL | Purpose |
|-----|---------|
| `/kyero/` | Status dashboard |
| `/kyero/logs.php` | View update history |
| `/kyero/feeds/kyero_feed.xml` | Kyero feed (for import) |
| `/kyero/update.php?key=XXX` | Trigger manual update |

## Configuration

Edit `config.php`:

```php
// Ghestia FTP Settings
define('GHESTIA_FTP_HOST', 'ftp.ghestia.cat');
define('GHESTIA_FTP_USER', 'your_username');
define('GHESTIA_FTP_PASS', 'your_password');
define('GHESTIA_FTP_FILE', 'INMUEBLES_MODIFICADOS.xml');

// Agent Info
define('AGENT_ID', 'your_agent_id');
define('AGENT_NAME', 'Your Agency Name');
define('AGENT_EMAIL', 'your@email.com');
// ... etc

// Security key for URL access
define('SECURITY_KEY', 'your-secret-key');
```

## Property Type Mapping

| Ghestia | Kyero |
|---------|-------|
| Piso/Apartamento | apartment |
| Piso/Ático | penthouse |
| Casa/Chalet | villa |
| Casa/Unifamiliar adosada | town_house |
| Casa/Casa rural | country_house |
| Casa/Bungalow | bungalow |
| Suelo/* | plot |
| Local/* | commercial |
| Parking/* | garage |

## Requirements

- PHP 7.0+
- FTP extension enabled
- SimpleXML extension enabled
- DOM extension enabled

## License

MIT License

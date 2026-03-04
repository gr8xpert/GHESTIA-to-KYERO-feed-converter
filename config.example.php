<?php
/**
 * Ghestia to Kyero Feed Converter - Configuration
 *
 * Copy this file to config.php and fill in your details.
 */

// Ghestia FTP Settings
define('GHESTIA_FTP_HOST', 'ftp.ghestia.cat');
define('GHESTIA_FTP_USER', 'your_ftp_username');
define('GHESTIA_FTP_PASS', 'your_ftp_password');
define('GHESTIA_FTP_FILE', 'INMUEBLES_MODIFICADOS.xml');

// Output Settings
define('OUTPUT_DIR', __DIR__ . '/feeds/');
define('OUTPUT_FILE', 'kyero_feed.xml');

// Agent Info (from your AGENCIAS.xml or set manually)
define('AGENT_ID', 'your_agent_id');
define('AGENT_NAME', 'Your Agency Name');
define('AGENT_EMAIL', 'your@email.com');
define('AGENT_TEL', '123456789');
define('AGENT_ADDR', 'Your Address');
define('AGENT_TOWN', 'Your Town');
define('AGENT_REGION', 'Your Region');
define('AGENT_POSTCODE', '12345');

// Security key for triggering updates via URL (change this to something unique!)
define('SECURITY_KEY', 'change-this-to-a-random-string');

// Timezone
date_default_timezone_set('Europe/Madrid');

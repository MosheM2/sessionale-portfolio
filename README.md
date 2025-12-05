# Sessionale Portfolio Theme

A WordPress theme for portfolio websites with Adobe Portfolio migration capabilities.

## Features

- Portfolio post type with custom gallery system
- Adobe Portfolio import wizard
- Responsive grid layout with hover overlays
- Dark/light theme toggle
- Mobile-friendly navigation
- Contact form with Google reCAPTCHA v3 support

## Server Requirements

The Adobe Portfolio import downloads and processes many images. You may need to increase the following PHP settings in your server configuration (php.ini or hosting panel):

| Setting | Recommended Value | Description |
|---------|------------------|-------------|
| `max_input_time` | `300` | Maximum time in seconds a script is allowed to parse input data |
| `post_max_size` | `64M` | Maximum size of POST data. Should be larger than `upload_max_filesize` |
| `upload_max_filesize` | `32M` | Maximum size of an uploaded file |
| `max_execution_time` | `300` | Maximum time in seconds a script is allowed to run |
| `memory_limit` | `256M` | Maximum memory a script can consume |

**How to change these settings:**
- **XAMPP**: Edit `php.ini` in `xampp/php/php.ini`
- **Plesk**: Domains → Your Domain → PHP Settings
- **cPanel**: Select PHP Version → Options
- **Contact your hosting provider** if you don't have access to change these settings

## Recommended Plugins

After activating the theme, you'll see a notice recommending the following plugins:

### Complianz – GDPR/CCPA Cookie Consent
Helps you manage cookie consent banners and comply with GDPR/CCPA regulations.

### OMGF | GDPR/DSGVO Compliant, Faster Google Fonts
Hosts Google Fonts locally for GDPR compliance and faster loading.

To install them:

1. Click "Begin installing plugins" in the admin notice
2. Install and activate the plugins
3. Configure settings as needed

These plugins are recommended but not required for the theme to function.

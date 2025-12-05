# Sessionale Portfolio Theme

A WordPress theme for portfolio websites with Adobe Portfolio migration capabilities.

## Features

- Portfolio post type with custom gallery system
- Adobe Portfolio import wizard
- Responsive grid layout with hover overlays
- Dark/light theme toggle
- Mobile-friendly navigation
- Contact form with Google reCAPTCHA v3 support
- Automatic email confirmation to visitors

## Contact Form Email Setup

The contact form sends emails using your site's domain (e.g., `noreply@yourdomain.com`). For emails to be delivered reliably:

### Required: Create the noreply mailbox

Create a `noreply@yourdomain.com` email account in your hosting panel:

- **Plesk**: Mail → Mail Accounts → Create
- **cPanel**: Email Accounts → Create

This mailbox doesn't need to be monitored—it just needs to exist so the server accepts it as a valid sender.

### Contact Form Settings

1. Go to **Sessionale Dashboard** in WordPress admin
2. Set the **Email Address** field to where you want to receive messages
3. The visitor will also receive a copy of their message at the email they entered

---

### Troubleshooting: DNS Records (usually not needed)

Most hosting providers automatically configure the correct DNS records for email. Only check this section if emails are not being delivered.

Your domain needs these DNS records for email authentication:

| Record | Purpose |
|--------|---------|
| **SPF** | Authorizes your server to send email for your domain |
| **DKIM** | Adds a digital signature to verify emails are legitimate |
| **DMARC** | Tells receiving servers how to handle failed authentication |

**How to check/configure:**
- **Plesk**: Domains → DNS Settings (often auto-configured)
- **cPanel**: Zone Editor or Email Deliverability
- Use [MXToolbox](https://mxtoolbox.com/) to test your domain's email configuration

If emails land in spam or don't arrive, contact your hosting provider—they can usually resolve this quickly.

---

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

# Sessionale Portfolio Theme

A WordPress theme for portfolio websites with Adobe Portfolio migration capabilities.

## Features

- Portfolio post type with custom gallery system
- Adobe Portfolio import wizard (auto-detects projects, images, videos)
- Responsive grid layout with hover overlays
- Dark/light theme toggle
- Mobile-friendly navigation
- Contact form with Google reCAPTCHA v3 support

## Getting Started

1. Activate the theme in WordPress
2. Go to **Sessionale** in the admin menu
3. Enter your details and Adobe Portfolio URL(s)
4. Click **Save Settings & Start Import**

The import automatically downloads high-quality images, detects duplicates, and sets up your portfolio pages.

## Contact Form Email Setup

Emails are sent from `noreply@yourdomain.com` by default (or the address you configure in Sessionale settings).

**Required:** Create this mailbox in your hosting panel so the server accepts it as a valid sender:
- **Plesk**: Mail → Mail Accounts → Create
- **cPanel**: Email Accounts → Create

### VPS / Deliverability Issues

If emails land in spam, install **WP Mail SMTP** and route emails through your hosting provider's SMTP server instead of PHP's `mail()` function.

## Server Requirements

The import downloads many images. You may need to increase these PHP settings:

| Setting | Recommended |
|---------|-------------|
| `max_execution_time` | `300` |
| `memory_limit` | `256M` |
| `post_max_size` | `64M` |
| `upload_max_filesize` | `32M` |

**How to change:**
- **XAMPP**: Edit `xampp/php/php.ini`
- **Plesk**: Domains → PHP Settings
- **cPanel**: Select PHP Version → Options

## Troubleshooting

**Import fails or times out:**
- Increase `max_execution_time` and `memory_limit`
- Check WordPress debug log for errors

**Images are low quality:**
- Run the import again (it upgrades existing images)
- Verify high-quality versions exist on Adobe Portfolio

**Emails not arriving:**
- Ensure the "From" mailbox exists on your server
- Check spam folder
- Use WP Mail SMTP plugin for better deliverability

## Recommended Plugins

After activation, you'll see a notice recommending:
- **Complianz** – Cookie consent for GDPR/CCPA compliance
- **OMGF** – Hosts Google Fonts locally for GDPR compliance

These are optional but recommended for EU compliance.

# Sessionale Portfolio Theme

A minimal WordPress theme for artists and creatives migrating from Adobe Portfolio. Features a one-click import wizard that automatically transfers your projects, images, and videos to a self-hosted WordPress site.

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

The "From" address must exist as a valid mailbox on your server. If emails land in spam, consider using the **WP Mail SMTP** plugin to route emails through your SMTP server.

## Server Requirements

The import downloads many images. Recommended PHP settings:

| Setting | Recommended |
|---------|-------------|
| `max_execution_time` | `300` |
| `memory_limit` | `256M` |
| `post_max_size` | `64M` |
| `upload_max_filesize` | `32M` |

## Troubleshooting

**Import fails or times out:** Increase `max_execution_time` and `memory_limit`, check WordPress debug log.

**Images are low quality:** Run the import again (it upgrades existing images).

**Emails not arriving:** Ensure the "From" mailbox exists, check spam folder, use WP Mail SMTP plugin.

## Recommended Plugins

After activation, you'll see a notice recommending:
- **Complianz** – Cookie consent for GDPR/CCPA compliance
- **OMGF** – Hosts Google Fonts locally for GDPR compliance

These are optional but recommended for EU compliance.

## Legal Compliance (Germany)

For German users requiring DSGVO-compliant legal texts (Impressum, Datenschutzerklärung), consider the [IT-Recht Kanzlei AGB Starterpaket](https://www.it-recht-kanzlei.de/agb-starterpaket.php?partner_id=1380).

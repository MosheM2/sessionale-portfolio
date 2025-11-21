# Portfolio Import System - Unified Version

This theme now uses a single, unified import system that combines all the best features from previous implementations.

## Features

✅ **Smart Duplicate Detection**
- Detects duplicates by URL, file hash, and image UUID
- Prevents the same image from being used across multiple portfolio items
- Session caching to avoid re-downloading during the same import

✅ **High-Quality Image Downloads**
- Automatically attempts to download highest quality versions (up to 4K)
- Upgrades low-quality thumbnail URLs to full-resolution versions
- Rejects images smaller than 100x100 pixels
- Validates image dimensions before saving

✅ **Automatic Cleanup**
- Post-import cleanup removes any remaining low-quality duplicates
- Keeps the highest resolution version when duplicates are found
- Updates featured images to use best quality versions

✅ **Video Support**
- Embeds YouTube and Vimeo videos found in projects
- Properly formats video embeds for WordPress

✅ **Proper Featured Images**
- Sets the first high-quality image as featured image
- No duplicate featured images across projects

## How to Use

### Method 1: WordPress Admin (Recommended)
1. Go to `Appearance → Portfolio Import` in your WordPress admin
2. Enter your Adobe Portfolio URL (e.g., `yourname.myportfolio.com`)
3. Click "Start Import"

### Method 2: Direct Script
1. Access: `http://localhost/adobekiller/wp-content/themes/sessionale-portfolio/run-import-unified.php`
2. Choose auto-import from main URL OR manual import from specific URLs
3. Follow the on-screen instructions

## Files Structure

```
wp-content/themes/sessionale-portfolio/
├── inc/
│   └── class-portfolio-import.php     # Main unified import class
├── functions.php                      # WordPress integration (cleaned)
├── run-import-unified.php            # Standalone import script
└── IMPORT-README.md                  # This file
```

## What Was Removed

The following old files were removed to eliminate duplication:
- `portfolio-import-fixed.php`
- `portfolio-import-enhanced.php`
- `run-import.php`
- `cleanup-portfolio.php`

All functionality from these files has been combined into the unified `Portfolio_Import` class.

## Troubleshooting

### If images are still low quality:
1. Run the cleanup process via the admin panel or script
2. Check the debug log for URL upgrade attempts
3. Manually verify that high-quality versions exist on the Adobe Portfolio CDN

### If duplicates still appear:
1. Use the "Run Cleanup Only" option
2. Check for images with different UUIDs but identical content
3. Manually delete low-quality duplicates from Media Library

### If import fails:
1. Check WordPress debug log for detailed error messages
2. Verify the Adobe Portfolio URL is accessible
3. Ensure sufficient server memory (512M recommended)
4. Check for server timeout issues (increase max_execution_time)

## Security

Remember to delete `run-import-unified.php` after completing your import for security reasons.
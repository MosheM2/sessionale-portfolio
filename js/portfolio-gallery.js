/**
 * Portfolio Gallery - Smart Masonry Layout & Lightbox
 * Intelligently arranges images to eliminate empty spaces
 */

(function() {
    'use strict';

    // Wait for DOM and images to load
    document.addEventListener('DOMContentLoaded', function() {
        initSmartMasonry();
        initLightbox();
    });

    /**
     * Smart Masonry Layout
     * Groups images intelligently based on aspect ratios
     */
    let masonryInitialized = false;

    function initSmartMasonry() {
        const container = document.querySelector('.layout-auto .project-content, .layout-grid .project-content');
        if (!container) return;

        // Select all media figures: images, videos, and embeds
        const figures = Array.from(container.querySelectorAll('figure.wp-block-image, figure.wp-block-video, figure.wp-block-embed'));
        if (figures.length === 0) return;

        // Wait for all images to load to get accurate dimensions
        const images = figures.map(fig => fig.querySelector('img')).filter(Boolean);
        let loadedCount = 0;
        const totalToLoad = images.length || 1; // At least 1 to trigger if no images

        function onMediaLoad() {
            loadedCount++;
            if (loadedCount >= totalToLoad && !masonryInitialized) {
                masonryInitialized = true;
                arrangeMedia(container, figures);
            }
        }

        if (images.length === 0) {
            // No images, just videos/embeds - arrange immediately
            masonryInitialized = true;
            arrangeMedia(container, figures);
        } else {
            images.forEach(img => {
                if (img.complete) {
                    onMediaLoad();
                } else {
                    img.addEventListener('load', onMediaLoad);
                    img.addEventListener('error', onMediaLoad);
                }
            });

            // Fallback timeout
            setTimeout(() => {
                if (!masonryInitialized) {
                    masonryInitialized = true;
                    arrangeMedia(container, figures);
                }
            }, 2000);
        }
    }

    /**
     * Arrange media (images, videos, embeds) into smart rows
     */
    function arrangeMedia(container, figures) {
        // Get ALL children to preserve non-media elements
        const allChildren = Array.from(container.children);

        // Separate credits (goes at top) from other non-media elements
        const creditsElements = allChildren.filter(child =>
            child.matches('.portfolio-credits, .wp-block-group.portfolio-credits, [class*="portfolio-credits"]')
        );
        const otherNonMediaElements = allChildren.filter(child =>
            !child.matches('figure.wp-block-image, figure.wp-block-video, figure.wp-block-embed') &&
            !child.matches('.portfolio-credits, .wp-block-group.portfolio-credits, [class*="portfolio-credits"]')
        );

        // Analyze each media figure
        const mediaItems = figures.map(fig => {
            let ratio = 16 / 9; // Default aspect ratio for videos/embeds

            // For images, get actual dimensions
            const img = fig.querySelector('img');
            if (img) {
                const width = img.naturalWidth || img.width || 16;
                const height = img.naturalHeight || img.height || 9;
                ratio = width / height;
            }

            // For videos, try to get dimensions or use 16:9 default
            const video = fig.querySelector('video');
            if (video && video.videoWidth && video.videoHeight) {
                ratio = video.videoWidth / video.videoHeight;
            }

            // Get layout preference from data attribute
            const layoutPref = fig.getAttribute('data-layout') || 'auto';

            return {
                element: fig,
                ratio: ratio,
                isLandscape: ratio > 1.2,
                isPortrait: ratio < 0.9,
                isSquare: ratio >= 0.9 && ratio <= 1.2,
                layout: layoutPref,
                isFull: layoutPref === 'full',
                isTwoThirds: layoutPref === 'two-thirds',
                isHalf: layoutPref === 'half',
                isTwoFifths: layoutPref === 'two-fifths',
                isThird: layoutPref === 'third',
                isQuarter: layoutPref === 'quarter',
                isFifth: layoutPref === 'fifth',
                isSixth: layoutPref === 'sixth',
                isEighth: layoutPref === 'eighth'
            };
        }).filter(Boolean);

        if (mediaItems.length === 0) return;

        // Group media items into rows
        const rows = createSmartRows(mediaItems);

        // Clear container
        container.innerHTML = '';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '20px';
        container.style.columnCount = 'unset';

        // Add credits at the top first
        creditsElements.forEach(el => {
            container.appendChild(el);
        });

        // Add all image rows (only if they have items)
        rows.forEach(row => {
            if (row && row.length > 0) {
                const rowDiv = createRowElement(row);
                if (rowDiv) {
                    container.appendChild(rowDiv);
                }
            }
        });

        // Add other non-media elements at the end (buttons, etc.)
        otherNonMediaElements.forEach(el => {
            container.appendChild(el);
        });
    }

    /**
     * Create a row element from row data
     */
    function createRowElement(row) {
        // Don't create empty rows
        if (!row || row.length === 0) return null;

        const rowDiv = document.createElement('div');
        rowDiv.className = 'gallery-row';
        rowDiv.style.display = 'flex';
        rowDiv.style.gap = '20px';
        rowDiv.style.width = '100%';

        // Helper function to style media element
        function styleMediaElement(item) {
            const img = item.element.querySelector('img');
            const video = item.element.querySelector('video');
            const embedContainer = item.element.querySelector('.video-embed-container');
            const iframe = item.element.querySelector('iframe');

            if (img) {
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
            }
            if (video) {
                video.style.width = '100%';
                video.style.height = '100%';
                video.style.objectFit = 'cover';
            }
            if (embedContainer) {
                embedContainer.style.width = '100%';
                embedContainer.style.height = '100%';
                embedContainer.style.paddingBottom = '0';
                embedContainer.style.position = 'relative';
            }
            if (iframe && !embedContainer) {
                iframe.style.width = '100%';
                iframe.style.height = '100%';
            }
        }

        // Check if this is a compact row (4 items: L+P+L+P pattern)
        if (row.isCompact) {
            rowDiv.className = 'gallery-row gallery-row-compact';

            row.forEach(item => {
                item.element.style.flex = `${item.ratio} 0 0`;
                item.element.style.minWidth = '0';
                item.element.style.margin = '0';
                styleMediaElement(item);
                rowDiv.appendChild(item.element);
            });
        } else if (row.length === 1) {
            const item = row[0];
            item.element.style.flex = '1';
            item.element.style.maxWidth = item.isPortrait ? '50%' : '100%';
            item.element.style.margin = item.isPortrait ? '0 auto' : '0';
            rowDiv.appendChild(item.element);
        } else {
            // Multi-item row - use aspect ratio for flex grow to ensure equal heights
            rowDiv.style.alignItems = 'stretch';

            row.forEach(item => {
                // Determine flex value based on layout preference
                let flexValue = item.ratio; // Default: use aspect ratio

                // Explicit layout overrides - use actual width fractions
                if (hasExplicitLayout(item)) {
                    const width = getLayoutWidth(item);
                    // Use width as flex-grow (e.g., 2/5 = 0.4, 1/5 = 0.2)
                    // Multiply by 10 for nicer numbers
                    flexValue = width * 10;
                }

                item.element.style.flex = `${flexValue} 0 0`;
                item.element.style.minWidth = '0';
                item.element.style.margin = '0';
                item.element.style.overflow = 'hidden';
                styleMediaElement(item);
                rowDiv.appendChild(item.element);
            });
        }

        return rowDiv;
    }

    /**
     * Get the width fraction for a layout type
     */
    function getLayoutWidth(item) {
        if (item.isFull) return 1;
        if (item.isTwoThirds) return 2/3;
        if (item.isHalf) return 1/2;
        if (item.isTwoFifths) return 2/5;
        if (item.isThird) return 1/3;
        if (item.isQuarter) return 1/4;
        if (item.isFifth) return 1/5;
        if (item.isSixth) return 1/6;
        if (item.isEighth) return 1/8;
        return 0; // auto - will be handled separately
    }

    /**
     * Check if item has explicit layout (not auto)
     */
    function hasExplicitLayout(item) {
        return item.layout !== 'auto';
    }

    /**
     * Create smart rows based on image aspect ratios and layout preferences
     */
    function createSmartRows(items) {
        const rows = [];
        let i = 0;

        while (i < items.length) {
            const current = items[i];

            // Check for explicit layout preferences first
            if (current.isFull) {
                // Full width - always own row
                rows.push([current]);
                i++;
                continue;
            }

            // For any explicit fractional layout, group by total width
            if (hasExplicitLayout(current) && !current.isFull) {
                const row = [current];
                let totalWidth = getLayoutWidth(current);
                let j = i + 1;

                // Keep adding items until we reach ~100% width
                while (j < items.length && totalWidth < 0.99) {
                    const nextItem = items[j];
                    const nextWidth = hasExplicitLayout(nextItem) ? getLayoutWidth(nextItem) : 0;

                    // If next item is auto, use remaining space logic
                    if (!hasExplicitLayout(nextItem)) {
                        // Auto items fill remaining space, so add it and stop
                        row.push(nextItem);
                        j++;
                        break;
                    }

                    // Check if adding this item would exceed 100%
                    if (totalWidth + nextWidth <= 1.01) {
                        row.push(nextItem);
                        totalWidth += nextWidth;
                        j++;
                    } else {
                        // Would exceed, stop here
                        break;
                    }
                }

                rows.push(row);
                i = j;
                continue;
            }

            // Auto layout - use aspect ratio based logic
            // Landscape images get their own row (unless followed by specific patterns)
            if (current.isLandscape) {
                // Check for pattern: landscape, portrait, landscape, portrait
                // All 4 should go in a single row at reduced height
                if (i + 3 < items.length) {
                    const next1 = items[i + 1];
                    const next2 = items[i + 2];
                    const next3 = items[i + 3];

                    if (next1.isPortrait && next2.isLandscape && next3.isPortrait) {
                        // Put all 4 in one row - mark as compact row
                        const compactRow = [current, next1, next2, next3];
                        compactRow.isCompact = true;
                        rows.push(compactRow);
                        i += 4;
                        continue;
                    }
                }

                // Check for landscape followed by single portrait
                if (i + 1 < items.length && items[i + 1].isPortrait) {
                    // Check if there's another portrait after
                    if (i + 2 < items.length && items[i + 2].isPortrait) {
                        // Landscape alone, then 2 portraits together
                        rows.push([current]);
                        rows.push([items[i + 1], items[i + 2]]);
                        i += 3;
                        continue;
                    }
                    // Landscape with portrait side by side
                    rows.push([current, items[i + 1]]);
                    i += 2;
                    continue;
                }

                // Single landscape
                rows.push([current]);
                i++;
                continue;
            }

            // Portrait images - try to group 2-3 together
            if (current.isPortrait) {
                const portraitsInRow = [current];
                let j = i + 1;

                // Collect consecutive portraits (max 3)
                while (j < items.length && items[j].isPortrait && portraitsInRow.length < 3) {
                    portraitsInRow.push(items[j]);
                    j++;
                }

                // If we only have 1 portrait and next is landscape, pair them
                if (portraitsInRow.length === 1 && j < items.length && items[j].isLandscape) {
                    rows.push([current, items[j]]);
                    i = j + 1;
                    continue;
                }

                rows.push(portraitsInRow);
                i = j;
                continue;
            }

            // Square images - treat like portraits, group 2-3
            if (current.isSquare) {
                const squaresInRow = [current];
                let j = i + 1;

                while (j < items.length && (items[j].isSquare || items[j].isPortrait) && squaresInRow.length < 3) {
                    squaresInRow.push(items[j]);
                    j++;
                }

                rows.push(squaresInRow);
                i = j;
                continue;
            }

            // Fallback - single item row
            rows.push([current]);
            i++;
        }

        return rows;
    }

    /**
     * Lightbox functionality
     */
    function initLightbox() {
        const container = document.querySelector('.project-gallery');
        if (!container) return;

        // Create lightbox HTML
        const lightboxHTML = `
            <div id="portfolio-lightbox" class="portfolio-lightbox">
                <button class="lightbox-close" aria-label="Close">&times;</button>
                <button class="lightbox-prev" aria-label="Previous">&lsaquo;</button>
                <button class="lightbox-next" aria-label="Next">&rsaquo;</button>
                <div class="lightbox-content">
                    <img src="" alt="">
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', lightboxHTML);

        const lightbox = document.getElementById('portfolio-lightbox');
        const lightboxImg = lightbox.querySelector('.lightbox-content img');
        const closeBtn = lightbox.querySelector('.lightbox-close');
        const prevBtn = lightbox.querySelector('.lightbox-prev');
        const nextBtn = lightbox.querySelector('.lightbox-next');

        // Get all gallery images
        const images = Array.from(container.querySelectorAll('figure.wp-block-image img'));
        let currentIndex = 0;

        function openLightbox(index) {
            currentIndex = index;
            lightboxImg.classList.remove('loaded');
            lightboxImg.src = images[index].src;
            lightboxImg.onload = function() {
                lightboxImg.classList.add('loaded');
            };
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }

        function showPrev() {
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            lightboxImg.classList.remove('loaded');
            lightboxImg.src = images[currentIndex].src;
            lightboxImg.onload = function() {
                lightboxImg.classList.add('loaded');
            };
        }

        function showNext() {
            currentIndex = (currentIndex + 1) % images.length;
            lightboxImg.classList.remove('loaded');
            lightboxImg.src = images[currentIndex].src;
            lightboxImg.onload = function() {
                lightboxImg.classList.add('loaded');
            };
        }

        // Add click handlers to images
        images.forEach((img, index) => {
            img.style.cursor = 'pointer';
            img.addEventListener('click', function(e) {
                e.preventDefault();
                openLightbox(index);
            });
        });

        // Lightbox controls
        closeBtn.addEventListener('click', closeLightbox);
        prevBtn.addEventListener('click', showPrev);
        nextBtn.addEventListener('click', showNext);

        // Close on background click
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox || e.target.classList.contains('lightbox-content')) {
                closeLightbox();
            }
        });

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (!lightbox.classList.contains('active')) return;

            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') showPrev();
            if (e.key === 'ArrowRight') showNext();
        });
    }

})();

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

        const figures = Array.from(container.querySelectorAll('figure.wp-block-image'));
        if (figures.length === 0) return;

        // Wait for all images to load to get accurate dimensions
        const images = figures.map(fig => fig.querySelector('img')).filter(Boolean);
        let loadedCount = 0;

        function onImageLoad() {
            loadedCount++;
            if (loadedCount >= images.length && !masonryInitialized) {
                masonryInitialized = true;
                arrangeImages(container, figures);
            }
        }

        images.forEach(img => {
            if (img.complete) {
                onImageLoad();
            } else {
                img.addEventListener('load', onImageLoad);
                img.addEventListener('error', onImageLoad);
            }
        });

        // Fallback timeout
        setTimeout(() => {
            if (!masonryInitialized) {
                masonryInitialized = true;
                arrangeImages(container, figures);
            }
        }, 2000);
    }

    /**
     * Arrange images into smart rows
     */
    function arrangeImages(container, figures) {
        // Get ALL children to preserve non-image elements
        const allChildren = Array.from(container.children);

        // Separate credits (goes at top) from other non-image elements
        const creditsElements = allChildren.filter(child =>
            child.matches('.portfolio-credits, .wp-block-group.portfolio-credits, [class*="portfolio-credits"]')
        );
        const otherNonImageElements = allChildren.filter(child =>
            !child.matches('figure.wp-block-image') &&
            !child.matches('.portfolio-credits, .wp-block-group.portfolio-credits, [class*="portfolio-credits"]')
        );

        // Analyze each image figure
        const imageItems = figures.map(fig => {
            const img = fig.querySelector('img');
            if (!img) return null;

            const width = img.naturalWidth || img.width || 1;
            const height = img.naturalHeight || img.height || 1;
            const ratio = width / height;

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
                isThird: layoutPref === 'third',
                isQuarter: layoutPref === 'quarter'
            };
        }).filter(Boolean);

        if (imageItems.length === 0) return;

        // Group image items into rows
        const rows = createSmartRows(imageItems);

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

        // Add other non-image elements at the end (videos, buttons)
        otherNonImageElements.forEach(el => {
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

        // Check if this is a compact row (4 items: L+P+L+P pattern)
        if (row.isCompact) {
            rowDiv.className = 'gallery-row gallery-row-compact';

            row.forEach(item => {
                item.element.style.flex = `${item.ratio} 0 0`;
                item.element.style.minWidth = '0';
                item.element.style.margin = '0';
                const img = item.element.querySelector('img');
                if (img) {
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    img.style.objectPosition = 'center center';
                }
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

                // Explicit layout overrides
                if (item.isFull) {
                    flexValue = 1;
                } else if (item.isTwoThirds) {
                    flexValue = 2; // 2:1 ratio with third
                } else if (item.isHalf) {
                    flexValue = 1;
                } else if (item.isThird) {
                    flexValue = 1;
                } else if (item.isQuarter) {
                    flexValue = 1;
                }

                item.element.style.flex = `${flexValue} 0 0`;
                item.element.style.minWidth = '0';
                item.element.style.margin = '0';
                item.element.style.overflow = 'hidden';
                const img = item.element.querySelector('img');
                if (img) {
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                }
                rowDiv.appendChild(item.element);
            });
        }

        return rowDiv;
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

            if (current.isHalf) {
                // Half width - pair with next half or any image
                const halfRow = [current];
                if (i + 1 < items.length) {
                    halfRow.push(items[i + 1]);
                    i += 2;
                } else {
                    i++;
                }
                rows.push(halfRow);
                continue;
            }

            if (current.isTwoThirds) {
                // Two-thirds width - pair with a third or let it be alone
                const twoThirdsRow = [current];
                if (i + 1 < items.length && (items[i + 1].isThird || items[i + 1].layout === 'auto')) {
                    twoThirdsRow.push(items[i + 1]);
                    i += 2;
                } else {
                    i++;
                }
                rows.push(twoThirdsRow);
                continue;
            }

            if (current.isThird) {
                // Third width - group up to 3
                const thirdRow = [current];
                let j = i + 1;
                while (j < items.length && thirdRow.length < 3) {
                    thirdRow.push(items[j]);
                    j++;
                }
                rows.push(thirdRow);
                i = j;
                continue;
            }

            if (current.isQuarter) {
                // Quarter width - group up to 4
                const quarterRow = [current];
                let j = i + 1;
                while (j < items.length && quarterRow.length < 4) {
                    quarterRow.push(items[j]);
                    j++;
                }
                rows.push(quarterRow);
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

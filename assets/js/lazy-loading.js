/**
 * Lazy Loading Plugin JavaScript
 * Implements lazy loading using IntersectionObserver API with fallback support
 */

(function() {
    'use strict';

    // Default settings (will be overridden by WordPress localized script)
    var settings = window.lazyLoadingSettings || {
        threshold: 0.1,
        rootMargin: '50px',
        placeholder: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"%3E%3Crect width="100%25" height="100%25" fill="%23f1f1fa"/%3E%3C/svg%3E',
        fadeIn: true
    };

    var lazyImages;
    var imageObserver;
    var intersectionObserverSupported = 'IntersectionObserver' in window;

    /**
     * Initialize lazy loading
     */
    function init() {
        // Get all lazy images
        lazyImages = document.querySelectorAll('img.lazy[data-src]');
        
        if (lazyImages.length === 0) {
            return;
        }

        // Add class to document for CSS targeting
        if (!intersectionObserverSupported) {
            document.documentElement.classList.add('no-intersection-observer');
        }

        // Add fade-in class if enabled
        if (settings.fadeIn) {
            Array.prototype.forEach.call(lazyImages, function(img) {
                img.classList.add('fade-in');
            });
        }

        if (intersectionObserverSupported) {
            initIntersectionObserver();
        } else {
            initFallback();
        }
    }

    /**
     * Initialize IntersectionObserver for modern browsers
     */
    function initIntersectionObserver() {
        var observerOptions = {
            threshold: parseFloat(settings.threshold),
            rootMargin: settings.rootMargin
        };

        imageObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var image = entry.target;
                    loadImage(image);
                    imageObserver.unobserve(image);
                }
            });
        }, observerOptions);

        // Start observing all lazy images
        Array.prototype.forEach.call(lazyImages, function(image) {
            imageObserver.observe(image);
        });
    }

    /**
     * Fallback for browsers without IntersectionObserver support
     */
    function initFallback() {
        var scrollTimer = null;

        function checkImages() {
            Array.prototype.forEach.call(lazyImages, function(image) {
                if (isImageInViewport(image)) {
                    loadImage(image);
                }
            });
        }

        function throttledScroll() {
            if (scrollTimer) {
                clearTimeout(scrollTimer);
            }
            scrollTimer = setTimeout(checkImages, 100);
        }

        // Initial check
        checkImages();

        // Add event listeners
        window.addEventListener('scroll', throttledScroll);
        window.addEventListener('resize', throttledScroll);
        window.addEventListener('orientationchange', throttledScroll);
    }

    /**
     * Check if image is in viewport (fallback method)
     */
    function isImageInViewport(image) {
        var rect = image.getBoundingClientRect();
        var margin = parseInt(settings.rootMargin) || 50;
        
        return (
            rect.bottom >= -margin &&
            rect.right >= -margin &&
            rect.top <= (window.innerHeight || document.documentElement.clientHeight) + margin &&
            rect.left <= (window.innerWidth || document.documentElement.clientWidth) + margin
        );
    }

    /**
     * Load an image
     */
    function loadImage(image) {
        // Skip if already loaded
        if (image.classList.contains('loaded') || image.classList.contains('loading')) {
            return;
        }

        image.classList.add('loading');

        var dataSrc = image.getAttribute('data-src');
        if (!dataSrc) {
            return;
        }

        // Create a new image to preload
        var newImage = new Image();

        newImage.onload = function() {
            // Update the actual image
            image.src = dataSrc;
            image.classList.remove('loading', 'lazy');
            image.classList.add('loaded');
            
            // Remove data-src attribute
            image.removeAttribute('data-src');
            
            // Trigger custom event
            triggerEvent(image, 'lazyloaded');
        };

        newImage.onerror = function() {
            image.classList.remove('loading');
            image.classList.add('error');
            triggerEvent(image, 'lazyerror');
        };

        // Start loading
        newImage.src = dataSrc;
    }

    /**
     * Trigger custom event
     */
    function triggerEvent(element, eventName) {
        var event;
        if (typeof CustomEvent === 'function') {
            event = new CustomEvent(eventName, { bubbles: true });
        } else {
            event = document.createEvent('CustomEvent');
            event.initCustomEvent(eventName, true, false, null);
        }
        element.dispatchEvent(event);
    }

    /**
     * Handle dynamic content (for AJAX loaded content)
     */
    function refreshLazyImages() {
        if (imageObserver) {
            // Stop observing old images
            Array.prototype.forEach.call(lazyImages, function(image) {
                imageObserver.unobserve(image);
            });
        }

        // Re-initialize with new images
        init();
    }

    /**
     * Public API
     */
    window.LazyLoading = {
        refresh: refreshLazyImages,
        loadImage: loadImage,
        settings: settings
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Handle dynamically added content
    if ('MutationObserver' in window) {
        var mutationObserver = new MutationObserver(function(mutations) {
            var shouldRefresh = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    Array.prototype.forEach.call(mutation.addedNodes, function(node) {
                        if (node.nodeType === 1) { // Element node
                            if (node.tagName === 'IMG' && node.classList.contains('lazy')) {
                                shouldRefresh = true;
                            } else if (node.querySelectorAll && node.querySelectorAll('img.lazy').length > 0) {
                                shouldRefresh = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldRefresh) {
                refreshLazyImages();
            }
        });

        mutationObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

})();
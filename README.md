# WordPress Lazy Loading Plugin

A lightweight and efficient WordPress plugin that implements lazy loading for images using the modern IntersectionObserver API with fallback support for older browsers.

## Features

- 🚀 **Modern IntersectionObserver API** - Efficient lazy loading with excellent performance
- 🔄 **Fallback Support** - Works on older browsers without IntersectionObserver
- ⚡ **Automatic Image Processing** - Automatically converts images in post content and thumbnails
- 🎨 **Customizable Placeholders** - Support for custom placeholder images or SVG
- 🎭 **Smooth Animations** - Optional fade-in effects and loading animations
- ⚙️ **Admin Settings** - Easy configuration through WordPress admin panel
- 📱 **Responsive** - Works perfectly on all device sizes
- 🔧 **Developer Friendly** - Provides hooks and events for customization
- 🖨️ **Print Friendly** - Shows all images when printing

## Installation

1. **Download or Clone**
   ```bash
   git clone https://github.com/your-username/lazy-loading-plugin.git
   ```

2. **Upload to WordPress**
   - Upload the plugin folder to `/wp-content/plugins/`
   - Or upload the ZIP file through WordPress admin

3. **Activate**
   - Go to WordPress Admin → Plugins
   - Find "Lazy Loading Plugin" and click "Activate"

## Usage

### Automatic Operation
Once activated, the plugin automatically:
- Converts all images in post content to lazy loading
- Processes featured images and thumbnails
- Adds the necessary CSS classes and data attributes

### Manual Implementation
You can also manually add lazy loading to specific images:

```html
<!-- Before: Regular image -->
<img src="image.jpg" alt="Description">

<!-- After: Lazy loaded image -->
<img class="lazy" data-src="image.jpg" src="placeholder.svg" alt="Description">
```

### JavaScript API
The plugin provides a global `LazyLoading` object for dynamic content:

```javascript
// Refresh lazy loading after adding new content
LazyLoading.refresh();

// Manually load a specific image
LazyLoading.loadImage(imageElement);

// Access current settings
console.log(LazyLoading.settings);
```

### Events
Listen for lazy loading events:

```javascript
// When an image is successfully loaded
document.addEventListener('lazyloaded', function(e) {
    console.log('Image loaded:', e.target);
});

// When an image fails to load
document.addEventListener('lazyerror', function(e) {
    console.log('Image failed to load:', e.target);
});
```

## Configuration

Go to **WordPress Admin → Settings → Lazy Loading** to configure:

### Basic Settings
- **Enable/Disable** - Toggle lazy loading on/off
- **Fade In Effect** - Enable smooth fade-in animation

### Advanced Settings
- **Intersection Threshold** (0-1) - How much of the image should be visible before loading
- **Root Margin** - Distance from viewport to start loading (e.g., "50px")
- **Placeholder Image** - Custom placeholder image URL or data URI

### Default Settings
```php
'threshold' => 0.1,           // Load when 10% visible
'rootMargin' => '50px',       // Start loading 50px before entering viewport
'fadeIn' => true,             // Enable fade-in animation
'placeholder' => 'data:image/svg+xml,...'  // SVG placeholder
```

## Browser Support

- **Modern Browsers**: Chrome 51+, Firefox 55+, Safari 12.1+, Edge 15+
- **Fallback**: All browsers with JavaScript support
- **Graceful Degradation**: Images load normally if JavaScript is disabled

## Performance Benefits

- **Faster Page Load** - Only load images when needed
- **Reduced Bandwidth** - Save data on mobile devices
- **Better Core Web Vitals** - Improved LCP and CLS scores
- **Smooth Scrolling** - No janky loading interruptions

## Customization

### CSS Classes
The plugin adds these CSS classes you can style:

```css
.lazy              /* Applied to lazy images */
.lazy.loading      /* While image is loading */
.lazy.loaded       /* After image loads successfully */
.lazy.error        /* If image fails to load */
.lazy.fade-in      /* For fade-in animation */
```

### WordPress Hooks
Available filters for developers:

```php
// Modify which images get lazy loading
add_filter('lazy_loading_skip_image', function($skip, $src, $img_tag) {
    // Return true to skip lazy loading for this image
    return $skip;
}, 10, 3);
```

## File Structure

```
lazy-loading-plugin/
├── lazy-loading-plugin.php     # Main plugin file
├── assets/
│   ├── css/
│   │   └── lazy-loading.css    # Plugin styles
│   └── js/
│       └── lazy-loading.js     # JavaScript functionality
├── README.md                   # This file
└── screenshots/                # Plugin screenshots
```

## FAQ

**Q: Will this work with my theme?**
A: Yes, the plugin works with any WordPress theme by automatically processing images in content.

**Q: What about SEO?**
A: The plugin preserves all image attributes and alt text, so SEO is not affected.

**Q: Does it work with page builders?**
A: Yes, it works with most page builders. Use the `LazyLoading.refresh()` method for dynamic content.

**Q: Can I exclude certain images?**
A: Yes, images with `data-src` attribute or already having the `lazy` class are automatically skipped.

## Changelog

### Version 1.0.0
- Initial release
- IntersectionObserver implementation
- Fallback support for older browsers
- WordPress admin settings page
- Automatic image processing
- Fade-in animations
- Custom placeholder support

## License

GPL v2 or later

## Support

For support, please create an issue on GitHub or contact the plugin author.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
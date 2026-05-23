=== WP Floating Contact Bar ===
Contributors: custom
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Floating contact bar with configurable items (label, icon, link), drag-and-drop sorting and responsive layout for desktop and mobile.

== Description ==

WP Floating Contact Bar adds a configurable contact bar that stays fixed on the screen.

You can:

* Enable/disable the bar globally.
* Choose the position (left / right / bottom).
* Add any number of contact items (Zalo, WhatsApp, Kakao Talk, Viber, Line, Email, Messenger, etc.).
* Upload an icon (via the Media Library) for each item.
* Set a link URL for each item.
* Drag and drop items in the settings page to change their order.

All settings are site-wide and do not depend on the active theme. The plugin is plug-and-play and can be reused on other WordPress sites.

== Installation ==

1. Copy the `wp-floating-contact-bar` folder into the `/wp-content/plugins/` directory, **or** zip the folder and upload it via `Plugins → Add New → Upload Plugin`.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. Go to `Settings → Floating Contact Bar`.
4. Tick **Enable bar** to show the bar on the frontend.
5. Choose the bar position (Left / Right / Bottom).
6. Add contact items:
   * Enter a **Label** (e.g. Zalo, WhatsApp).
   * Click **Choose Image** to pick an icon from the Media Library.
   * Enter the **Link URL** (e.g. `https://zalo.me/...`, `https://wa.me/...`, `mailto:you@example.com`, `https://facebook.com/...`).
   * Use drag-and-drop handle to reorder items.
7. Click **Save Changes**.

== Frequently Asked Questions ==

= Does it work with any theme? =

Yes. The bar is injected via hooks and uses its own CSS classes, so it should work with most themes without modification.

= Can I show different bars per page? =

Currently the plugin provides a single global configuration for the whole site.

= How do I move this plugin to another WordPress site? =

1. Zip the `wp-floating-contact-bar` plugin folder.
2. On the new site, go to `Plugins → Add New → Upload Plugin`.
3. Upload the zip file, install and activate.
4. Configure items again in `Settings → Floating Contact Bar`.

== Changelog ==

= 1.0.0 =
* Initial release.


<?php
/**
 * Plugin Name: WP Floating Contact Bar
 * Plugin URI:  https://example.com/wp-floating-contact-bar
 * Description: Thanh liên hệ nổi responsive (desktop + mobile) với các item tùy chỉnh (tên, logo, link) và sắp xếp bằng kéo-thả trong admin.
 * Version:     1.0.7
 * Author:      Custom
 * Text Domain: wfcb
 * Domain Path: /languages
 */

if (!defined("ABSPATH")) {
    exit();
}

define("WFCB_VERSION", "1.0.7");
define("WFCB_PLUGIN_FILE", __FILE__);
define("WFCB_PLUGIN_PATH", plugin_dir_path(__FILE__));
define("WFCB_PLUGIN_URL", plugin_dir_url(__FILE__));

/**
 * Load plugin textdomain.
 */
function wfcb_load_textdomain()
{
    load_plugin_textdomain(
        "wfcb",
        false,
        dirname(plugin_basename(__FILE__)) . "/languages",
    );
}
add_action("plugins_loaded", "wfcb_load_textdomain");

/**
 * Add settings link on Plugins screen.
 *
 * @param string[] $links Existing action links.
 * @return string[]
 */
function wfcb_plugin_action_links($links)
{
    $settings_url = admin_url("options-general.php?page=wfcb-settings");
    $settings_link =
        '<a href="' .
        esc_url($settings_url) .
        '">' .
        esc_html__("Settings", "wfcb") .
        "</a>";

    array_unshift($links, $settings_link);

    return $links;
}
add_filter(
    "plugin_action_links_" . plugin_basename(__FILE__),
    "wfcb_plugin_action_links",
);

/**
 * Include admin and frontend files.
 */
function wfcb_includes()
{
    if (is_admin()) {
        require_once WFCB_PLUGIN_PATH . "includes/admin-settings.php";
    }

    if (!is_admin()) {
        require_once WFCB_PLUGIN_PATH . "includes/frontend.php";
    }
}
add_action("init", "wfcb_includes");

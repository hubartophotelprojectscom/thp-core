<?php

namespace THP\Core\Admin;

defined('ABSPATH') || exit;

/**
 * The shared "THP Settings" top-level admin menu that THP plugins hang their
 * settings submenus off.
 *
 * Idempotent: other THP plugins keep their own stopgap registration, so
 * whichever runs first wins and the rest skip.
 */
class SettingsMenu
{
    public const SLUG = 'thp-settings';

    /**
     * Hook the menu registration. Priority 9 runs before CMB2 attaches its
     * options-page submenus at the default priority 10.
     *
     * @return void
     */
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'register'], 9);
    }

    /**
     * Register the container menu once. The callback renders a minimal landing page.
     *
     * @return void
     */
    public static function register(): void
    {
        global $admin_page_hooks;

        if (is_array($admin_page_hooks) && isset($admin_page_hooks[self::SLUG])) {
            return;
        }

        add_menu_page(
            'THP Settings',
            'THP Settings',
            'edit_pages',
            self::SLUG,
            [self::class, 'render'],
            'dashicons-admin-settings',
            80
        );
    }

    /**
     * Render the landing page: links to the pages registered under this menu.
     *
     * @return void
     */
    public static function render(): void
    {
        global $submenu;

        $items = [];
        $entries = is_array($submenu) && isset($submenu[self::SLUG]) && is_array($submenu[self::SLUG])
            ? $submenu[self::SLUG]
            : [];

        foreach ($entries as $entry) {
            // $entry: [0 => menu title, 1 => capability, 2 => slug, 3 => page title].
            if (!isset($entry[0], $entry[1], $entry[2])) {
                continue;
            }
            // Skip the parent's own auto-generated first entry.
            if ($entry[2] === self::SLUG) {
                continue;
            }
            if (!current_user_can($entry[1])) {
                continue;
            }
            $items[] = [
                'title' => wp_strip_all_tags((string) $entry[0]),
                'url'   => admin_url('admin.php?page=' . rawurlencode((string) $entry[2])),
            ];
        }

        echo '<div class="wrap"><h1>' . esc_html__('THP Settings', 'thp-core') . '</h1>';

        if ($items === []) {
            echo '<p>' . esc_html__('No settings pages are available.', 'thp-core') . '</p>';
        } else {
            echo '<ul>';
            foreach ($items as $item) {
                echo '<li><a href="' . esc_url($item['url']) . '">' . esc_html($item['title']) . '</a></li>';
            }
            echo '</ul>';
        }

        echo '</div>';
    }
}

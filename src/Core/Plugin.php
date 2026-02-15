<?php

declare(strict_types=1);

namespace Fabermind\Transiti\Core;

use Fabermind\Transiti\Admin\Admin;

final class Plugin
{
    public static function boot(): void
    {
        add_action('plugins_loaded', [self::class, 'onPluginsLoaded']);
        add_action('init', [self::class, 'registerPostTypes']);
        add_action('init', [self::class, 'registerTaxonomies']);
        add_action('init', [self::class, 'registerRoles'], 5);
        add_action('init', [self::class, 'ensureEditorialeTerm'], 20);
        add_action('template_redirect', [self::class, 'trackPostView'], 1);
        add_action('admin_init', [self::class, 'maybeEnsureForumPageInMainMenu']);
        add_action('admin_init', [self::class, 'registerViewsAdminColumns']);

        add_filter('wp_terms_checklist_args', [self::class, 'filterSezioniChecklistArgs']);
        add_action('admin_footer-post.php', [self::class, 'printSezioniRadioScript']);
        add_action('admin_footer-post-new.php', [self::class, 'printSezioniRadioScript']);

        add_action('save_post_post', [self::class, 'enforceSingleSezioneTerm']);
        add_action('add_meta_boxes_post', [self::class, 'reorderEditorialeMetabox'], 99);
    }

    public static function activate(): void
    {
        self::registerPostTypes();
        self::registerRoles();
        self::registerTaxonomies();
        self::ensureHomePage();
        self::ensureSezioniTerms();
        self::ensureEditorialeTerm();
        self::ensureSezioniTermsInMainMenu();
        self::ensureForumPageInMainMenuAfterSezioni();
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }

    public static function onPluginsLoaded(): void
    {
        self::loadTextDomain();
        Admin::boot();
    }

    public static function registerPostTypes(): void
    {
        register_post_type(
            'citazioni',
            array(
                'labels' => array(
                    'name'          => __('Citazioni', 'transiti'),
                    'singular_name' => __('Citazione', 'transiti'),
                    'add_new_item'  => __('Aggiungi citazione', 'transiti'),
                    'edit_item'     => __('Modifica citazione', 'transiti'),
                    'new_item'      => __('Nuova citazione', 'transiti'),
                    'view_item'     => __('Vedi citazione', 'transiti'),
                    'search_items'  => __('Cerca citazioni', 'transiti'),
                ),
                'public'              => true,
                'show_in_rest'        => true,
                'menu_position'       => 21,
                'menu_icon'           => 'dashicons-format-quote',
                'supports'            => array('title', 'editor'),
                'has_archive'         => false,
                'rewrite'             => array('slug' => 'citazioni'),
                'exclude_from_search' => true,
                'publicly_queryable'  => false,
            )
        );
    }

    public static function registerTaxonomies(): void
    {
        register_taxonomy(
            'sezioni',
            array('post'),
            array(
                'labels' => array(
                    'name'          => __('Sezioni', 'transiti'),
                    'singular_name' => __('Sezione', 'transiti'),
                    'search_items'  => __('Cerca sezioni', 'transiti'),
                    'all_items'     => __('Tutte le sezioni', 'transiti'),
                    'edit_item'     => __('Modifica sezione', 'transiti'),
                    'update_item'   => __('Aggiorna sezione', 'transiti'),
                    'add_new_item'  => __('Aggiungi sezione', 'transiti'),
                    'new_item_name' => __('Nuova sezione', 'transiti'),
                    'menu_name'     => __('Sezioni', 'transiti'),
                ),
                'public'            => true,
                'hierarchical'      => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'rewrite'           => array('slug' => 'sezioni'),
                'capabilities'      => array(
                    'manage_terms' => 'manage_options',
                    'edit_terms'   => 'manage_options',
                    'delete_terms' => 'manage_options',
                    'assign_terms' => 'edit_posts',
                ),
            )
        );

        register_taxonomy(
            'editoriale',
            array('post'),
            array(
                'labels' => array(
                    'name'          => __('Editoriale', 'transiti'),
                    'singular_name' => __('Editoriale', 'transiti'),
                    'search_items'  => __('Cerca editoriali', 'transiti'),
                    'all_items'     => __('Tutti gli editoriali', 'transiti'),
                    'edit_item'     => __('Modifica editoriale', 'transiti'),
                    'update_item'   => __('Aggiorna editoriale', 'transiti'),
                    'add_new_item'  => __('Aggiungi editoriale', 'transiti'),
                    'new_item_name' => __('Nuovo editoriale', 'transiti'),
                    'menu_name'     => __('Editoriale', 'transiti'),
                ),
                'public'            => false,
                'hierarchical'      => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'rewrite'           => false,
                'capabilities'      => array(
                    'manage_terms' => 'manage_editoriale_terms',
                    'edit_terms'   => 'manage_editoriale_terms',
                    'delete_terms' => 'manage_editoriale_terms',
                    'assign_terms' => 'manage_editoriale_terms',
                ),
            )
        );
    }

    public static function filterSezioniChecklistArgs(array $args): array
    {
        if (($args['taxonomy'] ?? '') !== 'sezioni') {
            return $args;
        }

        $args['checked_ontop'] = false;

        return $args;
    }

    public static function printSezioniRadioScript(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if (! $screen || $screen->post_type !== 'post') {
            return;
        }

        ?>
        <script>
            (function () {
                function setupSezioni() {
                    var container = document.getElementById('taxonomy-sezioni');
                    if (!container) {
                        return;
                    }

                    var checkboxes = container.querySelectorAll("input[type='checkbox'][name='tax_input[sezioni][]']");
                    checkboxes.forEach(function (input) {
                        input.type = 'radio';
                    });

                    <?php if (! current_user_can('manage_options')) : ?>
                    var addBox = container.querySelector('.category-add, .wp-hidden-children');
                    if (addBox) {
                        addBox.style.display = 'none';
                    }
                    <?php endif; ?>
                }

                function reorderBoxes() {
                    var sezioniBox = document.getElementById('sezionidiv');
                    var editorialeBox = document.getElementById('editorialediv');
                    if (sezioniBox && editorialeBox && sezioniBox.parentNode === editorialeBox.parentNode) {
                        sezioniBox.parentNode.insertBefore(editorialeBox, sezioniBox.nextSibling);
                    }

                    var acfBox = document.getElementById('acf-group_transiti_post_meta');
                    var normalSortables = document.getElementById('normal-sortables');
                    if (acfBox && normalSortables && acfBox.parentNode !== normalSortables) {
                        normalSortables.appendChild(acfBox);
                    }
                }

                function runAll() {
                    setupSezioni();
                    reorderBoxes();
                }

                runAll();
                document.addEventListener('DOMContentLoaded', runAll);

                var attempts = 0;
                var timer = setInterval(function () {
                    runAll();
                    attempts++;
                    if (attempts > 20) {
                        clearInterval(timer);
                    }
                }, 250);
            })();
        </script>
        <?php
    }

    public static function enforceSingleSezioneTerm(int $postId): void
    {
        if (wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
            return;
        }

        $termIds = wp_get_object_terms($postId, 'sezioni', array('fields' => 'ids'));

        if (is_wp_error($termIds) || count($termIds) <= 1) {
            return;
        }

        $firstTermId = (int) array_shift($termIds);
        wp_set_object_terms($postId, array($firstTermId), 'sezioni', false);
    }


    public static function reorderEditorialeMetabox(): void
    {
        remove_meta_box('editorialediv', 'post', 'side');

        add_meta_box(
            'editorialediv',
            __('Editoriale', 'transiti'),
            'post_categories_meta_box',
            'post',
            'side',
            'low',
            array(
                'taxonomy' => 'editoriale',
            )
        );
    }

    public static function addViewsAdminColumn(array $columns): array
    {
        $ordered = array();

        foreach ($columns as $key => $label) {
            $ordered[$key] = $label;

            if ($key === 'date') {
                $ordered['transiti_views'] = __('Views', 'transiti');
            }
        }

        if (! isset($ordered['transiti_views'])) {
            $ordered['transiti_views'] = __('Views', 'transiti');
        }

        return $ordered;
    }

    public static function renderViewsAdminColumn(string $column, int $postId): void
    {
        if ($column !== 'transiti_views') {
            return;
        }

        echo (int) get_post_meta($postId, '_transiti_views_count', true);
    }

    public static function registerViewsAdminColumns(): void
    {
        $postTypes = get_post_types(
            array(
                'show_ui' => true,
            ),
            'names'
        );

        foreach ($postTypes as $postType) {
            if (in_array($postType, array('attachment', 'revision', 'nav_menu_item'), true)) {
                continue;
            }

            add_filter("manage_{$postType}_posts_columns", [self::class, 'addViewsAdminColumn']);
            add_action("manage_{$postType}_posts_custom_column", [self::class, 'renderViewsAdminColumn'], 10, 2);
        }
    }

    public static function trackPostView(): void
    {
        error_log('[transiti_views] trackPostView start');

        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_preview()) {
            error_log('[transiti_views] skip: admin/ajax/cron/feed/preview');
            return;
        }

        if (! is_singular()) {
            error_log('[transiti_views] skip: not singular');
            return;
        }

        $post = get_queried_object();
        if (! $post instanceof \WP_Post) {
            error_log('[transiti_views] skip: queried object is not WP_Post');
            return;
        }

        $trackedPostTypes = self::getTrackedPostTypes();
        error_log('[transiti_views] current post ID/type: ' . (int) $post->ID . '/' . (string) $post->post_type);
        error_log('[transiti_views] configured post types: ' . wp_json_encode($trackedPostTypes));

        if (empty($trackedPostTypes) || ! in_array($post->post_type, $trackedPostTypes, true)) {
            error_log('[transiti_views] skip: post type not configured');
            return;
        }

        $postId = (int) $post->ID;
        $hasCookie = self::hasViewSessionCookie($postId);
        error_log('[transiti_views] cookie check for ' . $postId . ': ' . ($hasCookie ? '1' : '0'));

        if ($postId <= 0 || $hasCookie) {
            error_log('[transiti_views] skip: invalid post id or already viewed in session');
            return;
        }

        $currentCount = (int) get_post_meta($postId, '_transiti_views_count', true);
        error_log('[transiti_views] increment from ' . $currentCount . ' to ' . ($currentCount + 1));
        update_post_meta($postId, '_transiti_views_count', $currentCount + 1);
        self::setViewSessionCookie($postId);
        error_log('[transiti_views] done');
    }

    public static function registerRoles(): void
    {
        if (! get_role('redattore')) {
            add_role(
                'redattore',
                __('Redattore', 'transiti'),
                array(
                    'read'       => true,
                    'edit_posts' => true,
                )
            );
        }

        $administrator = get_role('administrator');
        if ($administrator instanceof \WP_Role && ! $administrator->has_cap('manage_editoriale_terms')) {
            $administrator->add_cap('manage_editoriale_terms');
        }

        $redattore = get_role('redattore');
        if ($redattore instanceof \WP_Role && ! $redattore->has_cap('manage_editoriale_terms')) {
            $redattore->add_cap('manage_editoriale_terms');
        }
    }

    public static function ensureEditorialeTerm(): void
    {
        if (term_exists('editoriale', 'editoriale')) {
            return;
        }

        wp_insert_term(
            'editoriale',
            'editoriale',
            array(
                'slug' => 'editoriale',
            )
        );
    }
    private static function ensureSezioniTerms(): void
    {
        $defaultTerms = array('Frontiere', 'Sguardi', 'Metamorfosi');

        foreach ($defaultTerms as $termName) {
            if (term_exists($termName, 'sezioni')) {
                continue;
            }

            wp_insert_term($termName, 'sezioni');
        }
    }

    private static function getTrackedPostTypes(): array
    {
        $selected = get_option('options_views_post_types', null);

        if ($selected === null && function_exists('get_field')) {
            $selected = get_field('views_post_types', 'option');
        }

        $rawValues = self::extractPostTypeValues($selected);
        if (empty($rawValues)) {
            return array('post');
        }

        $registeredPostTypes = get_post_types(array(), 'names');
        $sanitized = array_values(array_unique(array_map('sanitize_key', $rawValues)));

        return array_values(array_intersect($sanitized, $registeredPostTypes));
    }

    /**
     * Normalize ACF checkbox values from different saved formats.
     *
     * @param mixed $value
     * @return array<int, string>
     */
    private static function extractPostTypeValues($value): array
    {
        if (is_string($value) && $value !== '') {
            return array($value);
        }

        if (! is_array($value) || empty($value)) {
            return array();
        }

        $values = array();

        foreach ($value as $key => $item) {
            if (is_string($item) && $item !== '') {
                $values[] = $item;
                continue;
            }

            if (is_array($item) && isset($item['value']) && is_string($item['value']) && $item['value'] !== '') {
                $values[] = $item['value'];
                continue;
            }

            if (
                ! is_numeric($key)
                && is_string($key)
                && (is_bool($item) ? $item : ! empty($item))
            ) {
                $values[] = $key;
            }
        }

        return $values;
    }

    private static function hasViewSessionCookie(int $postId): bool
    {
        $cookieName = self::getViewCookieName($postId);

        return isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] === '1';
    }

    private static function setViewSessionCookie(int $postId): void
    {
        $cookieName = self::getViewCookieName($postId);
        $secure = is_ssl();
        $httponly = true;
        $value = '1';
        $expire = 0;
        $domain = defined('COOKIE_DOMAIN') ? (string) COOKIE_DOMAIN : '';
        $path = defined('COOKIEPATH') && COOKIEPATH ? (string) COOKIEPATH : '/';

        setcookie($cookieName, $value, $expire, $path, $domain, $secure, $httponly);

        if (defined('SITECOOKIEPATH') && SITECOOKIEPATH && SITECOOKIEPATH !== $path) {
            setcookie($cookieName, $value, $expire, (string) SITECOOKIEPATH, $domain, $secure, $httponly);
        }

        $_COOKIE[$cookieName] = $value;
    }

    private static function getViewCookieName(int $postId): string
    {
        return 'transiti_view_' . $postId;
    }

    private static function ensureSezioniTermsInMainMenu(): void
    {
        $locations = get_nav_menu_locations();
        $mainLocationKey = null;

        foreach (array('main-menu', 'main_menu') as $candidate) {
            if (! empty($locations[$candidate])) {
                $mainLocationKey = $candidate;
                break;
            }
        }

        if ($mainLocationKey === null) {
            return;
        }

        $menuId = (int) $locations[$mainLocationKey];
        if ($menuId <= 0) {
            return;
        }

        $terms = get_terms(
            array(
                'taxonomy'   => 'sezioni',
                'hide_empty' => false,
            )
        );

        if (is_wp_error($terms) || empty($terms)) {
            return;
        }

        $existingItems = wp_get_nav_menu_items($menuId);
        $existingTermIds = array();

        if (is_array($existingItems)) {
            foreach ($existingItems as $item) {
                if (($item->type ?? '') === 'taxonomy' && ($item->object ?? '') === 'sezioni') {
                    $existingTermIds[] = (int) ($item->object_id ?? 0);
                }
            }
        }

        foreach ($terms as $term) {
            $termId = (int) $term->term_id;

            if (in_array($termId, $existingTermIds, true)) {
                continue;
            }

            wp_update_nav_menu_item(
                $menuId,
                0,
                array(
                    'menu-item-title'     => $term->name,
                    'menu-item-object'    => 'sezioni',
                    'menu-item-object-id' => $termId,
                    'menu-item-type'      => 'taxonomy',
                    'menu-item-status'    => 'publish',
                    'menu-item-parent-id' => 0,
                )
            );
        }
    }

    public static function maybeEnsureForumPageInMainMenu(): void
    {
        if (! is_admin() || ! current_user_can('manage_options')) {
            return;
        }

        self::ensureForumPageInMainMenuAfterSezioni();
    }

    private static function ensureForumPageInMainMenuAfterSezioni(): void
    {
        $menuId = self::resolveMainMenuId(get_nav_menu_locations());
        if ($menuId <= 0) {
            return;
        }

        $forumPage = self::findForumPage();
        if (! $forumPage instanceof \WP_Post) {
            return;
        }

        update_post_meta((int) $forumPage->ID, '_wp_page_template', 'page-no-sidebar.php');

        $existingItems = wp_get_nav_menu_items($menuId);
        $targetPosition = self::getForumTargetPosition($existingItems);

        if (is_array($existingItems)) {
            foreach ($existingItems as $item) {
                if (
                    ($item->type ?? '') === 'post_type'
                    && ($item->object ?? '') === 'page'
                    && (int) ($item->object_id ?? 0) === (int) $forumPage->ID
                ) {
                    wp_update_nav_menu_item(
                        $menuId,
                        (int) $item->ID,
                        array(
                            'menu-item-title'     => $forumPage->post_title,
                            'menu-item-object'    => 'page',
                            'menu-item-object-id' => (int) $forumPage->ID,
                            'menu-item-type'      => 'post_type',
                            'menu-item-status'    => 'publish',
                            'menu-item-parent-id' => 0,
                            'menu-item-position'  => $targetPosition,
                        )
                    );

                    return;
                }
            }
        }

        wp_update_nav_menu_item(
            $menuId,
            0,
            array(
                'menu-item-title'     => $forumPage->post_title,
                'menu-item-object'    => 'page',
                'menu-item-object-id' => (int) $forumPage->ID,
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
                'menu-item-parent-id' => 0,
                'menu-item-position'  => $targetPosition,
            )
        );
    }

    private static function resolveMainMenuId(array $locations): int
    {
        foreach (array('main-menu', 'main_menu') as $candidate) {
            if (! empty($locations[$candidate])) {
                return (int) $locations[$candidate];
            }
        }

        return 0;
    }

    private static function findForumPage(): ?\WP_Post
    {
        $forumPage = get_page_by_path('forum', OBJECT, 'page');
        if ($forumPage instanceof \WP_Post) {
            return $forumPage;
        }

        $pages = get_posts(
            array(
                'post_type'      => 'page',
                'post_status'    => array('publish', 'draft', 'pending', 'private'),
                'posts_per_page' => 50,
            )
        );

        foreach ($pages as $page) {
            if ($page instanceof \WP_Post && has_shortcode((string) $page->post_content, 'forum')) {
                return $page;
            }
        }

        $maybeForumPages = get_posts(
            array(
                'post_type'      => 'page',
                'post_status'    => array('publish', 'draft', 'pending', 'private'),
                'posts_per_page' => 1,
                'title'          => 'Forum',
            )
        );

        if (! empty($maybeForumPages) && $maybeForumPages[0] instanceof \WP_Post) {
            return $maybeForumPages[0];
        }

        return null;
    }

    /**
     * Puts Forum right after the last "Sezioni" item when present, otherwise at end.
     *
     * @param array<int, object>|false $existingItems
     */
    private static function getForumTargetPosition($existingItems): int
    {
        if (! is_array($existingItems) || empty($existingItems)) {
            return 1;
        }

        $maxSezioniPosition = 0;
        $maxMenuPosition = 0;

        foreach ($existingItems as $item) {
            $position = (int) ($item->menu_order ?? 0);
            if ($position > $maxMenuPosition) {
                $maxMenuPosition = $position;
            }

            if (($item->type ?? '') === 'taxonomy' && ($item->object ?? '') === 'sezioni') {
                if ($position > $maxSezioniPosition) {
                    $maxSezioniPosition = $position;
                }
            }
        }

        if ($maxSezioniPosition > 0) {
            return $maxSezioniPosition + 1;
        }

        return $maxMenuPosition + 1;
    }

    private static function ensureHomePage(): void
    {
        $homePage = get_page_by_path('home', OBJECT, 'page');

        if (! $homePage instanceof \WP_Post) {
            $maybeHomePages = get_posts(
                array(
                    'post_type'      => 'page',
                    'post_status'    => array('publish', 'draft', 'pending', 'private'),
                    'posts_per_page' => 1,
                    'title'          => 'Home',
                )
            );

            if (! empty($maybeHomePages) && $maybeHomePages[0] instanceof \WP_Post) {
                $homePage = $maybeHomePages[0];
            }
        }

        if (! $homePage instanceof \WP_Post) {
            $homePageId = wp_insert_post(
                array(
                    'post_title'   => 'Home',
                    'post_name'    => 'home',
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => '',
                ),
                true
            );

            if (is_wp_error($homePageId) || ! is_int($homePageId)) {
                return;
            }

            $homePage = get_post($homePageId);
        }

        if (! $homePage instanceof \WP_Post) {
            return;
        }

        update_post_meta($homePage->ID, '_wp_page_template', 'home.php');
        update_option('show_on_front', 'page');
        update_option('page_on_front', (int) $homePage->ID);
    }

    private static function loadTextDomain(): void
    {
        load_plugin_textdomain('transiti', false, dirname(TRANSITI_PLUGIN_BASENAME) . '/languages');
    }
}


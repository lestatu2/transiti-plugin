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
        add_action('wp_enqueue_scripts', [self::class, 'enqueueFrontendAssets']);
        add_action('admin_init', [self::class, 'maybeEnsureForumPageInMainMenu']);
        add_action('admin_init', [self::class, 'registerViewsAdminColumns']);
        add_filter('manage_rivista_posts_columns', [self::class, 'addRivistaAdminColumns']);
        add_action('manage_rivista_posts_custom_column', [self::class, 'renderRivistaAdminColumn'], 10, 2);
        add_filter('manage_edit-rivista_sortable_columns', [self::class, 'sortableRivistaAdminColumns']);
        add_action('pre_get_posts', [self::class, 'handleRivistaAdminSorting']);

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

        register_post_type(
            'rivista',
            array(
                'labels' => array(
                    'name'          => __('Riviste', 'transiti'),
                    'singular_name' => __('Rivista', 'transiti'),
                    'add_new_item'  => __('Aggiungi rivista', 'transiti'),
                    'edit_item'     => __('Modifica rivista', 'transiti'),
                    'new_item'      => __('Nuova rivista', 'transiti'),
                    'view_item'     => __('Vedi rivista', 'transiti'),
                    'search_items'  => __('Cerca riviste', 'transiti'),
                ),
                'public'              => true,
                'show_in_rest'        => true,
                'menu_position'       => 22,
                'menu_icon'           => 'dashicons-book-alt',
                'supports'            => array('title', 'thumbnail'),
                'has_archive'         => true,
                'rewrite'             => array('slug' => 'rivista'),
                'exclude_from_search' => false,
                'publicly_queryable'  => true,
            )
        );

        register_post_type(
            'podcast',
            array(
                'labels' => array(
                    'name'          => __('Podcast', 'transiti'),
                    'singular_name' => __('Podcast', 'transiti'),
                    'add_new_item'  => __('Aggiungi podcast', 'transiti'),
                    'edit_item'     => __('Modifica podcast', 'transiti'),
                    'new_item'      => __('Nuovo podcast', 'transiti'),
                    'view_item'     => __('Vedi podcast', 'transiti'),
                    'search_items'  => __('Cerca podcast', 'transiti'),
                ),
                'public'              => true,
                'show_in_rest'        => true,
                'menu_position'       => 23,
                'menu_icon'           => 'dashicons-microphone',
                'supports'            => array('title', 'editor', 'excerpt', 'thumbnail'),
                'has_archive'         => true,
                'rewrite'             => array('slug' => 'podcast'),
                'exclude_from_search' => false,
                'publicly_queryable'  => true,
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


    public static function addRivistaAdminColumns(array $columns): array
    {
        $ordered = array();

        foreach ($columns as $key => $label) {
            $ordered[$key] = $label;

            if ($key === 'title') {
                $ordered['rivista_numero'] = __('Numero', 'transiti');
                $ordered['rivista_data'] = __('Data', 'transiti');
            }
        }

        if (! isset($ordered['rivista_numero'])) {
            $ordered['rivista_numero'] = __('Numero', 'transiti');
        }

        if (! isset($ordered['rivista_data'])) {
            $ordered['rivista_data'] = __('Data', 'transiti');
        }

        return $ordered;
    }

    public static function renderRivistaAdminColumn(string $column, int $postId): void
    {
        if ($column === 'rivista_numero') {
            echo (int) get_post_meta($postId, 'numero', true);
            return;
        }

        if ($column !== 'rivista_data') {
            return;
        }

        $rawDate = (string) get_post_meta($postId, 'data', true);
        if ($rawDate === '') {
            echo '-';
            return;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $rawDate);
        echo esc_html($date ? $date->format('d/m/Y') : $rawDate);
    }

    public static function sortableRivistaAdminColumns(array $columns): array
    {
        $columns['rivista_numero'] = 'rivista_numero';
        $columns['rivista_data'] = 'rivista_data';

        return $columns;
    }

    public static function handleRivistaAdminSorting(\WP_Query $query): void
    {
        if (! is_admin() || ! $query->is_main_query()) {
            return;
        }

        if ($query->get('post_type') !== 'rivista') {
            return;
        }

        $orderby = (string) $query->get('orderby');

        if ($orderby === 'rivista_numero') {
            $query->set('meta_key', 'numero');
            $query->set('orderby', 'meta_value_num');
            return;
        }

        if ($orderby === 'rivista_data') {
            $query->set('meta_key', 'data');
            $query->set('orderby', 'meta_value');
        }
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

        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_preview()) {
            return;
        }

        if (! is_singular()) {
            return;
        }

        $post = get_queried_object();
        if (! $post instanceof \WP_Post) {
            return;
        }

        $trackedPostTypes = self::getTrackedPostTypes();

        if (empty($trackedPostTypes) || ! in_array($post->post_type, $trackedPostTypes, true)) {
            return;
        }

        $postId = (int) $post->ID;
        $hasCookie = self::hasViewSessionCookie($postId);

        if ($postId <= 0 || $hasCookie) {
            return;
        }

        $currentCount = (int) get_post_meta($postId, '_transiti_views_count', true);
        update_post_meta($postId, '_transiti_views_count', $currentCount + 1);
        self::setViewSessionCookie($postId);
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

    /**
     * @return array<int, array{title:string,link:string,image_url:string}>
     */
    public static function getCatalogFeedBooks(int $limit = 12): array
    {
        $config = self::getFeedConfiguration();
        if ($config['url'] === '') {
            return array();
        }

        $cacheKey = 'transiti_feed_' . md5('v3_page_only|' . $config['url'] . '|' . $config['image_class']);
        $forceRefresh = isset($_GET['transiti_feed_refresh']) && (string) $_GET['transiti_feed_refresh'] === '1';

        if ($forceRefresh) {
            delete_transient($cacheKey);
        }

        $cached = get_transient($cacheKey);

        if (is_array($cached)) {
            return array_slice($cached, 0, max(1, $limit));
        }

        $items = self::fetchCatalogFeedItems($config['url'], $config['image_class']);
        if (! empty($items)) {
            set_transient($cacheKey, $items, 30 * MINUTE_IN_SECONDS);
        }

        return array_slice($items, 0, max(1, $limit));
    }

    public static function getCatalogFallbackImageUrl(): string
    {
        $config = self::getFeedConfiguration();
        if ($config['fallback_image_id'] <= 0) {
            return '';
        }

        return (string) wp_get_attachment_image_url($config['fallback_image_id'], 'large');
    }

    /**
     * @return array{url:string,fallback_image_id:int,image_class:string}
     */
    private static function getFeedConfiguration(): array
    {
        $feedUrl = '';
        $fallbackImageId = 0;
        $imageClass = '';

        if (function_exists('get_field')) {
            $feedUrl = (string) get_field('catalogo_feed_url', 'option');
            $fallbackImageId = (int) get_field('catalogo_feed_fallback_image', 'option');
            $imageClass = (string) get_field('catalogo_feed_image_class', 'option');
        }

        if ($feedUrl === '') {
            $feedUrl = (string) get_option('options_catalogo_feed_url', '');
        }

        if ($fallbackImageId <= 0) {
            $fallbackImageId = (int) get_option('options_catalogo_feed_fallback_image', 0);
        }

        if ($imageClass === '') {
            $imageClass = (string) get_option('options_catalogo_feed_image_class', 'imgzoom');
        }

        return array(
            'url' => esc_url_raw(trim($feedUrl)),
            'fallback_image_id' => $fallbackImageId,
            'image_class' => sanitize_html_class(trim($imageClass)),
        );
    }

    /**
     * @return array<int, array{title:string,link:string,image_url:string}>
     */
    private static function fetchCatalogFeedItems(string $feedUrl, string $imageClass): array
    {
        $response = wp_remote_get(
            $feedUrl,
            array(
                'timeout' => 12,
                'redirection' => 3,
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                'headers' => array(
                    'Accept' => 'application/rss+xml, application/xml;q=0.9, text/xml;q=0.8, */*;q=0.5',
                ),
            )
        );

        if (is_wp_error($response)) {
            return array();
        }

        $statusCode = (int) wp_remote_retrieve_response_code($response);
        if ($statusCode < 200 || $statusCode >= 300) {
            return array();
        }

        $body = (string) wp_remote_retrieve_body($response);
        if ($body === '') {
            return array();
        }

        $internalErrors = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        if (! $xml instanceof \SimpleXMLElement || ! isset($xml->channel->item)) {
            return array();
        }

        $items = array();

        foreach ($xml->channel->item as $item) {
            $title = trim((string) ($item->title ?? ''));
            $link = esc_url_raw(trim((string) ($item->link ?? '')));
            $imageUrl = '';

            if ($title === '' || $link === '') {
                continue;
            }

            // Fonte unica: pagina prodotto WooCommerce (immagine reale del libro)
            $imageUrl = self::fetchImageUrlFromItemPage($link, $imageClass);

            $items[] = array(
                'title' => $title,
                'link' => $link,
                'image_url' => $imageUrl,
            );
        }

        return $items;
    }

    private static function fetchImageUrlFromItemPage(string $url, string $imageClass): string
    {
        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 10,
                'redirection' => 3,
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                'headers' => array(
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ),
            )
        );

        if (is_wp_error($response)) {
            return '';
        }

        $statusCode = (int) wp_remote_retrieve_response_code($response);
        if ($statusCode < 200 || $statusCode >= 300) {
            return '';
        }

        $html = (string) wp_remote_retrieve_body($response);
        if ($html === '') {
            return '';
        }

        $wooImage = self::extractWooPrimaryImageFromHtml($html);
        if ($wooImage !== '') {
            return $wooImage;
        }

        return self::extractImageUrlByClassFromHtml($html, $imageClass);
    }

    private static function extractWooPrimaryImageFromHtml(string $html): string
    {
        // Target esplicito: <div class="woocommerce-product-gallery__image"><a href="...">
        if (
            preg_match(
                '/<div[^>]*class="[^"]*woocommerce-product-gallery__image[^"]*"[^>]*>.*?<a[^>]+href=["\']([^"\']+)["\']/is',
                $html,
                $m
            ) === 1
        ) {
            $href = esc_url_raw(trim((string) ($m[1] ?? '')));
            if ($href !== '') {
                return $href;
            }
        }

        // Fallback robusti specifici WooCommerce.
        if (preg_match('/<img[^>]+class="[^"]*wp-post-image[^"]*"[^>]+data-large_image=["\']([^"\']+)["\']/i', $html, $m) === 1) {
            $url = esc_url_raw(trim((string) ($m[1] ?? '')));
            if ($url !== '') {
                return $url;
            }
        }

        if (preg_match('/<img[^>]+class="[^"]*wp-post-image[^"]*"[^>]+src=["\']([^"\']+)["\']/i', $html, $m) === 1) {
            $url = esc_url_raw(trim((string) ($m[1] ?? '')));
            if ($url !== '') {
                return $url;
            }
        }

        if (preg_match('/<div[^>]+class="[^"]*woocommerce-product-gallery__image[^"]*"[^>]+data-thumb=["\']([^"\']+)["\']/i', $html, $m) === 1) {
            $url = esc_url_raw(trim((string) ($m[1] ?? '')));
            if ($url !== '') {
                return $url;
            }
        }

        return '';
    }

    private static function extractImageUrlByClassFromHtml(string $html, string $imageClass): string
    {
        if (! class_exists('\DOMDocument')) {
            return '';
        }

        $internalErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        if (! $loaded) {
            return '';
        }

        $xpath = new \DOMXPath($dom);
        $queries = array(
            '//meta[@property="og:image"]/@content',
            '//meta[@name="twitter:image"]/@content',
            '//img[contains(concat(" ", normalize-space(@class), " "), " wp-post-image ")]',
            '//figure[contains(@class, "woocommerce-product-gallery")]//img',
            '//div[contains(@class, "woocommerce-product-gallery")]//img',
        );

        $classCandidates = array_filter(array_unique(array_map('strtolower', array(
            trim($imageClass),
            'zoomimg',
            'imgzoom',
        ))));

        foreach ($classCandidates as $classCandidate) {
            $queries[] = sprintf(
                '//img[contains(concat(" ", translate(normalize-space(@class), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " "), " %s ")]',
                $classCandidate
            );
        }

        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if (! $nodes instanceof \DOMNodeList || $nodes->length === 0) {
                continue;
            }

            foreach ($nodes as $node) {
                $url = self::resolveImageUrlFromNode($node);
                if ($url !== '') {
                    return $url;
                }
            }
        }

        $genericImages = $xpath->query('//img');
        if (! $genericImages instanceof \DOMNodeList || $genericImages->length === 0) {
            return '';
        }

        $bestUrl = '';
        $bestScore = 0;

        foreach ($genericImages as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }

            $url = self::resolveImageUrlFromNode($node);
            if ($url === '' || preg_match('/(logo|icon|avatar|emoji|gravatar)/i', $url) === 1) {
                continue;
            }

            $width = (int) $node->getAttribute('width');
            $height = (int) $node->getAttribute('height');
            $score = max(1, $width * $height);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestUrl = $url;
            }
        }

        if ($bestUrl !== '') {
            return $bestUrl;
        }

        return '';
    }

    /**
     * @param \DOMNode|\DOMAttr $node
     */
    private static function resolveImageUrlFromNode($node): string
    {
        if ($node instanceof \DOMAttr) {
            return esc_url_raw(trim((string) $node->value));
        }

        if (! $node instanceof \DOMElement) {
            return '';
        }

        $candidateAttributes = array(
            'data-large_image',
            'data-src',
            'data-lazy-src',
            'src',
        );

        foreach ($candidateAttributes as $attribute) {
            $url = esc_url_raw(trim($node->getAttribute($attribute)));
            if ($url !== '') {
                return $url;
            }
        }

        $srcset = trim($node->getAttribute('srcset'));
        if ($srcset !== '') {
            $parts = array_map('trim', explode(',', $srcset));
            $best = '';
            $bestW = 0;

            foreach ($parts as $part) {
                if ($part === '') {
                    continue;
                }

                $tokens = preg_split('/\s+/', $part);
                if (! is_array($tokens) || empty($tokens)) {
                    continue;
                }

                $url = esc_url_raw((string) ($tokens[0] ?? ''));
                $w = 0;

                if (isset($tokens[1]) && preg_match('/(\d+)w/', (string) $tokens[1], $m) === 1) {
                    $w = (int) $m[1];
                }

                if ($url !== '' && $w >= $bestW) {
                    $bestW = $w;
                    $best = $url;
                }
            }

            if ($best !== '') {
                return $best;
            }
        }

        return '';
    }

    private static function loadTextDomain(): void
    {
        load_plugin_textdomain('transiti', false, dirname(TRANSITI_PLUGIN_BASENAME) . '/languages');
    }

    public static function enqueueFrontendAssets(): void
    {
        if (is_admin()) {
            return;
        }

        if (! is_page_template('home.php') && ! is_front_page()) {
            return;
        }

        wp_enqueue_style(
            'transiti-swiper',
            'https://cdn.jsdelivr.net/npm/swiper@latest/swiper-bundle.min.css',
            array(),
            null
        );

        wp_enqueue_script(
            'transiti-swiper',
            'https://cdn.jsdelivr.net/npm/swiper@latest/swiper-bundle.min.js',
            array(),
            null,
            true
        );
    }
}


<?php

declare(strict_types=1);

namespace Fabermind\Transiti\Core;

use Fabermind\Transiti\Admin\Admin;

final class Plugin
{
    private const PROFILE_PAGE_TEMPLATE = 'transiti-profile-template.php';
    private const ABOUT_PAGE_TEMPLATE = 'transiti-about-template.php';

    public static function boot(): void
    {
        add_action('plugins_loaded', [self::class, 'onPluginsLoaded']);
        add_action('init', [self::class, 'registerPostTypes']);
        add_action('init', [self::class, 'registerTaxonomies']);
        add_action('init', [self::class, 'registerRoles'], 5);
        add_action('init', [self::class, 'ensureRivistaCategoryTerms'], 25);
        add_action('wp_ajax_transiti_track_post_view', [self::class, 'trackPostViewAjax']);
        add_action('wp_ajax_nopriv_transiti_track_post_view', [self::class, 'trackPostViewAjax']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueFrontendAssets']);
        add_action('admin_init', [self::class, 'maybeEnsureForumPageInMainMenu']);
        add_action('admin_init', [self::class, 'maybeEnsureContactPage']);
        add_action('admin_init', [self::class, 'maybeEnsureProfilePage']);
        add_action('admin_init', [self::class, 'maybeEnsureAboutPage']);
        add_action('admin_init', [self::class, 'maybeEnsurePodcastArchiveInMainMenu']);
        add_action('admin_init', [self::class, 'maybeEnsureRivistaArchiveInMainMenu']);
        add_action('admin_init', [self::class, 'registerViewsAdminColumns']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueRivistaAdminAssets']);
        add_action('acf/save_post', [self::class, 'syncPodcastEpisodeActivityMeta'], 20);
        add_action('wp_ajax_transiti_track_podcast_session_view', [self::class, 'trackPodcastEpisodeSessionView']);
        add_action('wp_ajax_nopriv_transiti_track_podcast_session_view', [self::class, 'trackPodcastEpisodeSessionView']);
        add_action('wp_ajax_nopriv_transiti_custom_login', [self::class, 'handleCustomLoginAjax']);
        add_action('wp_ajax_nopriv_transiti_custom_lost_password', [self::class, 'handleCustomLostPasswordAjax']);
        add_filter('wp_nav_menu_objects', [self::class, 'injectPodcastMenuItemAtRender'], 20, 2);
        add_filter('theme_page_templates', [self::class, 'registerProfilePageTemplate']);
        add_filter('template_include', [self::class, 'resolveProfilePageTemplate'], 99);
        add_filter('pre_insert_term', [self::class, 'restrictSezioniTermCreation'], 10, 2);
        add_filter('manage_rivista_posts_columns', [self::class, 'addRivistaAdminColumns']);
        add_action('manage_rivista_posts_custom_column', [self::class, 'renderRivistaAdminColumn'], 10, 2);
        add_filter('manage_edit-rivista_sortable_columns', [self::class, 'sortableRivistaAdminColumns']);
        add_action('pre_get_posts', [self::class, 'handleRivistaAdminSorting']);

        add_filter('wp_terms_checklist_args', [self::class, 'filterSezioniChecklistArgs']);
        add_action('admin_footer-post.php', [self::class, 'printSezioniRadioScript']);
        add_action('admin_footer-post-new.php', [self::class, 'printSezioniRadioScript']);

        add_action('save_post_post', [self::class, 'enforceSingleSezioneTerm']);
        add_action('save_post_post', [self::class, 'ensureAsgarosTopicForPublishedPostOnSave'], 40, 3);
        add_action('add_meta_boxes_rivista', [self::class, 'registerRivistaAssociatedPostsMetabox']);
        add_action('save_post_rivista', [self::class, 'saveRivistaAssociatedPostsMetabox']);
        add_action('save_post_rivista', [self::class, 'syncRivistaForumInAsgaros'], 30, 3);
        add_action('transition_post_status', [self::class, 'initializeViewsCountOnFirstPublish'], 20, 3);
        add_action('transition_post_status', [self::class, 'createAsgarosTopicForPostOnPublish'], 30, 3);
        add_action('transition_post_status', [self::class, 'notifyRedattoriOnAuthorReview'], 20, 3);
    }

    public static function activate(): void
    {
        self::registerPostTypes();
        self::registerRoles();
        self::registerTaxonomies();
        self::ensureHomePage();
        self::ensureContactPage();
        self::ensureProfilePage();
        self::ensureAboutPage();
        self::ensureSezioniTerms();
        self::ensureRivistaCategoryTerms();
        self::ensureSezioniTermsInMainMenu();
        self::ensureForumPageInMainMenuAfterSezioni();
        self::ensurePodcastArchiveInMainMenuAfterForum();
        self::ensureRivistaArchiveInMainMenuAfterPodcast();
        self::ensureContactPageInMainMenuAfterForum();
        self::ensureAboutPageInMainMenuAfterPodcast();
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
                'supports'            => array('title', 'editor', 'excerpt', 'thumbnail', 'author'),
                'taxonomies'          => array('category'),
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

        register_post_type(
            'faq',
            array(
                'labels' => array(
                    'name'          => __('FAQ', 'transiti'),
                    'singular_name' => __('FAQ', 'transiti'),
                    'add_new_item'  => __('Aggiungi FAQ', 'transiti'),
                    'edit_item'     => __('Modifica FAQ', 'transiti'),
                    'new_item'      => __('Nuova FAQ', 'transiti'),
                    'view_item'     => __('Vedi FAQ', 'transiti'),
                    'search_items'  => __('Cerca FAQ', 'transiti'),
                ),
                'public'              => true,
                'show_in_rest'        => true,
                'menu_position'       => 24,
                'menu_icon'           => 'dashicons-editor-help',
                'supports'            => array('title', 'editor'),
                'has_archive'         => true,
                'rewrite'             => array('slug' => 'faq'),
                'exclude_from_search' => false,
                'publicly_queryable'  => true,
            )
        );

    }

    public static function registerTaxonomies(): void
    {
        register_taxonomy_for_object_type('category', 'rivista');

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
            'faq_category',
            array('faq'),
            array(
                'labels' => array(
                    'name'          => __('Categorie FAQ', 'transiti'),
                    'singular_name' => __('Categoria FAQ', 'transiti'),
                    'search_items'  => __('Cerca categorie FAQ', 'transiti'),
                    'all_items'     => __('Tutte le categorie FAQ', 'transiti'),
                    'edit_item'     => __('Modifica categoria FAQ', 'transiti'),
                    'update_item'   => __('Aggiorna categoria FAQ', 'transiti'),
                    'add_new_item'  => __('Aggiungi categoria FAQ', 'transiti'),
                    'new_item_name' => __('Nuova categoria FAQ', 'transiti'),
                    'menu_name'     => __('Categorie FAQ', 'transiti'),
                ),
                'public'            => true,
                'hierarchical'      => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'rewrite'           => array('slug' => 'categoria-faq'),
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

    /**
     * Allow creating terms in "sezioni" only to administrators.
     *
     * @param string|\WP_Error $term
     * @param string           $taxonomy
     * @return string|\WP_Error
     */
    public static function restrictSezioniTermCreation($term, string $taxonomy)
    {
        if ($taxonomy !== 'sezioni') {
            return $term;
        }

        if (current_user_can('manage_options')) {
            return $term;
        }

        return new \WP_Error(
            'transiti_sezioni_create_forbidden',
            __('Solo gli amministratori possono aggiungere nuove sezioni.', 'transiti')
        );
    }

    public static function notifyRedattoriOnAuthorReview(string $newStatus, string $oldStatus, \WP_Post $post): void
    {
        if ($newStatus !== 'pending' || $oldStatus === 'pending') {
            self::appendReviewNotificationLog(
                array(
                    'result'     => 'skipped',
                    'reason'     => 'status_not_pending_transition',
                    'post_id'    => (int) $post->ID,
                    'new_status' => $newStatus,
                    'old_status' => $oldStatus,
                )
            );
            return;
        }

        if ($post->post_type !== 'post') {
            self::appendReviewNotificationLog(
                array(
                    'result'     => 'skipped',
                    'reason'     => 'not_post_type_post',
                    'post_id'    => (int) $post->ID,
                    'new_status' => $newStatus,
                    'old_status' => $oldStatus,
                    'post_type'  => (string) $post->post_type,
                )
            );
            return;
        }

        $postId = (int) $post->ID;
        if ($postId <= 0 || wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
            self::appendReviewNotificationLog(
                array(
                    'result'  => 'skipped',
                    'reason'  => 'invalid_post_or_autosave_revision',
                    'post_id' => $postId,
                )
            );
            return;
        }

        $author = get_userdata((int) $post->post_author);
        if (! $author instanceof \WP_User || ! in_array('author', (array) $author->roles, true)) {
            self::appendReviewNotificationLog(
                array(
                    'result'       => 'skipped',
                    'reason'       => 'post_author_not_author_role',
                    'post_id'      => $postId,
                    'author_id'    => (int) $post->post_author,
                    'author_roles' => $author instanceof \WP_User ? (array) $author->roles : array(),
                )
            );
            return;
        }

        $recipientIds = self::getConfiguredRedattoriRecipientIds();
        if (empty($recipientIds)) {
            self::appendReviewNotificationLog(
                array(
                    'result'    => 'skipped',
                    'reason'    => 'no_configured_recipient_ids',
                    'post_id'   => $postId,
                    'author_id' => (int) $author->ID,
                )
            );
            return;
        }

        $recipientEmails = array();
        foreach ($recipientIds as $recipientId) {
            $recipient = get_userdata((int) $recipientId);
            if (! $recipient instanceof \WP_User) {
                continue;
            }

            if (! in_array('redattore', (array) $recipient->roles, true)) {
                continue;
            }

            $email = sanitize_email((string) $recipient->user_email);
            if ($email !== '') {
                $recipientEmails[] = $email;
            }
        }

        $recipientEmails = array_values(array_unique($recipientEmails));
        if (empty($recipientEmails)) {
            self::appendReviewNotificationLog(
                array(
                    'result'         => 'skipped',
                    'reason'         => 'no_valid_recipient_emails',
                    'post_id'        => $postId,
                    'author_id'      => (int) $author->ID,
                    'recipient_ids'  => $recipientIds,
                )
            );
            return;
        }

        $postTitle = trim((string) get_the_title($postId));
        $subject = sprintf(__('Nuovo articolo inviato in revisione: %s', 'transiti'), $postTitle !== '' ? $postTitle : ('#' . $postId));
        $editLink = admin_url('post.php?post=' . $postId . '&action=edit');
        $authorName = trim((string) $author->display_name);

        $message = implode(
            "\n\n",
            array(
                __('Un autore ha inviato un articolo in revisione.', 'transiti'),
                sprintf(__('Titolo: %s', 'transiti'), $postTitle !== '' ? $postTitle : ('#' . $postId)),
                sprintf(__('Autore: %s', 'transiti'), $authorName !== '' ? $authorName : ('#' . (int) $author->ID)),
                sprintf(__('Modifica articolo: %s', 'transiti'), $editLink),
            )
        );

        $mailSent = wp_mail($recipientEmails, $subject, $message);
        self::appendReviewNotificationLog(
            array(
                'result'            => $mailSent ? 'sent' : 'failed',
                'post_id'           => $postId,
                'post_title'        => $postTitle,
                'author_id'         => (int) $author->ID,
                'author_name'       => $authorName,
                'recipient_ids'     => $recipientIds,
                'recipient_emails'  => $recipientEmails,
            )
        );
    }

    /**
     * @return array<int, int>
     */
    private static function getConfiguredRedattoriRecipientIds(): array
    {
        $rawValue = null;

        if (function_exists('get_field')) {
            $rawValue = get_field('destinatari_redattori', 'option');
        }

        if (! is_array($rawValue)) {
            $rawValue = get_option('options_destinatari_redattori', array());
        }

        if (! is_array($rawValue)) {
            return array();
        }

        $ids = array();
        foreach ($rawValue as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        $ids = array_values(array_unique($ids));

        return $ids;
    }

    /**
     * Store last review notification events for local debugging.
     *
     * @param array<string, mixed> $entry
     */
    private static function appendReviewNotificationLog(array $entry): void
    {
        $optionKey = 'transiti_review_notification_log';
        $logs = get_option($optionKey, array());
        if (! is_array($logs)) {
            $logs = array();
        }

        $entry['timestamp'] = current_time('mysql');
        $logs[] = $entry;

        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }

        update_option($optionKey, $logs, false);
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

    public static function enqueueRivistaAdminAssets(string $hookSuffix): void
    {
        if (! in_array($hookSuffix, array('post.php', 'post-new.php'), true)) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (! $screen instanceof \WP_Screen || $screen->post_type !== 'rivista') {
            return;
        }

        wp_enqueue_script('jquery-ui-sortable');
    }

    public static function registerRivistaAssociatedPostsMetabox(): void
    {
        add_meta_box(
            'transiti-rivista-associated-posts',
            __('Articoli associati', 'transiti'),
            [self::class, 'renderRivistaAssociatedPostsMetabox'],
            'rivista',
            'normal',
            'default'
        );
    }

    public static function renderRivistaAssociatedPostsMetabox(\WP_Post $post): void
    {
        $rivistaId = (int) $post->ID;
        $associatedPosts = get_posts(
            array(
                'post_type'              => 'post',
                'post_status'            => 'publish',
                'posts_per_page'         => -1,
                'orderby'                => 'date',
                'order'                  => 'DESC',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'meta_query'             => array(
                    array(
                        'key'     => 'post_rivista_assoc',
                        'value'   => $rivistaId,
                        'compare' => '=',
                    ),
                ),
            )
        );

        $associatedIds = array_map(
            static fn($item): int => (int) $item->ID,
            array_filter(
                $associatedPosts,
                static fn($item): bool => $item instanceof \WP_Post
            )
        );

        $savedOrder = get_post_meta($rivistaId, '_transiti_rivista_related_posts_order', true);
        $savedOrder = is_array($savedOrder) ? array_map('intval', $savedOrder) : array();
        $savedOrder = array_values(array_intersect($savedOrder, $associatedIds));

        $remainingIds = array_values(array_diff($associatedIds, $savedOrder));
        $orderedIds = array_values(array_merge($savedOrder, $remainingIds));

        $orderedPosts = array();
        foreach ($orderedIds as $postId) {
            $candidate = get_post($postId);
            if ($candidate instanceof \WP_Post && $candidate->post_type === 'post' && $candidate->post_status === 'publish') {
                $orderedPosts[] = $candidate;
            }
        }

        wp_nonce_field('transiti_rivista_related_posts_order', 'transiti_rivista_related_posts_order_nonce');

        echo '<p>' . esc_html__('Seleziona e ordina gli articoli associati. L ordine determina la priorita.', 'transiti') . '</p>';
        echo '<input type="hidden" id="transiti-rivista-related-order" name="transiti_rivista_related_order" value="' . esc_attr(implode(',', $savedOrder)) . '">';
        echo '<ul id="transiti-rivista-related-list" style="margin:0;padding:0;list-style:none;">';

        foreach ($orderedPosts as $orderedPost) {
            $postId = (int) $orderedPost->ID;
            $authorName = get_the_author_meta('display_name', (int) $orderedPost->post_author);
            $authorProfession = '';
            if (function_exists('get_field')) {
                $authorProfession = trim((string) get_field('transiti_user_professione', 'user_' . (int) $orderedPost->post_author));
            }
            $sezioni = get_the_terms($postId, 'sezioni');
            $sezioneName = '';
            if (is_array($sezioni) && ! empty($sezioni) && $sezioni[0] instanceof \WP_Term) {
                $sezioneName = (string) $sezioni[0]->name;
            }
            echo '<li data-post-id="' . esc_attr((string) $postId) . '" style="display:flex;gap:12px;align-items:center;padding:10px 12px;border:1px solid #ddd;margin-bottom:8px;background:#fff;cursor:move;">';
            echo '<span class="dashicons dashicons-move" aria-hidden="true"></span>';
            echo '<div style="display:flex;align-items:flex-start;gap:8px;margin:0;flex:1;">';
            echo '<span>';
            echo '<strong>' . esc_html(get_the_title($postId)) . '</strong>';
            if ($sezioneName !== '' || $authorName !== '') {
                echo '<br><small style="opacity:.8;">';
                if ($sezioneName !== '') {
                    echo esc_html($sezioneName);
                }
                if ($sezioneName !== '' && $authorName !== '') {
                    echo ' / ';
                }
                if ($authorName !== '') {
                    echo esc_html((string) $authorName);
                }
                if ($authorProfession !== '') {
                    echo ' - ' . esc_html($authorProfession);
                }
                echo '</small>';
            }
            echo '</span>';
            echo '</div>';
            echo '<a href="' . esc_url(get_edit_post_link($postId, '')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Modifica', 'transiti') . '</a>';
            echo '</li>';
        }

        if (empty($orderedPosts)) {
            echo '<li style="padding:10px 12px;border:1px solid #ddd;background:#fff;">' . esc_html__('Nessun articolo associato a questa rivista.', 'transiti') . '</li>';
        }

        echo '</ul>';
        ?>
        <script>
            (function ($) {
                var list = $('#transiti-rivista-related-list');
                var input = $('#transiti-rivista-related-order');
                if (!list.length || !input.length) {
                    return;
                }

                var updateOrder = function () {
                    var ids = [];
                    list.find('li[data-post-id]').each(function () {
                        ids.push(String($(this).data('post-id')));
                    });
                    input.val(ids.join(','));
                };

                if (typeof list.sortable === 'function') {
                    list.sortable({
                        axis: 'y',
                        items: 'li[data-post-id]',
                        containment: 'parent',
                        update: updateOrder
                    });
                }

                updateOrder();
            })(jQuery);
        </script>
        <?php
    }

    public static function saveRivistaAssociatedPostsMetabox(int $postId): void
    {
        if (wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
            return;
        }

        if (! isset($_POST['transiti_rivista_related_posts_order_nonce']) || ! wp_verify_nonce((string) $_POST['transiti_rivista_related_posts_order_nonce'], 'transiti_rivista_related_posts_order')) {
            return;
        }

        if (! current_user_can('edit_post', $postId)) {
            return;
        }

        $rawOrder = isset($_POST['transiti_rivista_related_order']) ? (string) $_POST['transiti_rivista_related_order'] : '';
        $candidateIds = array_filter(array_map('intval', array_map('trim', explode(',', $rawOrder))));

        if (empty($candidateIds)) {
            delete_post_meta($postId, '_transiti_rivista_related_posts_order');
            return;
        }

        $validAssociatedIds = get_posts(
            array(
                'post_type'              => 'post',
                'post_status'            => 'publish',
                'posts_per_page'         => -1,
                'fields'                 => 'ids',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'meta_query'             => array(
                    array(
                        'key'     => 'post_rivista_assoc',
                        'value'   => (int) $postId,
                        'compare' => '=',
                    ),
                ),
            )
        );

        $validAssociatedIds = array_map('intval', is_array($validAssociatedIds) ? $validAssociatedIds : array());
        $ordered = array_values(array_intersect($candidateIds, $validAssociatedIds));

        if (empty($ordered)) {
            delete_post_meta($postId, '_transiti_rivista_related_posts_order');
            return;
        }

        update_post_meta($postId, '_transiti_rivista_related_posts_order', $ordered);
    }

    public static function syncRivistaForumInAsgaros(int $postId, \WP_Post $post, bool $update): void
    {
        self::logRivistaForumSync('START', array('post_id' => $postId, 'status' => (string) $post->post_status, 'update' => $update ? '1' : '0'));

        try {
            if (wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
                self::logRivistaForumSync('SKIP_AUTOSAVE_OR_REVISION', array('post_id' => $postId));
                return;
            }

            if ($post->post_type !== 'rivista' || $post->post_status !== 'publish') {
                self::logRivistaForumSync('SKIP_NOT_PUBLISHED_RIVISTA', array('post_id' => $postId, 'post_type' => (string) $post->post_type, 'status' => (string) $post->post_status));
                return;
            }

            if (! class_exists('AsgarosForum')) {
                self::logRivistaForumSync('SKIP_ASGAROS_CLASS_MISSING', array('post_id' => $postId));
                return;
            }

            global $asgarosforum;
            if (! $asgarosforum instanceof \AsgarosForum) {
                self::logRivistaForumSync('SKIP_ASGAROS_GLOBAL_INVALID', array('post_id' => $postId));
                return;
            }

            $categoryId = self::getAsgarosTransitiCategoryId();
            if ($categoryId <= 0) {
                self::logRivistaForumSync('SKIP_TRANSITI_CATEGORY_NOT_FOUND', array('post_id' => $postId));
                return;
            }

            $numero = trim((string) get_post_meta($postId, 'numero', true));
            $title = trim((string) get_the_title($postId));
            $forumName = $numero !== '' ? ($numero . ' - ' . $title) : $title;
            $forumName = $forumName !== '' ? $forumName : ('Rivista ' . (string) $postId);
            $forumName = sanitize_text_field($forumName);
            $forumName = wp_html_excerpt($forumName, 255, '');

            $forumDescription = sanitize_text_field(
                trim(
                    wp_strip_all_tags(
                        strip_shortcodes((string) $post->post_content)
                    )
                )
            );
            $forumDescription = wp_html_excerpt($forumDescription, 255, '');

            $forumsTable = (string) $asgarosforum->tables->forums;
            $db = $asgarosforum->db;
            $forumId = (int) get_post_meta($postId, '_transiti_asgaros_forum_id', true);

            if ($forumId > 0) {
                $existingForumId = (int) $db->get_var(
                    $db->prepare("SELECT id FROM {$forumsTable} WHERE id = %d LIMIT 1", $forumId)
                );

                if ($existingForumId > 0) {
                    $updated = $db->update(
                        $forumsTable,
                        array(
                            'name'         => $forumName,
                            'description'  => $forumDescription,
                            'parent_id'    => $categoryId,
                            'parent_forum' => 0,
                            'forum_status' => 'normal',
                        ),
                        array(
                            'id' => $existingForumId,
                        ),
                        array('%s', '%s', '%d', '%d', '%s'),
                        array('%d')
                    );
                    self::logRivistaForumSync('UPDATED_EXISTING_BY_META', array('post_id' => $postId, 'forum_id' => $existingForumId, 'db_result' => (string) $updated));
                    self::syncRivistaEditorialeTopicInForum($postId, $post, $existingForumId, $asgarosforum);
                    return;
                }
            }

            $existingByName = (int) $db->get_var(
                $db->prepare(
                    "SELECT id FROM {$forumsTable} WHERE parent_id = %d AND parent_forum = 0 AND name = %s LIMIT 1",
                    $categoryId,
                    $forumName
                )
            );

            if ($existingByName > 0) {
                update_post_meta($postId, '_transiti_asgaros_forum_id', $existingByName);
                $updated = $db->update(
                    $forumsTable,
                    array(
                        'description' => $forumDescription,
                    ),
                    array(
                        'id' => $existingByName,
                    ),
                    array('%s'),
                    array('%d')
                );
                self::logRivistaForumSync('LINKED_EXISTING_BY_NAME', array('post_id' => $postId, 'forum_id' => $existingByName, 'db_result' => (string) $updated));
                self::syncRivistaEditorialeTopicInForum($postId, $post, $existingByName, $asgarosforum);
                return;
            }

            $maxSort = (int) $db->get_var(
                $db->prepare(
                    "SELECT MAX(sort) FROM {$forumsTable} WHERE parent_id = %d AND parent_forum = 0",
                    $categoryId
                )
            );
            $nextSort = max(1, $maxSort + 1);

            $insertedForumId = (int) $asgarosforum->content->insert_forum(
                $categoryId,
                $forumName,
                $forumDescription,
                0,
                '',
                $nextSort,
                'normal'
            );

            if ($insertedForumId > 0) {
                update_post_meta($postId, '_transiti_asgaros_forum_id', $insertedForumId);
                self::logRivistaForumSync('CREATED_FORUM', array('post_id' => $postId, 'forum_id' => $insertedForumId, 'category_id' => $categoryId));
                self::syncRivistaEditorialeTopicInForum($postId, $post, $insertedForumId, $asgarosforum);
                return;
            }

            self::logRivistaForumSync('INSERT_FAILED', array('post_id' => $postId, 'category_id' => $categoryId, 'forum_name' => $forumName));
            self::logRivistaForumSync('INSERT_DB_ERROR', array('post_id' => $postId, 'db_last_error' => (string) $db->last_error, 'forum_name_length' => strlen($forumName), 'forum_description_length' => strlen($forumDescription)));
        } catch (\Throwable $e) {
            self::logRivistaForumSync('EXCEPTION', array('post_id' => $postId, 'message' => $e->getMessage()));
        }
    }

    private static function syncRivistaEditorialeTopicInForum(int $postId, \WP_Post $post, int $forumId, \AsgarosForum $asgarosforum): void
    {
        $topicTitle = wp_html_excerpt(sanitize_text_field('Editoriale'), 255, '');
        $topicBody = self::buildRivistaEditorialeTopicBody($postId, $post);
        $topicBody = $topicBody !== '' ? $topicBody : $topicTitle;

        $authorId = (int) $post->post_author;
        if ($authorId <= 0) {
            $authorId = (int) get_current_user_id();
        }

        $db = $asgarosforum->db;
        $topicsTable = (string) $asgarosforum->tables->topics;
        $postsTable = (string) $asgarosforum->tables->posts;

        $topicId = (int) get_post_meta($postId, '_transiti_asgaros_topic_id', true);
        if ($topicId > 0) {
            $existingTopicId = (int) $db->get_var(
                $db->prepare("SELECT id FROM {$topicsTable} WHERE id = %d AND parent_id = %d LIMIT 1", $topicId, $forumId)
            );

            if ($existingTopicId > 0) {
                $db->update(
                    $topicsTable,
                    array(
                        'name'   => $topicTitle,
                        'sticky' => 1,
                    ),
                    array(
                        'id' => $existingTopicId,
                    ),
                    array('%s', '%d'),
                    array('%d')
                );

                $firstPostId = (int) $db->get_var(
                    $db->prepare("SELECT id FROM {$postsTable} WHERE parent_id = %d ORDER BY id ASC LIMIT 1", $existingTopicId)
                );

                if ($firstPostId > 0) {
                    $db->update(
                        $postsTable,
                        array(
                            'text'    => $topicBody,
                            'forum_id'=> $forumId,
                        ),
                        array(
                            'id' => $firstPostId,
                        ),
                        array('%s', '%d'),
                        array('%d')
                    );
                    update_post_meta($postId, '_transiti_asgaros_topic_post_id', $firstPostId);
                    self::logRivistaForumSync('UPDATED_EDITORIALE_TOPIC', array('post_id' => $postId, 'forum_id' => $forumId, 'topic_id' => $existingTopicId, 'post_id_topic' => $firstPostId));
                    return;
                }

                $newPostId = (int) $asgarosforum->content->insert_post($existingTopicId, $forumId, $topicBody, $authorId, array());
                if ($newPostId > 0) {
                    update_post_meta($postId, '_transiti_asgaros_topic_post_id', $newPostId);
                    self::logRivistaForumSync('INSERTED_EDITORIALE_POST_IN_TOPIC', array('post_id' => $postId, 'forum_id' => $forumId, 'topic_id' => $existingTopicId, 'post_id_topic' => $newPostId));
                    return;
                }
            }
        }

        $existingByTitle = (int) $db->get_var(
            $db->prepare("SELECT id FROM {$topicsTable} WHERE parent_id = %d AND name = %s ORDER BY id ASC LIMIT 1", $forumId, $topicTitle)
        );

        if ($existingByTitle > 0) {
            update_post_meta($postId, '_transiti_asgaros_topic_id', $existingByTitle);
            $db->update(
                $topicsTable,
                array(
                    'name'   => $topicTitle,
                    'sticky' => 1,
                ),
                array(
                    'id' => $existingByTitle,
                ),
                array('%s', '%d'),
                array('%d')
            );

            $firstPostId = (int) $db->get_var(
                $db->prepare("SELECT id FROM {$postsTable} WHERE parent_id = %d ORDER BY id ASC LIMIT 1", $existingByTitle)
            );

            if ($firstPostId > 0) {
                $db->update(
                    $postsTable,
                    array(
                        'text'    => $topicBody,
                        'forum_id'=> $forumId,
                    ),
                    array(
                        'id' => $firstPostId,
                    ),
                    array('%s', '%d'),
                    array('%d')
                );
                update_post_meta($postId, '_transiti_asgaros_topic_post_id', $firstPostId);
                self::logRivistaForumSync('LINKED_EXISTING_EDITORIALE_TOPIC', array('post_id' => $postId, 'forum_id' => $forumId, 'topic_id' => $existingByTitle, 'post_id_topic' => $firstPostId));
                return;
            }
        }

        $inserted = $asgarosforum->content->insert_topic($forumId, $topicTitle, $topicBody, $authorId, array());
        $newTopicId = (int) ($inserted->topic_id ?? 0);
        $newPostId = (int) ($inserted->post_id ?? 0);

        if ($newTopicId > 0) {
            update_post_meta($postId, '_transiti_asgaros_topic_id', $newTopicId);
            $db->update(
                $topicsTable,
                array(
                    'sticky' => 1,
                ),
                array(
                    'id' => $newTopicId,
                ),
                array('%d'),
                array('%d')
            );
        }
        if ($newPostId > 0) {
            update_post_meta($postId, '_transiti_asgaros_topic_post_id', $newPostId);
        }

        self::logRivistaForumSync('CREATED_EDITORIALE_TOPIC', array('post_id' => $postId, 'forum_id' => $forumId, 'topic_id' => $newTopicId, 'post_id_topic' => $newPostId));
    }

    private static function buildRivistaEditorialeTopicBody(int $postId, \WP_Post $post): string
    {
        $parts = array();

        $thumbnailId = (int) get_post_thumbnail_id($postId);
        $featuredImageUrl = '';
        if ($thumbnailId > 0) {
            $featuredImageUrl = (string) wp_get_attachment_image_url($thumbnailId, 'medium');
            if ($featuredImageUrl === '') {
                $featuredImageUrl = (string) wp_get_attachment_image_url($thumbnailId, 'thumbnail');
            }
        }
        if ($featuredImageUrl !== '') {
            $safeImageUrl = esc_url_raw($featuredImageUrl);
            $parts[] = '<img src="' . $safeImageUrl . '" alt="" data-mce-src="' . $safeImageUrl . '">';
        }

        $excerpt = trim((string) $post->post_excerpt);
        if ($excerpt !== '') {
            $parts[] = wp_kses_post($excerpt);
        }

        $content = trim((string) $post->post_content);
        if ($content !== '') {
            $parts[] = $content;
        }

        return trim(implode("\n\n", $parts));
    }

    public static function createAsgarosTopicForPostOnPublish(string $newStatus, string $oldStatus, \WP_Post $post): void
    {
        if ($newStatus !== 'publish' || $oldStatus === 'publish') {
            return;
        }

        self::maybeCreateAsgarosTopicForPost($post);
    }

    public static function ensureAsgarosTopicForPublishedPostOnSave(int $postId, \WP_Post $post, bool $update): void
    {
        if (wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
            return;
        }

        if ($post->post_type !== 'post' || $post->post_status !== 'publish') {
            return;
        }

        self::maybeCreateAsgarosTopicForPost($post);
    }

    private static function maybeCreateAsgarosTopicForPost(\WP_Post $post): void
    {
        if ($post->post_type !== 'post' || $post->post_status !== 'publish') {
            return;
        }

        $postId = (int) $post->ID;
        if ($postId <= 0 || wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
            return;
        }

        if (! class_exists('AsgarosForum')) {
            return;
        }

        global $asgarosforum;
        if (! $asgarosforum instanceof \AsgarosForum) {
            return;
        }

        $rivistaId = 0;
        if (function_exists('get_field')) {
            $rivistaId = (int) get_field('post_rivista_assoc', $postId);
        }
        if ($rivistaId <= 0) {
            $rivistaId = (int) get_post_meta($postId, 'post_rivista_assoc', true);
        }
        if ($rivistaId <= 0) {
            return;
        }

        $rivistaPost = get_post($rivistaId);
        if (! $rivistaPost instanceof \WP_Post || $rivistaPost->post_type !== 'rivista' || $rivistaPost->post_status !== 'publish') {
            return;
        }

        $forumId = (int) get_post_meta($rivistaId, '_transiti_asgaros_forum_id', true);
        if ($forumId <= 0) {
            self::syncRivistaForumInAsgaros($rivistaId, $rivistaPost, true);
            $forumId = (int) get_post_meta($rivistaId, '_transiti_asgaros_forum_id', true);
        }
        if ($forumId <= 0) {
            return;
        }

        $topicTitle = wp_html_excerpt(trim((string) get_the_title($postId)), 255, '');
        if ($topicTitle === '') {
            $topicTitle = 'Post ' . (string) $postId;
        }

        $topicBody = self::buildPublishedPostTopicBody($postId, $post);
        if ($topicBody === '') {
            $topicBody = $topicTitle;
        }

        $authorId = (int) $post->post_author;
        if ($authorId <= 0) {
            $authorId = (int) get_current_user_id();
        }

        $db = $asgarosforum->db;
        $topicsTable = (string) $asgarosforum->tables->topics;
        $postsTable = (string) $asgarosforum->tables->posts;

        $existingTopicId = (int) get_post_meta($postId, '_transiti_asgaros_topic_id', true);
        if ($existingTopicId > 0) {
            $confirmedTopicId = (int) $db->get_var(
                $db->prepare("SELECT id FROM {$topicsTable} WHERE id = %d LIMIT 1", $existingTopicId)
            );

            if ($confirmedTopicId > 0) {
                $db->update(
                    $topicsTable,
                    array(
                        'name'      => $topicTitle,
                        'parent_id' => $forumId,
                    ),
                    array(
                        'id' => $confirmedTopicId,
                    ),
                    array('%s', '%d'),
                    array('%d')
                );

                $firstPostId = (int) $db->get_var(
                    $db->prepare("SELECT id FROM {$postsTable} WHERE parent_id = %d ORDER BY id ASC LIMIT 1", $confirmedTopicId)
                );

                if ($firstPostId > 0) {
                    $db->update(
                        $postsTable,
                        array(
                            'text'     => $topicBody,
                            'forum_id' => $forumId,
                        ),
                        array(
                            'id' => $firstPostId,
                        ),
                        array('%s', '%d'),
                        array('%d')
                    );
                    update_post_meta($postId, '_transiti_asgaros_topic_post_id', $firstPostId);
                    return;
                }

                $newPostId = (int) $asgarosforum->content->insert_post($confirmedTopicId, $forumId, $topicBody, $authorId, array());
                if ($newPostId > 0) {
                    update_post_meta($postId, '_transiti_asgaros_topic_post_id', $newPostId);
                    return;
                }
            }
        }

        $inserted = $asgarosforum->content->insert_topic($forumId, $topicTitle, $topicBody, $authorId, array());
        $topicId = (int) ($inserted->topic_id ?? 0);
        $topicPostId = (int) ($inserted->post_id ?? 0);

        if ($topicId > 0) {
            update_post_meta($postId, '_transiti_asgaros_topic_id', $topicId);
        }
        if ($topicPostId > 0) {
            update_post_meta($postId, '_transiti_asgaros_topic_post_id', $topicPostId);
        }
    }

    private static function buildPublishedPostTopicBody(int $postId, \WP_Post $post): string
    {
        $parts = array();

        $thumbnailId = (int) get_post_thumbnail_id($postId);
        if ($thumbnailId > 0) {
            $featuredImageUrl = (string) wp_get_attachment_image_url($thumbnailId, array(768, 0));
            if ($featuredImageUrl !== '') {
                $safeImageUrl = esc_url_raw($featuredImageUrl);
                $parts[] = '<img src="' . $safeImageUrl . '" alt="" data-mce-src="' . $safeImageUrl . '">';
            }
        }

        $excerpt = trim((string) get_post_field('post_excerpt', $postId));
        if ($excerpt !== '') {
            $parts[] = $excerpt;
        }

        $content = trim((string) $post->post_content);
        if ($content !== '') {
            $parts[] = $content;
        }

        return trim(implode("\n\n", $parts));
    }

    private static function logRivistaForumSync(string $step, array $context = array()): void
    {
        return;
    }

    private static function getAsgarosTransitiCategoryId(): int
    {
        $category = get_term_by('slug', 'transiti', 'asgarosforum-category');
        if ($category instanceof \WP_Term) {
            return (int) $category->term_id;
        }

        $terms = get_terms(
            array(
                'taxonomy'   => 'asgarosforum-category',
                'hide_empty' => false,
            )
        );

        if (! is_array($terms) || empty($terms)) {
            return 0;
        }

        foreach ($terms as $term) {
            if (! $term instanceof \WP_Term) {
                continue;
            }

            if (strtolower((string) $term->name) === 'transiti') {
                return (int) $term->term_id;
            }
        }

        return 0;
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

        if ($postId <= 0 || get_post_status($postId) !== 'publish') {
            return;
        }

        if (is_user_logged_in()) {
            return;
        }

        if (self::isLikelyPrefetchRequest()) {
            return;
        }

        $hasCookie = self::hasViewSessionCookie($postId);

        if ($postId <= 0 || $hasCookie) {
            return;
        }

        $currentCount = (int) get_post_meta($postId, '_transiti_views_count', true);
        update_post_meta($postId, '_transiti_views_count', $currentCount + 1);
        self::setViewSessionCookie($postId);
    }

    public static function initializeViewsCountOnFirstPublish(string $newStatus, string $oldStatus, \WP_Post $post): void
    {
        if ($newStatus !== 'publish' || $oldStatus === 'publish') {
            return;
        }

        $postId = (int) $post->ID;
        if ($postId <= 0) {
            return;
        }

        update_post_meta($postId, '_transiti_views_count', 0);
    }

    public static function trackPostViewAjax(): void
    {
        check_ajax_referer('transiti_post_view', 'nonce');

        $postId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($postId <= 0) {
            wp_send_json_error(array('message' => 'Invalid post.'), 400);
        }

        $post = get_post($postId);
        if (! $post instanceof \WP_Post || $post->post_status !== 'publish') {
            wp_send_json_error(array('message' => 'Invalid post.'), 400);
        }

        $trackedPostTypes = self::getTrackedPostTypes();
        if (empty($trackedPostTypes) || ! in_array($post->post_type, $trackedPostTypes, true)) {
            wp_send_json_error(array('message' => 'Post type not tracked.'), 400);
        }

        if (self::isLikelyPrefetchRequest() || self::hasViewSessionCookie($postId)) {
            wp_send_json_success(
                array(
                    'count' => (int) get_post_meta($postId, '_transiti_views_count', true),
                )
            );
        }

        $currentCount = (int) get_post_meta($postId, '_transiti_views_count', true);
        $nextCount = $currentCount + 1;
        update_post_meta($postId, '_transiti_views_count', $nextCount);
        self::setViewSessionCookie($postId);

        wp_send_json_success(
            array(
                'count' => $nextCount,
            )
        );
    }

    public static function trackPodcastEpisodeSessionView(): void
    {
        check_ajax_referer('transiti_podcast_session_view', 'nonce');

        $postId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($postId <= 0 || get_post_type($postId) !== 'podcast') {
            wp_send_json_error(array('message' => 'Invalid podcast.'), 400);
        }

        $episodeKeyRaw = isset($_POST['episode_key']) ? sanitize_text_field((string) $_POST['episode_key']) : '';
        $episodeKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $episodeKeyRaw);
        if ($episodeKey === '') {
            wp_send_json_error(array('message' => 'Invalid episode.'), 400);
        }

        $episodeViews = get_post_meta($postId, '_transiti_podcast_episode_session_views', true);
        if (! is_array($episodeViews)) {
            $episodeViews = array();
        }

        $current = isset($episodeViews[$episodeKey]) ? (int) $episodeViews[$episodeKey] : 0;
        $next = $current + 1;
        $episodeViews[$episodeKey] = $next;

        update_post_meta($postId, '_transiti_podcast_episode_session_views', $episodeViews);

        wp_send_json_success(
            array(
                'count'      => $next,
                'episodeKey' => $episodeKey,
            )
        );
    }

    public static function handleCustomLoginAjax(): void
    {
        check_ajax_referer('transiti_custom_login', 'nonce');

        $identifier = isset($_POST['identifier']) ? sanitize_text_field((string) $_POST['identifier']) : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $remember = isset($_POST['remember']) && (string) $_POST['remember'] === '1';

        if ($identifier === '' || $password === '') {
            wp_send_json_error(array('message' => __('Inserisci credenziali valide.', 'transiti')), 400);
        }

        $login = $identifier;
        if (is_email($identifier)) {
            $userByEmail = get_user_by('email', $identifier);
            if ($userByEmail instanceof \WP_User) {
                $login = (string) $userByEmail->user_login;
            }
        }

        $signon = wp_signon(
            array(
                'user_login'    => $login,
                'user_password' => $password,
                'remember'      => $remember,
            ),
            is_ssl()
        );

        if (is_wp_error($signon)) {
            wp_send_json_error(array('message' => __('Credenziali non valide.', 'transiti')), 401);
        }

        wp_set_current_user((int) $signon->ID);

        $redirect = wp_get_referer();
        if (! is_string($redirect) || $redirect === '' || strpos($redirect, '/wp-admin/admin-ajax.php') !== false) {
            $redirect = home_url('/');
        }

        wp_send_json_success(
            array(
                'message'  => __('Accesso effettuato.', 'transiti'),
                'redirect' => $redirect,
            )
        );
    }

    public static function handleCustomLostPasswordAjax(): void
    {
        check_ajax_referer('transiti_custom_lost_password', 'nonce');

        $identifier = isset($_POST['identifier']) ? sanitize_text_field((string) $_POST['identifier']) : '';
        if ($identifier === '') {
            wp_send_json_error(array('message' => __('Inserisci email o username.', 'transiti')), 400);
        }

        $result = retrieve_password($identifier);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => __('Impossibile inviare la richiesta. Verifica i dati.', 'transiti')), 400);
        }

        wp_send_json_success(
            array(
                'message' => __('Se l’account esiste, riceverai un’email per reimpostare la password.', 'transiti'),
            )
        );
    }

    /**
     * Keep podcast episode activity metadata in sync after ACF save.
     *
     * Tracked meta:
     * - _transiti_episodes_count
     * - _transiti_latest_episode_order
     * - _transiti_latest_episode_ts
     *
     * @param mixed $postId
     */
    public static function syncPodcastEpisodeActivityMeta($postId): void
    {
        if (! is_numeric($postId)) {
            return;
        }

        $postIdInt = (int) $postId;
        if ($postIdInt <= 0 || get_post_type($postIdInt) !== 'podcast' || ! function_exists('get_field')) {
            return;
        }

        $episodes = get_field('episodi', $postIdInt);
        $rows = array_values(
            array_filter(
                is_array($episodes) ? $episodes : array(),
                static fn($row): bool => is_array($row)
            )
        );

        $currentCount = count($rows);
        $currentMaxOrder = 0;

        foreach ($rows as $row) {
            $rowOrder = isset($row['numero_ordine']) ? (int) $row['numero_ordine'] : 0;
            if ($rowOrder > $currentMaxOrder) {
                $currentMaxOrder = $rowOrder;
            }
        }

        $previousCount = (int) get_post_meta($postIdInt, '_transiti_episodes_count', true);
        $previousMaxOrder = (int) get_post_meta($postIdInt, '_transiti_latest_episode_order', true);
        $isNewEpisode = $currentCount > $previousCount || $currentMaxOrder > $previousMaxOrder;

        update_post_meta($postIdInt, '_transiti_episodes_count', $currentCount);
        update_post_meta($postIdInt, '_transiti_latest_episode_order', $currentMaxOrder);

        if ($isNewEpisode) {
            update_post_meta($postIdInt, '_transiti_latest_episode_ts', (string) current_time('timestamp'));
        } elseif (get_post_meta($postIdInt, '_transiti_latest_episode_ts', true) === '') {
            update_post_meta($postIdInt, '_transiti_latest_episode_ts', (string) get_post_timestamp($postIdInt, 'date'));
        }
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
        if ($administrator instanceof \WP_Role && ! $administrator->has_cap('manage_transiti_config')) {
            $administrator->add_cap('manage_transiti_config');
        }

        $redattore = get_role('redattore');
        if ($redattore instanceof \WP_Role && ! $redattore->has_cap('manage_transiti_config')) {
            $redattore->add_cap('manage_transiti_config');
        }
    }

    public static function ensureRivistaCategoryTerms(): void
    {
        if (! taxonomy_exists('category')) {
            return;
        }

        $requiredTerms = array(
            array('name' => 'Società', 'slug' => 'societa'),
            array('name' => 'Letteratura', 'slug' => 'letteratura'),
            array('name' => 'Cinema', 'slug' => 'cinema'),
        );

        foreach ($requiredTerms as $requiredTerm) {
            $slug = (string) ($requiredTerm['slug'] ?? '');
            $name = (string) ($requiredTerm['name'] ?? '');
            if ($slug === '' || $name === '') {
                continue;
            }

            if (get_term_by('slug', $slug, 'category') instanceof \WP_Term) {
                continue;
            }

            wp_insert_term($name, 'category', array('slug' => $slug));
        }
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

    private static function isLikelyPrefetchRequest(): bool
    {
        $xMoz = isset($_SERVER['HTTP_X_MOZ']) ? strtolower((string) $_SERVER['HTTP_X_MOZ']) : '';
        if ($xMoz === 'prefetch') {
            return true;
        }

        $secPurpose = isset($_SERVER['HTTP_SEC_PURPOSE']) ? strtolower((string) $_SERVER['HTTP_SEC_PURPOSE']) : '';
        if ($secPurpose !== '' && strpos($secPurpose, 'prefetch') !== false) {
            return true;
        }

        $purpose = isset($_SERVER['HTTP_PURPOSE']) ? strtolower((string) $_SERVER['HTTP_PURPOSE']) : '';
        if ($purpose !== '' && strpos($purpose, 'prefetch') !== false) {
            return true;
        }

        return false;
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
        self::ensurePodcastArchiveInMainMenuAfterForum();
        self::ensureRivistaArchiveInMainMenuAfterPodcast();
        self::ensureContactPageInMainMenuAfterForum();
        self::ensureAboutPageInMainMenuAfterPodcast();
    }

    public static function maybeEnsurePodcastArchiveInMainMenu(): void
    {
        if (! is_admin() || ! current_user_can('manage_options')) {
            return;
        }

        self::ensurePodcastArchiveInMainMenuAfterForum();
        self::ensureRivistaArchiveInMainMenuAfterPodcast();
        self::ensureContactPageInMainMenuAfterForum();
        self::ensureAboutPageInMainMenuAfterPodcast();
    }

    public static function maybeEnsureRivistaArchiveInMainMenu(): void
    {
        if (! is_admin() || ! current_user_can('manage_options')) {
            return;
        }

        self::ensurePodcastArchiveInMainMenuAfterForum();
        self::ensureRivistaArchiveInMainMenuAfterPodcast();
        self::ensureContactPageInMainMenuAfterForum();
        self::ensureAboutPageInMainMenuAfterPodcast();
    }

    public static function maybeEnsureContactPage(): void
    {
        if (! is_admin() || ! current_user_can('manage_options')) {
            return;
        }

        self::ensureContactPage();
        self::ensurePodcastArchiveInMainMenuAfterForum();
        self::ensureRivistaArchiveInMainMenuAfterPodcast();
        self::ensureContactPageInMainMenuAfterForum();
        self::ensureAboutPageInMainMenuAfterPodcast();
    }

    public static function maybeEnsureProfilePage(): void
    {
        if (! is_admin() || ! current_user_can('manage_options')) {
            return;
        }

        self::ensureProfilePage();
    }

    public static function maybeEnsureAboutPage(): void
    {
        if (! is_admin() || ! current_user_can('manage_options')) {
            return;
        }

        self::ensureAboutPage();
        self::ensurePodcastArchiveInMainMenuAfterForum();
        self::ensureRivistaArchiveInMainMenuAfterPodcast();
        self::ensureContactPageInMainMenuAfterForum();
        self::ensureAboutPageInMainMenuAfterPodcast();
    }

    private static function ensureAboutPageInMainMenuAfterPodcast(): void
    {
        $menuId = self::resolveMainMenuId(get_nav_menu_locations());
        if ($menuId <= 0) {
            return;
        }

        $aboutPage = self::findAboutPage();
        if (! $aboutPage instanceof \WP_Post) {
            return;
        }

        $existingItems = wp_get_nav_menu_items($menuId);
        $targetPosition = self::getAboutTargetPosition($existingItems);

        if (is_array($existingItems)) {
            foreach ($existingItems as $item) {
                if (
                    ($item->type ?? '') === 'post_type'
                    && ($item->object ?? '') === 'page'
                    && (int) ($item->object_id ?? 0) === (int) $aboutPage->ID
                ) {
                    wp_update_nav_menu_item(
                        $menuId,
                        (int) $item->ID,
                        array(
                            'menu-item-title'     => $aboutPage->post_title,
                            'menu-item-object'    => 'page',
                            'menu-item-object-id' => (int) $aboutPage->ID,
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
                'menu-item-title'     => $aboutPage->post_title,
                'menu-item-object'    => 'page',
                'menu-item-object-id' => (int) $aboutPage->ID,
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
                'menu-item-parent-id' => 0,
                'menu-item-position'  => $targetPosition,
            )
        );
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

    private static function ensureContactPageInMainMenuAfterForum(): void
    {
        $menuId = self::resolveMainMenuId(get_nav_menu_locations());
        if ($menuId <= 0) {
            return;
        }

        $contactPage = self::findContactPage();
        if (! $contactPage instanceof \WP_Post) {
            return;
        }

        $forumPage = self::findForumPage();
        $forumPageId = $forumPage instanceof \WP_Post ? (int) $forumPage->ID : 0;

        $existingItems = wp_get_nav_menu_items($menuId);
        $targetPosition = self::getContactTargetPosition($existingItems, $forumPageId);

        if (is_array($existingItems)) {
            foreach ($existingItems as $item) {
                if (
                    ($item->type ?? '') === 'post_type'
                    && ($item->object ?? '') === 'page'
                    && (int) ($item->object_id ?? 0) === (int) $contactPage->ID
                ) {
                    wp_update_nav_menu_item(
                        $menuId,
                        (int) $item->ID,
                        array(
                            'menu-item-title'     => $contactPage->post_title,
                            'menu-item-object'    => 'page',
                            'menu-item-object-id' => (int) $contactPage->ID,
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
                'menu-item-title'     => $contactPage->post_title,
                'menu-item-object'    => 'page',
                'menu-item-object-id' => (int) $contactPage->ID,
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
                'menu-item-parent-id' => 0,
                'menu-item-position'  => $targetPosition,
            )
        );
    }

    private static function ensurePodcastArchiveInMainMenuAfterForum(): void
    {
        $menuId = self::resolveMainMenuId(get_nav_menu_locations());
        if ($menuId <= 0) {
            return;
        }

        if (! post_type_exists('podcast')) {
            return;
        }

        $forumPage = self::findForumPage();
        $forumPageId = $forumPage instanceof \WP_Post ? (int) $forumPage->ID : 0;

        $existingItems = wp_get_nav_menu_items($menuId);
        $targetPosition = self::getPodcastTargetPosition($existingItems, $forumPageId);

        if (is_array($existingItems)) {
            foreach ($existingItems as $item) {
                if (
                    ($item->type ?? '') === 'post_type_archive'
                    && ($item->object ?? '') === 'podcast'
                ) {
                    wp_update_nav_menu_item(
                        $menuId,
                        (int) $item->ID,
                        array(
                            'menu-item-title'     => __('Podcast', 'transiti'),
                            'menu-item-object'    => 'podcast',
                            'menu-item-object-id' => 0,
                            'menu-item-type'      => 'post_type_archive',
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
                'menu-item-title'     => __('Podcast', 'transiti'),
                'menu-item-object'    => 'podcast',
                'menu-item-object-id' => 0,
                'menu-item-type'      => 'post_type_archive',
                'menu-item-status'    => 'publish',
                'menu-item-parent-id' => 0,
                'menu-item-position'  => $targetPosition,
            )
        );
    }

    private static function ensureRivistaArchiveInMainMenuAfterPodcast(): void
    {
        $menuId = self::resolveMainMenuId(get_nav_menu_locations());
        if ($menuId <= 0) {
            return;
        }

        if (! post_type_exists('rivista')) {
            return;
        }

        $existingItems = wp_get_nav_menu_items($menuId);
        $targetPosition = self::getRivistaTargetPosition($existingItems);

        if (is_array($existingItems)) {
            foreach ($existingItems as $item) {
                if (
                    ($item->type ?? '') === 'post_type_archive'
                    && ($item->object ?? '') === 'rivista'
                ) {
                    wp_update_nav_menu_item(
                        $menuId,
                        (int) $item->ID,
                        array(
                            'menu-item-title'     => __('La rivista', 'transiti'),
                            'menu-item-object'    => 'rivista',
                            'menu-item-object-id' => 0,
                            'menu-item-type'      => 'post_type_archive',
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
                'menu-item-title'     => __('La rivista', 'transiti'),
                'menu-item-object'    => 'rivista',
                'menu-item-object-id' => 0,
                'menu-item-type'      => 'post_type_archive',
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

    private static function findContactPage(): ?\WP_Post
    {
        $contactPage = get_page_by_path('contattaci', OBJECT, 'page');
        if ($contactPage instanceof \WP_Post) {
            return $contactPage;
        }

        $maybeContactPages = get_posts(
            array(
                'post_type'      => 'page',
                'post_status'    => array('publish', 'draft', 'pending', 'private'),
                'posts_per_page' => 1,
                'title'          => 'Contattaci',
            )
        );

        if (! empty($maybeContactPages) && $maybeContactPages[0] instanceof \WP_Post) {
            return $maybeContactPages[0];
        }

        return null;
    }

    private static function findProfilePage(): ?\WP_Post
    {
        $profilePage = get_page_by_path('profilo', OBJECT, 'page');
        if ($profilePage instanceof \WP_Post) {
            return $profilePage;
        }

        $maybeProfilePages = get_posts(
            array(
                'post_type'      => 'page',
                'post_status'    => array('publish', 'draft', 'pending', 'private'),
                'posts_per_page' => 1,
                'title'          => 'Profilo',
            )
        );

        if (! empty($maybeProfilePages) && $maybeProfilePages[0] instanceof \WP_Post) {
            return $maybeProfilePages[0];
        }

        return null;
    }

    private static function findAboutPage(): ?\WP_Post
    {
        $aboutPage = get_page_by_path('chi-siamo', OBJECT, 'page');
        if ($aboutPage instanceof \WP_Post) {
            return $aboutPage;
        }

        $maybeAboutPages = get_posts(
            array(
                'post_type'      => 'page',
                'post_status'    => array('publish', 'draft', 'pending', 'private'),
                'posts_per_page' => 1,
                'title'          => 'Chi siamo',
            )
        );

        if (! empty($maybeAboutPages) && $maybeAboutPages[0] instanceof \WP_Post) {
            return $maybeAboutPages[0];
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

    /**
     * Puts Contattaci right after Forum when present, otherwise at end.
     *
     * @param array<int, object>|false $existingItems
     */
    private static function getContactTargetPosition($existingItems, int $forumPageId): int
    {
        if (! is_array($existingItems) || empty($existingItems)) {
            return 1;
        }

        $maxMenuPosition = 0;
        $forumPosition = 0;
        $podcastPosition = 0;
        $aboutPage = self::findAboutPage();
        $aboutPageId = $aboutPage instanceof \WP_Post ? (int) $aboutPage->ID : 0;
        $aboutPosition = 0;

        foreach ($existingItems as $item) {
            $position = (int) ($item->menu_order ?? 0);
            if ($position > $maxMenuPosition) {
                $maxMenuPosition = $position;
            }

            if (
                $forumPageId > 0
                && ($item->type ?? '') === 'post_type'
                && ($item->object ?? '') === 'page'
                && (int) ($item->object_id ?? 0) === $forumPageId
            ) {
                $forumPosition = $position;
            }

            if (
                ($item->type ?? '') === 'post_type_archive'
                && ($item->object ?? '') === 'podcast'
            ) {
                $podcastPosition = $position;
            }

            if (
                ($item->type ?? '') === 'post_type'
                && ($item->object ?? '') === 'page'
                && $aboutPageId > 0
                && (int) ($item->object_id ?? 0) === $aboutPageId
            ) {
                $aboutPosition = $position;
            }
        }

        if ($aboutPosition > 0) {
            return $aboutPosition + 1;
        }

        if ($podcastPosition > 0) {
            return $podcastPosition + 1;
        }

        if ($forumPosition > 0) {
            return $forumPosition + 1;
        }

        return $maxMenuPosition + 1;
    }

    /**
     * @param array<int, object>|false $existingItems
     */
    private static function getPodcastTargetPosition($existingItems, int $forumPageId): int
    {
        if (! is_array($existingItems) || empty($existingItems)) {
            return 1;
        }

        $maxMenuPosition = 0;
        $forumPosition = 0;

        foreach ($existingItems as $item) {
            $position = (int) ($item->menu_order ?? 0);
            if ($position > $maxMenuPosition) {
                $maxMenuPosition = $position;
            }

            if (
                $forumPageId > 0
                && ($item->type ?? '') === 'post_type'
                && ($item->object ?? '') === 'page'
                && (int) ($item->object_id ?? 0) === $forumPageId
            ) {
                $forumPosition = $position;
            }
        }

        if ($forumPosition > 0) {
            return $forumPosition + 1;
        }

        return $maxMenuPosition + 1;
    }

    /**
     * @param array<int, object>|false $existingItems
     */
    private static function getAboutTargetPosition($existingItems): int
    {
        if (! is_array($existingItems) || empty($existingItems)) {
            return 1;
        }

        $maxMenuPosition = 0;
        $rivistaPosition = 0;
        $podcastPosition = 0;

        foreach ($existingItems as $item) {
            $position = (int) ($item->menu_order ?? 0);
            if ($position > $maxMenuPosition) {
                $maxMenuPosition = $position;
            }

            if (
                ($item->type ?? '') === 'post_type_archive'
                && ($item->object ?? '') === 'rivista'
            ) {
                $rivistaPosition = $position;
            }

            if (
                ($item->type ?? '') === 'post_type_archive'
                && ($item->object ?? '') === 'podcast'
            ) {
                $podcastPosition = $position;
            }
        }

        if ($rivistaPosition > 0) {
            return $rivistaPosition + 1;
        }

        if ($podcastPosition > 0) {
            return $podcastPosition + 1;
        }

        return $maxMenuPosition + 1;
    }

    /**
     * @param array<int, object>|false $existingItems
     */
    private static function getRivistaTargetPosition($existingItems): int
    {
        if (! is_array($existingItems) || empty($existingItems)) {
            return 1;
        }

        $maxMenuPosition = 0;
        $podcastPosition = 0;

        foreach ($existingItems as $item) {
            $position = (int) ($item->menu_order ?? 0);
            if ($position > $maxMenuPosition) {
                $maxMenuPosition = $position;
            }

            if (
                ($item->type ?? '') === 'post_type_archive'
                && ($item->object ?? '') === 'podcast'
            ) {
                $podcastPosition = $position;
            }
        }

        if ($podcastPosition > 0) {
            return $podcastPosition + 1;
        }

        return $maxMenuPosition + 1;
    }

    /**
     * @param array<int, \WP_Post> $items
     * @param mixed                $args
     * @return array<int, \WP_Post>
     */
    public static function injectPodcastMenuItemAtRender(array $items, $args): array
    {
        $themeLocation = isset($args->theme_location) ? (string) $args->theme_location : '';
        if (! in_array($themeLocation, array('main-menu', 'main_menu'), true)) {
            return $items;
        }

        $forumIndex = null;
        foreach ($items as $index => $item) {
            $title = trim(wp_strip_all_tags((string) ($item->title ?? '')));
            $isForumTitle = strcasecmp($title, 'Forum') === 0;
            $isForumPath = strpos(untrailingslashit((string) ($item->url ?? '')), '/forum') !== false;
            if ($isForumTitle || $isForumPath) {
                $forumIndex = (int) $index;
                break;
            }
        }

        $buildItem = static function (string $title, string $url): \WP_Post {
            return new \WP_Post((object) array(
                'ID'                   => 0,
                'db_id'                => 0,
                'menu_item_parent'     => 0,
                'object_id'            => 0,
                'object'               => 'custom',
                'type'                 => 'custom',
                'type_label'           => __('Custom Link', 'transiti'),
                'title'                => $title,
                'url'                  => $url,
                'target'               => '',
                'attr_title'           => '',
                'description'          => '',
                'classes'              => array('menu-item', 'menu-item-type-custom', 'menu-item-object-custom'),
                'xfn'                  => '',
                'current'              => false,
                'current_item_ancestor'=> false,
                'current_item_parent'  => false,
                'menu_order'           => 9999,
                'post_type'            => 'nav_menu_item',
                'post_status'          => 'publish',
            ));
        };

        $podcastUrl = post_type_exists('podcast') ? get_post_type_archive_link('podcast') : false;
        if (is_string($podcastUrl) && $podcastUrl !== '') {
            $podcastExists = false;
            foreach ($items as $item) {
                if (
                    (($item->type ?? '') === 'post_type_archive' && ($item->object ?? '') === 'podcast')
                    || untrailingslashit((string) ($item->url ?? '')) === untrailingslashit($podcastUrl)
                ) {
                    $podcastExists = true;
                    break;
                }
            }

            if (! $podcastExists) {
                $podcastItem = $buildItem(__('Podcast', 'transiti'), $podcastUrl);
                if ($forumIndex === null) {
                    $items[] = $podcastItem;
                } else {
                    array_splice($items, $forumIndex + 1, 0, array($podcastItem));
                }
            }
        }

        $rivistaUrl = post_type_exists('rivista') ? get_post_type_archive_link('rivista') : false;
        if (is_string($rivistaUrl) && $rivistaUrl !== '') {
            $rivistaExists = false;
            $podcastIndex = null;

            foreach ($items as $index => $item) {
                if (
                    (($item->type ?? '') === 'post_type_archive' && ($item->object ?? '') === 'rivista')
                    || untrailingslashit((string) ($item->url ?? '')) === untrailingslashit($rivistaUrl)
                ) {
                    $rivistaExists = true;
                }

                if (
                    (($item->type ?? '') === 'post_type_archive' && ($item->object ?? '') === 'podcast')
                    || (is_string($podcastUrl) && $podcastUrl !== '' && untrailingslashit((string) ($item->url ?? '')) === untrailingslashit($podcastUrl))
                ) {
                    $podcastIndex = (int) $index;
                }
            }

            if (! $rivistaExists) {
                $rivistaItem = $buildItem(__('La rivista', 'transiti'), $rivistaUrl);
                if ($podcastIndex === null) {
                    $items[] = $rivistaItem;
                } else {
                    array_splice($items, $podcastIndex + 1, 0, array($rivistaItem));
                }
            }
        }

        return $items;
    }

    /**
     * @param array<string, string> $templates
     * @return array<string, string>
     */
    public static function registerProfilePageTemplate(array $templates): array
    {
        $templates[self::PROFILE_PAGE_TEMPLATE] = __('Profilo (Transiti)', 'transiti');
        $templates[self::ABOUT_PAGE_TEMPLATE] = __('Chi siamo (Transiti)', 'transiti');

        return $templates;
    }

    public static function resolveProfilePageTemplate(string $template): string
    {
        if (! is_page()) {
            return $template;
        }

        $pageId = (int) get_queried_object_id();
        if ($pageId <= 0) {
            return $template;
        }

        $assignedTemplate = (string) get_page_template_slug($pageId);
        if ($assignedTemplate === self::PROFILE_PAGE_TEMPLATE) {
            $profileTemplate = TRANSITI_PLUGIN_PATH . 'templates/page-profile.php';
            if (file_exists($profileTemplate)) {
                return $profileTemplate;
            }
        }

        if ($assignedTemplate === self::ABOUT_PAGE_TEMPLATE) {
            $aboutTemplate = TRANSITI_PLUGIN_PATH . 'templates/page-about-us.php';
            if (file_exists($aboutTemplate)) {
                return $aboutTemplate;
            }
        }

        return $template;
    }

    public static function getProfilePageUrl(): string
    {
        $profilePage = self::findProfilePage();
        if ($profilePage instanceof \WP_Post) {
            $permalink = get_permalink((int) $profilePage->ID);
            if (is_string($permalink) && $permalink !== '') {
                return $permalink;
            }
        }

        return home_url('/profilo/');
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

    private static function ensureContactPage(): void
    {
        $contactPage = get_page_by_path('contattaci', OBJECT, 'page');

        if (! $contactPage instanceof \WP_Post) {
            $maybeContactPages = get_posts(
                array(
                    'post_type'      => 'page',
                    'post_status'    => array('publish', 'draft', 'pending', 'private'),
                    'posts_per_page' => 1,
                    'title'          => 'Contattaci',
                )
            );

            if (! empty($maybeContactPages) && $maybeContactPages[0] instanceof \WP_Post) {
                $contactPage = $maybeContactPages[0];
            }
        }

        if (! $contactPage instanceof \WP_Post) {
            $contactPageId = wp_insert_post(
                array(
                    'post_title'   => 'Contattaci',
                    'post_name'    => 'contattaci',
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => '',
                ),
                true
            );

            if (is_wp_error($contactPageId) || ! is_int($contactPageId)) {
                return;
            }

            $contactPage = get_post($contactPageId);
        }

        if (! $contactPage instanceof \WP_Post) {
            return;
        }

        update_post_meta((int) $contactPage->ID, '_wp_page_template', 'page-contact-us.php');
    }

    private static function ensureProfilePage(): void
    {
        $profilePage = self::findProfilePage();

        if (! $profilePage instanceof \WP_Post) {
            $profilePageId = wp_insert_post(
                array(
                    'post_title'   => 'Profilo',
                    'post_name'    => 'profilo',
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => '',
                ),
                true
            );

            if (is_wp_error($profilePageId) || ! is_int($profilePageId)) {
                return;
            }

            $profilePage = get_post($profilePageId);
        }

        if (! $profilePage instanceof \WP_Post) {
            return;
        }

        update_post_meta((int) $profilePage->ID, '_wp_page_template', self::PROFILE_PAGE_TEMPLATE);
    }

    private static function ensureAboutPage(): void
    {
        $aboutPage = self::findAboutPage();

        if (! $aboutPage instanceof \WP_Post) {
            $aboutPageId = wp_insert_post(
                array(
                    'post_title'   => 'Chi siamo',
                    'post_name'    => 'chi-siamo',
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => '',
                ),
                true
            );

            if (is_wp_error($aboutPageId) || ! is_int($aboutPageId)) {
                return;
            }

            $aboutPage = get_post($aboutPageId);
        }

        if (! $aboutPage instanceof \WP_Post) {
            return;
        }

        update_post_meta((int) $aboutPage->ID, '_wp_page_template', self::ABOUT_PAGE_TEMPLATE);
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


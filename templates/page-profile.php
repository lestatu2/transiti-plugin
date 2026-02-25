<?php
/**
 * Plugin page template: Profilo (no sidebar) with ACF front-end form.
 */

if (! defined('ABSPATH')) {
    exit;
}

$currentUser = wp_get_current_user();
$userId = $currentUser instanceof \WP_User ? (int) $currentUser->ID : 0;
$isLogged = $userId > 0;

if ($isLogged && function_exists('acf_form_head')) {
    add_action(
        'acf/validate_save_post',
        static function (): void {
            if (! isset($_POST['transiti_profile_form'])) {
                return;
            }

            $privacyAccepted = isset($_POST['transiti_profile_privacy']) && (string) $_POST['transiti_profile_privacy'] === '1';
            if (! $privacyAccepted) {
                acf_add_validation_error('transiti_profile_privacy', __('Devi accettare la privacy policy.', 'transiti'));
            }

            $email = isset($_POST['transiti_profile_email']) ? sanitize_email(wp_unslash((string) $_POST['transiti_profile_email'])) : '';
            if ($email === '' || ! is_email($email)) {
                acf_add_validation_error('transiti_profile_email', __('Inserisci una email valida.', 'transiti'));
            }
        },
        10
    );

    add_action(
        'acf/save_post',
        static function ($postId) use ($userId): void {
            if (! isset($_POST['transiti_profile_form'])) {
                return;
            }

            if (! is_string($postId) || strpos($postId, 'user_') !== 0) {
                return;
            }

            $savedUserId = (int) str_replace('user_', '', $postId);
            if ($savedUserId <= 0 || $savedUserId !== $userId) {
                return;
            }

            $email = isset($_POST['transiti_profile_email']) ? sanitize_email(wp_unslash((string) $_POST['transiti_profile_email'])) : '';
            if ($email === '' || ! is_email($email)) {
                return;
            }

            wp_update_user(
                array(
                    'ID'         => $savedUserId,
                    'user_email' => $email,
                )
            );
        },
        20
    );

    acf_form_head();
}

get_header();

the_post();

$profileEmail = $isLogged ? (string) $currentUser->user_email : '';
$userRoles = ($isLogged && is_array($currentUser->roles)) ? $currentUser->roles : array();
$isRedattore = in_array('redattore', $userRoles, true) || in_array('editor', $userRoles, true) || in_array('author', $userRoles, true);
$privacyPageId = (int) get_option('wp_page_for_privacy_policy');
$privacyPage = $privacyPageId > 0 ? get_post($privacyPageId) : null;
$privacyTitle = $privacyPage instanceof \WP_Post ? get_the_title($privacyPageId) : (string) __('privacy policy', 'transiti');
$privacyContent = $privacyPage instanceof \WP_Post ? (string) $privacyPage->post_content : '';
$privacyModalLeft = '';
$privacyModalRight = '';

if ($privacyPage instanceof \WP_Post) {
    $privacyModalContent = (string) apply_filters('the_content', $privacyContent);
    $chunks = preg_split('/(?=<h[1-6][^>]*>)/i', $privacyModalContent, -1, PREG_SPLIT_NO_EMPTY);

    if (! is_array($chunks) || count($chunks) < 2) {
        $chunks = preg_split('/(?=<p[^>]*>)/i', $privacyModalContent, -1, PREG_SPLIT_NO_EMPTY);
    }

    if (is_array($chunks) && ! empty($chunks)) {
        $half = (int) ceil(count($chunks) / 2);
        $privacyModalLeft = implode('', array_slice($chunks, 0, $half));
        $privacyModalRight = implode('', array_slice($chunks, $half));
    }

    if ($privacyModalLeft === '' && $privacyModalRight === '') {
        $privacyModalLeft = $privacyModalContent;
    }
}
?>
<div id="post-<?php the_ID(); ?>" <?php post_class('content transiti-profile-page'); ?>>
    <h1 class="entry-title"><?php the_title(); ?></h1>

    <?php if (! $isLogged) : ?>
        <p class="mb-0"><?php esc_html_e('Devi effettuare il login per visualizzare il tuo profilo.', 'transiti'); ?></p>
    <?php elseif (! function_exists('acf_form')) : ?>
        <p class="mb-0"><?php esc_html_e('ACF non disponibile.', 'transiti'); ?></p>
    <?php else : ?>
        <?php
        $beforeFields = '<input type="hidden" name="transiti_profile_form" value="1">';
        $beforeFields .= '<div class="mb-3">';
        $beforeFields .= '<label class="form-label" for="transiti_profile_email">' . esc_html__('Email', 'transiti') . '</label>';
        $beforeFields .= '<div class="input-group flex-nowrap">';
        $beforeFields .= '<span class="input-group-text" id="transiti-profile-email-addon">@</span>';
        $beforeFields .= '<input type="email" class="form-control" placeholder="' . esc_attr__('Email', 'transiti') . '" aria-label="' . esc_attr__('Email', 'transiti') . '" aria-describedby="transiti-profile-email-addon" id="transiti_profile_email" name="transiti_profile_email" required value="' . esc_attr($profileEmail) . '">';
        $beforeFields .= '</div>';
        $beforeFields .= '</div>';
        $submitButton = '<button type="submit" class="acf-button button button-primary button-large">' . esc_html__('Salva profilo', 'transiti') . '</button>';
        $submitButton .= '<div class="mt-3">';
        $submitButton .= '<label class="form-label d-inline-flex align-items-center gap-2" for="transiti_profile_privacy">';
        $submitButton .= '<input type="checkbox" id="transiti_profile_privacy" name="transiti_profile_privacy" value="1" required>';
        $submitButton .= esc_html__('Accetto la', 'transiti') . ' ';
        if ($privacyPage instanceof \WP_Post) {
            $submitButton .= '<button type="button" class="btn btn-link p-0 align-baseline" data-bs-toggle="modal" data-bs-target="#transitiProfilePrivacyPolicyModal">' . esc_html($privacyTitle) . '</button>';
        } else {
            $submitButton .= '<span>' . esc_html($privacyTitle) . '</span>';
        }
        $submitButton .= '</label>';
        $submitButton .= '</div>';

        acf_form(
            array(
                'post_id' => 'user_' . $userId,
                'fields' => $isRedattore
                    ? array(
                        'field_transiti_user_avatar',
                        'field_transiti_user_professione',
                        'field_transiti_user_bio',
                        'field_transiti_user_social_networks',
                    )
                    : array(
                        'field_transiti_user_avatar',
                        'field_transiti_user_social_networks',
                    ),
                'form' => true,
                'submit_value' => __('Salva profilo', 'transiti'),
                'html_submit_button' => $submitButton,
                'updated_message' => __('Profilo aggiornato.', 'transiti'),
                'html_before_fields' => $beforeFields,
                'return' => add_query_arg('updated', 'true', get_permalink()),
            )
        );
        ?>
    <?php endif; ?>
</div>

<?php if ($privacyPage instanceof \WP_Post) : ?>
    <div class="modal fade" id="transitiProfilePrivacyPolicyModal" tabindex="-1" aria-labelledby="transitiProfilePrivacyPolicyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transitiProfilePrivacyPolicyModalLabel"><?php echo esc_html($privacyTitle); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo esc_attr__('Chiudi', 'transiti'); ?>"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-6 col-12">
                            <?php echo wp_kses_post($privacyModalLeft); ?>
                        </div>
                        <div class="col-md-6 col-12">
                            <?php echo wp_kses_post($privacyModalRight); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
get_footer();

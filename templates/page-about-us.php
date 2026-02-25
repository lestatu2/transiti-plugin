<?php
/**
 * Plugin page template: Chi siamo.
 */

if (! defined('ABSPATH')) {
	exit;
}

get_header();
the_post();

$transiti_get_author_full_name = static function (int $author_id): string {
	$first_name = trim((string) get_the_author_meta('first_name', $author_id));
	$last_name = trim((string) get_the_author_meta('last_name', $author_id));
	$full_name = trim($first_name . ' ' . $last_name);

	if ($full_name !== '') {
		return $full_name;
	}

	return (string) get_the_author_meta('display_name', $author_id);
};

$transiti_redattori_users_raw = get_users(
	array(
		'role__in' => array('editor', 'redattore'),
		'orderby'  => 'display_name',
		'order'    => 'ASC',
	)
);

$transiti_redattori_users = array();
foreach ($transiti_redattori_users_raw as $transiti_redattore_user) {
	if (! $transiti_redattore_user instanceof \WP_User) {
		continue;
	}

	$transiti_redattore_id = (int) $transiti_redattore_user->ID;
	$transiti_redattore_post_count = (int) count_user_posts($transiti_redattore_id, 'post', true);
	if ($transiti_redattore_post_count <= 0) {
		continue;
	}

	$transiti_redattori_users[] = $transiti_redattore_user;
}

$transiti_mission_content = function_exists('get_field') ? (string) get_field('mission', 'option') : '';

$transiti_gallery_items = function_exists('get_field') ? get_field('catalogo_gallery', 'option') : array();
$transiti_gallery_slides = array();
if (is_array($transiti_gallery_items)) {
	foreach ($transiti_gallery_items as $transiti_gallery_item) {
		$transiti_image_id = 0;
		if (is_array($transiti_gallery_item) && isset($transiti_gallery_item['ID'])) {
			$transiti_image_id = (int) $transiti_gallery_item['ID'];
		} elseif (is_numeric($transiti_gallery_item)) {
			$transiti_image_id = (int) $transiti_gallery_item;
		}

		if ($transiti_image_id <= 0) {
			continue;
		}

		$transiti_image_url = (string) wp_get_attachment_image_url($transiti_image_id, 'large');
		if ($transiti_image_url === '') {
			continue;
		}

		$transiti_gallery_slides[] = array(
			'title' => (string) get_the_title($transiti_image_id),
			'url'   => $transiti_image_url,
		);
	}
}

$transiti_gallery_fallback = class_exists('\Fabermind\Transiti\Core\Plugin')
	? \Fabermind\Transiti\Core\Plugin::getCatalogFallbackImageUrl()
	: '';

$transiti_author_users = get_users(
	array(
		'role'    => 'author',
		'orderby' => 'display_name',
		'order'   => 'ASC',
	)
);

$transiti_social_icons = array(
	'facebook'  => 'fa-brands fa-facebook-f',
	'instagram' => 'fa-brands fa-instagram',
	'linkedin'  => 'fa-brands fa-linkedin-in',
	'youtube'   => 'fa-brands fa-youtube',
	'tiktok'    => 'fa-brands fa-tiktok',
	'x'         => 'fa-brands fa-x-twitter',
	'whatsapp'  => 'fa-brands fa-whatsapp',
	'telegram'  => 'fa-brands fa-telegram',
	'pinterest' => 'fa-brands fa-pinterest-p',
);
?>
<div id="post-<?php the_ID(); ?>" <?php post_class('content transiti-about-page'); ?>>
	<div class="container p-0">
		<h2 class="text-uppercase oswald text-center text-black"><?php the_title(); ?></h2>
		<h5 class="text-uppercase oswald text-center text-subtitle pb-5 border-bottom mb-0">
			<?php
			$transiti_about_subtitle = trim( (string) get_the_excerpt() );
			echo esc_html( $transiti_about_subtitle !== '' ? $transiti_about_subtitle : __( 'Scopri chi siamo', 'transiti' ) );
			?>
		</h5>
	</div>

	<section class="transiti-about-redattori pb-5">
		<div class="container">
			<h2 class="oswald text-uppercase mb-4"><?php echo esc_html__('Redattori', 'transiti'); ?></h2>
			<div class="row g-4">
				<?php foreach ($transiti_redattori_users as $transiti_redattore_user) : ?>
					<?php if (! $transiti_redattore_user instanceof \WP_User) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<div class="col-lg-9 col-12">
						<?php $transiti_previous_authordata = $GLOBALS['authordata'] ?? null; ?>
						<?php $GLOBALS['authordata'] = get_userdata((int) $transiti_redattore_user->ID); ?>
						<?php get_template_part('author', 'bio'); ?>
						<?php $GLOBALS['authordata'] = $transiti_previous_authordata; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="transiti-home-editoriale">
		<div class="container">
			<div class="row mx-lg-0 border-top py-4">

				<div class="col-lg-9 col-12 ps-lg-0">
					<article class="transiti-editoriale-article">
						<div class="transiti-editoriale-label"><?php echo esc_html__('Chi siamo', 'transiti'); ?></div>
						<h2 class="transiti-editoriale-title"><?php echo esc_html__('Mission', 'transiti'); ?></h2>
						<div class="transiti-editoriale-content">
							<?php if ($transiti_mission_content !== '') : ?>
								<?php echo wp_kses_post(apply_filters('the_content', $transiti_mission_content)); ?>
							<?php endif; ?>
						</div>
					</article>
				</div>
                <div class="col-lg-3 col-12 transiti-rivista-col pe-lg-0">
                    <?php if (! empty($transiti_gallery_slides)) : ?>
                        <div class="swiper transiti-rivista-swiper mb-4">
                            <div class="swiper-wrapper">
                                <?php foreach ($transiti_gallery_slides as $transiti_slide) : ?>
                                    <div class="swiper-slide">
                                        <article class="transiti-rivista-card">
                                            <img class="transiti-rivista-image" src="<?php echo esc_url((string) $transiti_slide['url']); ?>" alt="<?php echo esc_attr((string) $transiti_slide['title']); ?>">
                                        </article>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="swiper-pagination transiti-rivista-pagination"></div>
                        </div>
                    <?php elseif ($transiti_gallery_fallback !== '') : ?>
                        <article class="transiti-rivista-card transiti-rivista-fallback-card mb-4">
                            <img class="transiti-rivista-image" src="<?php echo esc_url($transiti_gallery_fallback); ?>" alt="<?php echo esc_attr__('Immagine mission', 'transiti'); ?>">
                        </article>
                    <?php endif; ?>
                </div>
			</div>
		</div>
	</section>

	<section class="transiti-about-authors pb-5">
		<div class="container">
			<h2 class="oswald text-uppercase mb-4"><?php echo esc_html__('Autori', 'transiti'); ?></h2>
			<ol class="list-group list-group-numbered">
				<?php foreach ($transiti_author_users as $transiti_author_user) : ?>
					<?php if (! $transiti_author_user instanceof \WP_User) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<?php
					$transiti_author_id = (int) $transiti_author_user->ID;
					$transiti_author_name = $transiti_get_author_full_name($transiti_author_id);
					$transiti_author_avatar = '';
					if (function_exists('get_field')) {
						$transiti_author_avatar_id = (int) get_field('transiti_user_avatar', 'user_' . $transiti_author_id);
						if ($transiti_author_avatar_id > 0) {
							$transiti_author_avatar = (string) wp_get_attachment_image_url($transiti_author_avatar_id, 'thumbnail');
						}
					}
					if ($transiti_author_avatar === '') {
						$transiti_author_avatar = (string) get_avatar_url($transiti_author_id, array('size' => 96));
					}
					$transiti_author_socials = function_exists('get_field') ? get_field('transiti_user_social_networks', 'user_' . $transiti_author_id) : array();
					$transiti_author_post_count = (int) count_user_posts($transiti_author_id, 'post', true);
					$transiti_author_profile_text = function_exists('get_field') ? trim((string) get_field('transiti_user_bio', 'user_' . $transiti_author_id)) : '';
					?>
					<li class="list-group-item d-flex justify-content-between align-items-start">
						<div class="ms-2 me-auto d-flex align-items-start gap-3">
							<div>
								<div class="fw-bold">
									<a class="text-dark text-decoration-none" href="<?php echo esc_url(get_author_posts_url($transiti_author_id)); ?>"><?php echo esc_html($transiti_author_name); ?></a>
								</div>
								<?php if ($transiti_author_profile_text !== '') : ?>
									<div class="mt-1"><?php echo esc_html($transiti_author_profile_text); ?></div>
								<?php endif; ?>
								<?php if (is_array($transiti_author_socials) && ! empty($transiti_author_socials)) : ?>
									<div class="d-flex align-items-center gap-2 mt-2">
										<?php foreach ($transiti_author_socials as $transiti_social_row) : ?>
											<?php
											$transiti_social_key = isset($transiti_social_row['social']) ? trim((string) $transiti_social_row['social']) : '';
											$transiti_social_url = isset($transiti_social_row['link']) ? trim((string) $transiti_social_row['link']) : '';
											if ($transiti_social_key === '' || $transiti_social_url === '' || ! isset($transiti_social_icons[$transiti_social_key])) {
												continue;
											}
											?>
											<a class="text-dark" href="<?php echo esc_url($transiti_social_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr(ucfirst($transiti_social_key)); ?>">
												<i class="<?php echo esc_attr($transiti_social_icons[$transiti_social_key]); ?>"></i>
											</a>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						</div>
						<span class="badge text-bg-success rounded-pill p-2"><i class="fa-solid fa-newspaper"></i> <?php echo esc_html((string) $transiti_author_post_count); ?></span>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<section class="transiti-about-contacts">
		<div class="container p-0">
			<?php get_template_part('module', 'azienda-contatti'); ?>
		</div>
	</section>
</div>
<?php
get_footer();

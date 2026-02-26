<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

add_action('acf/init', 'transiti_register_acf_configuration_fields');
add_filter('acf/load_field/key=field_transiti_views_post_types', 'transiti_load_views_post_type_choices');
add_filter('acf/load_field/key=field_transiti_destinatari_redattori', 'transiti_load_destinatari_redattori_choices');
add_filter('acf/load_value/key=field_transiti_views_post_types', 'transiti_load_views_post_type_default', 10, 3);
add_filter('acf/prepare_field', 'transiti_prepare_configurazione_fields_for_roles', 20);
add_filter('acf/fields/post_object/query/key=field_transiti_home_featured_post', 'transiti_filter_home_featured_post_query', 10, 3);
add_filter('acf/prepare_field/key=field_transiti_editoriale_firma_autore_post', 'transiti_prepare_editoriale_firma_field');
add_filter('acf/prepare_field/key=field_transiti_user_professione', 'transiti_prepare_redattore_profile_field');
add_filter('acf/prepare_field/key=field_transiti_user_bio', 'transiti_prepare_redattore_profile_field');

function transiti_register_acf_configuration_fields(): void
{
    if (! function_exists('acf_add_options_page') || ! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_options_page(array(
        'page_title' => __('Configurazione', 'transiti'),
        'menu_title' => __('Configurazione', 'transiti'),
        'menu_slug'  => 'transiti-configurazione',
        'capability' => 'manage_editoriale_terms',
        'redirect'   => false,
        'position'   => 59,
        'icon_url'   => 'dashicons-admin-generic',
    ));

    acf_add_local_field_group(array(
        'key' => 'group_transiti_configurazione',
        'title' => __('Configurazione', 'transiti'),
        'fields' => array(
            array(
                'key' => 'field_transiti_tab_home',
                'label' => __('Home', 'transiti'),
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_transiti_home_featured_post',
                'label' => __('Post in evidenza (prima colonna)', 'transiti'),
                'name' => 'home_featured_post',
                'type' => 'post_object',
                'post_type' => array('post'),
                'return_format' => 'id',
                'ui' => 1,
                'allow_null' => 1,
                'multiple' => 0,
                'instructions' => __('Se vuoto, usa l\'ultimo articolo pubblicato.', 'transiti'),
            ),
            array(
                'key' => 'field_transiti_tab_azienda',
                'label' => __('Azienda', 'transiti'),
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_transiti_azienda_nome',
                'label' => __('Nome', 'transiti'),
                'name' => 'azienda_nome',
                'type' => 'text',
            ),
            array(
                'key' => 'field_transiti_azienda_mail',
                'label' => __('Mail', 'transiti'),
                'name' => 'azienda_mail',
                'type' => 'email',
            ),
            array(
                'key' => 'field_transiti_recaptcha_site_key',
                'label' => __('reCAPTCHA Site Key', 'transiti'),
                'name' => 'recaptcha_site_key',
                'type' => 'text',
            ),
            array(
                'key' => 'field_transiti_recaptcha_secret_key',
                'label' => __('reCAPTCHA Secret Key', 'transiti'),
                'name' => 'recaptcha_secret_key',
                'type' => 'text',
            ),
            array(
                'key' => 'field_transiti_azienda_telefoni',
                'label' => __('Telefoni', 'transiti'),
                'name' => 'azienda_telefoni',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => __('Aggiungi telefono', 'transiti'),
                'sub_fields' => array(
                    array(
                        'key' => 'field_transiti_azienda_telefono_numero',
                        'label' => __('Telefono', 'transiti'),
                        'name' => 'telefono',
                        'type' => 'acfe_phone_number',
                        'country' => 'IT',
                        'default_country' => 'it',
                        'default_country_code' => 'IT',
                        'initial_country' => 'it',
                        'preferred_countries' => array('IT'),
                    ),
                ),
            ),
            array(
                'key' => 'field_transiti_azienda_mails',
                'label' => __('Mails', 'transiti'),
                'name' => 'azienda_mails',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => __('Aggiungi mail', 'transiti'),
                'sub_fields' => array(
                    array(
                        'key' => 'field_transiti_azienda_mail_indirizzo',
                        'label' => __('Mail', 'transiti'),
                        'name' => 'mail',
                        'type' => 'email',
                    ),
                ),
            ),
            array(
                'key' => 'field_transiti_azienda_indirizzo',
                'label' => __('Indirizzo', 'transiti'),
                'name' => 'azienda_indirizzo',
                'type' => 'google_map',
                'center_lat' => '41.902782',
                'center_lng' => '12.496366',
                'zoom' => 14,
                'height' => 400,
            ),
            array(
                'key' => 'field_transiti_mission',
                'label' => __('Mission', 'transiti'),
                'name' => 'mission',
                'type' => 'wysiwyg',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
            ),
            array(
                'key' => 'field_transiti_mission_footer',
                'label' => __('Mission Footer', 'transiti'),
                'name' => 'mission_footer',
                'type' => 'wysiwyg',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
            ),
            array(
                'key' => 'field_transiti_editoriale_firma_autore',
                'label' => __('Firma editoriale con autore', 'transiti'),
                'name' => 'editoriale_firma_autore',
                'type' => 'true_false',
                'ui' => 1,
                'default_value' => 1,
                'message' => __('Sì = autore del post, No = Redazione', 'transiti'),
            ),
            array(
                'key' => 'field_transiti_social_networks',
                'label' => __('Social Network', 'transiti'),
                'name' => 'social_networks',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => __('Aggiungi social', 'transiti'),
                'sub_fields' => array(
                    array(
                        'key' => 'field_transiti_social_name',
                        'label' => __('Social', 'transiti'),
                        'name' => 'social',
                        'type' => 'select',
                        'ui' => 1,
                        'return_format' => 'value',
                        'choices' => array(
                            'facebook' => 'Facebook',
                            'instagram' => 'Instagram',
                            'linkedin' => 'LinkedIn',
                            'youtube' => 'YouTube',
                            'tiktok' => 'TikTok',
                            'x' => 'X',
                            'whatsapp' => 'WhatsApp',
                            'telegram' => 'Telegram',
                            'pinterest' => 'Pinterest',
                        ),
                    ),
                    array(
                        'key' => 'field_transiti_social_link',
                        'label' => __('Link', 'transiti'),
                        'name' => 'link',
                        'type' => 'url',
                    ),
                ),
            ),
            array(
                'key' => 'field_transiti_tab_casa_editrice',
                'label' => __('Casa Editrice', 'transiti'),
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_transiti_casa_editrice_immagine',
                'label' => __('Immagine Casa Editrice', 'transiti'),
                'name' => 'casa_editrice_immagine',
                'type' => 'image',
                'return_format' => 'id',
                'preview_size' => 'medium',
                'library' => 'all',
            ),
            array(
                'key' => 'field_transiti_casa_editrice_link_sito',
                'label' => __('Link Sito Casa Editrice', 'transiti'),
                'name' => 'casa_editrice_link_sito',
                'type' => 'url',
            ),
            array(
                'key' => 'field_transiti_casa_editrice_descrizione',
                'label' => __('Descrizione Casa Editrice', 'transiti'),
                'name' => 'casa_editrice_descrizione',
                'type' => 'textarea',
                'rows' => 4,
                'new_lines' => 'br',
            ),
            array(
                'key' => 'field_transiti_casa_editrice_indirizzo',
                'label' => __('Indirizzo Casa Editrice', 'transiti'),
                'name' => 'casa_editrice_indirizzo',
                'type' => 'google_map',
                'center_lat' => '41.902782',
                'center_lng' => '12.496366',
                'zoom' => 14,
                'height' => 400,
            ),
            array(
                'key' => 'field_transiti_casa_editrice_social_networks',
                'label' => __('Social Network Casa Editrice', 'transiti'),
                'name' => 'casa_editrice_social_networks',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => __('Aggiungi social', 'transiti'),
                'sub_fields' => array(
                    array(
                        'key' => 'field_transiti_casa_editrice_social_name',
                        'label' => __('Social', 'transiti'),
                        'name' => 'social',
                        'type' => 'select',
                        'ui' => 1,
                        'return_format' => 'value',
                        'choices' => array(
                            'facebook' => 'Facebook',
                            'instagram' => 'Instagram',
                            'linkedin' => 'LinkedIn',
                            'youtube' => 'YouTube',
                            'tiktok' => 'TikTok',
                            'x' => 'X',
                            'whatsapp' => 'WhatsApp',
                            'telegram' => 'Telegram',
                            'pinterest' => 'Pinterest',
                        ),
                    ),
                    array(
                        'key' => 'field_transiti_casa_editrice_social_link',
                        'label' => __('Link', 'transiti'),
                        'name' => 'link',
                        'type' => 'url',
                    ),
                ),
            ),
            array(
                'key' => 'field_transiti_tab_destinatari',
                'label' => __('Destinatari', 'transiti'),
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_transiti_destinatari_redattori',
                'label' => __('Redattori notificati per revisioni autore', 'transiti'),
                'name' => 'destinatari_redattori',
                'type' => 'checkbox',
                'choices' => array(),
                'layout' => 'vertical',
                'return_format' => 'value',
                'allow_custom' => 0,
                'save_custom' => 0,
            ),
            array(
                'key' => 'field_transiti_tab_views',
                'label' => __('Views', 'transiti'),
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_transiti_views_post_types',
                'label' => __('Post type da tracciare', 'transiti'),
                'name' => 'views_post_types',
                'type' => 'checkbox',
                'choices' => array(),
                'layout' => 'vertical',
                'return_format' => 'value',
                'allow_custom' => 0,
                'save_custom' => 0,
                'default_value' => array('post'),
            ),
            array(
                'key' => 'field_transiti_tab_feed',
                'label' => __('Feed', 'transiti'),
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_transiti_catalogo_feed_url',
                'label' => __('URL feed catalogo', 'transiti'),
                'name' => 'catalogo_feed_url',
                'type' => 'url',
                'default_value' => 'https://www.lesflaneursedizioni.it/catalogo/feed/',
            ),
            array(
                'key' => 'field_transiti_catalogo_source_mode',
                'label' => __('Sorgente slider', 'transiti'),
                'name' => 'catalogo_source_mode',
                'type' => 'button_group',
                'choices' => array(
                    'feed' => __('Feed', 'transiti'),
                    'gallery' => __('Gallery', 'transiti'),
                ),
                'default_value' => 'feed',
                'layout' => 'horizontal',
                'return_format' => 'value',
            ),
            array(
                'key' => 'field_transiti_catalogo_gallery',
                'label' => __('Galleria immagini', 'transiti'),
                'name' => 'catalogo_gallery',
                'type' => 'gallery',
                'return_format' => 'id',
                'preview_size' => 'medium',
                'library' => 'all',
                'min' => 0,
                'insert' => 'append',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_transiti_catalogo_source_mode',
                            'operator' => '==',
                            'value' => 'gallery',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_transiti_catalogo_feed_fallback_image',
                'label' => __('Immagine fallback feed', 'transiti'),
                'name' => 'catalogo_feed_fallback_image',
                'type' => 'image',
                'return_format' => 'id',
                'preview_size' => 'medium',
                'library' => 'all',
            ),
            array(
                'key' => 'field_transiti_catalogo_feed_image_class',
                'label' => __('Classe immagine pagina prodotto', 'transiti'),
                'name' => 'catalogo_feed_image_class',
                'type' => 'text',
                'default_value' => 'imgzoom',
                'instructions' => __('Classe CSS dell\'immagine da estrarre dalla pagina del libro (es. imgzoom).', 'transiti'),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'transiti-configurazione',
                ),
            ),
        ),
    ));
    acf_add_local_field_group(array(
        'key' => 'group_transiti_rivista',
        'title' => __('Dati Rivista', 'transiti'),
        'fields' => array(
            array(
                'key' => 'field_transiti_rivista_numero',
                'label' => __('Numero', 'transiti'),
                'name' => 'numero',
                'type' => 'number',
                'min' => 1,
                'step' => 1,
            ),
            array(
                'key' => 'field_transiti_rivista_data',
                'label' => __('Data', 'transiti'),
                'name' => 'data',
                'type' => 'date_picker',
                'display_format' => 'd/m/Y',
                'return_format' => 'Y-m-d',
                'first_day' => 1,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'rivista',
                ),
            ),
        ),
    ));
    acf_add_local_field_group(array(
        'key' => 'group_transiti_podcast',
        'title' => __('Dati Podcast', 'transiti'),
        'fields' => array(
            array(
                'key' => 'field_transiti_podcast_episodi',
                'label' => __('Episodi', 'transiti'),
                'name' => 'episodi',
                'type' => 'repeater',
                'layout' => 'block',
                'collapsed' => 'field_transiti_podcast_episodio_titolo_breve',
                'button_label' => __('Aggiungi episodio', 'transiti'),
                'sub_fields' => array(
                    array(
                        'key' => 'field_transiti_podcast_episodio_numero',
                        'label' => __('Numero ordine', 'transiti'),
                        'name' => 'numero_ordine',
                        'type' => 'number',
                        'min' => 1,
                        'step' => 1,
                    ),
                    array(
                        'key' => 'field_transiti_podcast_episodio_titolo_breve',
                        'label' => __('Titolo breve', 'transiti'),
                        'name' => 'titolo_breve',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_transiti_podcast_episodio_descrizione',
                        'label' => __('Descrizione', 'transiti'),
                        'name' => 'descrizione',
                        'type' => 'textarea',
                        'rows' => 3,
                    ),
                    array(
                        'key' => 'field_transiti_podcast_episodio_durata_minuti',
                        'label' => __('Durata (minuti)', 'transiti'),
                        'name' => 'durata_minuti',
                        'type' => 'number',
                        'min' => 0,
                        'step' => 1,
                    ),
                    array(
                        'key' => 'field_transiti_podcast_episodio_relatori',
                        'label' => __('Relatori', 'transiti'),
                        'name' => 'relatori',
                        'type' => 'repeater',
                        'layout' => 'table',
                        'button_label' => __('Aggiungi relatore', 'transiti'),
                        'sub_fields' => array(
                            array(
                                'key' => 'field_transiti_podcast_episodio_relatore_nome',
                                'label' => __('Relatore', 'transiti'),
                                'name' => 'relatore',
                                'type' => 'text',
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_transiti_podcast_episodio_tipo_media',
                        'label' => __('Tipo media', 'transiti'),
                        'name' => 'tipo_media',
                        'type' => 'button_group',
                        'choices' => array(
                            'audio' => __('Audio', 'transiti'),
                            'video' => __('Video', 'transiti'),
                        ),
                        'default_value' => 'audio',
                        'layout' => 'horizontal',
                        'return_format' => 'value',
                    ),
                    array(
                        'key' => 'field_transiti_podcast_episodio_file_audio',
                        'label' => __('File audio', 'transiti'),
                        'name' => 'file_audio',
                        'type' => 'file',
                        'return_format' => 'id',
                        'library' => 'all',
                        'mime_types' => 'mp3,m4a,wav,ogg',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_transiti_podcast_episodio_tipo_media',
                                    'operator' => '==',
                                    'value' => 'audio',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_transiti_podcast_episodio_video_source',
                        'label' => __('Sorgente video', 'transiti'),
                        'name' => 'video_source',
                        'type' => 'button_group',
                        'choices' => array(
                            'file' => __('File', 'transiti'),
                            'embed' => __('Embed', 'transiti'),
                        ),
                        'default_value' => 'file',
                        'layout' => 'horizontal',
                        'return_format' => 'value',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_transiti_podcast_episodio_tipo_media',
                                    'operator' => '==',
                                    'value' => 'video',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_transiti_podcast_episodio_file_video',
                        'label' => __('File video', 'transiti'),
                        'name' => 'file_video',
                        'type' => 'file',
                        'return_format' => 'id',
                        'library' => 'all',
                        'mime_types' => 'mp4,mov,webm,m4v',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_transiti_podcast_episodio_tipo_media',
                                    'operator' => '==',
                                    'value' => 'video',
                                ),
                                array(
                                    'field' => 'field_transiti_podcast_episodio_video_source',
                                    'operator' => '==',
                                    'value' => 'file',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_transiti_podcast_episodio_video_embed',
                        'label' => __('Embed video', 'transiti'),
                        'name' => 'video_embed',
                        'type' => 'oembed',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_transiti_podcast_episodio_tipo_media',
                                    'operator' => '==',
                                    'value' => 'video',
                                ),
                                array(
                                    'field' => 'field_transiti_podcast_episodio_video_source',
                                    'operator' => '==',
                                    'value' => 'embed',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'podcast',
                ),
            ),
        ),
    ));
    acf_add_local_field_group(array(
        'key' => 'group_transiti_user_profile',
        'title' => __('Profilo Utente', 'transiti'),
        'fields' => array(
            array(
                'key' => 'field_transiti_user_avatar',
                'label' => __('Avatar personalizzato', 'transiti'),
                'name' => 'transiti_user_avatar',
                'type' => 'image',
                'return_format' => 'id',
                'preview_size' => 'thumbnail',
                'library' => 'all',
            ),
            array(
                'key' => 'field_transiti_user_professione',
                'label' => __('Professione', 'transiti'),
                'name' => 'transiti_user_professione',
                'type' => 'text',
            ),
            array(
                'key' => 'field_transiti_user_bio',
                'label' => __('Bio', 'transiti'),
                'name' => 'transiti_user_bio',
                'type' => 'textarea',
                'rows' => 5,
                'new_lines' => 'br',
            ),
            array(
                'key' => 'field_transiti_user_social_networks',
                'label' => __('Social Network', 'transiti'),
                'name' => 'transiti_user_social_networks',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => __('Aggiungi social', 'transiti'),
                'sub_fields' => array(
                    array(
                        'key' => 'field_transiti_user_social_name',
                        'label' => __('Social', 'transiti'),
                        'name' => 'social',
                        'type' => 'select',
                        'ui' => 1,
                        'return_format' => 'value',
                        'choices' => array(
                            'facebook' => 'Facebook',
                            'instagram' => 'Instagram',
                            'linkedin' => 'LinkedIn',
                            'youtube' => 'YouTube',
                            'tiktok' => 'TikTok',
                            'x' => 'X',
                            'whatsapp' => 'WhatsApp',
                            'telegram' => 'Telegram',
                            'pinterest' => 'Pinterest',
                        ),
                    ),
                    array(
                        'key' => 'field_transiti_user_social_link',
                        'label' => __('Link', 'transiti'),
                        'name' => 'link',
                        'type' => 'url',
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'user_form',
                    'operator' => '==',
                    'value' => 'all',
                ),
            ),
        ),
    ));
    acf_add_local_field_group(array(
        'key' => 'group_transiti_post_meta',
        'title' => __('Dati articolo', 'transiti'),
        'fields' => array(
            array(
                'key' => 'field_transiti_tempo_lettura',
                'label' => __('Tempo di lettura', 'transiti'),
                'name' => 'tempo_di_lettura',
                'type' => 'number',
                'default_value' => 5,
                'min' => 1,
                'step' => 1,
                'append' => __('minuti', 'transiti'),
            ),
            array(
                'key' => 'field_transiti_post_rivista_assoc',
                'label' => __('Rivista associata', 'transiti'),
                'name' => 'post_rivista_assoc',
                'type' => 'post_object',
                'post_type' => array('rivista'),
                'post_status' => array('publish'),
                'return_format' => 'id',
                'ui' => 1,
                'allow_null' => 1,
                'multiple' => 0,
            ),
            array(
                'key' => 'field_transiti_editoriale_firma_autore_post',
                'label' => __('Firma editoriale con autore', 'transiti'),
                'name' => 'editoriale_firma_autore_post',
                'type' => 'true_false',
                'ui' => 1,
                'default_value' => 1,
                'message' => __('Sì = autore del post, No = Redazione', 'transiti'),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'post',
                ),
            ),
        ),
        'position' => 'normal',
    ));
}

function transiti_load_views_post_type_choices(array $field): array
{
    $choices = array();
    $post_types = get_post_types(
        array(
            'public' => true,
        ),
        'objects'
    );

    foreach ($post_types as $post_type) {
        if (! isset($post_type->name, $post_type->labels->singular_name)) {
            continue;
        }

        if (in_array($post_type->name, array('attachment', 'revision', 'nav_menu_item'), true)) {
            continue;
        }

        $choices[$post_type->name] = $post_type->labels->singular_name;
    }

    $field['choices'] = $choices;

    return $field;
}

/**
 * Keep "post" checked by default when no value has been saved yet.
 *
 * @param mixed  $value
 * @param mixed  $post_id
 * @param array  $field
 * @return mixed
 */
function transiti_load_views_post_type_default($value, $post_id, array $field)
{
    if (empty($value)) {
        return array('post');
    }

    return $value;
}

function transiti_load_destinatari_redattori_choices(array $field): array
{
    $field['choices'] = array();

    $redattori = get_users(
        array(
            'role'    => 'redattore',
            'orderby' => 'display_name',
            'order'   => 'ASC',
            'fields'  => 'all',
        )
    );

    if (! is_array($redattori)) {
        return $field;
    }

    foreach ($redattori as $redattore) {
        $user_id = isset($redattore->ID) ? (int) $redattore->ID : 0;
        if ($user_id <= 0) {
            continue;
        }

        $label = isset($redattore->display_name) ? trim((string) $redattore->display_name) : '';
        $email = isset($redattore->user_email) ? trim((string) $redattore->user_email) : '';
        if ($email !== '') {
            $label .= ' (' . $email . ')';
        }

        $field['choices'][(string) $user_id] = $label;
    }

    return $field;
}

/**
 * Hide profile fields unless the edited user has role editor/redattore/author.
 *
 * @param array<string, mixed> $field
 * @return array<string, mixed>|false
 */
function transiti_prepare_redattore_profile_field(array $field)
{
    if (transiti_is_redattore_profile_user()) {
        return $field;
    }

    return false;
}

function transiti_is_redattore_profile_user(): bool
{
    $user_id = 0;

    if (isset($_GET['user_id'])) {
        $user_id = absint(wp_unslash($_GET['user_id']));
    } elseif (isset($_POST['user_id'])) {
        $user_id = absint(wp_unslash($_POST['user_id']));
    } else {
        $user_id = get_current_user_id();
    }

    if ($user_id <= 0) {
        return false;
    }

    $user = get_userdata($user_id);
    if (! $user instanceof WP_User) {
        return false;
    }

    $roles = (array) $user->roles;

    return in_array('editor', $roles, true) || in_array('redattore', $roles, true) || in_array('author', $roles, true);
}

/**
 * Show "Firma editoriale con autore" only when term "editoriale" is assigned.
 *
 * @param array<string, mixed> $field
 * @return array<string, mixed>|false
 */
function transiti_prepare_editoriale_firma_field(array $field)
{
    $post_id = 0;

    if (isset($_GET['post'])) {
        $post_id = (int) $_GET['post'];
    } elseif (isset($_POST['post_ID'])) {
        $post_id = (int) $_POST['post_ID'];
    }

    if ($post_id <= 0) {
        return false;
    }

    if (! has_term('editoriale', 'editoriale', $post_id)) {
        return false;
    }

    return $field;
}

/**
 * Show only Home tab/field in Configurazione for non-admin users.
 *
 * @param mixed $field
 * @return mixed
 */
function transiti_prepare_configurazione_fields_for_roles($field)
{
    if (! is_array($field)) {
        return $field;
    }

    if (current_user_can('manage_options')) {
        return $field;
    }

    $parent = isset($field['parent']) ? (string) $field['parent'] : '';
    if ($parent !== 'group_transiti_configurazione') {
        return $field;
    }

    $allowed_keys = array(
        'field_transiti_tab_home',
        'field_transiti_home_featured_post',
    );

    $field_key = isset($field['key']) ? (string) $field['key'] : '';
    if (in_array($field_key, $allowed_keys, true)) {
        return $field;
    }

    return false;
}

/**
 * Exclude "editoriale" posts from Home featured post selector.
 *
 * @param array<string, mixed> $args
 * @param array<string, mixed> $field
 * @param int|string           $post_id
 * @return array<string, mixed>
 */
function transiti_filter_home_featured_post_query(array $args, array $field, $post_id): array
{
    $args['post_status'] = array('publish');
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'editoriale',
            'field'    => 'slug',
            'terms'    => array('editoriale'),
            'operator' => 'NOT IN',
        ),
    );

    return $args;
}

<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

add_action('acf/init', 'transiti_register_acf_configuration_fields');
add_filter('acf/load_field/key=field_transiti_views_post_types', 'transiti_load_views_post_type_choices');
add_filter('acf/load_value/key=field_transiti_views_post_types', 'transiti_load_views_post_type_default', 10, 3);

function transiti_register_acf_configuration_fields(): void
{
    if (! function_exists('acf_add_options_page') || ! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_options_page(array(
        'page_title' => __('Configurazione', 'transiti'),
        'menu_title' => __('Configurazione', 'transiti'),
        'menu_slug'  => 'transiti-configurazione',
        'capability' => 'manage_options',
        'redirect'   => false,
        'position'   => 59,
        'icon_url'   => 'dashicons-admin-generic',
    ));

    acf_add_local_field_group(array(
        'key' => 'group_transiti_configurazione',
        'title' => __('Configurazione', 'transiti'),
        'fields' => array(
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
                'key' => 'field_transiti_azienda_indirizzo',
                'label' => __('Indirizzo', 'transiti'),
                'name' => 'azienda_indirizzo',
                'type' => 'textarea',
                'rows' => 3,
                'new_lines' => 'br',
            ),            array(
                'key' => 'field_transiti_mission',
                'label' => __('Mission', 'transiti'),
                'name' => 'mission',
                'type' => 'wysiwyg',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
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
            array(
                'key' => 'field_transiti_rivista_argomenti',
                'label' => __('Argomenti', 'transiti'),
                'name' => 'argomenti',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => __('Aggiungi argomento', 'transiti'),
                'sub_fields' => array(
                    array(
                        'key' => 'field_transiti_rivista_argomento_titolo',
                        'label' => __('Argomento', 'transiti'),
                        'name' => 'argomento',
                        'type' => 'text',
                    ),
                ),
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

<?php
if (!defined('ABSPATH')) { exit; }
function pd_register_content() {
    foreach (array('pd_project' => array('Проекты домов', 'Проект дома', 'projects'), 'pd_case' => array('Построенные дома', 'Построенный дом', 'built-houses')) as $type => $names) {
        register_post_type($type, array('labels' => array('name' => $names[0], 'singular_name' => $names[1], 'add_new_item' => 'Добавить: ' . $names[1], 'edit_item' => 'Редактировать: ' . $names[1]), 'public' => true, 'show_in_rest' => true, 'has_archive' => false, 'rewrite' => array('slug' => $names[2], 'with_front' => false), 'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions'), 'menu_icon' => $type === 'pd_project' ? 'dashicons-admin-home' : 'dashicons-images-alt'));
    }
}
add_action('init', 'pd_register_content');
function pd_cover($id) {
    if (has_post_thumbnail($id)) { return get_the_post_thumbnail_url($id, 'large'); }
    $images = get_post_meta($id, '_pd_images', true);
    return is_array($images) && !empty($images[0]) ? get_template_directory_uri() . '/assets/' . rawurlencode(basename($images[0])) : '';
}
function pd_cards($type, $limit = -1, $exclude = array()) {
    $q = new WP_Query(array('post_type' => $type, 'posts_per_page' => $limit, 'post_status' => 'publish', 'post__not_in' => $exclude, 'orderby' => $type === 'post' ? 'date' : 'menu_order', 'order' => $type === 'post' ? 'DESC' : 'ASC'));
    ob_start();
    echo '<div class="pd-cards">';
    while ($q->have_posts()) {
        $q->the_post(); $cover = pd_cover(get_the_ID());
        echo '<article class="pd-card"><a href="' . esc_url(get_permalink()) . '">';
        if ($cover) { echo '<img src="' . esc_url($cover) . '" alt="' . esc_attr(get_the_title()) . '" loading="lazy" width="1000" height="750">'; }
        echo '<h3>' . esc_html(get_the_title()) . '</h3></a><p>' . esc_html(wp_trim_words(get_the_excerpt(), 28)) . '</p><a class="outline" href="' . esc_url(get_permalink()) . '">' . ($type === 'post' ? 'Читать статью' : 'Подробнее') . '</a></article>';
    }
    echo '</div>'; wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('pd_catalog', function () { return pd_cards('pd_project'); });
add_shortcode('pd_cases', function () { return pd_cards('pd_case'); });
add_shortcode('pd_articles', function ($attrs) { $attrs = shortcode_atts(array('limit' => 3), $attrs); return pd_cards('post', max(1, min(12, (int) $attrs['limit']))); });
function pd_article_toc($content) {
    $toc = array(); $i = 0;
    $content = preg_replace_callback('/<h([23])([^>]*)>(.*?)<\/h[23]>/is', function ($m) use (&$toc, &$i) {
        $i++; $id = 'article-section-' . $i;
        if (preg_match('/\bid=["\']([^"\']+)["\']/', $m[2], $match)) { $id = $match[1]; $attrs = $m[2]; }
        else { $attrs = $m[2] . ' id="' . $id . '"'; }
        $toc[] = array('id' => $id, 'text' => wp_strip_all_tags($m[3]));
        return '<h' . $m[1] . $attrs . '>' . $m[3] . '</h' . $m[1] . '>';
    }, $content);
    return array($content, $toc);
}
function pd_render_header() {
    $html = file_get_contents(get_template_directory() . '/home-v2.html');
    $pos = strpos($html, '<main id="pd-home"');
    echo pd_render_html(str_replace('href="#pd-top"', 'href="{{HOME_URL}}"', substr($html, 0, $pos)));
}
function pd_content_urls($content) {
    return strtr($content, array('{{ASSET_URL}}' => get_template_directory_uri() . '/assets', '{{HOME_URL}}' => home_url('/'), '{{BLOG_URL}}' => home_url('/blog/'), '{{LEAD_URL}}' => admin_url('admin-post.php')));
}
function pd_render_footer() {
    $html = file_get_contents(get_template_directory() . '/home-v2.html');
    echo pd_render_html(str_replace('href="#pd-top"', 'href="{{HOME_URL}}"', substr($html, strpos($html, '<footer id="pd-contact"'))));
}
function pd_crumb($label, $parent = '', $path = '') {
    echo '<nav class="pd-crumb" aria-label="Хлебные крошки"><a href="' . esc_url(home_url('/')) . '">Главная</a><span>/</span>';
    if ($parent) { echo '<a href="' . esc_url(home_url($path)) . '">' . esc_html($parent) . '</a><span>/</span>'; }
    echo '<span aria-current="page">' . esc_html($label) . '</span></nav>';
}
add_action('wp_head', function () {
    if (is_404()) { return; }
    $description = is_front_page() ? 'Planodom — современные каркасные дома под ключ в Москве и Московской области. Проекты, комплектации и фотографии построенных домов.' : wp_trim_words(wp_strip_all_tags(strip_shortcodes(get_the_excerpt() ?: get_post_field('post_content', get_queried_object_id()))), 32, '');
    if (is_home()) { $description = 'Полезные статьи Planodom о выборе проекта, комплектации и подготовке к строительству дома.'; }
    echo '<meta name="description" content="' . esc_attr($description) . '"><meta property="og:locale" content="ru_RU"><meta property="og:site_name" content="Planodom"><meta property="og:title" content="' . esc_attr(wp_get_document_title()) . '"><meta property="og:description" content="' . esc_attr($description) . '"><meta property="og:type" content="' . (is_single() && get_post_type() === 'post' ? 'article' : 'website') . '">';
    $url = is_front_page() ? home_url('/') : (is_home() ? get_permalink(get_option('page_for_posts')) : get_permalink(get_queried_object_id()));
    if ($url) { echo '<meta property="og:url" content="' . esc_url($url) . '">'; }
    $cover = pd_cover(get_queried_object_id());
    if ($cover) { echo '<meta property="og:image" content="' . esc_url($cover) . '">'; }
    if (is_single() && get_post_type() === 'post') {
        $schema = array('@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => get_the_title(), 'datePublished' => get_the_date(DATE_W3C), 'dateModified' => get_the_modified_date(DATE_W3C), 'author' => array('@type' => 'Organization', 'name' => 'Planodom'), 'publisher' => array('@type' => 'Organization', 'name' => 'Planodom'), 'mainEntityOfPage' => $url);
        if ($cover) { $schema['image'] = $cover; }
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>';
    }
}, 20);
add_filter('robots_txt', function ($output, $public) { return pd_is_staging() ? "User-agent: *\nDisallow: /\n" : $output; }, 20, 2);
add_action('add_meta_boxes', function () {
    foreach (array('pd_project', 'pd_case') as $type) {
        add_meta_box('pd_images', 'Галерея проекта', function ($post) {
            wp_nonce_field('pd_images', 'pd_images_nonce');
            echo '<p>Имена изображений в папке темы assets — по одному в строке. Обложку можно заменить через «Изображение записи».</p><textarea name="pd_images" style="width:100%;min-height:150px">' . esc_textarea(implode("\n", (array) get_post_meta($post->ID, '_pd_images', true))) . '</textarea>';
        }, $type, 'side');
    }
});
add_action('save_post', function ($id) {
    if (empty($_POST['pd_images_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pd_images_nonce'])), 'pd_images') || !current_user_can('edit_post', $id) || wp_is_post_revision($id) || wp_is_post_autosave($id)) { return; }
    $images = array_filter(array_map('sanitize_file_name', preg_split('/\R/', wp_unslash(isset($_POST['pd_images']) ? $_POST['pd_images'] : ''))));
    update_post_meta($id, '_pd_images', array_values($images));
});
add_action('admin_menu', function () { add_management_page('Контент Planodom', 'Контент Planodom', 'manage_options', 'pd-content', 'pd_content_screen'); });
function pd_content_screen() {
    if (!current_user_can('manage_options')) { return; }
    $items = json_decode(file_get_contents(get_template_directory() . '/content.json'), true);
    echo '<div class="wrap"><h1>Контент Planodom</h1><p>Подготовлены страницы разделов, проекты домов, построенные объекты и статьи. Повторный импорт не перезаписывает уже созданные материалы.</p>';
    if (isset($_GET['imported'])) { echo '<div class="notice notice-success"><p>Импорт завершён. Создано материалов: ' . (int) $_GET['imported'] . '.</p></div>'; }
    if (isset($_GET['resolved'])) { echo '<div class="notice notice-success"><p>Ссылки и изображения в редакторах обновлены. Материалов: ' . (int) $_GET['resolved'] . '.</p></div>'; }
    if (get_option('pd_content_version') !== '0.2.0') {
        echo '<p>Главная обновится только при совпадении с первоначальной версией; текущая копия сохранится в резервной записи настроек. Стандартные Sample Page и Hello world будут перемещены в корзину только при совпадении с исходными шаблонами.</p><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('pd_import_content'); echo '<input type="hidden" name="action" value="pd_import_content"><button class="button button-primary">Импортировать подготовленный контент</button></form>';
    } else { echo '<p><strong>Контент версии 0.2.0 установлен.</strong></p>'; }
    if (get_option('pd_content_urls_version') !== '0.2.1') {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">'; wp_nonce_field('pd_resolve_content');
        echo '<input type="hidden" name="action" value="pd_resolve_content"><button class="button">Обновить ссылки и изображения в редакторах</button><p>Сохраняет тексты и заменяет служебные адреса изображений действующими URL сайта. Предыдущая копия каждого изменённого материала сохраняется.</p></form>';
    }
    echo '<h2>Проверка настройки</h2><ul><li>Индексация: ' . (get_option('blog_public') === '0' ? 'запрещена' : 'разрешена') . '</li><li>Проекты: ' . (int) wp_count_posts('pd_project')->publish . '</li><li>Построенные дома: ' . (int) wp_count_posts('pd_case')->publish . '</li><li>Статьи: ' . (int) wp_count_posts('post')->publish . '</li></ul><p>Формы сохраняют заявки в разделе «Заявки сайта». Уведомления идут на текущий административный адрес WordPress; доставка требует проверки почтового сервиса. CRM и MAX подключаются после предоставления данных компании.</p></div>';
}
add_action('admin_post_pd_import_content', function () {
    if (!current_user_can('manage_options')) { wp_die('Нет доступа.', '', array('response' => 403)); }
    check_admin_referer('pd_import_content'); pd_register_content();
    $items = json_decode(file_get_contents(get_template_directory() . '/content.json'), true);
    if (!is_array($items)) { wp_die('Не удалось прочитать контент.'); }
    update_option('timezone_string', 'Europe/Moscow'); update_option('date_format', 'd.m.Y'); update_option('time_format', 'H:i');
    $count = 0;
    foreach ($items as $order => $item) {
        $existing = get_page_by_path($item['slug'], OBJECT, $item['type']);
        if ($existing) { continue; }
        $id = wp_insert_post(wp_slash(array('post_type' => $item['type'], 'post_status' => 'publish', 'post_title' => $item['title'], 'post_name' => $item['slug'], 'post_content' => pd_content_urls($item['content']), 'post_excerpt' => isset($item['excerpt']) ? $item['excerpt'] : '', 'menu_order' => $order, 'comment_status' => 'closed', 'ping_status' => 'closed')), true);
        if (is_wp_error($id)) { wp_die(esc_html($id->get_error_message())); }
        if (!empty($item['images'])) { update_post_meta($id, '_pd_images', $item['images']); }
        if (!empty($item['source'])) { update_post_meta($id, '_pd_source', esc_url_raw($item['source'])); }
        if (!empty($item['category'])) {
            $term = term_exists($item['category'], 'category');
            if (!$term) { $term = wp_insert_term($item['category'], 'category'); }
            if (!is_wp_error($term)) { wp_set_post_categories($id, array((int) (is_array($term) ? $term['term_id'] : $term))); }
        }
        $count++;
    }
    $front = (int) get_option('page_on_front'); $current = get_post_field('post_content', $front);
    if ($front && trim($current) === trim(file_get_contents(get_template_directory() . '/home.html'))) {
        if (!get_option('pd_home_backup_v1')) { update_option('pd_home_backup_v1', $current, false); }
        wp_update_post(wp_slash(array('ID' => $front, 'post_content' => file_get_contents(get_template_directory() . '/home-v2.html'))));
    }
    $privacy = get_page_by_path('politconf'); if ($privacy) { update_option('wp_page_for_privacy_policy', $privacy->ID); }
    $sample = get_post(2); if ($sample && $sample->post_name === 'sample-page' && $sample->post_title === 'Sample Page') { wp_trash_post(2); }
    $sample = get_post(1); if ($sample && $sample->post_name === 'hello-world' && $sample->post_title === 'Hello world!') { wp_trash_post(1); }
    update_option('timezone_string', 'Europe/Moscow'); update_option('date_format', 'd.m.Y'); update_option('time_format', 'H:i');
    update_option('pd_content_version', '0.2.0'); flush_rewrite_rules();
    wp_safe_redirect(add_query_arg(array('page' => 'pd-content', 'imported' => $count), admin_url('tools.php'))); exit;
});
add_action('admin_post_pd_resolve_content', function () {
    if (!current_user_can('manage_options')) { wp_die('Нет доступа.', '', array('response' => 403)); }
    check_admin_referer('pd_resolve_content');
    $items = json_decode(file_get_contents(get_template_directory() . '/content.json'), true); $ids = array();
    foreach ($items as $item) { $post = get_page_by_path($item['slug'], OBJECT, $item['type']); if ($post) { $ids[] = $post->ID; } }
    $ids[] = (int) get_option('page_on_front'); $ids[] = (int) get_option('page_for_posts'); $count = 0;
    foreach (array_unique(array_filter($ids)) as $id) {
        $post = get_post($id); $content = $post->post_content;
        $content = preg_replace_callback('/href="\{\{HOME_URL\}\}#([a-zA-Z0-9_-]+)"/', function ($match) use ($content) { return strpos($content, 'id="' . $match[1] . '"') !== false ? 'href="#' . $match[1] . '"' : $match[0]; }, $content);
        $resolved = pd_content_urls($content); $update = array('ID' => $id);
        if ($resolved !== $post->post_content) {
            if (!metadata_exists('post', $id, '_pd_content_before_urls')) { update_post_meta($id, '_pd_content_before_urls', $post->post_content); }
            $update['post_content'] = $resolved;
        }
        if ($post->post_date === $post->post_date_gmt && $post->post_date_gmt !== '0000-00-00 00:00:00') { $update['post_date'] = get_date_from_gmt($post->post_date_gmt); $update['post_date_gmt'] = $post->post_date_gmt; }
        if (count($update) > 1) { $result = wp_update_post(wp_slash($update), true); if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message())); } $count++; }
    }
    update_option('pd_content_urls_version', '0.2.1');
    wp_safe_redirect(add_query_arg(array('page' => 'pd-content', 'resolved' => $count), admin_url('tools.php'))); exit;
});
// Existing legacy landing addresses resolve to current content instead of obsolete offers.
add_action('template_redirect', function () {
    if (!is_404()) { return; }
    $path = trim((string) wp_parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH), '/');
    $map = array('page26427604.html' => '/', 'compact' => '/catalog/', 'page133939406.html' => '/');
    if (isset($map[$path])) { wp_safe_redirect(home_url($map[$path]), 301); exit; }
});
add_filter('manage_pd_lead_posts_columns', function ($columns) { $columns['pd_delivery'] = 'Уведомление'; return $columns; });
add_action('manage_pd_lead_posts_custom_column', function ($column, $id) {
    if ($column === 'pd_delivery') { echo get_post_meta($id, '_pd_test', true) ? 'Тест: письмо не отправлялось' : (get_post_meta($id, '_pd_email_sent', true) === '1' ? 'Передано почтовому сервису' : 'Не отправлено'); }
}, 10, 2);

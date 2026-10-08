<?php
if (!defined('ABSPATH')) { exit; }
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'gallery', 'caption', 'style', 'script'));
});
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('planodom-font', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap', array(), null);
    wp_enqueue_style('planodom', get_stylesheet_uri(), array(), '0.2.1');
    wp_enqueue_script('planodom', get_template_directory_uri() . '/site.js', array(), '0.2.1', true);
});
function pd_is_staging() {
    return strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST)) === 'new.planodom.ru';
}
add_filter('pre_option_blog_public', function ($value) { return pd_is_staging() ? '0' : $value; });
add_filter('wp_robots', function ($robots) {
    if (pd_is_staging()) { $robots['noindex'] = true; $robots['nofollow'] = true; unset($robots['index'], $robots['follow']); }
    return $robots;
});
add_action('send_headers', function () {
    if (pd_is_staging()) { header('X-Robots-Tag: noindex, nofollow, noarchive', true); }
});
add_action('init', function () {
    register_post_type('pd_lead', array('labels' => array('name' => 'Заявки сайта', 'singular_name' => 'Заявка'), 'public' => false, 'show_ui' => true, 'show_in_rest' => false, 'supports' => array('title', 'editor'), 'capability_type' => 'post', 'map_meta_cap' => true, 'menu_icon' => 'dashicons-email-alt'));
});
function pd_render_html($html) {
    $html = preg_replace_callback('/href="\{\{HOME_URL\}\}#([a-zA-Z0-9_-]+)"/', function ($match) use ($html) {
        return strpos($html, 'id="' . $match[1] . '"') !== false ? 'href="#' . $match[1] . '"' : $match[0];
    }, $html);
    $fields = wp_nonce_field('pd_lead', 'pd_nonce', false, false);
    $fields = str_replace(' id="pd_nonce"', '', $fields);
    if (current_user_can('manage_options') && isset($_GET['pd_test']) && $_GET['pd_test'] === '1') { $fields .= '<input type="hidden" name="pd_test" value="1">'; }
    $fields .= '<input type="hidden" name="action" value="pd_lead"><input type="hidden" name="return_url" value="' . esc_url(home_url('/')) . '"><label style="position:absolute;left:-10000px" aria-hidden="true">Оставьте пустым<input name="company_url" tabindex="-1" autocomplete="off"></label>';
    return do_shortcode(strtr($html, array('{{ASSET_URL}}' => esc_url(get_template_directory_uri() . '/assets'), '{{BLOG_URL}}' => esc_url(home_url('/blog/')), '{{LEAD_URL}}' => esc_url(admin_url('admin-post.php')), '{{HOME_URL}}' => esc_url(home_url('/')), '{{LEAD_FIELDS}}' => $fields)));
}
function pd_lead_handler() {
    if (empty($_POST['pd_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pd_nonce'])), 'pd_lead')) {
        wp_die('Обновите страницу и попробуйте отправить заявку ещё раз.', 'Заявка', array('response' => 403));
    }
    if (!empty($_POST['company_url'])) { wp_safe_redirect(home_url('/')); exit; }
    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
    $digits = preg_replace('/\D/', '', $phone);
    if (empty($_POST['consent']) || strlen($digits) < 10 || strlen($digits) > 15 || strlen($name) > 200) {
        wp_die('Укажите корректный телефон и подтвердите согласие на обработку данных.', 'Заявка', array('response' => 400));
    }
    $rate_key = 'pd_lead_' . hash_hmac('sha256', isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '', wp_salt());
    if (get_transient($rate_key)) { wp_die('Заявка уже отправлена. Подождите минуту перед повторной отправкой.', 'Заявка', array('response' => 429)); }
    $message = 'Имя: ' . $name . "\nТелефон: " . $phone . "\nСогласие на обработку данных: да\nДата: " . current_time('mysql');
    $lead = wp_insert_post(array('post_type' => 'pd_lead', 'post_status' => 'private', 'post_title' => 'Заявка ' . current_time('d.m.Y H:i'), 'post_content' => $message), true);
    if (is_wp_error($lead)) { wp_die('Не удалось сохранить заявку. Попробуйте ещё раз или позвоните нам.', 'Заявка', array('response' => 500)); }
    set_transient($rate_key, true, MINUTE_IN_SECONDS);
    $test = current_user_can('manage_options') && isset($_POST['pd_test']) && $_POST['pd_test'] === '1';
    if ($test) { update_post_meta($lead, '_pd_test', '1'); }
    $sent = $test ? false : wp_mail(get_option('admin_email'), 'Новая заявка Planodom', $message);
    update_post_meta($lead, '_pd_email_sent', $sent ? '1' : '0');
    wp_safe_redirect(add_query_arg('pd_sent', '1', home_url('/sps/'))); exit;
}
add_action('admin_post_pd_lead', 'pd_lead_handler');
add_action('admin_post_nopriv_pd_lead', 'pd_lead_handler');
add_action('after_switch_theme', function () {
    if (pd_is_staging()) { update_option('blog_public', 0); }
    if (!get_option('pd_initial_home')) {
        $html = file_get_contents(get_template_directory() . '/home.html');
        $id = wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Планодом — каркасные дома под ключ', 'post_content' => $html), true);
        if (!is_wp_error($id)) { update_option('pd_initial_home', $id); update_option('show_on_front', 'page'); update_option('page_on_front', $id); }
    }
    if (!get_option('pd_initial_blog')) {
        $id = wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Блог', 'post_name' => 'blog'), true);
        if (!is_wp_error($id)) { update_option('pd_initial_blog', $id); update_option('page_for_posts', $id); }
    }
    flush_rewrite_rules();
});

require_once get_template_directory() . '/content-tools.php';

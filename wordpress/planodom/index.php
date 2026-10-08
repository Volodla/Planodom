<?php if (!defined('ABSPATH')) { exit; } ?>
<!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head><body <?php body_class(); ?>><?php wp_body_open(); ?>
<?php
if (is_front_page()) {
    $content = get_post_field('post_content', get_option('page_on_front'));
    echo pd_render_html($content ? $content : file_get_contents(get_template_directory() . '/home-v2.html'));
} else {
    pd_render_header();
    echo '<main id="pd-content" class="pd-content">';
    if (is_404()) {
        echo '<section class="pd-page-head"><p class="category">Ошибка 404</p><h1>Страница не найдена</h1><p>Возможно, адрес изменился. Выберите проект дома или вернитесь на главную.</p><a class="orange" href="' . esc_url(home_url('/catalog/')) . '">Каталог домов</a> <a class="outline" href="' . esc_url(home_url('/')) . '">Главная</a></section>';
    } elseif (is_singular()) {
        while (have_posts()) {
            the_post(); $type = get_post_type(); $article = $type === 'post'; $project = $type === 'pd_project'; $case = $type === 'pd_case';
            echo '<section class="pd-page-head">';
            pd_crumb(get_the_title(), $article ? 'Блог' : ($project ? 'Каталог домов' : ($case ? 'Построенные дома' : '')), $article ? '/blog/' : ($project ? '/catalog/' : '/built/'));
            if ($article) { echo '<p class="category">' . wp_kses_post(get_the_category_list(' · ')) . '</p>'; }
            echo '<h1>' . esc_html(get_the_title()) . '</h1>';
            if (has_excerpt()) { echo '<p class="pd-intro">' . esc_html(get_the_excerpt()) . '</p>'; }
            if ($article) {
                $minutes = max(1, (int) ceil((function_exists('mb_strlen') ? mb_strlen(wp_strip_all_tags(get_the_content())) : strlen(wp_strip_all_tags(get_the_content())) / 2) / 1200));
                echo '<p class="muted">Planodom · <time datetime="' . esc_attr(get_the_date(DATE_W3C)) . '">' . esc_html(get_the_date()) . '</time> · ' . $minutes . ' мин. чтения</p>';
            }
            $cover = pd_cover(get_the_ID());
            if ($cover && ($article || $project)) { echo '<img class="pd-cover" src="' . esc_url($cover) . '" alt="' . esc_attr(get_the_title()) . '" width="1600" height="1000" fetchpriority="high">'; }
            echo '</section>';
            $content = pd_render_html(apply_filters('the_content', get_the_content()));
            if ($article) {
                list($content, $toc) = pd_article_toc($content);
                echo '<div class="pd-article-layout"><article class="pd-prose">' . $content;
                wp_link_pages(array('before' => '<nav class="pd-pagination">', 'after' => '</nav>'));
                echo '</article><aside class="pd-sidebar"><h2>В этой статье</h2><nav aria-label="Оглавление">';
                foreach ($toc as $entry) { echo '<a href="#' . esc_attr($entry['id']) . '">' . esc_html($entry['text']) . '</a>'; }
                echo '</nav><div class="pd-side-cta"><h3>Подберём дом для вас</h3><p>По планировке, площади и бюджету.</p><button class="orange lead" type="button">Обсудить мой дом</button></div></aside></div><section><h2>Проекты по теме</h2>' . pd_cards('pd_project', 3) . '</section><section><h2>Читайте также</h2>' . pd_cards('post', 2, array(get_the_ID())) . '</section>';
            } else {
                echo '<div class="pd-prose pd-page-body">';
                if ($project) {
                    $images = (array) get_post_meta(get_the_ID(), '_pd_images', true);
                    if ($images) {
                        echo '<h2>Фотографии и планировки</h2><div class="pd-gallery">';
                        foreach ($images as $i => $image) { $url = get_template_directory_uri() . '/assets/' . rawurlencode(basename($image)); echo '<a href="' . esc_url($url) . '"><img src="' . esc_url($url) . '" alt="' . esc_attr(get_the_title() . ' — вид ' . ($i + 1)) . '" loading="lazy" width="1200" height="900"></a>'; }
                        echo '</div><h2>Описание и комплектация</h2>';
                    }
                }
                echo $content;
                if ($project || $case) { echo '<div class="article-cta"><h2>Обсудим ваш будущий дом</h2><p>Планировку, комплектацию, срок и стоимость согласуем для вашего участка.</p><button class="orange lead" type="button">Получить расчёт</button></div>'; }
                echo '</div>';
            }
        }
    } else {
        echo '<section class="pd-page-head">';
        if (is_search()) { echo '<h1>Поиск: ' . esc_html(get_search_query()) . '</h1>'; }
        elseif (is_home()) { echo '<h1>Блог Planodom</h1><p class="pd-intro">О выборе проекта, комплектации и подготовке к строительству.</p>'; }
        else { echo '<h1>' . esc_html(wp_strip_all_tags(get_the_archive_title())) . '</h1>'; }
        echo '</section><section><div class="pd-cards">';
        if (have_posts()) {
            while (have_posts()) { the_post(); $cover = pd_cover(get_the_ID()); echo '<article class="pd-card"><a href="' . esc_url(get_permalink()) . '">'; if ($cover) { echo '<img src="' . esc_url($cover) . '" alt="' . esc_attr(get_the_title()) . '" loading="lazy" width="1000" height="750">'; } echo '<h2>' . esc_html(get_the_title()) . '</h2></a><p>' . esc_html(wp_trim_words(get_the_excerpt(), 28)) . '</p><a class="outline" href="' . esc_url(get_permalink()) . '">Читать далее</a></article>'; }
        } else { echo '<p>Материалы не найдены. <a href="' . esc_url(home_url('/catalog/')) . '">Перейти к проектам домов</a>.</p>'; }
        echo '</div>'; the_posts_pagination(array('prev_text' => 'Назад', 'next_text' => 'Далее')); echo '</section>';
    }
    echo '</main>'; pd_render_footer();
}
wp_footer();
?></body></html>

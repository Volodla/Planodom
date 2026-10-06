<?php if (!defined('ABSPATH')) { exit; } ?>
<!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head><body <?php body_class(); ?>><?php wp_body_open(); ?>
<?php
$html = file_get_contents(get_template_directory() . '/home.html');
if (is_front_page()) {
    $content = get_post_field('post_content', get_option('page_on_front'));
    echo pd_render_html($content ? $content : $html);
    if (isset($_GET['pd_sent']) && $_GET['pd_sent'] === '1') { echo '<div role="status" style="position:fixed;bottom:20px;left:20px;right:20px;background:#fff;padding:20px;z-index:1000;border:2px solid #ff7300">Заявка получена. Мы свяжемся с вами.</div>'; }
} else {
    $header = substr($html, 0, strpos($html, '<main id="pd-home"'));
    $footer = substr($html, strpos($html, '<footer id="pd-contact"'));
    $header = preg_replace('/href="#(pd-[^"]+)"/', 'href="' . esc_url(home_url('/')) . '#$1"', $header);
    $footer = preg_replace('/href="#(pd-[^"]+)"/', 'href="' . esc_url(home_url('/')) . '#$1"', $footer);
    echo pd_render_html($header);
    echo '<main id="pd-blog">';
    if (is_singular()) {
        while (have_posts()) { the_post(); ?>
        <section class="article-head"><p class="crumb"><a href="<?php echo esc_url(home_url('/')); ?>">Главная</a> / <a href="<?php echo esc_url(home_url('/blog/')); ?>">Блог</a></p><h1><?php the_title(); ?></h1><p class="muted">Planodom · <?php echo esc_html(get_the_date()); ?></p><?php if (has_post_thumbnail()) { the_post_thumbnail('large', array('class' => 'article-cover')); } ?></section>
        <div class="article-layout"><article><?php the_content(); ?></article><aside><h3>Подберём дом для вас</h3><p>По площади, планировке и бюджету.</p><button class="orange lead" type="button">Подобрать проект</button></aside></div>
        <?php }
    } else {
        echo '<section><h1>Блог Planodom</h1><div class="built-grid">';
        if (have_posts()) { while (have_posts()) { the_post(); echo '<article>'; if (has_post_thumbnail()) { the_post_thumbnail('medium_large'); } echo '<h2><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h2>'; the_excerpt(); echo '</article>'; } }
        else { echo '<p>Статьи скоро появятся.</p>'; }
        echo '</div></section>';
    }
    echo '</main>' . pd_render_html($footer);
}
wp_footer();
?></body></html>

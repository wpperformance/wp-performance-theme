<?php

namespace WP_Performance;

if (! defined('WP_ENV')) {
  define('WP_ENV', 'development');
}

// inc, you can modify this files like you want
require_once dirname(__FILE__) . '/inc/gutenberg.php';
require_once dirname(__FILE__) . '/inc/login_assets.php';
require_once dirname(__FILE__) . '/inc/sortable.php';

// variations
require_once dirname(__FILE__) . '/inc/variations_blocks.php';

// binding meta
require_once dirname(__FILE__) . '/inc/binding-meta.php';

// post type
require_once dirname(__FILE__) . '/post-type/snippet.php';
require_once dirname(__FILE__) . '/post-type/training.php';

// pwa icons
if (file_exists(dirname(__FILE__) . '/inc/pwa_head.php')) {
  include dirname(__FILE__) . '/inc/pwa_head.php';
}

/**
 * Theme setup.
 */
function setup()
{
  add_theme_support('automatic-feed-links');

  add_theme_support('title-tag');

  add_theme_support('post-thumbnails');

  load_theme_textdomain('press-wind', get_template_directory() . '/languages');
}

add_action('after_setup_theme', __NAMESPACE__ . '\setup');

/**
 * init assets front
 */
if (class_exists('PressWind\PWVite')) {

  \PressWind\PWVite::init(port: 3000, path: '');
  /**
   * init assets admin
   */
  \PressWind\PWVite::init(
    port: 4444,
    path: '/admin',
    position: 'editor',
    is_ts: false
  );
}
/** disable caching wp query */
function disable_caching($wp_query)
{
  if (WP_ENV === 'development') {
    $wp_query->query_vars['cache_results'] = false;
  }
}

add_action('parse_query', __NAMESPACE__ . '\disable_caching');

add_action('init', function () {
  add_filter('jpeg_quality', function () {
    return 100;
  }, 10, 2);
});

register_block_style(
  'core/image',
  [
    'name' => 'img-dropshadow',
    'label' => __('Drop Shadow', 'press-wind'),
  ]
);

register_block_style(
  'core/image',
  [
    'name' => 'img-dropshadow-rounded',
    'label' => __('Drop Shadow Rounded', 'press-wind'),
  ]
);

register_block_style(
  'core/heading',
  [
    'name' => 'text-gradient',
    'label' => __('Text Gradient', 'press-wind'),
  ]
);

register_block_style(
  'core/heading',
  [
    'name' => 'text-effect',
    'label' => __('Text Effect', 'press-wind'),
  ]
);

register_block_style(
  'core/heading',
  [
    'name' => 'title-hero',
    'label' => __('Title Hero', 'press-wind'),
  ]
);

register_block_style(
  'core/paragraph',
  [
    'name' => 'underscore',
    'label' => __('Underscore', 'press-wind'),
  ]
);


function add_img_size($content)
{
  $pattern = '/<img [^>]*?src="(https?:\/\/[^"]+?)"[^>]*?>/iu';
  preg_match_all($pattern, $content, $imgs);
  foreach ($imgs[0] as $i => $img) {
    if (str_contains($img, 'width=') && str_contains($img, 'height=')) {
      continue;
    }
    $img_url = $imgs[1][$i];
    $img_size = @getimagesize($img_url);

    if ($img_size === false) {
      continue;
    }
    $replaced_img = str_replace('<img ', '<img ' . $img_size[3] . ' ', $imgs[0][$i]);
    $content = str_replace($img, $replaced_img, $content);
  }

  return $content;
}
add_filter('the_content', __NAMESPACE__ . '\add_img_size');

// pass youtube to youtube-nocookie
add_filter(
  'render_block',
  function ($block_content, $block) {
    // filter block
    if ($block['blockName'] === 'core/embed') {
      $tags = new \WP_HTML_Tag_Processor($block_content);
      $tags->next_tag('iframe');
      $url = $tags->get_attribute('src');
      $parameters = parse_url($url);

      // check if youtube
      if ($parameters['host'] !== 'www.youtube.com') {
        return $block_content;
      }

      // update host
      $parameters['host'] = 'www.youtube-nocookie.com';
      // add rel=0 for related video. Show only the video from the
      // current channel, remove the next line if you want to show
      // related videos from other channels
      $parameters['query'] = $parameters['query'] === '' ? 'rel=0' : 'rel=0&' . $parameters['query'];
      // reconstruct url
      $url = $parameters['scheme'] . '://' . $parameters['host'] . $parameters['path'] . '?' . $parameters['query'];
      $tags->set_attribute('src', $url);

      return $tags;
    }

    return $block_content;
  },
  1,
  2
);

/**
 * Add stylesheet link tag to head test
 */
// function add_stylesheet_link() {
//     echo '<link rel="stylesheet" href="' . get_stylesheet_directory_uri() . '/style.css" type="text/css" media="all">';
// }
// add_action('wp_head', __NAMESPACE__ . '\add_stylesheet_link');

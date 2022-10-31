<?php

namespace PressWind\inc\assets;

function getTokenName($key)
{
  // get token file
  $k = explode('.', $key);
  $token = $key;
  if (array_key_exists(1, $k)) {
    $token = $k[1];
  }
  return $token;
}



/**
 * get manifest file generated by vite
 */
function getManifest()
{
  $strJsonFileContents = file_get_contents(dirname(__FILE__) . "/../dist/manifest.json");
  return json_decode(str_replace(
    '\u0000',
    '',
    $strJsonFileContents
  ));
}


/**
 * Enqueue scripts.
 *
 */
function addScript()
{
  $path = get_template_directory_uri();

  if (WP_ENV !== 'development') {
    // get files name list from manifest
    $config = namespace\getManifest();

    if (!$config) return;
    // load others files
    $files = get_object_vars($config);
    $sc = [];
    $legacyIsIn = false;
    foreach ($files as $key => $value) {
      if (property_exists($value, 'isEntry') === false) continue;
      $file = $value->file;
      // get token file
      $token = getTokenName($file);
      $f = ['token' => $token, 'file' => $file];
      // all file except legacy
      if (strpos($file, 'polyfills') === false && strpos($file, 'legacy') === false) {
        $sc[] = $f;
        // main legacy after polyfill if already in $sc
      } else if (strpos($file, 'legacy') !== false && strpos($file, 'polyfills') === false && $legacyIsIn === true) {
        // split array into two parts
        $split1 = array_slice($sc, 0, 1, true);
        $split2 = array_slice($sc, 1, null, true);
        // add new array element at between two parts
        $sc = array_merge($split1, [1 => $f], $split2);
        // polyfill in first
      } else {
        $legacyIsIn = true;
        array_unshift($sc, $f);
      }
    }
    foreach ($sc as $key => $value) {
      wp_enqueue_script('press-wind-theme-' . $value['token'], $path . '/dist/' . $value['file'], array(), $value['token'], true);
      // add prefetch
      // add_action('wp_head', function () use ($path, $value) {
      //   echo '<link rel="prefetch" href="' . $path . '/dist/' . $value['file'] . '" as="script" />';
      // });
    }
  } else {
    // development
    wp_enqueue_script('press-wind-theme', 'http://localhost:3000/main.js', []);
  }
}


/**
 * Register the JavaScript for the public-facing side of the site.
 */
function enqueue_scripts()
{
  add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if (strpos($handle, 'press-wind-theme') === false) {
      return $tag;
    }
    // change the script tag by adding type="module" and return it.
    $tag = '<script type="module" crossorigin src="' . esc_url($src) . '"></script>';
    return $tag;
  }, 10, 3);

  add_action('wp_enqueue_scripts', __NAMESPACE__ . '\addScript');
}


/**
 * Register the CSS
 */
function enqueue_styles()
{
  add_action(
    'wp_enqueue_scripts',
    function () {
      $path = get_template_directory_uri();
      if (WP_ENV !== 'development') {
        // get file name from manifest
        $config = namespace\getManifest();
        if (!$config) return;
        $files = get_object_vars($config);
        // search css key
        foreach ($files as $key => $value) {
          // only entry and css
          if (property_exists($value, 'isEntry') === false || property_exists($value, 'css') === false) continue;
          $css = $value->css;
          // $css is array
          foreach ($css as $file) {
            // get token file
            $token = getTokenName($file);
            wp_enqueue_style(
              'press-wind-theme-' . $token,
              $path . '/dist/' . $file,
              array(),
              $token,
              'all'
            );
            // add prefetch
            add_action('wp_head', function () use ($path, $file) {
              echo '<link rel="preload" href="' . $path . '/dist/' . $file . '" as="style" />';
            });
          }
        }
      }
    }
  );
}



/**
 * Completely Remove jQuery From WordPress if not admin and is not connected
 */
function removeJquery()
{
  if ($GLOBALS['pagenow'] !== 'wp-login.php' && !is_admin() && !is_user_logged_in()) {
    wp_deregister_script('jquery');
    wp_register_script('jquery', false);
  }
}


function remove_jquery_migrate($scripts)
{

  if (!is_admin() && isset($scripts->registered['jquery'])) {

    $script = $scripts->registered['jquery'];

    if ($script->deps) {
      $script->deps = array_diff($script->deps, array('jquery-migrate'));
    }
  }
}


// add_action('init', __NAMESPACE__ . '\removeJquery');
add_action('wp_default_scripts', __NAMESPACE__ . '\remove_jquery_migrate');
add_action('init', __NAMESPACE__ . '\enqueue_scripts');
add_action('init', __NAMESPACE__ . '\enqueue_styles');

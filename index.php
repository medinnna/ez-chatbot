<?php
/*
Plugin Name: EZ Chatbot
Description: EZ Chatbot is a WordPress plugin that allows you to create a custom chatbot for your website using the OpenAI API.
Version: 1.0.0
Author: Carlos Medina
Author URI: https://medina.dev
*/

add_action('rest_api_init', 'ez_chatbot_register_rest_route');
add_action('wp_enqueue_scripts', 'ez_chatbot_enqueue_scripts');
add_action('admin_enqueue_scripts', 'ez_chatbot_load_media');
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ez_chatbot_links', 10, 2);
add_action('admin_menu', 'ez_chatbot_settings_page');
add_action('wp_footer', 'ez_chatbot_root');

function ez_chatbot_register_rest_route() {
  register_rest_route('ez-chatbot/v1', '/openai', array(
    'methods' => 'POST',
    'callback' => 'ez_chatbot_request',
    'permission_callback' => 'ez_chatbot_request_validator',
  ));
}

function ez_chatbot_request(WP_REST_Request $request) {
  $api_key = ez_chatbot_get_api_key();

  if (!$api_key) {
    return new WP_REST_Response(['error' => 'API Key no configurada'], 400);
  }

  $url = 'https://api.openai.com/v1/chat/completions';
  $curl = curl_init($url);
  $body = $request->get_json_params();
  $headers = [
    'Authorization: Bearer ' . $api_key,
    'Content-Type: application/json',
  ];
  $data = json_encode(array_merge($body, ['stream' => true]));

  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
  curl_setopt($curl, CURLOPT_WRITEFUNCTION, function ($curl, $data) {
    echo $data;
    ob_flush();
    flush();
    return strlen($data);
  });
  curl_setopt($curl, CURLOPT_TIMEOUT, 0);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($curl, CURLOPT_HEADER, false);

  header('Content-Type: text/event-stream');
  header('Cache-Control: no-cache');
  header('Connection: keep-alive');

  curl_exec($curl);
  $err = curl_error($curl);

  if ($err) {
    return new WP_REST_Response(['error' => 'cURL Error: ' . $err], 500);
  }

  curl_close($curl);

  die();
}

function ez_chatbot_request_validator() {
  $referer = $_SERVER['HTTP_REFERER'] ?? '';
  $allow = home_url();

  return strpos($referer, $allow) === 0;
}

function ez_chatbot_enqueue_scripts() {
  // Scripts
  wp_register_script('ez_chatbot', plugins_url('/dist/assets/index.js', __FILE__), [], '1.0.0', true);
  wp_localize_script('ez_chatbot', 'ez_chatbot_settings', [
    "base_url" => home_url(),
    "assets_url" => plugins_url('/dist', __FILE__),
    "enabled" => get_option('ez_chatbot_enabled'),
    "image" => get_option('ez_chatbot_image'),
    "name" => get_option('ez_chatbot_name'),
    "color" => get_option('ez_chatbot_color'),
    "system" => get_option('ez_chatbot_system'),
    "knowledge" => get_option('ez_chatbot_knowledge'),
    "welcome" => get_option('ez_chatbot_welcome')
  ]);
  wp_enqueue_script('ez_chatbot');

  // Styles
  wp_enqueue_style('ez_chatbot', plugins_url('/dist/assets/index.css', __FILE__), [], '1.0.0');
}

function ez_chatbot_load_media() {
  wp_enqueue_media();
}

function ez_chatbot_links($links, $file) {
  static $ez_chatbot;

  if (!$ez_chatbot) {
    $ez_chatbot = plugin_basename(__FILE__);
  }

  if ($file == $ez_chatbot) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=ez-chatbot-settings') . '">Configuración</a>';

    array_unshift($links, $settings_link);
  }

  return $links;
}

function ez_chatbot_settings_page() {
  add_options_page(
    'EZ Chatbot',
    'EZ Chatbot',
    'manage_options',
    'ez-chatbot-settings',
    'ez_chatbot_settings_html'
  );
}

function ez_chatbot_set_api_key($api_key) {
  $cipher = 'aes-256-cbc';
  $key = wp_salt('SECURE_AUTH_KEY');
  $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));

  $encrypted = openssl_encrypt($api_key, $cipher, $key, 0, $iv);
  $data = base64_encode($iv . '::' . $encrypted);

  update_option('ez_chatbot_api_key', $data);
}

function ez_chatbot_get_api_key() {
  $cipher = 'aes-256-cbc';
  $key = wp_salt('SECURE_AUTH_KEY');
  $encrypted_data = get_option('ez_chatbot_api_key', '');

  if (!$encrypted_data) {
    return null;
  }

  list($iv, $encrypted) = explode('::', base64_decode($encrypted_data), 2);

  return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
}

function ez_chatbot_settings_html() {
  if (isset($_POST['submit'])) {
    $enabled = $_POST['ez_chatbot_enabled'] === '1' ? true : false;

    update_option('ez_chatbot_enabled', $enabled);
    update_option('ez_chatbot_image', sanitize_text_field($_POST['ez_chatbot_image']));
    update_option('ez_chatbot_name', sanitize_text_field($_POST['ez_chatbot_name']));
    update_option('ez_chatbot_color', $_POST['ez_chatbot_color']);
    update_option('ez_chatbot_system', sanitize_text_field($_POST['ez_chatbot_system']));
    update_option('ez_chatbot_knowledge', sanitize_text_field($_POST['ez_chatbot_knowledge']));
    update_option('ez_chatbot_welcome', sanitize_text_field($_POST['ez_chatbot_welcome']));

    if ($_POST['ez_chatbot_api_key'] === '') {
      update_option('ez_chatbot_api_key', '');
    } else {
      $api_key = sanitize_text_field($_POST['ez_chatbot_api_key']);

      ez_chatbot_set_api_key($api_key);
    }
  }

  $enable = get_option('ez_chatbot_enabled');
  $image = get_option('ez_chatbot_image');
  $name = get_option('ez_chatbot_name');
  $color = get_option('ez_chatbot_color');
  $system = get_option('ez_chatbot_system');
  $welcome = get_option('ez_chatbot_welcome');
  $knowledge = get_option('ez_chatbot_knowledge');
  $api_key = ez_chatbot_get_api_key();

  include(plugin_dir_path(__FILE__) . 'dist/settings.php');
}

function ez_chatbot_root() {
  echo '<div id="ez-chatbot-wrapper"></div>';
}

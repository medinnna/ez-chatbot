<?php
/*
  Plugin Name: EZ Chatbot
  Plugin URI: https://github.com/medinnna/ez-chatbot
  Description: EZ Chatbot is a WordPress plugin that allows you to create a custom chatbot for your website using the OpenAI API.
  Text Domain: ez-chatbot
  Version: 1.2.0
  Author: Carlos Medina
  Author URI: https://medina.dev
  License: GPL v3 or later
  License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

defined('ABSPATH') or die();

class EZChatbot {
  public function __construct() {
    add_action('init', [$this, 'load_languages']);
    add_filter('load_textdomain_mofile', [$this, 'load_mofiles'], 10, 2);

    if (isset($_GET['_wpnonce'], $_GET['download_conversation_id'])) {
      $nonce = $_GET['_wpnonce'];
      $conversation_id = intval($_GET['download_conversation_id']);
      
      add_action('init', function() use ($nonce, $conversation_id) {
        $this->download_conversation($nonce, $conversation_id);
      }, 10, 0);
    }

    if (isset($_GET['_wpnonce'], $_GET['delete_conversation_id'])) {
      $nonce = $_GET['_wpnonce'];
      $conversation_id = intval($_GET['delete_conversation_id']);
      
      add_action('init', function() use ($nonce, $conversation_id) {
        $this->delete_conversation($nonce, $conversation_id);
      }, 10, 0);
    }

    add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'action_links'], 10, 2);
    add_action('admin_menu', [$this, 'create_settings_page']);
    add_action('admin_menu', [$this, 'create_conversations_page']);
    add_action('admin_head', [$this, 'rename_settings_page']);
    add_action('admin_enqueue_scripts', [$this, 'load_media']);
    add_action('init', [$this, 'conversations_post_type']);
    add_action('wp_footer', [$this, 'create_element']);
    add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    add_action('rest_api_init', [$this, 'register_rest_routes']);
  }

  public function action_links($links, $file) {
    static $ez_chatbot;

    if (!$ez_chatbot) {
      $ez_chatbot = plugin_basename(__FILE__);
    }

    if ($file == $ez_chatbot) {
      $settings_link = '<a href="' . admin_url('admin.php?page=ez-chatbot-settings') . '">' . __('Settings', 'ez-chatbot') . '</a>';

      array_unshift($links, $settings_link);
    }

    return $links;
  }

  public function create_settings_page() {
    add_menu_page(
      'EZ Chatbot',
      'EZ Chatbot',
      'manage_options',
      'ez-chatbot-settings',
      [$this, 'settings_page'],
      plugin_dir_url(__FILE__) . 'dist/assets/icon.svg'
    );
  }

  public function create_conversations_page() {
    add_submenu_page(
      'ez-chatbot-settings',
      __('Conversations', 'ez-chatbot'),
      __('Conversations', 'ez-chatbot'),
      'manage_options',
      'ez-chatbot-conversations',
      [$this, 'conversations_page']
    );
  }

  public function rename_settings_page() {
    global $submenu;

    if (isset($submenu['ez-chatbot-settings'])) {
      $submenu['ez-chatbot-settings'][0][0] = __('Settings', 'ez-chatbot');
    }
  }

  public function settings_page() {
    if (isset($_POST['submit'])) {
      $enabled = $_POST['ez_chatbot_enabled'] === '1' ? true : false;

      update_option('ez_chatbot_enabled', $enabled);
      update_option('ez_chatbot_image', sanitize_text_field($_POST['ez_chatbot_image']));
      update_option('ez_chatbot_name', sanitize_text_field($_POST['ez_chatbot_name']));
      update_option('ez_chatbot_color', $_POST['ez_chatbot_color']);
      update_option('ez_chatbot_system', sanitize_textarea_field(stripslashes($_POST['ez_chatbot_system'])));
      update_option('ez_chatbot_knowledge', sanitize_textarea_field(stripslashes($_POST['ez_chatbot_knowledge'])));
      update_option('ez_chatbot_welcome', sanitize_textarea_field(stripslashes($_POST['ez_chatbot_welcome'])));

      if ($_POST['ez_chatbot_api_key'] === '') {
        update_option('ez_chatbot_api_key', '');
      } else {
        $api_key = sanitize_text_field($_POST['ez_chatbot_api_key']);

        $this->set_api_key($api_key);
      }
    }

    $enable = get_option('ez_chatbot_enabled');
    $image = get_option('ez_chatbot_image');
    $name = get_option('ez_chatbot_name');
    $color = get_option('ez_chatbot_color');
    $system = get_option('ez_chatbot_system');
    $system_default = "You are a chatbot. Do not answer questions that are not related to the knowledge assigned to you. Do not answer questions about world knowledge, famous people, etc. Before you can respond to the user on a topic, they must provide their name and email address. You must first ask for their name in one message and then ask for their email separately. If the user has already given their name, explicitly ask them for their email address. Use the 'get_user_name' function when the user writes a name. Use the 'get_user_email' function only if the user's message contains a valid email (with an '@' and appropriate formatting). Do not call a function if the data is invalid and ask the user to provide a correct value. If the user has already provided their name and email, you can answer their questions. Your knowledge is as follows:";
    $welcome = get_option('ez_chatbot_welcome');
    $knowledge = get_option('ez_chatbot_knowledge');
    $api_key = $this->get_api_key();

    require_once plugin_dir_path(__FILE__) . 'pages/settings.php';
  }

  public function load_media() {
    wp_enqueue_media();
  }

  private function set_api_key($api_key) {
    $cipher = 'aes-256-cbc';
    $key = wp_salt('SECURE_AUTH_KEY');
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));

    $encrypted = openssl_encrypt($api_key, $cipher, $key, 0, $iv);
    $data = base64_encode($iv . '::' . $encrypted);

    update_option('ez_chatbot_api_key', $data);
  }

  private function get_api_key() {
    $cipher = 'aes-256-cbc';
    $key = wp_salt('SECURE_AUTH_KEY');
    $encrypted_data = get_option('ez_chatbot_api_key', '');

    if (!$encrypted_data) {
      return null;
    }

    list($iv, $encrypted) = explode('::', base64_decode($encrypted_data), 2);

    return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
  }

  public function conversations_post_type() {
    register_post_type('chat_conversation', [
      'labels' => [
        'name' => __('Chat Conversations', 'ez-chatbot'),
        'singular_name' => __('Chat Conversation', 'ez-chatbot'),
      ],
      'public' => false,
      'show_ui' => false,
      'supports' => ['title'],
      'capability_type' => 'post',
      'has_archive' => false,
      'menu_icon' => 'dashicons-format-chat',
    ]);
  }

  public function conversations_page() {
    if (isset($_GET['conversation_id']) && !isset($_GET['ez-chatbot-download'])) {
      $conversation_id = intval($_GET['conversation_id']);
      $messages = get_post_meta($conversation_id, 'messages', true);

      require_once plugin_dir_path(__FILE__) . 'pages/conversation.php';
    } else {
      $conversations = get_posts([
        'post_type' => 'chat_conversation',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'ID',
        'order' => 'ASC'
      ]);

      require_once plugin_dir_path(__FILE__) . 'pages/conversations.php';
    }
  }

  private function download_conversation($nonce, $conversation_id) {
    if (!wp_verify_nonce($nonce, 'download_conversation_' . $conversation_id) || !current_user_can('manage_options')) {
      wp_die(__('You do not have permission to download this conversation.', 'ez-chatbot'));
    }

    $conversation = new WP_Query([
      'p' => $conversation_id,
      'post_type' => 'chat_conversation',
    ]);
    $email = get_post_meta($conversation->post->ID, 'email', true);
    $file_headers = [
      __('Sender', 'ez-chatbot'),
      __('Message', 'ez-chatbot'),
      __('Date', 'ez-chatbot')
    ];
    $fileName = "ez_chatbot-" . $email . ".csv";
    $tempFile = tempnam(get_temp_dir(), 'download_');
    $file = fopen($tempFile, 'w');

    fputcsv($file, $file_headers);
    
    if($conversation->have_posts()): $conversation->the_post();
      $messages = get_post_meta($conversation->post->ID, 'messages', true);

      foreach ($messages as $message):
        $data = [
          'sender' => $message['sender'],
          'message' => $message['message'],
          'timestamp' => $message['timestamp']
        ];

        fputcsv($file, $data);
      endforeach;
    endif; wp_reset_query();

    fclose($file);

    if (file_exists($tempFile)) {
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename=' . $fileName);
      header('Content-Transfer-Encoding: binary');
      header('Expires: 0');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Pragma: public');
      header('Content-Length: ' . filesize($tempFile));

      readfile($tempFile);
      exit;
    }
  }

  private function delete_conversation($nonce, $conversation_id) {
    if (!wp_verify_nonce($nonce, 'delete_conversation_' . $conversation_id) || !current_user_can('manage_options')) {
      wp_die(__('You do not have permission to delete this conversation.', 'ez-chatbot'));
    }

    wp_delete_post($conversation_id, true);
    wp_redirect(admin_url('admin.php?page=ez-chatbot-conversations'));
    exit;
  }

  public function load_languages() {
    load_plugin_textdomain('ez-chatbot', false, dirname(plugin_basename(__FILE__)) . '/languages');
  }

  public function load_mofiles($mofile, $domain) {
    if ('ez-chatbot' !== $domain) {
      return $mofile;
    }

    $locale = apply_filters('plugin_locale', determine_locale(), $domain);

    if (strpos($locale, "es_") === 0) {
      $mofile = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)) . '/languages/' . $domain . '-es_ES.mo';
    }

    return $mofile;
  }

  public function create_element() {
    echo '<div id="ez-chatbot-wrapper"></div>';
  }

  public function enqueue_scripts() {
    // Scripts
    wp_register_script('ez_chatbot', plugins_url('/dist/assets/index.js', __FILE__), [], '1.1.1', true);
    wp_localize_script('ez_chatbot', 'ez_chatbot_settings', [
      "base_url" => home_url(),
      "assets_url" => plugins_url('/dist', __FILE__),
      "enabled" => get_option('ez_chatbot_enabled'),
      "image" => get_option('ez_chatbot_image'),
      "name" => get_option('ez_chatbot_name'),
      "color" => get_option('ez_chatbot_color'),
      "welcome" => get_option('ez_chatbot_welcome'),
      "placeholder" => __('What can I help you with?', 'ez-chatbot')
    ]);
    wp_enqueue_script('ez_chatbot');

    // Styles
    wp_enqueue_style('ez_chatbot', plugins_url('/dist/assets/index.css', __FILE__), [], '1.0.0');
  }

  public function register_rest_routes() {    
    register_rest_route('ez-chatbot/v1', '/openai', array(
      'methods' => 'POST',
      'callback' => [$this, 'create_request'],
      'permission_callback' => [$this, 'request_validator']
    ));

    register_rest_route('ez-chatbot/v1', '/conversations', array(
      'methods' => 'POST',
      'callback' => [$this, 'create_conversation'],
      'permission_callback' => [$this, 'request_validator']
    ));

    register_rest_route('ez-chatbot/v1', '/messages', array(
      'methods' => 'POST',
      'callback' => [$this, 'save_message'],
      'permission_callback' => [$this, 'request_validator']
    ));
  }

  public function create_conversation(WP_REST_Request $request) {
    $conversation = $request->get_json_params();

    $args = array(
      'post_type' => 'chat_conversation',
      'posts_per_page' => -1,
      'meta_query' => array(
        array(
          'key' => 'email',
          'value' => $conversation['email']
        )
      )
    );

    $history = new WP_Query($args);

    if (!$history->have_posts()) {
      $conversation_id = wp_insert_post([
        'post_type' => 'chat_conversation',
        'post_title' => $conversation['name'],
        'post_status' => 'publish',
      ]);

      update_post_meta($conversation_id, 'email', $conversation['email']);

      foreach ($conversation['messages'] as $message) {
        if ($message['role'] === 'system') continue;

        $new_message = [
          'conversation_id' => $conversation_id,
          'sender' => $message['role'] === 'assistant' ? 'Chatbot' : 'User',
          'message' => $message['content']
        ];

        $message_json = json_encode($new_message);

        $this->save_message($message_json);
      }
    }
  }

  public function create_request(WP_REST_Request $request) {
    $api_key = $this->get_api_key();

    if (!$api_key) {
      return new WP_REST_Response(['error' => 'API Key no configurada'], 400);
    }

    $url = 'https://api.openai.com/v1/chat/completions';
    $curl = curl_init($url);
    $headers = [
      'Authorization: Bearer ' . $api_key,
      'Content-Type: application/json',
    ];
    $instructions = [
      'role' => 'system',
      'content' => get_option('ez_chatbot_system')
    ];
    $knowledge = [
      'role' => 'system',
      'content' => get_option('ez_chatbot_knowledge')
    ];
    $system_prompt = [$instructions, $knowledge];
    $body = $request->get_json_params();
    $messages = array_merge($system_prompt, $body['messages']);
    $data = json_encode(array_merge($body, ['messages' => $messages]));
    $streaming = isset($body['stream']) ? boolval($body['stream']) : false;

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, $streaming ? false : true);
    curl_setopt($curl, CURLOPT_WRITEFUNCTION, function ($curl, $data) {
      echo $data;
      ob_flush();
      flush();
      return strlen($data);
    });
    curl_setopt($curl, CURLOPT_TIMEOUT, 0);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_HEADER, false);

    if ($streaming) {
      header('Content-Type: text/event-stream');
      header('Cache-Control: no-cache');
      header('Connection: keep-alive');
    } else {
      header('Content-Type: application/json');
    }

    curl_exec($curl);
    $err = curl_error($curl);

    if ($err) {
      return new WP_REST_Response(['error' => 'cURL Error: ' . $err], 500);
    }

    curl_close($curl);

    die();
  }

  public function request_validator() {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $allow = home_url();

    return strpos($referer, $allow) === 0;
  }

  public function save_message($request) {
    if (is_a($request, 'WP_REST_Request')) {
      $message = $request->get_json_params();

      $args = array(
        'post_type' => 'chat_conversation',
        'posts_per_page' => 1,
        'meta_query' => array(
          array(
            'key' => 'email',
            'value' => $message['email']
          )
        )
      );

      $conversation = new WP_Query($args);

      $body = [
        'conversation_id' => $conversation->post->ID,
        'sender' => $message['role'] === 'assistant' ? 'Chatbot' : 'User',
        'message' => $message['content']
      ];

      $data = json_encode($body);
    } else {
      $data = $request;
    }

    $new_message = json_decode($data);
    $messages = get_post_meta($new_message->conversation_id, 'messages', true);

    if (!$messages) {
      $messages = [];
    }

    $messages[] = [
      'sender' => $new_message->sender,
      'message' => $new_message->message,
      'timestamp' => current_time('mysql'),
    ];

    update_post_meta($new_message->conversation_id, 'messages', $messages);
  }
}

$ez_chatbot = new EZChatbot();
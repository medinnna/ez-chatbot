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
?>
  <style>
    input[type="text"],
    input[type="password"],
    textarea {
      width: 400px;
      max-width: 80%;
    }

    .ez_chatbot_image {
      display: block;
      width: 100px;
      height: 100px;
      margin-bottom: 10px;

      &.hidden {
        display: none;
      }
    }
  </style>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelector('.ez_chatbot_image_select').addEventListener('click', function(e) {
        const image = wp.media({
          title: 'Subir Imagen',
          multiple: false
        }).open().on('select', function(e) {
          const uploaded_image = image.state().get('selection').first();
          const ez_chatbot_image = document.querySelector('.ez_chatbot_image');
          const ez_chatbot_image_upload = document.querySelector('.ez_chatbot_image_upload');

          ez_chatbot_image.src = uploaded_image.toJSON().url;
          ez_chatbot_image.classList.remove("hidden");
          ez_chatbot_image_upload.value = uploaded_image.toJSON().url;
        });

        e.preventDefault();
      });
    });
  </script>

  <div class="wrap">
    <h1>EZ Chatbot - Configuración</h1>

    <form method="post" action="">
      <table class="form-table">
        <tr>
          <th>
            <h2 style="margin-bottom: 0">Personalización</h2>
          </th>
        </tr>

        <tr>
          <th scope="row">Mostrar</th>
          <td>
            <input type="hidden" name="ez_chatbot_enabled" value="0">
            <input type="checkbox" name="ez_chatbot_enabled" value="1" <?php echo ($enable ? 'checked' : ''); ?> />
          </td>
        </tr>

        <tr>
          <th scope="row">Imagen del chatbot</th>
          <td>
            <img src="<?php echo esc_attr($image); ?>" class="ez_chatbot_image <?php echo (empty($image) ? 'hidden' : ''); ?>" alt="EZ Chatbot image" />

            <input type="hidden" class="ez_chatbot_image_upload" name="ez_chatbot_image" value="<?php echo esc_attr($image); ?>">
            <input type="button" class="ez_chatbot_image_select" value="Subir Imagen" />
          </td>
        </tr>

        <tr>
          <th scope="row">Nombre del chatbot</th>
          <td>
            <input type="text" name="ez_chatbot_name" value="<?php echo (empty($name) ? 'Chatbot' : esc_attr($name)); ?>" placeholder="Chatbot" />
          </td>
        </tr>

        <tr>
          <th scope="row">Color del chatbot</th>
          <td>
            <input type="color" name="ez_chatbot_color" value="<?php echo esc_attr($color); ?>" />
          </td>
        </tr>

        <tr>
          <th scope="row">Mensaje de bienvenida</th>
          <td>
            <textarea rows="5" name="ez_chatbot_welcome"><?php echo (empty($welcome) ? '¡Hola! Soy tu asistente virtual. ¿En qué te puedo ayudar?' : esc_textarea($welcome)); ?></textarea>
          </td>
        </tr>

        <tr>
          <th scope="row">Prompt del sistema</th>
          <td>
            <textarea rows="5" name="ez_chatbot_system"><?php echo esc_textarea($system); ?></textarea>
          </td>
        </tr>

        <tr>
          <th scope="row">Conocimientos</th>
          <td>
            <textarea rows="5" name="ez_chatbot_knowledge"><?php echo esc_textarea($knowledge); ?></textarea>
          </td>
        </tr>

        <tr>
          <th>
            <h2 style="margin-bottom: 0">Conexión</h2>
          </th>
        </tr>

        <tr valign="top">
          <th scope="row">API Key</th>
          <td>
            <?php if ($api_key === ''): ?>
              <input type="text" name="ez_chatbot_api_key" />
            <?php else: ?>
              <input type="password" name="ez_chatbot_api_key" value="<?php echo esc_attr($api_key); ?>" />
            <?php endif; ?>
          </td>
        </tr>
      </table>

      <?php submit_button(); ?>
    </form>
  </div>
<?php
}

function ez_chatbot_root() {
  echo '<div id="ez-chatbot-wrapper"></div>';
}

<?php defined('ABSPATH') or die(); ?>

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
  <h1><?php _e('EZ Chatbot - Settings', 'ez-chatbot'); ?></h1>

  <form method="post" action="">
    <table class="form-table">
      <tr>
        <th>
          <h2 style="margin-bottom: 0">
            <?php _e('Customization', 'ez-chatbot'); ?>
          </h2>
        </th>
      </tr>

      <tr>
        <th>
          <?php _e('Enable', 'ez-chatbot'); ?>
        </th>

        <td>
          <input type="hidden" name="ez_chatbot_enabled" value="0">
          <input type="checkbox" name="ez_chatbot_enabled" value="1" <?php echo ($enable ? 'checked' : ''); ?> />
        </td>
      </tr>

      <tr>
        <th>
          <?php _e('Profile image', 'ez-chatbot'); ?>
        </th>

        <td>
          <img src="<?php echo esc_attr($image); ?>" class="ez_chatbot_image <?php echo (empty($image) ? 'hidden' : ''); ?>" alt="EZ Chatbot Image" />

          <input type="hidden" class="ez_chatbot_image_upload" name="ez_chatbot_image" value="<?php echo esc_attr($image); ?>">
          <input type="button" class="ez_chatbot_image_select" value="<?php esc_html_e('Upload image', 'ez-chatbot'); ?>" />
        </td>
      </tr>

      <tr>
        <th>
          <?php _e('Profile name', 'ez-chatbot'); ?>
        </th>

        <td>
          <input type="text" name="ez_chatbot_name" value="<?php echo (empty($name) ? 'Chatbot' : esc_attr($name)); ?>" placeholder="Chatbot" />
        </td>
      </tr>

      <tr>
        <th>
          <?php _e('Color', 'ez-chatbot'); ?>
        </th>

        <td>
          <input type="color" name="ez_chatbot_color" value="<?php echo esc_attr($color); ?>" />
        </td>
      </tr>

      <tr>
        <th>
          <?php _e('Welcome message', 'ez-chatbot'); ?>
        </th>

        <td>
          <textarea rows="5" name="ez_chatbot_welcome"><?php echo (empty($welcome) ? _e("Hi! I'm your virtual assistant. What can I help you with?", 'ez-chatbot') : esc_textarea($welcome)); ?></textarea>
        </td>
      </tr>

      <tr>
        <th>
          <?php _e('Knowledge', 'ez-chatbot'); ?>
        </th>

        <td>
          <textarea rows="5" name="ez_chatbot_knowledge"><?php echo esc_textarea($knowledge); ?></textarea>
        </td>
      </tr>

      <tr>
        <th>
          <h2 style="margin-bottom: 0">
            <?php _e('Advanced settings (Caution!)', 'ez-chatbot'); ?>
          </h2>
        </th>
      </tr>

      <tr>
        <th>
          <?php _e('Open AI API Key', 'ez-chatbot'); ?>
        </th>

        <td>
          <?php if ($api_key === ''): ?>
            <input type="text" name="ez_chatbot_api_key" />
          <?php else: ?>
            <input type="password" name="ez_chatbot_api_key" value="<?php echo esc_attr($api_key); ?>" />
          <?php endif; ?>
        </td>
      </tr>

      <tr>
        <th>
          <?php _e('System prompt', 'ez-chatbot'); ?>
        </th>

        <td>
          <textarea rows="5" name="ez_chatbot_system"><?php echo esc_textarea($system); ?></textarea>
        </td>
      </tr>
    </table>

    <?php submit_button(); ?>
  </form>
</div>
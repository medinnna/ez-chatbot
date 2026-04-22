<?php
  defined('ABSPATH') or die();
  $active_tab = $_GET['tab'] ?? 'Appearance';
?>

<div class="ez-chatbot__admin">
  <div class="ez-chatbot__admin-col settings">
    <header>
      <div class="container">
        <div class="row">
          <div class="col">
            <h1><?php _e('EZ Chatbot', 'ez-chatbot'); ?><sup>v2.0.0</sup></h1>
            <h2><?= __('Settings', 'ez-chatbot'); ?></h2>
          </div>
        </div>

        <div class="row">
          <div class="col">
            <h3 class="ez-chatbot__admin-tabs">
              <a href="?page=ez-chatbot-settings&tab=Appearance"
                class="ez-chatbot__admin-tab <?php echo $active_tab === 'Appearance' ? 'active' : ''; ?>">
                <?php _e('Appearance', 'ez-chatbot'); ?>
              </a>

              <a href="?page=ez-chatbot-settings&tab=webhook"
                class="ez-chatbot__admin-tab <?php echo $active_tab === 'webhook' ? 'active' : ''; ?>">
                <?php _e('Webhook', 'ez-chatbot'); ?>
              </a>

              <a href="?page=ez-chatbot-settings&tab=advanced"
                class="ez-chatbot__admin-tab <?php echo $active_tab === 'advanced' ? 'active' : ''; ?>">
                <?php _e('Advanced settings', 'ez-chatbot'); ?>
              </a>
            </h3>
          </div>
        </div>
      </div>
    </header>

    <main>
      <div class="container">
        <div class="ez-chatbot__admin-content">
          <form method="post" action="">
            <?php wp_nonce_field('ez_chatbot_settings_action', 'ez_chatbot_nonce') ?>
            
            <?php if ($active_tab === 'Appearance') : ?>
              <fieldset>
                <p><?= esc_html_x('Enable', 'visibility', 'ez-chatbot'); ?></p>

                <div class="input">
                  <label class="ez-chatbot__switch" for="ez_chatbot_enabled">
                    <div class="ez-chatbot__switch-toggle"></div>
                  </label>
                  
                  <input type="hidden" name="ez_chatbot_enabled" value="0">
                  <input type="checkbox" name="ez_chatbot_enabled" id="ez_chatbot_enabled" value="1" <?php echo ($settings['enabled'] ? 'checked' : ''); ?> />
                </div>
              </fieldset>

              <fieldset>
                <p><?= _e('Notification bubble', 'ez-chatbot'); ?></p>

                <div class="input">
                  <label class="ez-chatbot__switch" for="ez_chatbot_notifications">
                    <div class="ez-chatbot__switch-toggle"></div>
                  </label>
                  
                  <input type="hidden" name="ez_chatbot_notifications" value="0">
                  <input type="checkbox" name="ez_chatbot_notifications" id="ez_chatbot_notifications" value="1" <?php echo ($settings['notifications'] ? 'checked' : ''); ?> />
                </div>
              </fieldset>

              <fieldset>
                <p><?php _e('Profile image', 'ez-chatbot'); ?></p>

                <div class="input">
                  <input type="hidden" class="ez_chatbot_image_upload" name="ez_chatbot_image" value="<?php echo esc_attr($settings['image']); ?>">
                  <input type="button" class="ez_chatbot_image_select ez-chatbot__button" value="<?= empty($settings['image']) ? esc_html_e('Select image', 'ez-chatbot') : esc_html_e('Replace image', 'ez-chatbot'); ?>" />
                </div>
              </fieldset>

              <fieldset>
                <p>
                  <?php _e('Profile name', 'ez-chatbot'); ?>
                </p>

                <div class="input">
                  <input type="text" name="ez_chatbot_name" id="ez_chatbot_profile_name" value="<?php echo (empty($settings['name']) ? 'EZ Chatbot' : esc_attr($settings['name'])); ?>" placeholder="Chatbot" />
                </div>
              </fieldset>

              <fieldset>
                <p>
                  <?php _e('Color', 'ez-chatbot'); ?>
                </p>

                <div class="input">
                  <input type="color" name="ez_chatbot_color" id="ez_chatbot_color" value="<?php echo esc_attr($settings['color']); ?>" />
                </div>
              </fieldset>

              <fieldset>
                <p>
                  <?php _e('Welcome message', 'ez-chatbot'); ?>
                </p>

                <div class="input">
                  <textarea rows="5" name="ez_chatbot_welcome" id="ez_chatbot_welcome"><?php echo (empty($settings['welcome']) ? _e("Hi! I'm your virtual assistant. What can I help you with?", 'ez-chatbot') : esc_textarea($settings['welcome'])); ?></textarea>
                </div>
              </fieldset>

              <fieldset>
                <p>
                  <?php _e('Knowledge', 'ez-chatbot'); ?>
                </p>

                <div class="input">
                  <textarea rows="5" name="ez_chatbot_knowledge" id="ez_chatbot_knowledge"><?php echo esc_textarea($settings['knowledge']); ?></textarea>
                </div>
              </fieldset>
            <?php elseif ($active_tab === 'webhook') : ?>
              <fieldset>
                <p>
                  <?= esc_html_x('Enable', 'activation', 'ez-chatbot'); ?>
                </p>

                <div class="input">
                  <label class="ez-chatbot__switch" for="ez_chatbot_webhook">
                    <div class="ez-chatbot__switch-toggle"></div>
                  </label>

                  <input type="hidden" name="ez_chatbot_webhook" value="0"/>
                  <input type="checkbox" name="ez_chatbot_webhook" id="ez_chatbot_webhook" value="1" <?php echo ($settings['webhook'] ? 'checked' : ''); ?>/>
                </div>
              </fieldset>
              
              <fieldset>
                <p>
                  <?php _e('Webhook URL', 'ez-chatbot'); ?>
                </p>

                <div class="input">
                  <input type="url" placeholder="https://example.com" pattern="https://.*" name="ez_chatbot_webhook_url" value="<?php echo esc_attr($settings['webhook_url']); ?>" />
                </div>
              </fieldset>

              <fieldset>
                <p>
                  <?php _e('Headers', 'ez-chatbot'); ?>
                </p>

                <div class="input">
                  <textarea rows="5" name="ez_chatbot_webhook_headers" placeholder="Content-Type: application/json"><?php echo esc_textarea($settings['webhook_headers']); ?></textarea>
                </div>
              </fieldset>
            <?php elseif ($active_tab === 'advanced') : ?>
              <fieldset>
                <p>
                  <?php _e('Open AI API Key', 'ez-chatbot'); ?>
                </p>

                <div class="input">
                  <?php if ($settings['api_key'] === ''): ?>
                    <input type="text" name="ez_chatbot_api_key" />
                  <?php else: ?>
                    <input type="password" name="ez_chatbot_api_key" value="<?php echo esc_attr($settings['api_key']); ?>" />
                  <?php endif; ?>
                </div>
              </fieldset>

              <fieldset>
                <p>
                  <?php _e('System prompt', 'ez-chatbot'); ?>
                </p>

                <div class="input">
                  <textarea rows="15" name="ez_chatbot_system"><?php echo esc_textarea(!empty($settings['system']) ? $settings['system'] : $settings['system_default']); ?></textarea>
                </div>
              </fieldset>
            <?php endif; ?>

            <?php submit_button(); ?>
          </form>
        </div>
      </div>
    </main>
  </div>

  <div class="ez-chatbot__admin-col preview">
    <div class="ez-chatbot-admin-preview">
      <div id="ez-chatbot-wrapper">
        <div class="chatbot__widget" style="--color: <?= $settings['color'] ? $settings['color'] : '#000000'; ?>">
          <div class="chatbot__widget-window">
            <header>
              <img
                class="profile"
                alt="Profile image of the chatbot"
                src="<?= $settings['image'] ?: plugins_url('../dist/assets/profile.png', __FILE__); ?>"
              />

              <p aria-label="Chatbot name: EZ Chatbot">
                <?php echo (empty($settings['name']) ? 'EZ Chatbot' : esc_attr($settings['name'])); ?>
              </p>

              <i data-lucide="x" class="close"></i>
            </header>

            <main>
              <div class="messages">
                <div class="message assistant">
                  <p><?= (empty($settings['welcome']) ? esc_html__("Hi! I'm your virtual assistant. What can I help you with?", 'ez-chatbot') : nl2br(esc_html($settings['welcome']))); ?></p>
                </div>
              </div>

              <div class="loading" style="display: none">
                <div class="dots">
                  <div class="dot"></div>
                  <div class="dot"></div>
                  <div class="dot"></div>
                </div>
              </div>
            </main>

            <footer>
              <form>
                <input
                  placeholder="<?php esc_attr_e('What can I help you with?', 'ez-chatbot'); ?>"
                  type="text"
                  value=""
                />
                
                <button type="submit" disabled="">
                  <i data-lucide="send" class="send" width="15"></i>
                </button>
              </form>
            </footer>
          </div>

          <div class="chatbot__widget-btn">
            <i data-lucide="message-square"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
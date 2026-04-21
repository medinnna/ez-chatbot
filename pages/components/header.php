<?php
  defined('ABSPATH') or die();
?>

<header>
  <div class="container">
    <div class="row">
      <div class="col">Back</div>
    </div>

    <div class="row">
      <div class="col">
        <h1><?php _e('EZ Chatbot', 'ez-chatbot'); ?><sup>v2.0</sup></h1>
        <h2><?= $header['subtitle']; ?></h2>
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
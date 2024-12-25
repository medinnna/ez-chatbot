<?php defined('ABSPATH') or die(); ?>

<div class="wrap">
  <h1>
    <?php _e('Conversation Details', 'ez-chatbot') ?>
  </h1>

  <a href="<?php echo admin_url('admin.php?page=ez-chatbot-conversations'); ?>">
    <?php _e('Back to conversations', 'ez-chatbot') ?>
  </a>

  <br>
  <br>

  <table class="wp-list-table widefat fixed striped">
    <thead>
      <tr>
        <th><?php _e('Sender', 'ez-chatbot') ?></th>
        <th><?php _e('Message', 'ez-chatbot') ?></th>
        <th><?php _e('Date', 'ez-chatbot') ?></th>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($messages as $message): ?>
        <tr>
          <td><?php echo $message['sender']; ?></td>
          <td><?php echo $message['message']; ?></td>
          <td><?php echo $message['timestamp']; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
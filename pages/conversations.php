<?php defined('ABSPATH') or die(); ?>

<div class="wrap">
  <?php if (count($conversations) > 0): ?>
    <h1>
      <?php _e('Chat History', 'ez-chatbot') ?>
    </h1>

    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th><?php _e('ID', 'ez-chatbot') ?></th>
          <th><?php _e('Title', 'ez-chatbot') ?></th>
          <th><?php _e('Date', 'ez-chatbot') ?></th>
          <th><?php _e('Actions', 'ez-chatbot') ?></th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($conversations as $conversation): ?>
          <tr>
            <td><?php echo $conversation->ID; ?></td>
            <td><?php echo $conversation->post_title; ?></td>
            <td><?php echo $conversation->post_date; ?></td>
            <td>
              <a href="<?php echo admin_url('admin.php?page=ez-chatbot-conversations&conversation_id=' . $conversation->ID); ?>">
                <?php _e('View', 'ez-chatbot') ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <h1>
      <?php _e('No conversations found', 'ez-chatbot') ?>
    </h1>
  <?php endif; ?>
</div>
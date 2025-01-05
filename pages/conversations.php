<?php defined('ABSPATH') or die(); ?>

<div class="wrap">
  <?php if (count($conversations) > 0): ?>
    <h1>
      <?php _e('EZ Chatbot Conversations', 'ez-chatbot') ?>
    </h1>

    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th><?php _e('Name', 'ez-chatbot') ?></th>
          <th><?php _e('Email', 'ez-chatbot') ?></th>
          <th><?php _e('Date', 'ez-chatbot') ?></th>
          <th><?php _e('Actions', 'ez-chatbot') ?></th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($conversations as $conversation): ?>
          <tr>
            <td><?php echo $conversation->post_title; ?></td>
            <td><?php echo get_post_meta($conversation->ID, 'email', true); ?></td>
            <td><?php echo $conversation->post_date; ?></td>
            <td>
              <a href="<?php echo admin_url('admin.php?page=ez-chatbot-conversations&conversation_id=' . $conversation->ID); ?>">
                <?php _e('View', 'ez-chatbot') ?>
              </a>

              |

              <a href="<?php echo admin_url('admin.php?page=ez-chatbot-conversations&conversation_id=' . $conversation->ID . '&ez-chatbot-download=1'); ?>">
                <?php _e('Download', 'ez-chatbot') ?>
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
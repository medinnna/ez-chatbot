<?php defined('ABSPATH') or die(); ?>

<div class="ez-chatbot__admin">
  <div class="ez-chatbot__admin-col settings">
    <header>
      <div class="container">
        <div class="row">
          <div class="col">
            <a href="<?= admin_url('admin.php?page=ez-chatbot-conversations'); ?>" class="ez-chatbot__admin-back <?= isset($_GET['conversation_id']) ? '' : 'hidden'; ?>">
              <i data-lucide="arrow-left"></i>
            </a>
          </div>
        </div>

        <div class="row align-items-end">
          <div class="col">
            <h1><?php _e('EZ Chatbot', 'ez-chatbot'); ?><sup>v2.0.0</sup></h1>
            <h2><?php _e('Conversation details', 'ez-chatbot'); ?></h2>
          </div> 
        </div>

        <?php
          $args = [
            'post_type'      => 'chat_conversation',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'post_date',
            'order'          => 'DESC',
            'fields'         => 'ids'
          ];

          if (!empty($_GET['ez_search'])) {
            $argsByEmail = array_merge($args, ['meta_query' => [
              [
                'key' => 'email',
                'value' => $_GET['ez_search'],
                'compare' => 'LIKE'
                ]
              ],
            ]);

            $args['s'] = sanitize_text_field($_GET['ez_search']);
                
            $conversationsByTitle = (new WP_Query($args))->posts;
            $conversationsByEmail = (new WP_Query($argsByEmail))->posts;
            $conversationsSearch = array_unique(array_merge($conversationsByTitle, $conversationsByEmail));

            $conversations = new WP_Query([
              'post_type' => 'chat_conversation',
              'post__in' => !empty($conversationsSearch) ? $conversationsSearch : [0],
            ]);
          } else {
            $conversations = new WP_Query($args);
          }
        ?>
      </div>
    </header>

    <main>
      <div class="container">
        <div class="ez-chatbot__admin-content">
          <table class="ez-chatbot__admin-conversations">
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
                  <td><?= $message['sender']; ?></td>
                  <td><?= $message['message']; ?></td>
                  <td><?= date("d-m-Y g:iA", strtotime($message['timestamp'])); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>
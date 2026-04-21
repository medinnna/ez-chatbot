<?php defined('ABSPATH') or die(); ?>

<div class="ez-chatbot__admin">
  <div class="ez-chatbot__admin-col settings">
    <header>
      <div class="container">
        <div class="row">
          <div class="col">
            <a href="<?= admin_url('admin.php?page=ez-chatbot-conversations'); ?>" class="ez-chatbot__admin-back <?= isset($_GET['ez_search']) ? '' : 'hidden'; ?>">
              <i data-lucide="arrow-left"></i>
            </a>
          </div>
        </div>

        <div class="row align-items-end">
          <div class="col">
            <h1><?php _e('EZ Chatbot', 'ez-chatbot'); ?><sup>v2.0.0</sup></h1>
            <h2><?php _e('Conversations', 'ez-chatbot'); ?></h2>
          </div>

          <div class="col">
            <div class="ez-chatbot__admin-search">
              <i data-lucide="search"></i>

              <form method="GET" action="">
                <input type="hidden" name="page" value="ez-chatbot-conversations">
                <input type="text" name="ez_search" value="<?= isset($_GET['ez_search']) ? esc_attr($_GET['ez_search']) : ''; ?>" placeholder="<?php _e('Search conversation', 'ez-chatbot'); ?>">
              </form>
            </div>
          </div>
        </div>

        <?php
          $paged = isset($_GET['paged']) ? (int) $_GET['paged'] : 1;
          $args = [
            'post_type'      => 'chat_conversation',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'post_date',
            'order'          => 'DESC',
            'fields'         => 'ids',
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
              'posts_per_page' => 10,
              'post__in' => !empty($conversationsSearch) ? $conversationsSearch : [0],
              'paged' => $paged,
            ]);
          } else {
            $args['posts_per_page'] = 10;
            $args['paged'] = $paged;
            $conversations = new WP_Query($args);
          }
        ?>
      </div>
    </header>
    
    <main>
      <div class="container">
        <div class="ez-chatbot__admin-content">
          <?php if(!isset($_GET['ez_search'])): ?>
            <div class="ez-chatbot__admin-stats">
              <?php $data = $this->get_stats("chat_conversation"); ?>

              <div class="ez-chatbot__admin-stat total">
                <p><?php _e('Total conversations', 'ez-chatbot'); ?></p>
                <p class="quantity"><?= $data['total'] ?></p>
                <p><i data-lucide="users"></i></p>
              </div>

              <div class="ez-chatbot__admin-stat monthly <?= $data['month']['comparison'] < 0 ? 'down' : 'up' ?>">
                <p><?php _e('This month', 'ez-chatbot'); ?></p>

                <p class="quantity"><?= $data['month']['current'] ?></p>

                <div class="icon">
                  <?php if($data['month']['comparison'] < 0): ?>
                    <i data-lucide="trending-down"></i>
                  <?php else: ?>
                    <i data-lucide="trending-up"></i>
                  <?php endif; ?>
                </div>

                <p class="percentage"><?= sprintf('%+d%%', $data['month']['comparison']) ?> <?php _e('from last month', 'ez-chatbot'); ?></p>
              </div>

              <div class="ez-chatbot__admin-stat weekly <?= $data['week']['comparison'] < 0 ? 'down' : 'up' ?>">
                <p><?php _e('This week', 'ez-chatbot'); ?></p>

                <p class="quantity"><?= $data['week']['current'] ?></p>

                <div class="icon">
                  <?php if($data['week']['comparison'] < 0): ?>
                    <i data-lucide="trending-down"></i>
                  <?php else: ?>
                    <i data-lucide="trending-up"></i>
                  <?php endif; ?>
                </div>

                <p class="percentage"><?= sprintf('%+d%%', $data['week']['comparison']) ?> <?php _e('from last week', 'ez-chatbot'); ?></p>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($conversations->found_posts > 0): ?>
            <table class="ez-chatbot__admin-conversations">
              <thead>
                <tr>
                  <th><?php _e('Name', 'ez-chatbot') ?></th>
                  <th><?php _e('Email', 'ez-chatbot') ?></th>
                  <th><?php _e('Date', 'ez-chatbot') ?></th>
                  <th><?php _e('Actions', 'ez-chatbot') ?></th>
                </tr>
              </thead>

              <tbody>
                <?php while ($conversations->have_posts()): $conversations->the_post(); ?>
                  <tr>
                    <td>
                      <a href="<?= admin_url('admin.php?page=ez-chatbot-conversations&conversation_id=' . get_the_ID()); ?>"><?= get_the_title(); ?></a>
                    </td>

                    <td>
                      <div class="link">
                        <a href="mailto:<?= get_post_meta(get_the_ID(), 'email', true); ?>"><?= get_post_meta(get_the_ID(), 'email', true); ?></a>
                        <i data-lucide="external-link" stroke-width="3"></i>
                      </div>
                    </td>

                    <td><?= get_the_date("d-m-Y g:iA"); ?></td>
                    
                    <td>
                      <div class="ez-chatbot__conversation-actions">
                        <a href="<?= admin_url('admin.php?page=ez-chatbot-conversations&conversation_id=' . get_the_ID()); ?>" title="<?php _e('View', 'ez-chatbot') ?>">
                          <i data-lucide="eye"></i>
                        </a>

                        <a href="<?= wp_nonce_url(admin_url('admin.php?page=ez-chatbot-conversations&download_conversation_id=' . get_the_ID()), 'download_conversation_' . get_the_ID()); ?>" title="<?php _e('Download', 'ez-chatbot') ?>">
                          <i data-lucide="download"></i>
                        </a>

                        <a href="<?= wp_nonce_url(admin_url('admin.php?page=ez-chatbot-conversations&delete_conversation_id=' . get_the_ID()), 'delete_conversation_' . get_the_ID()); ?>" onclick="return confirm('<?php _e('Are you sure you want to delete this conversation?', 'ez-chatbot'); ?>')" title="<?php _e('Delete', 'ez-chatbot') ?>">
                          <i data-lucide="trash"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          <?php else: ?>
            <h1>
              <?php _e('No conversations found', 'ez-chatbot') ?>
            </h1>
          <?php endif; ?>

          <div class="ez-chatbot__admin-pagination">
            <?= paginate_links([
                'base' => add_query_arg([
                  'paged' => '%#%',
                  'ez_search' => isset($_GET['ez_search']) ? $_GET['ez_search'] : null,
                ]),
                'format' => '',
                'current' => $paged,
                'total' => $conversations->max_num_pages,
                'mid_size' => 2,
                'prev_text' => '<',
                'next_text' => '>',
              ]);
            ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
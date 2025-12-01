<?php
/**
 * Front-end Admin UI page for inventory management.
 */

if (!defined('ABSPATH')) exit;

/**
 * Strip headings/descriptions that shouldn't appear on the public Admin UI page.
 *
 * @param string $content
 * @return string
 */
function ptcgdm_strip_admin_ui_headings($content) {
  $content = preg_replace('~<h1[^>]*>\s*PTCG Deck \xe2\x80\x93 Card Inventory\s*</h1>~ui', '', $content);
  $content = preg_replace('~<h1[^>]*>\s*One Piece TCG \xe2\x80\x93 Card Inventory\s*</h1>~ui', '', $content);
  $content = preg_replace('~<p[^>]*class="description"[^>]*>\s*Maintain a single card inventory list using the local dataset\.\s*</p>~ui', '', $content);
  $content = preg_replace('~<p[^>]*class="description"[^>]*>\s*Track One Piece TCG card inventory using the local dataset\.\s*</p>~ui', '', $content);

  return $content;
}

/**
 * Render a specific dataset inventory and return the sanitized markup.
 *
 * @param callable $callback
 * @return string
 */
function ptcgdm_capture_admin_ui_panel($callback) {
  if (!is_callable($callback)) {
    return '<div class="wrap"><p>Inventory UI is unavailable.</p></div>';
  }

  ob_start();
  call_user_func($callback);
  $content = ob_get_clean();

  return ptcgdm_strip_admin_ui_headings((string) $content);
}

/**
 * Render a simple WooCommerce orders list for the Admin UI page.
 */
function ptcgdm_render_admin_orders_panel() {
  echo '<div class="wrap"><h2>Orders</h2>';

  if (!function_exists('wc_get_orders')) {
    echo '<p class="ptcgdm-orders__empty">WooCommerce is not available.</p>';
    echo '</div>';
    return;
  }

  $orders = wc_get_orders([
    'limit'   => 20,
    'orderby' => 'date',
    'order'   => 'DESC',
  ]);

  if (empty($orders)) {
    echo '<p class="ptcgdm-orders__empty">No orders found.</p>';
    echo '</div>';
    return;
  }

  echo '<table class="ptcgdm-orders__table">';
  echo '<thead><tr>';
  echo '<th scope="col">Order</th>';
  echo '<th scope="col">Date</th>';
  echo '<th scope="col">Status</th>';
  echo '<th scope="col">Total</th>';
  echo '<th scope="col">Customer</th>';
  echo '</tr></thead>';
  echo '<tbody>';

  foreach ($orders as $order) {
    if (!$order instanceof WC_Order) {
      continue;
    }

    $order_number = esc_html($order->get_order_number());
    $status       = esc_html(wc_get_order_status_name($order->get_status()));

    $date_created = $order->get_date_created();
    $date_label   = $date_created ? esc_html($date_created->date_i18n(get_option('date_format') . ' ' . get_option('time_format'))) : '-';

    $customer_name = esc_html($order->get_formatted_billing_full_name() ?: __('Guest', 'woocommerce'));

    $total = $order->get_formatted_order_total();
    if (!is_string($total)) {
      $total = wc_price($order->get_total());
    }

    echo '<tr>';
    echo '<td>#' . $order_number . '</td>';
    echo '<td>' . $date_label . '</td>';
    echo '<td>' . $status . '</td>';
    echo '<td>' . wp_kses_post($total) . '</td>';
    echo '<td>' . $customer_name . '</td>';
    echo '</tr>';
  }

  echo '</tbody>';
  echo '</table>';
  echo '</div>';
}

/**
 * Build the inventory management markup for the public page/shortcode.
 *
 * @return string
 */
function ptcgdm_get_admin_ui_content() {
  $sections = [
    'pokemon'   => [
      'label'   => 'Pokémon Inventory',
      'content' => ptcgdm_capture_admin_ui_panel('ptcgdm_render_pokemon_inventory'),
    ],
    'one_piece' => [
      'label'   => 'One Piece Inventory',
      'content' => ptcgdm_capture_admin_ui_panel('ptcgdm_render_one_piece_inventory'),
    ],
    'orders' => [
      'label'   => 'Orders',
      'content' => ptcgdm_capture_admin_ui_panel('ptcgdm_render_admin_orders_panel'),
    ],
  ];

  ob_start();
  ?>
  <div class="ptcgdm-admin-ui">
    <style>
      .ptcgdm-admin-ui .wrap > h1,
      .ptcgdm-admin-ui .wrap > p.description { display: none; }
      .ptcgdm-admin-ui__shell { display: grid; grid-template-columns: 240px 1fr; gap: 16px; align-items: flex-start; }
      .ptcgdm-admin-ui__sidebar { background: #0f1218; border: 1px solid #1f2533; border-radius: 12px; padding: 12px; position: sticky; top: 16px; }
      .ptcgdm-admin-ui__tab { width: 100%; text-align: left; border: 1px solid #1f2533; background: #111725; color: #cfd6e6; padding: 10px 12px; border-radius: 10px; cursor: pointer; margin-bottom: 8px; font-weight: 600; }
      .ptcgdm-admin-ui__tab.is-active { background: linear-gradient(180deg, #28304a, #1b2034); border-color: #324061; color: #fff; }
      .ptcgdm-admin-ui__tab:last-child { margin-bottom: 0; }
      .ptcgdm-admin-ui__content { min-height: 360px; }
      .ptcgdm-admin-ui__panel { display: none; }
      .ptcgdm-admin-ui__panel.is-active { display: block; }
      .ptcgdm-orders__table { width: 100%; border-collapse: collapse; background: #0f1218; border: 1px solid #1f2533; border-radius: 12px; overflow: hidden; }
      .ptcgdm-orders__table th, .ptcgdm-orders__table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #1f2533; color: #cfd6e6; }
      .ptcgdm-orders__table th { background: #111725; font-weight: 700; }
      .ptcgdm-orders__table tr:last-child td { border-bottom: none; }
      .ptcgdm-orders__empty { padding: 12px; background: #0f1218; border: 1px solid #1f2533; border-radius: 12px; color: #cfd6e6; }
      @media (max-width: 900px) {
        .ptcgdm-admin-ui__shell { grid-template-columns: 1fr; }
        .ptcgdm-admin-ui__sidebar { position: static; }
        .ptcgdm-admin-ui__tab { display: inline-block; width: auto; margin-right: 8px; }
      }
    </style>

    <div class="ptcgdm-admin-ui__shell">
      <aside class="ptcgdm-admin-ui__sidebar" aria-label="Inventory navigation">
        <?php $first = true; foreach ($sections as $slug => $section) : ?>
          <button type="button" class="ptcgdm-admin-ui__tab<?php echo $first ? ' is-active' : ''; ?>" data-panel="<?php echo esc_attr($slug); ?>">
            <?php echo esc_html($section['label']); ?>
          </button>
        <?php $first = false; endforeach; ?>
      </aside>

      <main class="ptcgdm-admin-ui__content">
        <?php $first = true; foreach ($sections as $slug => $section) : ?>
          <div id="ptcgdm-panel-<?php echo esc_attr($slug); ?>" class="ptcgdm-admin-ui__panel<?php echo $first ? ' is-active' : ''; ?>">
            <?php echo $section['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          </div>
        <?php $first = false; endforeach; ?>
      </main>
    </div>

    <script>
      (function() {
        const wrapper = document.currentScript.closest('.ptcgdm-admin-ui');
        if (!wrapper) return;

        const tabs = Array.from(wrapper.querySelectorAll('.ptcgdm-admin-ui__tab'));
        const panels = Array.from(wrapper.querySelectorAll('.ptcgdm-admin-ui__panel'));

        const activate = (slug) => {
          tabs.forEach((btn) => {
            const isActive = btn.dataset.panel === slug;
            btn.classList.toggle('is-active', isActive);
          });

          panels.forEach((panel) => {
            const isActive = panel.id === 'ptcgdm-panel-' + slug;
            panel.classList.toggle('is-active', isActive);
          });
        };

        tabs.forEach((btn) => {
          btn.addEventListener('click', () => {
            const slug = btn.dataset.panel;
            if (!slug) return;
            activate(slug);
          });
        });
      })();
    </script>
  </div>
  <?php

  return ob_get_clean();
}

/**
 * Echo the inventory UI within a dedicated wrapper for the front-end page.
 */
function ptcgdm_render_admin_ui_page() {
  echo ptcgdm_get_admin_ui_content();
}

/**
 * Shortcode handler to embed the admin UI on a standard WordPress page.
 *
 * @return string
 */
function ptcgdm_render_admin_ui_shortcode() {
  ob_start();
  ptcgdm_render_admin_ui_page();
  return ob_get_clean();
}

/**
 * Register the shortcode for the Admin UI page.
 */
function ptcgdm_register_admin_ui_shortcode() {
  add_shortcode('ptcg_admin_ui', 'ptcgdm_render_admin_ui_shortcode');
}
add_action('init', 'ptcgdm_register_admin_ui_shortcode');

/**
 * Ensure a public page exists that renders the Admin UI via shortcode.
 */
function ptcgdm_ensure_admin_ui_page_exists() {
  $stored_id = (int) get_option('ptcgdm_admin_ui_page_id', 0);

  if ($stored_id > 0) {
    $existing_page = get_post($stored_id);
    if ($existing_page instanceof WP_Post && $existing_page->post_status !== 'trash') {
      return;
    }
  }

  $page = get_page_by_path('ptcg-admin-ui');
  if ($page instanceof WP_Post && $page->post_status !== 'trash') {
    update_option('ptcgdm_admin_ui_page_id', $page->ID);
    return;
  }

  $page_id = wp_insert_post([
    'post_title'   => 'Admin UI',
    'post_name'    => 'ptcg-admin-ui',
    'post_status'  => 'publish',
    'post_type'    => 'page',
    'post_content' => '[ptcg_admin_ui]',
  ], true);

  if (!is_wp_error($page_id) && $page_id > 0) {
    update_option('ptcgdm_admin_ui_page_id', $page_id);
  }
}

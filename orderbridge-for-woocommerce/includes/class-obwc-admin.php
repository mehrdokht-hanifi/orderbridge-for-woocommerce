<?php
defined( 'ABSPATH' ) || exit;

final class OBWC_Admin {
	private $client; private $sync; private $logger;
	public function __construct( OBWC_Client $client, OBWC_Sync $sync, OBWC_Logger $logger ) {
		$this->client=$client; $this->sync=$sync; $this->logger=$logger;
		add_action( 'admin_menu', array( $this, 'menu' ) ); add_action( 'admin_init', array( $this, 'settings' ) );
		add_action( 'wp_ajax_obwc_test_connection', array( $this, 'test_connection' ) );
		add_action( 'add_meta_boxes', array( $this, 'meta_box' ) );
	}
	public function menu() { add_submenu_page( 'woocommerce', 'OrderBridge', 'OrderBridge', 'manage_woocommerce', 'orderbridge', array( $this, 'page' ) ); }
	public function settings() {
		register_setting( 'obwc', 'obwc_api_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
		register_setting( 'obwc', 'obwc_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'obwc', 'obwc_webhook_secret', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'obwc', 'obwc_log_retention', array( 'sanitize_callback' => 'absint', 'default' => 30 ) );
	}
	public function page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
		$logs=$this->logger->recent(); ?>
		<div class="wrap"><h1>OrderBridge for WooCommerce</h1><p>Reliable order synchronization with signed webhooks and automatic retries.</p>
		<form method="post" action="options.php"><?php settings_fields( 'obwc' ); ?><table class="form-table">
		<tr><th><label for="obwc_api_url">ERP API URL</label></th><td><input class="regular-text" type="url" id="obwc_api_url" name="obwc_api_url" value="<?php echo esc_attr( get_option('obwc_api_url') ); ?>" placeholder="http://localhost:8787/api/" /></td></tr>
		<tr><th><label for="obwc_api_key">API key</label></th><td><input class="regular-text" type="password" id="obwc_api_key" name="obwc_api_key" value="<?php echo esc_attr( get_option('obwc_api_key') ); ?>" autocomplete="new-password" /></td></tr>
		<tr><th><label for="obwc_webhook_secret">Webhook secret</label></th><td><input class="regular-text" type="password" id="obwc_webhook_secret" name="obwc_webhook_secret" value="<?php echo esc_attr( get_option('obwc_webhook_secret') ); ?>" autocomplete="new-password" /></td></tr>
		<tr><th><label for="obwc_log_retention">Log retention</label></th><td><input type="number" min="7" max="365" id="obwc_log_retention" name="obwc_log_retention" value="<?php echo esc_attr( get_option('obwc_log_retention',30) ); ?>" /> days</td></tr>
		</table><?php submit_button(); ?></form>
		<p><button class="button" id="obwc-test">Test connection</button> <span id="obwc-result"></span></p>
		<script>document.getElementById('obwc-test').onclick=async()=>{const r=document.getElementById('obwc-result');r.textContent='Testing…';const b=new URLSearchParams({action:'obwc_test_connection',_ajax_nonce:'<?php echo esc_js(wp_create_nonce('obwc_test')); ?>'});const x=await fetch(ajaxurl,{method:'POST',body:b});const j=await x.json();r.textContent=j.data.message;};</script>
		<h2>Recent synchronization activity</h2><table class="widefat striped"><thead><tr><th>Time (UTC)</th><th>Order</th><th>Direction</th><th>Event</th><th>Status</th><th>HTTP</th><th>Attempt</th><th>Message</th></tr></thead><tbody>
		<?php foreach($logs as $log): ?><tr><td><?php echo esc_html($log->created_at); ?></td><td><?php echo esc_html($log->order_id); ?></td><td><?php echo esc_html($log->direction); ?></td><td><?php echo esc_html($log->event); ?></td><td><?php echo esc_html($log->status); ?></td><td><?php echo esc_html($log->http_code); ?></td><td><?php echo esc_html($log->attempt); ?></td><td><?php echo esc_html($log->message); ?></td></tr><?php endforeach; ?>
		</tbody></table></div><?php
	}
	public function test_connection() {
		check_ajax_referer( 'obwc_test' ); if(!current_user_can('manage_woocommerce')){wp_send_json_error(array('message'=>'Permission denied.'),403);}
		$response=$this->client->test(); if(is_wp_error($response)){wp_send_json_error(array('message'=>$response->get_error_message()));}
		$code=wp_remote_retrieve_response_code($response); $ok=$code>=200&&$code<300;
		if ( $ok ) { wp_send_json_success( array( 'message' => 'Connection successful.' ) ); }
		wp_send_json_error( array( 'message' => 'Connection failed (HTTP ' . $code . ').' ) );
	}
	public function meta_box() { add_meta_box('obwc-order','OrderBridge','OBWC_Admin::render_meta_box',array('shop_order','woocommerce_page_wc-orders'),'side'); }
	public static function render_meta_box($post_or_order){ $order=$post_or_order instanceof WC_Order?$post_or_order:wc_get_order($post_or_order->ID); if(!$order)return; echo '<p><strong>Status:</strong> '.esc_html($order->get_meta('_obwc_sync_status')?:'Not synchronized').'</p><p><strong>Last sync:</strong> '.esc_html($order->get_meta('_obwc_last_synced_at')?:'—').'</p><p><strong>ERP ID:</strong> '.esc_html($order->get_meta('_obwc_remote_id')?:'—').'</p>'; }
}

<?php
/**
 * Plugin Name: Xilu Woocommerce Checkout TW Fields
 * Plugin URI: https://github.com/alazy21/xilu-woo-tw-fields
 * Description: 調整 Woocommerce 結帳頁面欄位符合台灣使用習慣。可搭配 WC_Address_Book 使用
 * Author: Stan Hsiao
 * Version: 1.1.1
 * Author URI: http://stanhsiao.tw/
 * Text Domain: xilu-woo-tw-fields
 * Domain Path: /languages
 * 
 * 刪除 last_name, company, address_2 三個欄位
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
	deactivate_plugins( plugin_basename( __FILE__ ) );

	/**
	 * Deactivate the plugin if WooCommerce is not active.
	 *
	 * @since    1.0.0
	 */
	function wc_address_book_woocommerce_notice_error() {
		$class   = 'notice notice-error';
		$message = 'WooCommerce 未啟用；外掛已停用。';

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_attr( $message ) );
	}
	add_action( 'admin_notices', 'wc_address_book_woocommerce_notice_error' );
	add_action( 'network_admin_notices', 'wc_address_book_woocommerce_notice_error' );

} else {
	require plugin_dir_path( __FILE__ ) . 'includes/class-xilu_woo_tw_fields.php';
	// Init Class.
	Xilu_woo_tw_fields::get_instance();
}
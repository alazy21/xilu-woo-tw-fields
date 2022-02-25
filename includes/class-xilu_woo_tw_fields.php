<?php

class Xilu_woo_tw_fields {

    protected static $instance = null;

    public $row_hide = 'xilu-form-row-none';

    public function __construct() {
        if( $this->woocommerce_version_check() ) {
            $this->_xilu_hooks();
        }
    }

    public static function get_instance() {	
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

    private function _xilu_hooks() {
        
        add_action( 'wp_enqueue_scripts', [$this, 'xilu_script'], 50);

        add_filter( 'woocommerce_default_address_fields', array($this, 'default_address_fields'), 10, 1); // 預設欄位刪除調整
        add_filter( 'woocommerce_billing_fields', array($this, 'billing_fields'), 10, 1); // 帳單表單
        add_filter( 'woocommerce_shipping_fields', array($this, 'shipping_fields'), 10, 1);// 送貨表單
        add_filter( 'woocommerce_admin_shipping_fields', array($this, 'admin_shipping_fields'), 10, 1); // admin 送貨表單
        add_filter( 'woocommerce_order_get_formatted_shipping_address', array( $this, 'show_shipping_phone'), 10, 3); // 送貨地址加上收件人電話
        add_filter( 'woocommerce_localisation_address_formats', array( $this, 'address_formats' ), -15, 1 ); // 修改 TW 地址格式
        
        add_filter( 'wc_address_book_address_select_label', array( $this, 'wc_address_book_option_formats'), -10, 3 );
        add_action( 'wp_ajax_xilu_get_postcode', array( $this, 'xilu_get_postcode' ) );
    }

    function wc_address_book_option_formats(  $label, $address, $name ) {
        
        $label = '';
		$address_nickname = get_user_meta( get_current_user_id(), $name . '_address_nickname', true );
		if ( $address_nickname ) {
			$label .= '['.$address_nickname . '] ';
		}

        if ( ! empty( $address[ $name . '_first_name' ] ) ) {
			$label .= $address[ $name . '_first_name' ].' ';
		}

		if ( ! empty( $address[ $name . '_state' ] ) ) {
			$label .= $address[ $name . '_state' ];
		}

        if ( ! empty( $address[ $name . '_city' ] ) ) {
			$label .= $address[ $name . '_city' ];
		}

        return $label;
    }


    // css & script 檔案
    public function xilu_script() {
        if ( is_checkout() || is_account_page() ):
            wp_enqueue_style( 'xiluTWfields', plugin_dir_url( __DIR__ ).'css/xilu-woo-tw-fields.min.css', array() );
            wp_enqueue_script( 'twzipcode', plugin_dir_url( __DIR__ ).'js/jquery.twzipcode.min.js', array(), false, true );
            wp_enqueue_script( 'xiluTWfields', plugin_dir_url( __DIR__ ).'js/xilu_woo_tw_fields.js', array(), false, true );
        endif;

        if( is_checkout() ) {
            wp_localize_script(
                'jquery',
                'xilu_ajax_script',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'get_postcode' => wp_create_nonce( 'xilu-get-postcode' )
                )
            );
        }
    }

    // 檢查 woocommerce 版本
    public function woocommerce_version_check( $version = '3.3' ) {
        if ( class_exists( 'WooCommerce' ) ) {
            global $woocommerce;
            if ( version_compare( $woocommerce->version, $version, ">=" )) {
                return true;
            }
        }
        return false;
    }

    // 檢查 WC_Address_Book 是否存在
    public function wc_address_book_check() {
        if( class_exists( 'WC_Address_Book' ) ) {
            return true;
        }
        return false;
    }

    // 預設欄位刪除調整
    public function default_address_fields( $fields ) {
        unset( $fields[ 'last_name' ] );
        unset( $fields[ 'company' ] );
        unset( $fields[ 'address_2' ] );
        return $fields;
    }

    // 帳單表單
    public function billing_fields( $billing_fields ) {

        array_push($billing_fields['billing_country']['class'], $this->row_hide);
        array_push($billing_fields['billing_postcode']['class'], $this->row_hide);
        array_push($billing_fields['billing_state']['class'], $this->row_hide);
        array_push($billing_fields['billing_city']['class'], $this->row_hide);

        return $billing_fields;
    }

    // 送貨表單
    public function shipping_fields( $shipping_fields ) {

        // 新增送貨收件人電話欄位
        $shipping_fields['shipping_phone'] = array(
            'label' => '收件人電話',
            'placeholder' => '可聯絡手機電話',
            'type' => 'text',
            'required' => true,
            'priority' => 55
        );

        array_push($shipping_fields['shipping_country']['class'], $this->row_hide);
        array_push($shipping_fields['shipping_postcode']['class'], $this->row_hide);
        array_push($shipping_fields['shipping_state']['class'], $this->row_hide);
        array_push($shipping_fields['shipping_city']['class'], $this->row_hide);
       
        return $shipping_fields;
    }

    // admin 送貨表單
    public function admin_shipping_fields( $shipping_fields ) {

        $shipping_fields['phone'] = array(
            'label' => '聯絡電話',
            'type' => 'text'
        );
        
        return $shipping_fields;
    }

    // 送貨地址加上收件人電話
    function show_shipping_phone( $address, $raw_address, $order ) {
        if( is_admin() ) return $address;
        $phone = get_post_meta( $order->get_id(), '_shipping_phone', true );
        return $phone ? $address.'<br>'.$phone : $address;
    }

    // 修改地址格式
    public function address_formats( $formats ) {
        
        foreach ( $formats as $iso_code => $format ) {
            $formats[ $iso_code ] = "{postcode} {state}{city}{address_1}\n{first_name}";
        }
        return $formats;
    }


    // ajax 返回郵遞區號
    public function xilu_get_postcode() {
        check_ajax_referer( 'xilu-get-postcode', 'nonce' );

        if ( ! isset( $_REQUEST ['shipping'] ) ) {
			die( 'no id passed' );
		}

        $shipping = $_REQUEST['shipping'];
        $customer_id   = get_current_user_id();
        $post_id = get_user_meta( $customer_id,  $shipping.'_postcode', true );
        echo  $post_id;
        die();
    }

}

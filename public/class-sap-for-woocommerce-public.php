<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://fuji-9.com/
 * @since      1.0.0
 *
 * @package    Sap_For_Woocommerce
 * @subpackage Sap_For_Woocommerce/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Sap_For_Woocommerce
 * @subpackage Sap_For_Woocommerce/public
 * @author     Fuji 9 <info@fuji-9.com>
 */
class Sap_For_Woocommerce_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;


	private $enrolled_label;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->enrolled_label = Sap_For_Woocommerce_Settings::get_option('sapfw_basic_enrolled_label', 'sapfw_basic');
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/sap-for-woocommerce-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/sap-for-woocommerce-public.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Overrides the link for add to cart, on loop
	 *
	 * @since    1.0.0
	 * @param    string   $link       Original link
	 * @param    object   $product    Product
	 * @param    array    $args       Array of arguments to pass
	 * @return   string   URL
	 */
	public function sap_woocommerce_loop_add_to_cart_link($link, $product, $args) {

		//We check status of the product for the user
    	$sap_check_pap = $this->sap_check_purchase_and_subscription($product->get_id());

    	//We override default button for adding to cart
    	if ($sap_check_pap != '') {
    		$link = sprintf( '<a href="%s" data-quantity="%s" class="%s" %s>%s</a>',
				'javascript: void(0)',
				'0',
				'button',
				isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
				esc_html( $product->add_to_cart_text() )
			);
    		return $link;
    	}

		$link = sprintf( '<a href="%s" data-quantity="%s" class="%s" %s>%s</a>',
			esc_url( $product->add_to_cart_url() ),
			esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
			esc_attr( isset( $args['class'] ) ? $args['class'] : 'button' ),
			isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
			esc_html( $product->add_to_cart_text() )
		);
		return $link;
	}


	/**
	 * Overrides the add to cart button text
	 *
	 * @since    1.0.0
	 * @param    string   $text       Original text
	 * @return   string   Text
	 */
	public function sap_woocommerce_product_add_to_cart_text($text) {
		global $product;

		//We check status of the product for the user
    	$sap_check_pap = $this->sap_check_purchase_and_subscription($product->get_id());

    	if ($sap_check_pap != '') {
    		//Check if text not empty
			if ( $this->enrolled_label != '') {
				return $this->enrolled_label;
			}
    		return $text;
    	}
    	return $text;
	}

	/**
	 * Show product as not purchsable
	 *
	 * @since    1.0.0
	 * @param    boolean  $boolean    Is purchasable
	 * @param    object   $product    Product
	 * @return   boolean  Is purchasable
	 */
	public function sap_woocommerce_is_purchasable($boolean, $product) {

		if (is_product() or is_shop() or is_product_taxonomy() or is_product_category()) {
			//We check status of the product for the user
			$sap_check_pap = $this->sap_check_purchase_and_subscription($product->get_id());

			if ($sap_check_pap != '' and !is_wc_endpoint_url( 'order-received' )) {
	    		return false;
	    	}
    	}

    	return $boolean;
	}


	/**
	 * Custom action to show inactive button
	 *
	 * @since    1.0.0
	 * @return   boolean  Is purchasable
	 */
	public function sap_woocommerce_simple_add_to_cart() {
		global $product;
		$sap_check_pap = $this->sap_check_purchase_and_subscription($product->get_id());
		$text = $product->add_to_cart_text();

		//Check if text not empty
		if ( $this->enrolled_label != '') {
			$text = $this->enrolled_label;
		}

    	if ($sap_check_pap != '') {
    		echo '<button type="submit" name="add-to-cart" value="55" class="button alt">' . $text . '</button>';;
    	}
	}


	/**
	 * Checks if product is purchased and have active subscription, for the current user or non logged one
	 *
	 * @since    1.0.0
	 * @param    int  $product_id    ID of the product
	 * @return   boolean  Result
	 */
	private function sap_check_purchase_and_subscription($product_id) {

		$result = false;

		//If user is logged-in
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			//We check if user has purchased product
			if (function_exists('wc_customer_bought_product')) {
				$has_purchased = wc_customer_bought_product($current_user->user_email, $current_user->ID, $product_id);

				if ($has_purchased) {
					//We check then if user has active subscription
					if (function_exists('wcs_user_has_subscription')) {
						$has_sub = wcs_user_has_subscription( '', $product_id, 'active' );
						if ($has_sub) {
							$result = true;
						}
					}
				}
			}
		} else {
			//If user is not logged-in, we always return false
			$result = false;
		}

		return $result;

	}

}

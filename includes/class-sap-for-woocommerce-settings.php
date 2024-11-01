<?php
/**
 * Settings class
 *
 * @link       https://fuji-9.com/
 * @since      1.0.0
 *
 * @package    Sap_For_Woocommerce
 * @subpackage Sap_For_Woocommerce/includes
 */

/**
 * Settings class
 *
 * @since      1.0.0
 * @package    Sap_For_Woocommerce
 * @subpackage Sap_For_Woocommerce/includes
 * @author     Fuji 9 <info@fuji-9.com>
 */


class Sap_For_Woocommerce_Settings {

    private $settings_api;

    public function __construct() {
        $this->settings_api = new WeDevs_Settings_API;

        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    public function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    public function admin_menu() {
        add_options_page( 'Show Active Purchases for WooCommerce', 'Show Active Purchases for WooCommerce', 'manage_options', 'sap-for-woocommerce-admin', array($this, 'plugin_page') );
    }

    private function get_settings_sections() {
        $sections = array(
            array(
                'id' => 'sapfw_basic',
                'title' => __( 'Basic Settings', 'sap-for-woocommerce' )
            )
        );
        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    private function get_settings_fields() {
        $settings_fields = array(
            'sapfw_basic' => array(
                array(
                    'name'              => 'sapfw_basic_enrolled_label',
                    'label'             => __( 'Button text', 'sap-for-woocommerce' ),
                    'desc'              => __( 'Text for buy button when user is already enrolled.<br>Leave
                        empty to restore default WooCommerce one.', 'sap-for-woocommerce' ),
                    'type'              => 'text',
                    'default'           => '',
                    'sanitize_callback' => 'strval'
                )
            )
        );

        return $settings_fields;
    }

    public function plugin_page() {
        echo '<div class="wrap">';

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }

    public static function get_option( $option, $section, $default = '' ) {

        $options = get_option( $section );

        if ( isset( $options[$option] ) and $options[$option] != '') {
            return $options[$option];
        }

        return $default;
    }

}


$sapfw_basic_settings = new Sap_For_Woocommerce_Settings();
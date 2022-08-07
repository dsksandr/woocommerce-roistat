<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

final class WC_RS_Integration_Settings_Page extends WC_Integration {

    /**
     * Init and hook in the integration.
     */
    public function __construct() {
        $this->id                 = 'woocommerce-roistat';
        $this->method_title       = __( 'Roistat integration', 'woocommerce-roistat' );
        $this->method_description = null; //__( '', 'woocommerce-roistat' );

        $this->init_form_fields();
        $this->init_settings();

        add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * Initialize integration settings form fields.
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'project_id' => array(
                'title'       => __( 'Project Id', 'woocommerce-roistat' ),
                'type'        => 'text',
                'description' => null, // __( '', 'woocommerce-roistat' ),
                'desc_tip'    => true,
                'default'     => '',
            ),
        );
    }
}
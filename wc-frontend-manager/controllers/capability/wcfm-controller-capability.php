<?php










class WCFM_Capability_Controller {

	public function __construct() {
		global $WCFM;

		$this->processing();
	}

	public function processing() {
		global $WCFM, $wpdb, $_POST;

		$wcfm_capability_form_data = array();
		parse_str( $_POST['wcfm_capability_form'], $wcfm_capability_form );

		 
		if ( isset( $wcfm_capability_form['wcfm_capability_options'] ) ) {
			update_option( 'wcfm_capability_options', $wcfm_capability_form['wcfm_capability_options'] );
		} else {
			update_option( 'wcfm_capability_options', array() );
		}

		if ( wcfm_is_marketplace() ) {
			$WCFM->wcfm_vendor_support->vendors_capability_option_updates();
		}

		do_action( 'wcfm_capability_update', $wcfm_capability_form );

		echo '{"status": true, "message": "' . esc_html( __( 'Capability saved successfully', 'wc-frontend-manager' ) ) . '"}';

		die;
	}
}

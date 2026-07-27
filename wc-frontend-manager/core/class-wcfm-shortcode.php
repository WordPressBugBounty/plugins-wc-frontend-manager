<?php










class WCFM_Shortcode {

	public $list_product;

	public function __construct() {
		 
		add_shortcode( 'wc_frontend_manager', array( &$this, 'wc_frontend_manager' ) );

		 
		add_shortcode( 'wcfm', array( &$this, 'wcfm_endpoint_shortcode' ) );

		 
		add_shortcode( 'wcfm_notifications', array( &$this, 'wcfm_notifications_shortcode' ) );

		 
		add_shortcode( 'wcfm_enquiry', array( &$this, 'wcfm_enquiry_shortcode' ) );
		add_shortcode( 'wcfm_inquiry', array( &$this, 'wcfm_enquiry_shortcode' ) );

		 
		add_shortcode( 'wcfm_policy', array( &$this, 'wcfm_policy_shortcode' ) );

		 
		if ( WCFM_Dependencies::wcfmu_plugin_active_check() ) {
			add_shortcode( 'wcfm_follow', array( &$this, 'wcfm_follow_shortcode' ) );
		}
	}

	public function wc_frontend_manager( $attr ) {
		global $WCFM;
		$WCFM->nocache();

		wc_nocache_headers();

		$this->load_class( 'wc-frontend-manager' );
		return $this->shortcode_wrapper( array( 'WCFM_Frontend_Manager_Shortcode', 'output' ), $attr );
	}

	


	public function wcfm_endpoint_shortcode( $attr ) {
		global $WCFM, $wp, $WCFM_Query;
		$this->load_class( 'endpoint' );
		return $this->shortcode_wrapper( array( 'WCFM_Endpoint_Shortcode', 'output' ), $attr );
	}

	


	public function wcfm_notifications_shortcode( $attr ) {
		global $WCFM, $wp, $WCFM_Query;
		$this->load_class( 'notification' );
		return $this->shortcode_wrapper( array( 'WCFM_Notification_Shortcode', 'output' ), $attr );
	}

	


	function wcfm_enquiry_shortcode( $attr ) {
		global $WCFM;

		 

		$this->load_class( 'enquiry' );
		return $this->shortcode_wrapper( array( 'WCFM_Enquiry_Shortcode', 'output' ), $attr );
	}

	


	function wcfm_policy_shortcode( $attr ) {
		global $WCFM;

		 

		$this->load_class( 'policy' );
		return $this->shortcode_wrapper( array( 'WCFM_Policy_Shortcode', 'output' ), $attr );
	}

	


	function wcfm_follow_shortcode( $attr ) {
		global $WCFM;
		$this->load_class( 'follow' );
		return $this->shortcode_wrapper( array( 'WCFM_Follow_Shortcode', 'output' ), $attr );
	}

	



	







	public function shortcode_wrapper( $function, $atts = array() ) {
		ob_start();
		call_user_func( $function, $atts );
		return ob_get_clean();
	}

	






	public function load_class( $class_name = '' ) {
		global $WCFM;
		if ( '' != $class_name && '' != $WCFM->token ) {
			require_once $WCFM->plugin_path . 'includes/shortcodes/class-' . esc_attr( $WCFM->token ) . '-shortcode-' . esc_attr( $class_name ) . '.php';
		}
	}
}

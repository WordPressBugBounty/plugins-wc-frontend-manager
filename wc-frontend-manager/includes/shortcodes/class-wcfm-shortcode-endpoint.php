<?php










class WCFM_Endpoint_Shortcode {

	public function __construct() {
	}

	






	public static function output( $attr ) {
		global $WCFM, $wp, $WCFM_Query;
		$WCFM->nocache();

		echo '<div id="wcfm-main-contentainer"> <div id="wcfm-content">';

		$menu = true;
		if ( isset( $attr['menu'] ) && ! empty( $attr['menu'] ) && ( 'false' == $attr['menu'] ) ) {
			$menu = false; }

		if ( ! isset( $attr['endpoint'] ) || ( isset( $attr['endpoint'] ) && empty( $attr['endpoint'] ) ) ) {

			 
			$WCFM->library->load_scripts( 'wcfm-dashboard' );

			 
			$WCFM->library->load_styles( 'wcfm-dashboard' );

			 
			$WCFM->library->load_views( 'wcfm-dashboard', $menu );
		} else {
			$wcfm_endpoints = $WCFM_Query->get_query_vars();

			foreach ( $wcfm_endpoints as $key => $value ) {
				if ( isset( $attr['endpoint'] ) && ! empty( $attr['endpoint'] ) && ( $key == $attr['endpoint'] ) ) {
					 
					$WCFM->library->load_scripts( $key );

					 
					$WCFM->library->load_styles( $key );

					 
					$WCFM->library->load_views( $key, $menu );
				}
			}
		}

		echo '</div></div>';
	}
}

<?php
/**
 * WCFM plugin core
 *
 * Enquiry board core
 *
 * @author 		WC Lovers
 * @package 	wcfm/core
 * @version   3.2.8
 */
 
class WCFM_Enquiry {
	
	public $wcfm_myaccount_inquiry_endpoint = 'inquiry';
	public $wcfm_myaccount_view_inquiry_endpoint = 'view-inquiry';

	public function __construct() {
		global $WCFM;
		
		$wcfm_myac_modified_endpoints = wcfm_get_option( 'wcfm_myac_endpoints', array() );
		$this->wcfm_myaccount_inquiry_endpoint = ! empty( $wcfm_myac_modified_endpoints['inquiry'] ) ? $wcfm_myac_modified_endpoints['inquiry'] : 'inquiry';
		$this->wcfm_myaccount_view_inquiry_endpoint = ! empty( $wcfm_myac_modified_endpoints['view-inquiry'] ) ? $wcfm_myac_modified_endpoints['view-inquiry'] : 'view-inquiry';
		
		add_filter( 'wcfm_query_vars', array( &$this, 'wcfm_enquiry_query_vars' ), 20 );
		add_filter( 'wcfm_endpoint_title', array( &$this, 'wcfm_enquiry_endpoint_title' ), 20, 2 );
		add_action( 'init', array( &$this, 'wcfm_enquiry_init' ), 20 );
		
		// Enquiry Endpoint Edit
		add_filter( 'wcfm_endpoints_slug', array( $this, 'enquiry_wcfm_endpoints_slug' ) );
		
		// Enquiry Load Scripts
		add_action( 'wcfm_load_scripts', array( &$this, 'load_scripts' ), 30 );
		add_action( 'after_wcfm_load_scripts', array( &$this, 'load_scripts' ), 30 );
		
		// Enquiry Load Styles
		add_action( 'wcfm_load_styles', array( &$this, 'load_styles' ), 30 );
		add_action( 'after_wcfm_load_styles', array( &$this, 'load_styles' ), 30 );
		
		// Enquiry Load views
		add_action( 'wcfm_load_views', array( &$this, 'load_views' ), 30 );
		
		// Enquiry From Content
		add_action( 'wp_ajax_wcfm_enquiry_form_content', array( &$this, 'wcfm_enquiry_form_content' ) );
		add_action( 'wp_ajax_nopriv_wcfm_enquiry_form_content', array( &$this, 'wcfm_enquiry_form_content' ) );
		
		// Enquiry Ajax Controllers
		add_action( 'after_wcfm_ajax_controller', array( &$this, 'ajax_controller' ) );
		add_action( 'wp_ajax_nopriv_wcfm_ajax_controller', array( &$this, 'ajax_controller' ) );
		
		// My Account Support End Point
		add_action( 'init', array( &$this, 'wcfm_enquiry_my_account_endpoints' ) );
		
		// My Account Support Query Vars
		add_filter( 'query_vars', array( &$this, 'wcfm_enquiry_my_account_query_vars' ), 0 );
		
		// My Account Support Rule Flush
		register_activation_hook( $WCFM->file, array( &$this,'wcfm_enquiry_my_account_flush_rewrite_rules' ) );
		register_deactivation_hook( $WCFM->file, array( &$this, 'wcfm_enquiry_my_account_flush_rewrite_rules' ) );
		
		// My Account Support Menu
		add_filter( 'woocommerce_account_menu_items', array( &$this, 'wcfm_enquiry_my_account_menu_items' ), 200 );
		
		// My Account Support End Point Title
		add_filter( 'the_title', array( &$this, 'wcfm_enquiry_my_account_endpoint_title' ) );
		
		// My Account Support End Point Content
		add_action( 'woocommerce_account_'.$this->wcfm_myaccount_inquiry_endpoint.'_endpoint', array( &$this, 'wcfm_enquiry_my_account_endpoint_content' ) );
		add_action( 'woocommerce_account_'.$this->wcfm_myaccount_view_inquiry_endpoint.'_endpoint', array( &$this, 'wcfm_enquiry_view_my_account_endpoint_content' ) );
		
		// Delete Enquiry
		add_action( 'wp_ajax_delete_wcfm_enquiry', array( &$this, 'delete_wcfm_enquiry' ) );
		
		// Delete Enquiry Response
		add_action( 'wp_ajax_delete_wcfm_enquiry_response', array( &$this, 'delete_wcfm_enquiry_response' ) );
		
		// Bulk Delete Enquiry
		add_action( 'wp_ajax_wcfm_enquiry_bulk_delete', array( &$this, 'wcfm_enquiry_bulk_delete' ) );
		
		
		// Enquiry tab on Single Product
		if( apply_filters( 'wcfm_is_pref_enquiry_tab', true ) ) {
			//add_filter( 'woocommerce_product_tabs', array( &$this, 'wcfm_enquiry_product_tab' ) );
		}
		
		// Single Product page enquiry button
		$wcfm_enquiry_button_position  = isset( $WCFM->wcfm_options['wcfm_enquiry_button_position'] ) ? $WCFM->wcfm_options['wcfm_enquiry_button_position'] : 'bellow_atc';
		if( $wcfm_enquiry_button_position == 'bellow_price' ) {
			add_action( 'woocommerce_single_product_summary',	array( &$this, 'wcfm_enquiry_button' ), 15 );
		} elseif( $wcfm_enquiry_button_position == 'bellow_sc' ) {
			add_action( 'woocommerce_single_product_summary',	array( &$this, 'wcfm_enquiry_button' ), 25 );
		} else {
			add_action( 'woocommerce_single_product_summary',	array( &$this, 'wcfm_enquiry_button' ), 35 );
		}
		
		// YiTh Quick View Inquiry Button
		//add_action( 'yith_wcqv_product_summary',	array( &$this, 'wcfm_enquiry_button' ), 35 );
		
		// WCFM Marketplace Store enquiry button
		add_action( 'wcfmmp_store_enquiry',	array( &$this, 'wcfmmp_store_enquiry_button' ), 35 );
		
		// WCFM Marketplace Store List enquiry button
		add_action( 'wcfmmp_store_list_after_store_info',	array( &$this, 'wcfmmp_store_list_enquiry_button' ), 35 );
		
		// Enquiry list in WCFM Dashboard
		add_action( 'after_wcfm_dashboard_zone_analytics', array( $this, 'wcfm_dashboard_enquiry_list' ) );
		
		// Enquiry direct message type
		add_filter( 'wcfm_message_types', array( &$this, 'wcfm_enquiry_message_types' ), 25 );
		
		// Inquiry Login Require
		add_filter( 'wcfm_is_allow_enquiry_with_login', array( &$this, 'wcfm_enquiry_with_login' ), 25 );
		
		// Enqueue scripts
		add_action('wp_enqueue_scripts', array(&$this, 'wcfm_enquiry_scripts'));
		
		// Enqueue styles
		add_action('wp_enqueue_scripts', array(&$this, 'wcfm_enquiry_styles'));
	}
	
	/**
   * Enquiry Query Var
   */
  function wcfm_enquiry_query_vars( $query_vars ) {
  	$wcfm_modified_endpoints = wcfm_get_option( 'wcfm_endpoints', array() );
  	
		$query_enquiry_vars = array(
			'wcfm-enquiry'                 => ! empty( $wcfm_modified_endpoints['wcfm-enquiry'] ) ? $wcfm_modified_endpoints['wcfm-enquiry'] : 'enquiry',
			'wcfm-enquiry-manage'          => ! empty( $wcfm_modified_endpoints['wcfm-enquiry-manage'] ) ? $wcfm_modified_endpoints['wcfm-enquiry-manage'] : 'enquiry-manage'
		);
		
		$query_vars = array_merge( $query_vars, $query_enquiry_vars );
		
		return $query_vars;
  }
  
  /**
   * Enquiry End Point Title
   */
  function wcfm_enquiry_endpoint_title( $title, $endpoint ) {
  	global $wp;
  	switch ( $endpoint ) {
  		case 'wcfm-enquiry' :
				$title = __( 'Enquiry Dashboard', 'wc-frontend-manager' );
			break;
			case 'wcfm-enquiry-manage' :
				$title = __( 'Enquiry Manager', 'wc-frontend-manager' );
			break;
  	}
  	
  	return $title;
  }
  
  /**
   * Enquiry Endpoint Intialize
   */
  function wcfm_enquiry_init() {
  	global $WCFM_Query;
	
		// Intialize WCFM End points
		$WCFM_Query->init_query_vars();
		$WCFM_Query->add_endpoints();
		
		add_rewrite_endpoint( $this->wcfm_myaccount_inquiry_endpoint, EP_ROOT | EP_PAGES );
		add_rewrite_endpoint( $this->wcfm_myaccount_view_inquiry_endpoint, EP_ROOT | EP_PAGES );
		
		if( !get_option( 'wcfm_updated_end_point_Enquiry' ) ) {
			// Flush rules after endpoint update
			flush_rewrite_rules();
			update_option( 'wcfm_updated_end_point_Enquiry', 1 );
		}
  }
  
  /**
	 * Enquiry Endpoiint Edit
	 */
	function enquiry_wcfm_endpoints_slug( $endpoints ) {
		
		$enquiry_endpoints = array(
													'wcfm-enquiry'          => 'enquiry',
													'wcfm-enquiry-manage'   => 'enquiry-manage',
													);
		
		$endpoints = array_merge( $endpoints, $enquiry_endpoints );
		
		return $endpoints;
	}
  
  /**
   * Enquiry Scripts
   */
  public function load_scripts( $end_point ) {
	  global $WCFM;
    
	  switch( $end_point ) {
	  	case 'wcfm-enquiry':
      	$WCFM->library->load_datatable_lib();
      	$WCFM->library->load_daterangepicker_lib();
      	$WCFM->library->load_select2_lib();
      	// wp_enqueue_script( 'wcfm_enquiry_js', $WCFM->library->js_lib_url_min . 'enquiry/wcfm-script-enquiry.js', array('jquery'), $WCFM->version, true );
      	wp_enqueue_script( 'wcfm_enquiry_js', $WCFM->library->js_lib_url . 'enquiry/wcfm-script-enquiry.js', array('jquery'), $WCFM->version, true );
      	
      	$wcfm_screen_manager_data = array();
    		if( !$WCFM->is_marketplace || wcfm_is_vendor() ) {
	    		$wcfm_screen_manager_data[3] = 'yes';
	    	}
	    	$wcfm_screen_manager_data[4] = 'yes';
	    	//$wcfm_screen_manager_data[5] = 'yes';
	    	$wcfm_screen_manager_data = apply_filters( 'wcfm_enquiry_screen_manage', $wcfm_screen_manager_data );
	    	$wcfm_screen_manager_data = apply_filters( 'wcfm_screen_manager_data_columns', $wcfm_screen_manager_data, 'inquiry' );
	    	wp_localize_script( 'wcfm_enquiry_js', 'wcfm_enquiry_screen_manage', $wcfm_screen_manager_data );
	    	wp_localize_script( 'wcfm_enquiry_js', 'wcfm_enquiry_auto_refresher', array( 'is_allow' => apply_filters( 'wcfm_enquiry_is_allow_auto_refresher', true ), 'duration' => apply_filters( 'wcfm_enquiry_auto_refresher_duration', 60000 ) ) );
      break;
      
      case 'wcfm-enquiry-manage':
      	$WCFM->library->load_select2_lib();
      	$WCFM->library->load_multiinput_lib();
      	$WCFM->library->load_collapsible_lib();
      	wp_enqueue_script( 'wcfm_enquiry_manage_js', $WCFM->library->js_lib_url_min . 'enquiry/wcfm-script-enquiry-manage.js', array('jquery'), $WCFM->version, true );
      	// Localized Script
        $wcfm_messages = get_wcfm_enquiry_manage_messages();
			  wp_localize_script( 'wcfm_enquiry_manage_js', 'wcfm_enquiry_manage_messages', $wcfm_messages );
      break;
	  }
	}
	
	/**
   * Enquiry Styles
   */
	public function load_styles( $end_point ) {
	  global $WCFM, $WCFMu;
		
	  switch( $end_point ) {
	  	case 'wcfm-enquiry':
		    // wp_enqueue_style( 'wcfm_enquiry_css',  $WCFM->library->css_lib_url_min . 'enquiry/wcfm-style-enquiry.css', array(), $WCFM->version );
		    wp_enqueue_style( 'wcfm_enquiry_css',  $WCFM->library->css_lib_url . 'enquiry/wcfm-style-enquiry.css', array(), $WCFM->version );
		  break;
		  
		  case 'wcfm-enquiry-manage':
		  	wp_enqueue_style( 'collapsible_css',  $WCFM->library->css_lib_url_min . 'wcfm-style-collapsible.css', array(), $WCFM->version );
		  	wp_enqueue_style( 'wcfm_enquiry_manage_css',  $WCFM->library->css_lib_url_min . 'enquiry/wcfm-style-enquiry-manage.css', array(), $WCFM->version );
		  break;
	  }
	}
	
	/**
   * Enquiry Views
   */
  public function load_views( $end_point ) {
	  global $WCFM, $WCFMu;
	  
	  switch( $end_point ) {
	  	case 'wcfm-enquiry':
        $WCFM->template->get_template( 'enquiry/wcfm-view-enquiry.php' );
      break;
      
      case 'wcfm-enquiry-manage':
        $WCFM->template->get_template( 'enquiry/wcfm-view-enquiry-manage.php' );
      break;
	  }
	}
	
	/**
   * Enquiry Ajax Controllers
   */
  public function ajax_controller() {
  	global $WCFM, $WCFMu;
  	
  	if ( ! check_ajax_referer( 'wcfm_ajax_nonce', 'wcfm_ajax_nonce', false ) ) {
  		wp_send_json_error( esc_html__( 'Invalid nonce! Refresh your page and try again.', 'wc-frontend-manager' ) );
  		wp_die();
  	}
  	
  	$controllers_path = $WCFM->plugin_path . 'controllers/enquiry/';
  	
  	$controller = '';
  	if( isset( $_POST['controller'] ) ) {
  		$controller = wc_clean( $_POST['controller'] );
  		
  		switch( $controller ) {
  			case 'wcfm-enquiry':
  				if ( !current_user_can( 'manage_woocommerce' ) && !current_user_can( 'wcfm_vendor' ) && !current_user_can( 'seller' ) && !current_user_can( 'vendor' ) && !current_user_can( 'shop_staff' ) ) {
						wp_send_json_error( esc_html__( 'You don&#8217;t have permission to do this.', 'woocommerce' ) );
						wp_die();
					}
		
					include_once( $controllers_path . 'wcfm-controller-enquiry.php' );
					if( defined('WCFM_REST_API_CALL') ) {
						$enquiry_wcfm_manage_object = new WCFM_Enquiry_Controller();
						return $enquiry_wcfm_manage_object->processing();
          } else {
            new WCFM_Enquiry_Controller();
          }
				break;
				
				case 'wcfm-enquiry-manage':
					if ( !current_user_can( 'manage_woocommerce' ) && !current_user_can( 'wcfm_vendor' ) && !current_user_can( 'seller' ) && !current_user_can( 'vendor' ) && !current_user_can( 'shop_staff' ) ) {
						wp_send_json_error( esc_html__( 'You don&#8217;t have permission to do this.', 'woocommerce' ) );
						wp_die();
					}
		
					include_once( $controllers_path . 'wcfm-controller-enquiry-manage.php' );
					//new WCFM_Enquiry_Manage_Controller();
					if( defined('WCFM_REST_API_CALL') ) {
						$enquiry_wcfm_manage_object = new WCFM_Enquiry_Manage_Controller();
						return $enquiry_wcfm_manage_object->processing();
          } else {
            new WCFM_Enquiry_Manage_Controller();
          }
				break;
				
				case 'wcfm-enquiry-tab':
					include_once( $controllers_path . 'wcfm-controller-enquiry-form.php' );
					new WCFM_Enquiry_Form_Controller();
				break;
				
				case 'wcfm-enquiry-form':
					include_once( $controllers_path . 'wcfm-controller-enquiry-form.php' );
					new WCFM_Enquiry_Form_Controller();
				break;
				
				case 'wcfm-my-account-enquiry-manage':
					include_once( $controllers_path . 'wcfm-controller-enquiry-manage.php' );
					new WCFM_My_Account_Enquiry_Manage_Controller();
				break;
  		}
  	}
  }
  
  
  function wcfm_enquiry_my_account_endpoints() {
		add_rewrite_endpoint( $this->wcfm_myaccount_inquiry_endpoint, EP_ROOT | EP_PAGES );
		add_rewrite_endpoint( $this->wcfm_myaccount_view_inquiry_endpoint, EP_ROOT | EP_PAGES );
	}
	
	function wcfm_enquiry_my_account_query_vars( $vars ) {
		$vars[] = $this->wcfm_myaccount_inquiry_endpoint;
		$vars[] = $this->wcfm_myaccount_view_inquiry_endpoint;
	
		return $vars;
	}
	
	function wcfm_enquiry_my_account_flush_rewrite_rules() {
		add_rewrite_endpoint( $this->wcfm_myaccount_inquiry_endpoint, EP_ROOT | EP_PAGES );
		add_rewrite_endpoint( $this->wcfm_myaccount_view_inquiry_endpoint, EP_ROOT | EP_PAGES );
		flush_rewrite_rules();
	}
	
	function wcfm_enquiry_my_account_menu_items( $items ) {
		// Insert your custom endpoint.
		$items = array_slice($items, 0, count($items) - 2, true) +
																	array(
																				$this->wcfm_myaccount_inquiry_endpoint => __( 'Inquiries', 'wc-frontend-manager' )
																				) +
																	array_slice($items, count($items) - 2, count($items) - 1, true) ;
		return $items;
	}
	
	function wcfm_enquiry_my_account_endpoint_title( $title ) {
		global $wp_query;
	
		if( !defined( 'WCFM_ENQUERY_LOOP') ) {
			$is_endpoint = isset( $wp_query->query_vars[$this->wcfm_myaccount_inquiry_endpoint] );
		
			if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
				// New page title.
				$title = __( 'Inquiries', 'wc-frontend-manager' );
				remove_filter( 'the_title', array( $this, 'wcfm_enquiry_my_account_endpoint_title' ) );
			}
			
			$is_endpoint = isset( $wp_query->query_vars[$this->wcfm_myaccount_view_inquiry_endpoint] );
		
			if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
				// New page title.
				$title = __( 'Inquiry', 'wc-frontend-manager' ) . ' #' . sprintf( '%06u', $wp_query->query_vars[$this->wcfm_myaccount_view_inquiry_endpoint] );
				remove_filter( 'the_title', array( $this, 'wcfm_enquiry_my_account_endpoint_title' ) );
			}
		}
	
		return $title;
	}
	
	function wcfm_enquiry_my_account_endpoint_content() {
		global $WCFM, $wpdb;
		$WCFM->template->get_template( 'enquiry/wcfm-view-my-account-enquiry.php' );
	}
	
	function wcfm_enquiry_view_my_account_endpoint_content() {
		global $_POST, $wp_query, $wp, $WCFM;
		$WCFM->template->get_template( 'enquiry/wcfm-view-my-account-enquiry-manage.php' );
	}
  
  /**
   * Delete Enquiry 
   */
  function delete_wcfm_enquiry() {
  	global $WCFM, $wpdb, $_POST;
  	
  	if ( ! check_ajax_referer( 'wcfm_ajax_nonce', 'wcfm_ajax_nonce', false ) ) {
  		wp_send_json_error( __( 'Invalid nonce! Refresh your page and try again.', 'wc-frontend-manager' ) );
  		wp_die();
  	}
  	
  	if ( !current_user_can( 'manage_woocommerce' ) && !current_user_can( 'wcfm_vendor' ) && !current_user_can( 'seller' ) && !current_user_can( 'vendor' ) && !current_user_can( 'shop_staff' ) ) {
  		wp_send_json_error( esc_html__( 'You don&#8217;t have permission to do this.', 'woocommerce' ) );
			wp_die();
		}
  	
  	if( isset( $_POST['enquiryid'] ) && !empty( $_POST['enquiryid'] ) ) {
  		$enquiryid = absint( $_POST['enquiryid'] );
  		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}wcfm_enquiries WHERE ID = %d", $enquiryid ) );
  		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}wcfm_enquiries_meta WHERE enquiry_id = %d", $enquiryid ) );
  		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}wcfm_enquiries_response WHERE enquiry_id = %d", $enquiryid ) );
  	}
  	
  	echo esc_html("success");
  	die;
  }
  
  /**
   * Delete Enquiry Response
   */
  function delete_wcfm_enquiry_response() {
  	global $WCFM, $wpdb, $_POST;
  	
  	if ( ! check_ajax_referer( 'wcfm_ajax_nonce', 'wcfm_ajax_nonce', false ) ) {
  		wp_send_json_error( __( 'Invalid nonce! Refresh your page and try again.', 'wc-frontend-manager' ) );
  		wp_die();
  	}
  	
  	if ( !current_user_can( 'manage_woocommerce' ) && !current_user_can( 'wcfm_vendor' ) && !current_user_can( 'seller' ) && !current_user_can( 'vendor' ) && !current_user_can( 'shop_staff' ) ) {
  		wp_send_json_error( esc_html__( 'You don&#8217;t have permission to do this.', 'woocommerce' ) );
			wp_die();
		}
  	
  	if( isset( $_POST['responseid'] ) && !empty( $_POST['responseid'] ) ) {
  		$responseid = absint( $_POST['responseid'] );
  		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wcfm_enquiries_response WHERE ID = %d", $responseid ) );
  		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wcfm_enquiries_response_meta WHERE `enquiry_response_id` = %d", $responseid ) );
  	}
  	
  	echo esc_html("success");
  	die;
  }

	/**
	 * Handle Enquiry Bulk Delete
	 *
	 * @since 6.7.1
	 */
	function wcfm_enquiry_bulk_delete() {
		global $wpdb, $_POST;

		if (!check_ajax_referer('wcfm_ajax_nonce', 'wcfm_ajax_nonce', false)) {
			wp_send_json_error(__('Invalid nonce! Refresh your page and try again.', 'wc-frontend-manager'));
		}

		if (!current_user_can('manage_woocommerce') && !current_user_can('wcfm_vendor') && !current_user_can('seller') && !current_user_can('vendor') && !current_user_can('shop_staff') && !current_user_can('wcfm_delivery_boy') && !current_user_can('wcfm_affiliate')) {
			wp_send_json_error(esc_html__('You don&#8217;t have permission to do this.', 'woocommerce'));
		}

		if (isset($_POST['enquiry_ids'])) {
			$enquiry_ids = wc_clean($_POST['enquiry_ids']);
			if (is_array($enquiry_ids) && !empty($enquiry_ids)) {
				$enquiry_ids_placeholder	= implode(', ', array_fill(0, count($enquiry_ids), '%d'));

				$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wcfm_enquiries WHERE ID IN ({$enquiry_ids_placeholder})", $enquiry_ids ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wcfm_enquiries_meta WHERE enquiry_id IN ({$enquiry_ids_placeholder})", $enquiry_ids ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wcfm_enquiries_response WHERE enquiry_id IN ({$enquiry_ids_placeholder})", $enquiry_ids ) );

				if ( !$deleted ) {
					wp_send_json_error( [
						'message' => __( 'Unable to delete', 'wc-frontend-manager' )
					] );
				}
			}
		}

		wp_send_json_success( [
			'message' => __( 'Successfully deleted', 'wc-frontend-manager' )
		] );
	}
	
  /**
   * Enquiry Tab on Single Product
   */
	function wcfm_enquiry_product_tab( $tabs ) {
		global $WCFM, $wp;
		
		$tabs['wcfm_enquiry_tab'] = array(
			'title' 	=> __( 'Enquiries', 'wc-frontend-manager' ),
			'priority' 	=> 100,
			'callback' 	=> array( &$this, 'wcfm_enquiry_product_tab_content' )
		);
	
		return $tabs;
	}
	
	/**
   * Enquiry Button on Single Product Page
   *
   * @since 3.3.5
   */
	function wcfm_enquiry_button() {
		global $WCFM, $post;
		if( apply_filters( 'wcfm_is_pref_enquiry', true ) && apply_filters( 'wcfm_is_pref_enquiry_button', true ) && apply_filters( 'wcfm_is_allow_product_enquiry_bubtton', true ) && !defined('DOING_AJAX') ) {
			
			$vendor_id = 0;
			$product_id = 0;
			if( is_product() && $post && is_object( $post ) ) {
				$product_id = $post->ID;
				$vendor_id = wcfm_get_vendor_id_by_post( $product_id );
				
				//if( !wcfm_is_vendor( $vendor_id ) || !$WCFM->wcfm_vendor_support->wcfm_vendor_has_capability( $vendor_id, 'enquiry' ) ) return;
			}
			
			$button_style     = 'margin-right:10px;';
			$hover_color      = '';
			$hover_text_color = '#ffffff';
			$wcfm_options = $WCFM->wcfm_options;
			$wcfm_store_color_settings = get_option( 'wcfm_store_color_settings', array() );
			if( !empty( $wcfm_store_color_settings ) ) {
				if( isset( $wcfm_store_color_settings['button_bg'] ) ) { $button_style .= 'background: ' . $wcfm_store_color_settings['button_bg'] . ';border-bottom-color: ' . $wcfm_store_color_settings['button_bg'] . ';'; }
				if( isset( $wcfm_store_color_settings['button_text'] ) ) { $button_style .= 'color: ' . $wcfm_store_color_settings['button_text'] . ';'; }
				if( isset( $wcfm_store_color_settings['button_active_bg'] ) ) { $hover_color = $wcfm_store_color_settings['button_active_bg']; }
				if( isset( $wcfm_store_color_settings['button_active_text'] ) ) { $hover_text_color = $wcfm_store_color_settings['button_active_text']; }
			} else {
				if( isset( $wcfm_options['wc_frontend_manager_button_background_color_settings'] ) ) { $button_style .= 'background: ' . $wcfm_options['wc_frontend_manager_button_background_color_settings'] . ';border-bottom-color: ' . $wcfm_options['wc_frontend_manager_button_background_color_settings'] . ';'; }
				if( isset( $wcfm_options['wc_frontend_manager_button_text_color_settings'] ) ) { $button_style .= 'color: ' . $wcfm_options['wc_frontend_manager_button_text_color_settings'] . ';'; }
				if( isset( $wcfm_options['wc_frontend_manager_base_highlight_color_settings'] ) ) { $hover_color = $wcfm_options['wc_frontend_manager_base_highlight_color_settings']; }
			}
			
			$wcfm_enquiry_button_label  = isset( $wcfm_options['wcfm_enquiry_button_label'] ) ? $wcfm_options['wcfm_enquiry_button_label'] : __( 'Ask a Question', 'wc-frontend-manager' );
			
			$button_class = '';
			if( !is_user_logged_in() && apply_filters( 'wcfm_is_allow_enquiry_with_login', false ) ) { $button_class = ' wcfm_login_popup'; }
			?>
			<div class="wcfm_ele_wrapper wcfm_catalog_enquiry_button_wrapper">
				<div class="wcfm-clearfix"></div>
				<a href="#" class="wcfm_catalog_enquiry <?php echo esc_attr($button_class); ?>" data-store="<?php echo esc_attr($vendor_id); ?>" data-product="<?php echo esc_attr($product_id); ?>" style="<?php echo esc_attr($button_style); ?>"><span class="wcfmfa fa-question-circle"></span>&nbsp;&nbsp;<span class="add_enquiry_label"><?php esc_html_e( $wcfm_enquiry_button_label, 'wc-frontend-manager' ); ?></span></a>
				<?php do_action( 'wcfm_after_product_catalog_enquiry_button' ); ?>
				<?php if( $hover_color ) { ?>
					<style>
					a.wcfm_catalog_enquiry:hover{background: <?php echo esc_attr($hover_color); ?> !important;background-color: <?php echo esc_attr($hover_color); ?> !important;border-bottom-color: <?php echo esc_attr($hover_color); ?> !important;color: <?php echo esc_attr($hover_text_color); ?> !important;}
					</style>
				<?php } ?>
				<div class="wcfm-clearfix"></div>
			</div>
			<?php
		}
	}
	
	/**
   * Enquiry Button on WCFM Marketplace Store Page
   *
   * @since 5.0.0
   */
	function wcfmmp_store_enquiry_button() {
		global $WCFM, $WCFMmp;
		if( apply_filters( 'wcfm_is_pref_enquiry', true ) && apply_filters( 'wcfm_is_pref_enquiry_button', true ) && apply_filters( 'wcfmmp_is_allow_store_header_enquiry', true ) ) {
			$vendor_id = 0;
			if( ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) ) {
				$vendor_id = get_query_var( 'author' );
			}
			
			$button_class = '';
			if( !is_user_logged_in() && apply_filters( 'wcfm_is_allow_enquiry_with_login', false ) ) { $button_class = ' wcfm_login_popup'; }
			
			$wcfm_enquiry_button_label  = __( 'Inquiry', 'wc-frontend-manager' );
			
			if( apply_filters( 'wcfm_is_allow_store_inquiry_custom_button_label', false ) ) {
				$wcfm_options = $WCFM->wcfm_options;
				$wcfm_enquiry_button_label  = isset( $wcfm_options['wcfm_enquiry_button_label'] ) ? $wcfm_options['wcfm_enquiry_button_label'] : __( 'Inquiry', 'wc-frontend-manager' );
			}
			?>
			<div class="lft bd_icon_box"><a class="wcfm_store_enquiry <?php echo esc_attr($button_class); ?>" data-store="<?php echo esc_attr($vendor_id); ?>" data-product="0" href="#"><i class="wcfmfa fa-question" aria-hidden="true"></i><span><?php esc_html_e( $wcfm_enquiry_button_label, 'wc-frontend-manager' ); ?></span></a></div>
			<?php
		}
	}
	
	/**
   * Enquiry Button on WCFM Marketplace Store Page
   *
   * @since 5.0.0
   */
	function wcfmmp_store_list_enquiry_button( $store_id ) {
		global $WCFM, $WCFMmp;
		if( apply_filters( 'wcfm_is_pref_enquiry', true ) && apply_filters( 'wcfm_is_pref_enquiry_button', true ) && apply_filters( 'wcfm_is_allow_store_list_enquiry', true ) && $WCFM->wcfm_vendor_support->wcfm_vendor_has_capability( $store_id, 'enquiry' ) ) {
			$button_class = '';
			if( !is_user_logged_in() && apply_filters( 'wcfm_is_allow_enquiry_with_login', false ) ) { $button_class = ' wcfm_login_popup'; }
			
			$wcfm_enquiry_button_label  = __( 'Inquiry', 'wc-frontend-manager' );
			
			if( apply_filters( 'wcfm_is_allow_store_list_inquiry_custom_button_label', false ) ) {
				$wcfm_options = $WCFM->wcfm_options;
				$wcfm_enquiry_button_label  = isset( $wcfm_options['wcfm_enquiry_button_label'] ) ? $wcfm_options['wcfm_enquiry_button_label'] : __( 'Inquiry', 'wc-frontend-manager' );
			}
			?>
			<p class="store-enquiry"><a class="wcfm_catalog_enquiry <?php echo esc_attr($button_class); ?>" data-store="<?php echo esc_attr($store_id); ?>" data-product="0" href="#"><span class="wcfmfa fa-question-circle fa-question-circle"></span>&nbsp;<span class="add_enquiry_label"><?php esc_html_e( $wcfm_enquiry_button_label, 'wc-frontend-manager' ); ?></span></a></p>
			<?php
		}
	}
	
	/**
   * Enquiry List on WCFM Dashboard
   *
   * @since 3.3.5
   */
	function wcfm_dashboard_enquiry_list() {
		global $WCFM, $wpdb;
		
		if( apply_filters( 'wcfm_is_pref_enquiry', true ) && apply_filters( 'wcfm_is_allow_enquiry', true ) && apply_filters( 'wcfm_is_allow_dashboard_enquiry', true ) ) {
			$vendor_id = apply_filters( 'wcfm_message_author', get_current_user_id() );
			
			$enquiry_query = "SELECT * FROM {$wpdb->prefix}wcfm_enquiries AS wcfm_enquiries";
			$enquiry_query .= " WHERE 1 = 1";
			$enquiry_query .= " AND `reply` = ''";
			if( wcfm_is_vendor() ) { 
				$enquiry_query .= " AND `vendor_id` = %d";
				$enquiry_query = $wpdb->prepare( $enquiry_query, $vendor_id );
			}
			$enquiry_query = apply_filters( 'wcfm_enquery_list_query', $enquiry_query );
			$enquiry_query .= " ORDER BY wcfm_enquiries.`ID` DESC";
			$enquiry_query .= " LIMIT 8";
			$enquiry_query .= " OFFSET 0";
			
			$wcfm_enquirys_array = $wpdb->get_results( $enquiry_query );
			
			?>
			<div class="wcfm_dashboard_enquiries">
				<div class="page_collapsible" id="wcfm_dashboard_enquiries"><span class="wcfmfa fa-question-circle fa-question-circle"></span><span class="dashboard_widget_head"><?php _e('Inquiries', 'wc-frontend-manager'); ?></span></div>
				<div class="wcfm-container">
					<div id="wcfm_dashboard_enquiries_expander" class="wcfm-content">
					  <?php
					  if( !empty( $wcfm_enquirys_array ) ) {
					  	$counter = 0;
							foreach($wcfm_enquirys_array as $wcfm_enquirys_single) {
								if( $counter == 5 ) break;
								echo '<div class="wcfm_dashboard_enquiry"><a href="' . esc_url(get_wcfm_enquiry_manage_url($wcfm_enquirys_single->ID)). '" class="wcfm_dashboard_item_title"><span class="wcfmfa fa-question-circle"></span>' . wp_kses_post(substr( $wcfm_enquirys_single->enquiry, 0, 60 )) . ' ...</a></div>';
								$counter++;
							}
							if( count( $wcfm_enquirys_array ) > 5 ) {
								echo '<div class="wcfm_dashboard_enquiry_show_all"><a class="wcfm_submit_button" href="' . esc_url(get_wcfm_enquiry_url()) . '">' . esc_html__( 'Show All', 'wc-frontend-manager' ) . '</a></div>';
							}
						} else {
							esc_html_e( 'There is no enquiry yet!!', 'wc-frontend-manager' );
						}
						?>
					</div>
				</div>
			</div>
			<?php
		}
	}
	
	/**
   * Enquiry Form content
   */
	function wcfm_enquiry_form_content() {
		global $WCFM, $wp, $_POST;
		
		if ( ! check_ajax_referer( 'wcfm_ajax_nonce', 'wcfm_ajax_nonce', false ) ) {
			echo '{"status": false, "message": "' . esc_html__( 'Invalid nonce! Refresh your page and try again.', 'wc-frontend-manager' ) . '"}';
			wp_die();
		}
		
		$product_id = absint($_POST['product']);
		$vendor_id  = absint($_POST['store']);
		
		ob_start();
		$WCFM->template->get_template( 'enquiry/wcfm-view-enquiry-form.php', array( 'product_id' => $product_id, 'vendor_id' => $vendor_id ) );
		echo ob_get_clean();
		die;
	}
	
	/**
	 * Enquiry Reply Attachments Get/Show
	 */
	function wcfm_enquiry_reply_attachments( $wcfm_enquiry_reply_id, $context = 'view' ) {
		global $WCFM, $wpdb;
		
		$attachments = '';
		if( $wcfm_enquiry_reply_id ) {
			$wcfm_options = $WCFM->wcfm_options;
			$wcfm_enquiry_allow_attachment = isset( $wcfm_options['wcfm_enquiry_allow_attachment'] ) ? $wcfm_options['wcfm_enquiry_allow_attachment'] : 'yes';
			if( ( $wcfm_enquiry_allow_attachment == 'yes' ) && apply_filters( 'wcfm_is_allow_enquiry_reply_attachment', true ) ) {
				$wcfm_enquiry_attachments = $wpdb->get_results( $wpdb->prepare( "SELECT value from {$wpdb->prefix}wcfm_enquiries_response_meta WHERE `key` = 'attchment' AND `enquiry_response_id` = %d", $wcfm_enquiry_reply_id ) );
				if( !empty( $wcfm_enquiry_attachments ) ) {
					foreach( $wcfm_enquiry_attachments as $wcfm_enquiry_attachment ) {
						if( $wcfm_enquiry_attachment->value ) {
							$attachments = maybe_unserialize( $wcfm_enquiry_attachment->value );
							if( $attachments && is_array( $attachments ) && !empty( $attachments ) ) {
								if( $context == 'view' ) {
									echo '<div class="wcfm_clearfix"></div><br /><h2 style="font-size:15px;">' . esc_html__( 'Attachment(s)', 'wc-frontend-manager' ) . '</h2><div class="wcfm_clearfix"></div>';
									foreach( $attachments as $attachment ) {
										echo '<a class="wcfm-wp-fields-uploader wcfm_linked_attached" target="_blank" style="width:32px;height:32px;margin-right:10px;" href="' . esc_url($attachment) . '"><span style="font-size:32px;color:	#f86c6b;display:inline-block;" class="wcfmfa fa-file-image"></span></a>';
									}
									return;
								}
							}
						}
					}
				}
			}
		}
		
		return $attachments;
	}
	
	
	/**
   * Enquiry Tab content on Single Product
   */
	function wcfm_enquiry_product_tab_content() {
		global $WCFM, $wp;
		$WCFM->template->get_template( 'enquiry/wcfm-view-enquiry-tab.php' );
	}
	
	function wcfm_enquiry_message_types( $message_types ) {
		$message_types['enquiry'] = __( 'New Enquiry', 'wc-frontend-manager' );
		return $message_types;
	}
	
	function wcfm_enquiry_with_login( $is_allow ) {
		global $WCFM, $wp;
		$wcfm_options = $WCFM->wcfm_options;
		$wcfm_enquiry_with_login    = isset( $wcfm_options['wcfm_enquiry_with_login'] ) ? $wcfm_options['wcfm_enquiry_with_login'] : 'no';
		if( $wcfm_enquiry_with_login == 'yes' ) $is_allow = true;
		return $is_allow;
	}
	
	/**
	 * WCFM Enquiry JS
	 */
	function wcfm_enquiry_scripts() {
 		global $WCFM, $wp, $WCFM_Query;
 		
 		if( !is_user_logged_in() && apply_filters( 'wcfm_is_allow_enquiry_with_login', false ) ) {
 			$WCFM->library->load_wcfm_login_popup_lib();
 		} else {
			//wp_enqueue_script( 'wcfm_enquiry_tab_js', $WCFM->library->js_lib_url_min . 'enquiry/wcfm-script-enquiry-tab.js', array('jquery' ), $WCFM->version, true );
			// Localized Script
			//$wcfm_messages = get_wcfm_enquiry_manage_messages();
			//wp_localize_script( 'wcfm_enquiry_tab_js', 'wcfm_enquiry_manage_messages', $wcfm_messages );
		}
 		
 		if( is_account_page() ) {
 			if( is_user_logged_in() ) {
				if( isset( $wp->query_vars[$this->wcfm_myaccount_view_inquiry_endpoint] ) && !empty( $wp->query_vars[$this->wcfm_myaccount_view_inquiry_endpoint] ) ) {
					$WCFM->library->load_blockui_lib();
					$WCFM->library->load_select2_lib();
					$WCFM->library->load_multiinput_lib();
					$WCFM->library->load_collapsible_lib();
					wp_enqueue_script( 'wcfm_enquiry_manage_js', $WCFM->library->js_lib_url_min . 'enquiry/wcfm-script-my-account-enquiry-manage.js', array('jquery'), $WCFM->version, true );
					// Localized Script
					$wcfm_messages = get_wcfm_enquiry_manage_messages();
					wp_localize_script( 'wcfm_enquiry_manage_js', 'wcfm_enquiry_manage_messages', $wcfm_messages );
					
					$wcfm_dashboard_messages = get_wcfm_dashboard_messages();
					wp_localize_script( 'wcfm_enquiry_manage_js', 'wcfm_dashboard_messages', $wcfm_dashboard_messages );
				}
			}
 		}
 	}
 	
 	/**
 	 * WCFM Enquiry CSS
 	 */
 	function wcfm_enquiry_styles() {
 		global $WCFM, $wp, $WCFM_Query;
 		
 		if( is_product() ) {
 			wp_enqueue_style( 'wcfm_enquiry_tab_css',  $WCFM->library->css_lib_url_min . 'enquiry/wcfm-style-enquiry-tab.css', array(), $WCFM->version );
 		}
 		
 		//wp_enqueue_style( 'wcfm_enquiry_button_css',  $WCFM->library->css_lib_url_min . 'enquiry/wcfm-style-enquiry-button.css', array(), $WCFM->version );
 		
 		if( is_rtl() ) {
 			wp_enqueue_style( 'wcfm_enquiry_button_rtl_css',  $WCFM->library->css_lib_url_min . 'enquiry/wcfm-style-enquiry-button-rtl.css', array( 'wcfm_core_css' ), $WCFM->version );
 		}
 		
 		if( is_account_page() ) {
 			if( is_user_logged_in() ) {
 				if( isset( $wp->query_vars[$this->wcfm_myaccount_view_inquiry_endpoint] ) && !empty( $wp->query_vars[$this->wcfm_myaccount_view_inquiry_endpoint] ) ) {
 					//wp_enqueue_style( 'collapsible_css',  $WCFM->library->css_lib_url_min . 'wcfm-style-collapsible.css', array(), $WCFM->version );
 					wp_enqueue_style( 'wcfm_menu_css',  $WCFM->library->css_lib_url_min . 'menu/wcfm-style-menu.css', array(), $WCFM->version );
 					wp_enqueue_style( 'wcfm_enquiry_manage_css',  $WCFM->library->css_lib_url_min . 'enquiry/wcfm-style-enquiry-manage.css', array(), $WCFM->version );
 					wp_enqueue_style( 'wcfm_my_account_enquiry_manage_css',  $WCFM->library->css_lib_url_min . 'enquiry/wcfm-style-my-account-enquiry-manage.css', array(), $WCFM->version );
 				}
 			}
 		}
 	}
}
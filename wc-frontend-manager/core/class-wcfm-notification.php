<?php

/**
 * WCFM Notification Class
 *
 * @version		3.3.4
 * @package		wcfm/core
 * @author 		WC Lovers
 */
class WCFM_Notification {
	
	public $cache_group = '';
	
	public function __construct() {
		global $WCFM;
		
		$this->cache_group = 'wcfm-notification';
		
		// Order notification on WCFM Message
		if( apply_filters( 'wcfm_is_allow_orders_extended_notifications', true ) ) {
			add_action( 'woocommerce_order_status_on-hold', array( $this, 'wcfm_message_on_new_order' ) );
			add_action( 'woocommerce_order_status_pending', array( $this, 'wcfm_message_on_new_order' ) );
			add_action( 'woocommerce_order_status_processing', array( $this, 'wcfm_message_on_new_order' ) );
			add_action( 'ywraq_after_create_order', array( $this, 'wcfm_message_on_new_order' ) );
		}
		add_action( 'woocommerce_order_status_completed', array( $this, 'wcfm_message_on_new_order' ) );
		
		// Manual Order Desktop Notirfication
		add_action( 'wcfm_manual_orders_manage_complete', array( $this, 'wcfm_message_on_new_order' ) );
		
		// Product notification on Product Approve/Reject
		add_action( 'wcfm_after_product_approve', array( $this, 'wcfm_message_on_product_approve' ) );
		add_action( 'wcfm_after_product_reject', array( $this, 'wcfm_message_on_product_reject' ), 10, 2 );
		add_action( 'woocommerce_process_product_meta', array( $this, 'wcfm_message_on_product_approve' ) );
		
		// Message list in WCFM Dashboard
		add_action( 'after_wcfm_dashboard_product_stats', array( $this, 'wcfm_dashboard_notification_list' ) );
		
		// Message Auto Refresh Counter
		add_action( 'wp_ajax_wcfm_message_count', array( &$this, 'wcfm_message_count' ) );
		
		// Fetching new Message Notifications
		add_action( 'wp_ajax_wcfm_message_notification', array( &$this, 'wcfm_message_notification' ) );
		
		// Message Mark as Read
		add_action( 'wp_ajax_wcfm_messages_mark_read', array( &$this, 'wcfm_messages_mark_read' ) );
		add_action( 'wp_ajax_wcfm_messages_bulk_mark_read', array( &$this, 'wcfm_messages_bulk_mark_read' ) );
		
		// Message Delete
		add_action( 'wp_ajax_wcfm_messages_delete', array( &$this, 'wcfm_messages_delete' ) );
		
		// Messages Bulk Delete
		add_action( 'wp_ajax_wcfm_messages_bulk_mark_delete', array( &$this, 'wcfm_messages_bulk_mark_delete' ) );
	}
	
	/**
   * New Product notification on WCFM Message board
   */
	public function wcfm_admin_notification_new_product( $vendor_id, $product_id ) {
  	global $WCFM;
  	
  	if( get_post_status( $product_id ) != 'publish' ) return;
  	
  	$wcfm_new_product_notified = get_post_meta( $product_id, '_wcfm_new_product_notified', true );
  	
  	if( !$wcfm_new_product_notified ) {
			$author_id = $vendor_id;
			$author_is_admin = 0;
			$author_is_vendor = 1;
			$message_to = 0;
			$wcfm_messages = sprintf( __( 'A new product <b>%s</b> added by <b>%s</b>', 'wc-frontend-manager' ), '<a class="wcfm_dashboard_item_title" href="' . get_permalink( $product_id ) . '">' . get_the_title( $product_id ) . '</a>', wcfm_get_vendor_store( $vendor_id ) );

			$raw_message = [
				'l10n'	=> [
					'text' 		=> 'A new product <b>%s</b> added by <b>%s</b>',
					'domain'    => 'wc-frontend-manager',
					'wrapper'	=> [
						'function' 	=> 'sprintf',
						'args' 		=> [
							'<a class="wcfm_dashboard_item_title" href="' . get_permalink( $product_id ) . '">' . get_the_title( $product_id ) . '</a>', 
							wcfm_get_vendor_store( $vendor_id )
						]
					]
				]
			];

			$this->wcfm_send_direct_message( $author_id, $message_to, $author_is_admin, $author_is_vendor, $wcfm_messages, 'new_product', apply_filters( 'wcfm_is_allow_new_product_notification_email', false ), $raw_message );
			
			update_post_meta( $product_id, '_wcfm_new_product_notified', 'yes' );
			
			do_action( 'wcfm_after_new_product_by_vendor', $product_id, $vendor_id );
		}
	}
	
	/**
	 * Vendor Notification on Product Approve
	 */
	function wcfm_message_on_product_approve( $product_id ) {
		global $WCFM;
  	
  	if( get_post_status( $product_id ) != 'publish' ) return;
  	
  	$wcfm_product_approved_notified = get_post_meta( $product_id, '_wcfm_product_approved_notified', true );
  	
  	if( !$wcfm_product_approved_notified ) {
			$vendor_id = wcfm_get_vendor_id_by_post( $product_id );
			if( !$vendor_id ) return;
  	
			$wcfm_messages = sprintf( __( 'Product <b>%s</b> has been approved.', 'wc-frontend-manager' ), '<a class="wcfm_dashboard_item_title" target="_blank" href="' . get_permalink( $product_id ) . '">' . get_the_title( $product_id ) . '</a>' );

			$raw_message = [
				'l10n'	=> [
					'text' 		=> 'Product <b>%s</b> has been approved.',
					'domain'    => 'wc-frontend-manager',
					'wrapper'	=> [
						'function' 	=> 'sprintf',
						'args' 		=> [
							'<a class="wcfm_dashboard_item_title" target="_blank" href="' . get_permalink( $product_id ) . '">' . get_the_title( $product_id ) . '</a>'
						]
					]
				]
			];

			$this->wcfm_send_direct_message( -1, $vendor_id, 1, 0, $wcfm_messages, 'product_review', apply_filters( 'wcfm_is_allow_product_approved_notification_email', true ), $raw_message );
		
			update_post_meta( $product_id, '_wcfm_product_approved_notified', 'yes' );
		}
	}
	
	/**
	 * Vendor Notification on Product Reject
	 */
	function wcfm_message_on_product_reject( $product_id, $reason ) {
		global $WCFM;
  	
		$vendor_id = wcfm_get_vendor_id_by_post( $product_id );
		if( !$vendor_id ) return;
	
		$wcfm_messages = sprintf( __( 'Product <b>%s</b> has been rejected.<br/>Reason: %s', 'wc-frontend-manager' ), '<a class="wcfm_dashboard_item_title" target="_blank" href="' . get_wcfm_edit_product_url( $product_id ) . '">' . get_the_title( $product_id ) . '</a>', $reason );

		$raw_message = [
			'l10n'	=> [
				'text' 		=> 'Product <b>%s</b> has been rejected.<br/>Reason: %s',
				'domain'    => 'wc-frontend-manager',
				'wrapper'	=> [
					'function' 	=> 'sprintf',
					'args' 		=> [
						'<a class="wcfm_dashboard_item_title" target="_blank" href="' . get_wcfm_edit_product_url( $product_id ) . '">' . get_the_title( $product_id ) . '</a>', 
						$reason
					]
				]
			]
		];

		$this->wcfm_send_direct_message( -1, $vendor_id, 1, 0, $wcfm_messages, 'product_review', apply_filters( 'wcfm_is_allow_product_reject_notification_email', true ), $raw_message );
		
	}
	
	/**
   * Review notification on WCFM Message board
   */
	public function wcfm_admin_notification_product_review( $vendor_id, $product_id ) {
  	global $WCFM;
  	
  	$wcfm_review_product_notified = get_post_meta( $product_id, '_wcfm_review_product_notified', true );
  	
  	if( !$wcfm_review_product_notified ) {
			$author_id = $vendor_id;
			$author_is_admin = 0;
			$author_is_vendor = 1;
			$message_to = 0;
			$wcfm_messages = sprintf( __( 'Product <b>%s</b> awaiting for review', 'wc-frontend-manager' ), '<a class="wcfm_dashboard_item_title" href="' . get_wcfm_edit_product_url( $product_id ) . '">' . get_the_title( $product_id ) . '</a>' );

			$raw_message = [
				'l10n'	=> [
					'text' 		=> 'Product <b>%s</b> awaiting for review',
					'domain'    => 'wc-frontend-manager',
					'wrapper'	=> [
						'function' 	=> 'sprintf',
						'args' 		=> [
							'<a class="wcfm_dashboard_item_title" href="' . get_wcfm_edit_product_url( $product_id ) . '">' . get_the_title( $product_id ) . '</a>'
						]
					]
				]
			];

			$this->wcfm_send_direct_message( $author_id, $message_to, $author_is_admin, $author_is_vendor, $wcfm_messages, 'product_review', apply_filters( 'wcfm_is_allow_review_product_notification_email', true ), $raw_message );
				
			update_post_meta( $product_id, '_wcfm_review_product_notified', 'yes' );
			
			do_action( 'wcfm_after_review_product_by_vendor', $product_id, $vendor_id );
		}
	}

	/**
	 * New Order notification on WCFM Message board
	 */
	function wcfm_message_on_new_order($order_id, $is_renewal = false) {
		global $WCFM, $wpdb;

		if (is_admin() && !$is_renewal) return;

		$order = wc_get_order($order_id);
		if ($order->get_meta('_wcfm_new_order_notified')) return;

		$author_id = -2;
		$author_is_admin = 1;
		$author_is_vendor = 0;
		$message_to = 0;

		// Admin Notification
		$wcfm_messages = sprintf(__('You have received an Order <b>#%s</b>', 'wc-frontend-manager'), '<a target="_blank" class="wcfm_dashboard_item_title" href="' . get_wcfm_view_order_url($order_id) . '">' . $order->get_order_number() . '</a>');

		$raw_message = [
			'l10n'	=> [
				'text' 		=> 'You have received an Order <b>#%s</b>',
				'domain'    => 'wc-frontend-manager',
				'wrapper'	=> [
					'function' 	=> 'sprintf',
					'args' 		=> [
						'<a target="_blank" class="wcfm_dashboard_item_title" href="' . get_wcfm_view_order_url($order_id) . '">' . $order->get_order_number() . '</a>'
					]
				]
			]
		];

		$this->wcfm_send_direct_message($author_id, $message_to, $author_is_admin, $author_is_vendor, $wcfm_messages, 'order', apply_filters('wcfm_is_allow_order_notification_email', false), $raw_message);

		// Vendor Notification
		if ($WCFM->is_marketplace) {
			$order_vendors = array();
			foreach ($order->get_items() as $item_id => $item) {
				if (version_compare(WC_VERSION, '4.4', '<')) {
					$product = $order->get_product_from_item($item);
				} else {
					$product = $item->get_product();
				}
				$product_id   = 0;
				if (is_object($product)) {
					$product_id   = $item->get_product_id();
				}
				if ($product_id) {
					$author_id = -1;
					$message_to = wcfm_get_vendor_id_by_post($product_id);

					if ($message_to) {
						if (apply_filters('wcfm_is_allow_itemwise_notification', true)) {
							$wcfm_messages = sprintf(__('You have received an Order <b>#%s</b> for <b>%s</b>', 'wc-frontend-manager'), '<a target="_blank" class="wcfm_dashboard_item_title" href="' . get_wcfm_view_order_url($order_id) . '">' . $order->get_order_number() . '</a>', get_the_title($product_id));

							$raw_message = [
								'hook'    	=> [
									'name'  => 'wcfm_new_order_vendor_notification_message',
									'args'  => [
										$order_id,
										$message_to
									]
								],
								'l10n'	=> [
									'text' 		=> 'You have received an Order <b>#%s</b> for <b>%s</b>',
									'domain'    => 'wc-frontend-manager',
									'wrapper'	=> [
										'function' 	=> 'sprintf',
										'args' 		=> [
											'<a target="_blank" class="wcfm_dashboard_item_title" href="' . get_wcfm_view_order_url($order_id) . '">' . $order->get_order_number() . '</a>',
											get_the_title($product_id)
										]
									]
								]
							];
						} elseif (!in_array($message_to, $order_vendors)) {
							$wcfm_messages = sprintf(__('You have received an Order <b>#%s</b>', 'wc-frontend-manager'), '<a target="_blank" class="wcfm_dashboard_item_title" href="' . get_wcfm_view_order_url($order_id) . '">' . $order->get_order_number() . '</a>');

							$raw_message = [
								'hook'    	=> [
									'name'  => 'wcfm_new_order_vendor_notification_message',
									'args'  => [
										$order_id,
										$message_to
									]
								],
								'l10n'	=> [
									'text' 		=> 'You have received an Order <b>#%s</b>',
									'domain'    => 'wc-frontend-manager',
									'wrapper'	=> [
										'function' 	=> 'sprintf',
										'args' 		=> [
											'<a target="_blank" class="wcfm_dashboard_item_title" href="' . get_wcfm_view_order_url($order_id) . '">' . $order->get_order_number() . '</a>'
										]
									]
								]
							];
						} else {
							continue;
						}
						$wcfm_messages = apply_filters('wcfm_new_order_vendor_notification_message', $wcfm_messages, $order_id, $message_to);
						$this->wcfm_send_direct_message($author_id, $message_to, $author_is_admin, $author_is_vendor, $wcfm_messages, 'order', apply_filters('wcfm_is_allow_order_notification_email', false), $raw_message);
						$order_vendors[$message_to] = $message_to;

						do_action('wcfm_after_new_order_vendor_notification', $message_to, $product_id, $order_id);
					}
				}
			}
		}

		$order->update_meta_data('_wcfm_new_order_notified', 'yes');
		$order->save();
	}
  
  /**
   * WCFM Dashboard Notification List
   *
   * @since 3.3.5
   */
  function wcfm_dashboard_notification_list() {
  	global $WCFM, $wpdb;
  	
  	if( apply_filters( 'wcfm_is_allow_notifications', true ) && apply_filters( 'wcfm_is_allow_dashboard_notificaton', true ) ) {
  		$message_to = apply_filters( 'wcfm_message_author', get_current_user_id() );
  		if( $message_to ) {
				$sql = 'SELECT wcfm_messages.* FROM ' . $wpdb->prefix . 'wcfm_messages AS wcfm_messages';
				$sql .= ' WHERE 1=1';
				$sql .= " AND `is_direct_message` = 1";
				if( wcfm_is_vendor() || ( function_exists( 'wcfm_is_delivery_boy' ) && wcfm_is_delivery_boy() ) || ( function_exists( 'wcfm_is_affiliate' ) && wcfm_is_affiliate() ) ) { 
					$vendor_filter = " AND ( `author_id` = %d OR `message_to` = -1 OR `message_to` = %d )";
					$sql .= $vendor_filter;
					$sql = $wpdb->prepare( $sql, $message_to, $message_to );
				} else {
					$group_manager_filter = apply_filters( 'wcfm_notification_group_manager_filter', '' );
					if( $group_manager_filter ) {
						$sql .= $group_manager_filter;
					} else {
						$sql .= " AND `author_id` != -1";
					}
				}
				$sql .= " AND NOT EXISTS (SELECT * FROM {$wpdb->prefix}wcfm_messages_modifier as wcfm_messages_modifier_2 WHERE wcfm_messages.ID = wcfm_messages_modifier_2.message AND wcfm_messages_modifier_2.read_by=%d)";
				$sql .= " ORDER BY wcfm_messages.`ID` DESC";
				$sql .= " LIMIT 10";
				$sql .= " OFFSET 0";
				
				$wcfm_messages = $wpdb->get_results( $wpdb->prepare( $sql, $message_to ) );
			} else {
				$wcfm_messages = array();
			}
			
			?>
			<div class="wcfm_dashboard_notifications">
				<div class="page_collapsible" id="wcfm_dashboard_notifications"><span class="wcfmfa fa-bell"></span><span class="dashboard_widget_head"><?php _e('Notifications', 'wc-frontend-manager'); ?></span></div>
				<div class="wcfm-container">
					<div id="wcfm_dashboard_notifications_expander" class="wcfm-content">
					  <?php
					  if( !empty( $wcfm_messages ) ) {
					  	$counter = 0;
							foreach($wcfm_messages as $wcfm_message) {
								if( $counter == 5 ) break;
								// Type
								if( !$wcfm_message->message_type ) $wcfm_message->message_type = 'direct';
								$message_type = isset( $message_types[$wcfm_message->message_type] ) ? $message_types[$wcfm_message->message_type] : ucfirst($wcfm_message->message_type);
								$message_icon = $this->get_wcfm_notification_icon( $wcfm_message->message_type );
					
								// Message
								$message_text =  htmlspecialchars_decode($wcfm_message->message);
								$wcfm_dashboard_message_content_length = (int) apply_filters( 'wcfm_is_allow_dashboard_message_content_length', 80 );
								if( $wcfm_message->message_type  == 'direct' ) $message_text =  substr( strip_tags( $message_text ), 0, $wcfm_dashboard_message_content_length ) . ' ...';
								echo '<div class="wcfm_dashboard_notification">' . wp_kses_post($message_icon) . ' ' . wp_kses_post($message_text) . '</div>';
								$counter++;
							}
							if( count( $wcfm_messages ) > 5 ) {
								echo '<div class="wcfm_dashboard_notifications_show_all"><a class="wcfm_submit_button" href="' . esc_url(get_wcfm_messages_url()) . '">' . esc_html__( 'Show All', 'wc-frontend-manager' ) . '</a></div><div class="wcfm-clearfix"></div>';
							}
						} else {
							_e( 'There is no notification yet!!', 'wc-frontend-manager' );
						}
						?>
					</div>
				</div>
			</div>
			<?php
  	}
  }
  
  /**
   * WCFM Message Counter
   *
   * @since 2.3.4
   */
  public function wcfm_message_count() {
  	global $WCFM;

  	if( is_user_logged_in() ) {
  		if ( ! check_ajax_referer( 'wcfm_ajax_nonce', 'wcfm_ajax_nonce', false ) ) {
				echo '{"status": false, "message": "' . esc_html__( 'Invalid nonce! Refresh your page and try again.', 'wc-frontend-manager' ) . '"}';
				wp_die();
			}
		
  		if ( !current_user_can( 'manage_woocommerce' ) && !current_user_can( 'wcfm_vendor' ) && !current_user_can( 'seller' ) && !current_user_can( 'vendor' ) && !current_user_can( 'shop_staff' ) && !current_user_can( 'wcfm_delivery_boy' ) && !current_user_can( 'wcfm_affiliate' ) ) {
				//wp_send_json_error( esc_html__( 'You don&#8217;t have permission to do this.', 'woocommerce' ) );
				wp_die();
			}
		
			$unread_notice = $this->wcfm_direct_message_count( 'notice' );
			$unread_message = $this->wcfm_direct_message_count( 'message' );
			$unread_enquiry = $this->wcfm_direct_message_count( 'enquiry' );
			
			echo '{ "status": true, "notice": ' . esc_attr($unread_notice) . ', "message": ' . esc_attr($unread_message) . ', "enquiry": ' . esc_attr($unread_enquiry) . ' }';
		} else {
			echo '{ "status": false, "redirect": "' . esc_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ) . '" }';
		}
		die;
  }
  
  /**
	 * WCFM unread message count
	 *
	 * @since 2.3.4
	 */
	public function wcfm_direct_message_count( $message_type = 'notice', $message_status = 'unread' ) {
		global $WCFM, $wpdb;
		
		$message_to = apply_filters( 'wcfm_message_author', get_current_user_id() );
		if( !$message_to ) return 0;
		if( !wcfm_is_allow_wcfm() ) return 0;
		$total_mesaages = 0;
		if( $message_type == 'enquiry' ) {
			if( apply_filters( 'wcfm_is_pref_enquiry', true ) && apply_filters( 'wcfm_is_allow_enquiry', true ) ) {
				if( wcfm_is_vendor() ) { 
					$cache_key = $this->cache_group . '-enquiry-' . $message_to;
				} else {
					$cache_key = $this->cache_group . '-enquiry-0';
				}
				$total_mesaages = get_transient( $cache_key );
				
				if( empty( $total_mesaages ) ) {
					$sql = "SELECT COUNT(wcfm_enquiries.ID) FROM {$wpdb->prefix}wcfm_enquiries AS wcfm_enquiries";
					$sql .= " WHERE 1=1";
					$sql .= " AND `reply` = ''";
					if( wcfm_is_vendor() ) { 
						$sql .= " AND `vendor_id` = %d";
						$sql = $wpdb->prepare( $sql, $message_to );
					}
					$sql = apply_filters( 'wcfm_enquery_list_query', $sql );
					$total_mesaages = $wpdb->get_var( $sql );
					
					set_transient( $cache_key, $total_mesaages );
				}
			}
		} elseif( $message_type == 'notice' ) {
			if( ( $message_type == 'notice' ) && apply_filters( 'wcfm_is_pref_notice', true ) && apply_filters( 'wcfm_is_allow_notice', true ) ) {
				$cache_key = $this->cache_group . '-notice';
				$total_mesaages = get_transient( $cache_key );
				
				if( empty( $total_mesaages ) ) {
					$args = array(
								'posts_per_page'   => -1,
								'offset'           => 0,
								'post_type'        => 'wcfm_notice',
								'post_parent'      => 0,
								'post_status'      => array('publish'),
								'suppress_filters' => 0 
							);
					$args = apply_filters( 'wcfm_notice_args', $args );
			
					$wcfm_notices_array = get_posts( $args );
					$total_mesaages = count($wcfm_notices_array);
					
					set_transient( $cache_key, $total_mesaages );
				}
			}
		} else {
			if( ( $message_type == 'message' ) && apply_filters( 'wcfm_is_allow_notifications', true ) ) {
				if( wcfm_is_vendor() || ( function_exists( 'wcfm_is_delivery_boy' ) && wcfm_is_delivery_boy() ) || ( function_exists( 'wcfm_is_affiliate' ) && wcfm_is_affiliate() ) ) {
					$cache_key = $this->cache_group . '-message-' . $message_to;
				} else {
					$cache_key = $this->cache_group . '-message-0';
				}
				$total_mesaages = get_transient( $cache_key );
				
				if( empty( $total_mesaages ) ) {
					$sql = 'SELECT COUNT(wcfm_messages.ID) FROM ' . $wpdb->prefix . 'wcfm_messages AS wcfm_messages';
					$sql .= ' WHERE 1=1';
					
					$status_filter = " AND `is_direct_message` = 1";
					$sql .= $status_filter;
					
					if( wcfm_is_vendor() || ( function_exists( 'wcfm_is_delivery_boy' ) && wcfm_is_delivery_boy() ) || ( function_exists( 'wcfm_is_affiliate' ) && wcfm_is_affiliate() ) ) { 
						//$vendor_filter = " AND `author_is_admin` = 1";
						$vendor_filter = " AND ( `author_id` = %d OR `message_to` = -1 OR `message_to` = %d )";
						$sql .= $vendor_filter;
						$sql = $wpdb->prepare( $sql, $message_to, $message_to );
					} else {
						$group_manager_filter = apply_filters( 'wcfm_notification_group_manager_filter', '' );
						if( $group_manager_filter ) {
							$sql .= $group_manager_filter;
						} else {
							$sql .= " AND `author_id` != -1";
						}
					}
					
					$message_status_filter = " AND NOT EXISTS (SELECT * FROM {$wpdb->prefix}wcfm_messages_modifier as wcfm_messages_modifier_2 WHERE wcfm_messages.ID = wcfm_messages_modifier_2.message AND wcfm_messages_modifier_2.read_by=%d)";
					$sql .= $message_status_filter;
					
					$total_mesaages = $wpdb->get_var( $wpdb->prepare( $sql, $message_to ) );
					
					set_transient( $cache_key, $total_mesaages );
				}
			}
		}
		
		return  $total_mesaages;
	}

	/**
	 * WCFM sent messages
	 *
	 * @since 3.0.6
	 */
	public function wcfm_send_direct_message( $author_id, $message_to, $author_is_admin, $author_is_vendor, $wcfm_messages, $wcfm_messages_type = 'direct', $email_notification = true, $raw_message = [] ) {
		global $WCFM, $wpdb;
		
		if( !apply_filters( 'wcfm_is_pref_notification', true ) ) return;
		
		if( !apply_filters( 'wcfm_is_allow_send_notification', true, $wcfm_messages_type, $message_to ) ) return;
		
		$is_notice = 0;
		$is_direct_message = 1;
		
		$notification_messages  = esc_sql( wp_filter_post_kses( $wcfm_messages ) );
		$wcfm_messages_type     = esc_sql( wc_clean( $wcfm_messages_type ) );
		$current_time           = date( 'Y-m-d H:i:s', current_time( 'timestamp', 0 ) );
    		
		if( apply_filters( 'wcfm_is_allow_notification_message', true, $wcfm_messages_type, $message_to ) ) {
			$user_email = '';
			if( $message_to ) {
				if( $message_to != -1 && $message_to != '-1' ) {
					if( wcfm_is_vendor( $message_to ) ) {
						$user_email = wcfm_get_vendor_store_email_by_vendor( $message_to );
					} else {
						$userdata = get_userdata( $message_to );
						if( $userdata ) {
							$user_email = $userdata->user_email;
						}
					}
					if( $user_email ) {
						// Switch language context…
						if( apply_filters( 'wcfm_allow_wpml_email_translation', true ) ) {
							do_action('wpml_switch_language_for_email', $user_email);
						}

						if( apply_filters( 'wcfm_allow_wpml_email_translation', true ) && $raw_message ) {
							$notification_messages  = esc_sql( wp_filter_post_kses( $this->wcfm_direct_message_raw_to_l10n( $raw_message ) ) );
						}

						// switch language back
						if( apply_filters( 'wcfm_allow_wpml_email_translation', true ) ) {
							do_action('wpml_restore_language_from_email');
						}
					}
				}
			} else {
				$user_email = apply_filters( 'wcfm_admin_email_notification_receiver', get_bloginfo( 'admin_email' ), $wcfm_messages_type );
				if( $user_email ) {
					// Switch language context…
					if( apply_filters( 'wcfm_allow_wpml_email_translation', true ) ) {
						do_action('wpml_switch_language_for_email', $user_email);
					}

					if( apply_filters( 'wcfm_allow_wpml_email_translation', true ) && $raw_message ) {
						$notification_messages  = esc_sql( wp_filter_post_kses( $this->wcfm_direct_message_raw_to_l10n( $raw_message ) ) );
					}

					// switch language back
					if( apply_filters( 'wcfm_allow_wpml_email_translation', true ) ) {
						do_action('wpml_restore_language_from_email');
					}
				}
			}

			$wcfm_create_message	= $wpdb->prepare( 
				"INSERT into {$wpdb->prefix}wcfm_messages (
					`message`, 
					`author_id`, 
					`author_is_admin`, 
					`author_is_vendor`, 
					`is_notice`, 
					`is_direct_message`, 
					`message_to`, 
					`message_type`, 
					`created`
				) VALUES (
					%s, 
					%d, 
					%d, 
					%d, 
					%d, 
					%d, 
					%d, 
					%s, 
					%s
				)",
				$notification_messages, 
				$author_id, 
				$author_is_admin, 
				$author_is_vendor, 
				$is_notice, 
				$is_direct_message, 
				$message_to, 
				$wcfm_messages_type, 
				$current_time
			);
																	
			$wpdb->query($wcfm_create_message);
			
			$messageid = $wpdb->insert_id;
			$todate = date('Y-m-d H:i:s');
			if( $messageid && ( $author_id > 0 ) ) {
				$wcfm_read_message     = $wpdb->prepare(  
					"INSERT into {$wpdb->prefix}wcfm_messages_modifier (
						`message`, 
						`is_read`, 
						`read_by`, 
						`read_on`
					) VALUES ( 
						%d, 
						%d, 
						%d, 
						%s
					)",
					$messageid, 
					1, 
					$author_id, 
					$todate
				);
				$wpdb->query($wcfm_read_message);
			}
			
			if( $message_to ) {
				// Vendor Transient
				$cache_key = $this->cache_group . '-message-' . $message_to;
				delete_transient( $cache_key );
			} else {
				// Admin Transient
				$cache_key = $this->cache_group . '-message-0';
				delete_transient( $cache_key );
			}
		}
		
		if( $email_notification && apply_filters( 'wcfm_is_allow_notification_email', true, $wcfm_messages_type, $message_to ) ) {
			if( !defined( 'DOING_WCFM_EMAIL' ) ) 
				define( 'DOING_WCFM_EMAIL', true );
			
			$user_email = '';
			if( $message_to ) {
				if( $message_to != -1 && $message_to != '-1' ) {
					if( wcfm_is_vendor( $message_to ) ) {
						$user_email = wcfm_get_vendor_store_email_by_vendor( $message_to );
					} else {
						$userdata = get_userdata( $message_to );
						if( $userdata ) {
							$user_email = $userdata->user_email;
						}
					}
					if( $user_email ) {
						// Switch language context…
						if( apply_filters( 'wcfm_allow_wpml_email_translation', true ) ) {
							do_action('wpml_switch_language_for_email', $user_email);
						}

						$message_types = get_wcfm_message_types();
						$message_type = isset( $message_types[$wcfm_messages_type] ) ? $message_types[$wcfm_messages_type] : ucfirst($wcfm_messages_type);

						$subject = $this->wcfm_direct_message_subject( [
							'message_type'			=> $message_type, 
							'wcfm_messages_type'	=> $wcfm_messages_type
						] );

						$message = $this->wcfm_direct_message_body( [ 
							'message_to'			=> $message_to, 
							'message_type'			=> $message_type, 
							'wcfm_messages'			=> $wcfm_messages, 
							'wcfm_messages_type'	=> $wcfm_messages_type, 
							'raw_message'			=> $raw_message
						] ); 

						wp_mail( $user_email, $subject, $message );

						// switch language back
						if( apply_filters( 'wcfm_allow_wpml_email_translation', true ) ) {
							do_action('wpml_restore_language_from_email');
						}
					}
				} elseif( ( $wcfm_messages_type == 'notice' ) || apply_filters( 'wcfm_is_allow_notification_email_to_all', false ) ) {
					$all_vendors = $WCFM->wcfm_vendor_support->wcfm_get_vendor_list( true, '', '', '', '', false );
					if( !empty( $all_vendors ) ) {
						foreach( $all_vendors as $vendor_id => $all_vendor ) {
							if( $vendor_id && apply_filters( 'wcfm_is_allow_notification_email', true, $wcfm_messages_type, $vendor_id ) ) {
								$user_email = wcfm_get_vendor_store_email_by_vendor( $vendor_id );
								if( $user_email ) {
									// Switch language context…
									if( apply_filters( 'wcfm_allow_wpml_email_translation', true ) ) {
										do_action('wpml_switch_language_for_email', $user_email);
									}

									$message_types = get_wcfm_message_types();
									$message_type = isset( $message_types[$wcfm_messages_type] ) ? $message_types[$wcfm_messages_type] : ucfirst($wcfm_messages_type);

									$subject = $this->wcfm_direct_message_subject( [ 
										'message_type'			=> $message_type, 
										'wcfm_messages_type'	=> $wcfm_messages_type
									] );

									$message = $this->wcfm_direct_message_body( [ 
										'message_to'			=> $message_to, 
										'message_type'			=> $message_type, 
										'wcfm_messages'			=> $wcfm_messages, 
										'wcfm_messages_type'	=> $wcfm_messages_type, 
										'raw_message'			=> $raw_message
									] ); 
									
									wp_mail( $user_email, $subject, $message );

									// switch language back
									if( apply_filters( 'wcfm_allow_wpml_email_translation', true ) ) {
										do_action('wpml_restore_language_from_email');
									}
								}
							}
						}
					}
				}
			} else {
				$user_email = apply_filters( 'wcfm_admin_email_notification_receiver', get_bloginfo( 'admin_email' ), $wcfm_messages_type );
				if( $user_email ) {
					// Switch language context…
					if( apply_filters( 'wcfm_allow_wpml_email_translation', true ) ) {
						do_action('wpml_switch_language_for_email', $user_email);
					}

					$message_types = get_wcfm_message_types();
					$message_type = isset( $message_types[$wcfm_messages_type] ) ? $message_types[$wcfm_messages_type] : ucfirst($wcfm_messages_type);

					$subject = $this->wcfm_direct_message_subject( [
						'message_type'			=> $message_type, 
						'wcfm_messages_type'	=> $wcfm_messages_type
					] );

					$message = $this->wcfm_direct_message_body( [ 
						'message_to'			=> $message_to, 
						'message_type'			=> $message_type, 
						'wcfm_messages'			=> $wcfm_messages, 
						'wcfm_messages_type'	=> $wcfm_messages_type, 
						'raw_message'			=> $raw_message 
					] ); 

					wp_mail( $user_email, $subject, $message );

					// switch language back
					if( apply_filters( 'wcfm_allow_wpml_email_translation', true ) ) {
						do_action('wpml_restore_language_from_email');
					}
				}
			}
		}
		
		do_action( 'after_wcfm_notification', $author_id, $message_to, $author_is_admin, $author_is_vendor, $wcfm_messages, $wcfm_messages_type );
	}

	/**
	 * Prepares the direct message subject
	 * 
	 * @param array $args
	 * @return string $subject
	 */
	protected function wcfm_direct_message_subject( $args ) {
		$defaults = [
			'message_type' 			=> '',
			'wcfm_messages_type' 	=> '',
		];

		$args = wp_parse_args( $args, $defaults );

		$notificaton_mail_subject = "[{site_name}] " . apply_filters( 'wcfm_notification_mail_subject', __( "Notification", "wc-frontend-manager" ) . " - " . $args['message_type'], $args['wcfm_messages_type'] );
		$subject = str_replace( '{site_name}', get_bloginfo( 'name' ), $notificaton_mail_subject );
		$subject = apply_filters( 'wcfm_email_subject_wrapper', $subject );

		return $subject;
	}

	/**
	 * Prepares the direct message body
	 * 
	 * @param array $args
	 * @return string $message
	 */
	protected function wcfm_direct_message_body( $args ) {
		$defaults = [
			'message_to' 			=> null,
			'message_type' 			=> '',
			'wcfm_messages'			=> '',
			'wcfm_messages_type' 	=> '',
			'raw_message' 			=> [],
		];

		$args = wp_parse_args( $args, $defaults );

		if( ( $args['message_to'] != -1 ) &&  ( $args['message_to'] != '-1' ) && apply_filters( 'wcfm_is_pref_notification', true ) && ( !wcfm_is_vendor( $args['message_to'] ) || ( wcfm_is_vendor( $args['message_to'] )  && wcfm_vendor_has_capability( $args['message_to'], 'notification' ) ) ) ) {
			$notification_mail_body =  '<br/>' .
				__( 'Hi', 'wc-frontend-manager' ) .
				',<br/><br/>' . 
				__( 'You have received a new notification:', 'wc-frontend-manager' ) .
				'<br/><br/>' .
				'{notification_message}' .
				'<br/><br/>' .
				sprintf( __( 'Check more details %shere%s.', 'wc-frontend-manager' ), '<a href="{notification_url}">', '</a>' ) .
				'<br /><br/>' . __( 'Thank You', 'wc-frontend-manager' ) .
				'<br/><br/>';
		} else {
			$notification_mail_body =  '<br/>' .
				__( 'Hi', 'wc-frontend-manager' ) .
				',<br/><br/>' . 
				__( 'You have received a new notification:', 'wc-frontend-manager' ) .
				'<br/><br/>' .
				'{notification_message}' .
				'<br /><br/>' . __( 'Thank You', 'wc-frontend-manager' ) .
				'<br/><br/>';
		}

		if( apply_filters( 'wcfm_allow_wpml_email_translation', true ) && $args['raw_message'] ) {
			$args['wcfm_messages'] = $this->wcfm_direct_message_raw_to_l10n( $args['raw_message'] );
		}

		$notification_mail_body = apply_filters( 'wcfm_notification_mail_content', $notification_mail_body, $args['wcfm_messages_type'], $args['wcfm_messages'] );

		$message = str_replace( '{notification_message}', $args['wcfm_messages'], $notification_mail_body );
		$message = str_replace( '{notification_url}', get_wcfm_messages_url(), $message );
		$message = apply_filters( 'wcfm_email_content_wrapper', $message, __( "Notification", "wc-frontend-manager" ) . " - " . $args['message_type'] );
		$message = wcfm_removeslashes( $message );

		return $message;
	}

	/**
	 * Translates default string to locale language string
	 * 
	 * @param array $raw_message
	 * @return string $wcfm_messages
	 */
	protected function wcfm_direct_message_raw_to_l10n( $raw_message ) {
		$wcfm_messages = '';

		if( !is_array( $raw_message ) || empty( $raw_message ) ) {
			return '';
		}

		$defaults = [
			'hook'    	=> [
				'name'  => '',
				'args'  => []
			],
			'l10n'	=> [
				'text' 		=> '',
				'domain'    => '',
				'wrapper'	=> [
					'function' 	=> '',
					'args' 		=> []
				]
			]
		];

		$raw_message = wp_parse_args( $raw_message, $defaults );

		if( 
			isset( $raw_message['l10n'] ) && 
			isset( $raw_message['l10n']['text'] ) &&
			isset( $raw_message['l10n']['domain'] )
		) {
			$wcfm_messages = __( $raw_message['l10n']['text'], "{$raw_message['l10n']['domain']}" );

			if ( 
				isset( $raw_message['l10n']['wrapper'] ) &&
				isset( $raw_message['l10n']['wrapper']['function'] ) &&
				!empty( $raw_message['l10n']['wrapper']['function'] )
			) {
				$wcfm_messages = call_user_func_array( "{$raw_message['l10n']['wrapper']['function']}", array_merge(
					[ $wcfm_messages ],
					$raw_message['l10n']['wrapper']['args']
				) );
			}

			if ( 
				isset( $raw_message['hook'] ) && 
				isset( $raw_message['hook']['name'] ) &&
				!empty( $raw_message['hook']['name'] ) 
			) {
				$wcfm_messages = call_user_func_array( 'apply_filters', array_merge( 
					array( 
						"{$raw_message['hook']['name']}",
						$wcfm_messages
					), 
					$raw_message['hook']['args']
				) );
			}
		}

		return $wcfm_messages;
	}
  
  /**
   * WCFM New message notification
   *
   * @since 3.3.4
   */
  function wcfm_message_notification() {
  	global $WCFM, $wpdb;
  	
  	if ( ! check_ajax_referer( 'wcfm_ajax_nonce', 'wcfm_ajax_nonce', false ) ) {
			echo '{"status": false, "message": "' . esc_html__( 'Invalid nonce! Refresh your page and try again.', 'wc-frontend-manager' ) . '"}';
			wp_die();
		}
  	
  	if ( !current_user_can( 'manage_woocommerce' ) && !current_user_can( 'wcfm_vendor' ) && !current_user_can( 'seller' ) && !current_user_can( 'vendor' ) && !current_user_can( 'shop_staff' ) && !current_user_can( 'wcfm_delivery_boy' ) && !current_user_can( 'wcfm_affiliate' ) ) {
  		//wp_send_json_error( esc_html__( 'You don&#8217;t have permission to do this.', 'woocommerce' ) );
			wp_die();
		}
  	
  	if( isset( $_POST['limit'] ) && $_POST['limit'] ) {
  		$limit = absint( $_POST['limit'] );
  		
  		$message_to = apply_filters( 'wcfm_message_author', get_current_user_id() );
  		
  		if( $message_to && wcfm_is_allow_wcfm() ) {
				$sql = 'SELECT wcfm_messages.* FROM ' . $wpdb->prefix . 'wcfm_messages AS wcfm_messages';
				$sql .= ' WHERE 1=1';
				
				if( wcfm_is_vendor() || ( function_exists( 'wcfm_is_delivery_boy' ) && wcfm_is_delivery_boy() ) || ( function_exists( 'wcfm_is_affiliate' ) && wcfm_is_affiliate() ) ) {
					//$vendor_filter = " AND `author_is_admin` = 1";
					$vendor_filter = " AND ( `author_id` = %d OR `message_to` = -1 OR `message_to` = %d )";
					$sql .= $vendor_filter;
					$sql = $wpdb->prepare( $sql, $message_to, $message_to );
				} else {
					$sql .= " AND `author_id` != -1";
				}
				
				$message_status_filter = " AND NOT EXISTS (SELECT * FROM {$wpdb->prefix}wcfm_messages_modifier as wcfm_messages_modifier_2 WHERE wcfm_messages.ID = wcfm_messages_modifier_2.message AND wcfm_messages_modifier_2.read_by=%d)";
		
				$sql .= $message_status_filter;
				
				$sql .= " ORDER BY wcfm_messages.`ID` DESC";
		
				$sql .= " LIMIT %d";
		
				$sql .= " OFFSET 0";
				
				$wcfm_messages = $wpdb->get_results( $wpdb->prepare( $sql, $message_to, $limit ) );
			} else {
				$wcfm_messages = array();
			}
			
			$wcfm_messages_json_arr = '';
			
			if ( !empty( $wcfm_messages ) ) {
				foreach ( $wcfm_messages as $wcfm_message ) {
					$wcfm_messages_json_arr .=  '<div class="wcfm_notification_box">' . $this->get_wcfm_notification_icon( $wcfm_message->message_type ) . wcfm_removeslashes( htmlspecialchars_decode( $wcfm_message->message ) ) . '</div>';
				}
			}
			if( $wcfm_messages_json_arr ) $wcfm_messages_json_arr = '<div class="wcfm_notification_wrapper"><span class="wcfmfa fa-times-circle wcfm_notification_close"></span><div class="wcfm-clearfix"></div>' . $wcfm_messages_json_arr . '</div>';
			echo $wcfm_messages_json_arr;
  	}
  	
  	die;
  }
  
  /**
   * Handle Message mark as Read
   *
   * @since 2.3.4
   */
  function wcfm_messages_mark_read() {
  	global $WCFM, $wpdb, $_POST;
  	
  	if ( ! check_ajax_referer( 'wcfm_ajax_nonce', 'wcfm_ajax_nonce', false ) ) {
			echo '{"status": false, "message": "' . esc_html__( 'Invalid nonce! Refresh your page and try again.', 'wc-frontend-manager' ) . '"}';
			wp_die();
		}
  	
  	if ( !current_user_can( 'manage_woocommerce' ) && !current_user_can( 'wcfm_vendor' ) && !current_user_can( 'seller' ) && !current_user_can( 'vendor' ) && !current_user_can( 'shop_staff' ) && !current_user_can( 'wcfm_delivery_boy' ) && !current_user_can( 'wcfm_affiliate' ) ) {
  		wp_send_json_error( esc_html__( 'You don&#8217;t have permission to do this.', 'woocommerce' ) );
			wp_die();
		}
  	
  	$messageid = absint( $_POST['messageid'] );
  	$message_to = apply_filters( 'wcfm_message_author', get_current_user_id() );
  	$todate = date('Y-m-d H:i:s');
  	
  	$wcfm_read_message     = $wpdb->prepare( "INSERT into {$wpdb->prefix}wcfm_messages_modifier 
																(`message`, `is_read`, `read_by`, `read_on`)
																VALUES
																(%d, %d, %d, %s)",
																$messageid, 1, $message_to, $todate);
		$wpdb->query($wcfm_read_message);
		
		if( wcfm_is_vendor() || ( function_exists( 'wcfm_is_delivery_boy' ) && wcfm_is_delivery_boy() ) || ( function_exists( 'wcfm_is_affiliate' ) && wcfm_is_affiliate() ) ) {
			$cache_key = $this->cache_group . '-message-' . $message_to;
		} else {
			$cache_key = $this->cache_group . '-message-0';
		}
		delete_transient( $cache_key );
		
		die;
  }
  
  /**
   * Handle Message Bulk Mark as Read
   *
   * @since 3.4.2
   */
  function wcfm_messages_bulk_mark_read() {
  	global $WCFM, $wpdb, $_POST;
  	
  	if ( ! check_ajax_referer( 'wcfm_ajax_nonce', 'wcfm_ajax_nonce', false ) ) {
			echo '{"status": false, "message": "' . esc_html__( 'Invalid nonce! Refresh your page and try again.', 'wc-frontend-manager' ) . '"}';
			wp_die();
		}
  	
  	if ( !current_user_can( 'manage_woocommerce' ) && !current_user_can( 'wcfm_vendor' ) && !current_user_can( 'seller' ) && !current_user_can( 'vendor' ) && !current_user_can( 'shop_staff' ) && !current_user_can( 'wcfm_delivery_boy' ) && !current_user_can( 'wcfm_affiliate' ) ) {
  		wp_send_json_error( esc_html__( 'You don&#8217;t have permission to do this.', 'woocommerce' ) );
			wp_die();
		}
  	
  	if( isset($_POST['selected_messages']) ) {
			$selected_messages = wc_clean( $_POST['selected_messages'] );
			if( is_array( $selected_messages ) && !empty( $selected_messages ) ) {
				$message_to = apply_filters( 'wcfm_message_author', get_current_user_id() );
				foreach( $selected_messages as $messageid ) {
					$todate = date('Y-m-d H:i:s');
					
					$wcfm_read_message     = $wpdb->prepare( "INSERT into {$wpdb->prefix}wcfm_messages_modifier 
																			(`message`, `is_read`, `read_by`, `read_on`)
																			VALUES
																			(%d, %d, %d, %s)",
																			$messageid, 1, $message_to, $todate);
					$wpdb->query($wcfm_read_message);
				}
				
				if( wcfm_is_vendor() || ( function_exists( 'wcfm_is_delivery_boy' ) && wcfm_is_delivery_boy() ) || ( function_exists( 'wcfm_is_affiliate' ) && wcfm_is_affiliate() ) ) {
					$cache_key = $this->cache_group . '-message-' . $message_to;
				} else {
					$cache_key = $this->cache_group . '-message-0';
				}
				delete_transient( $cache_key );
			}
		}
		
		echo '{ "status": true }';
		die;
  }
  
  /**
   * Handle Message Delete
   *
   * @since 3.4.5
   */
  function wcfm_messages_delete() {
  	global $WCFM, $wpdb, $_POST;
  	
  	if ( ! check_ajax_referer( 'wcfm_ajax_nonce', 'wcfm_ajax_nonce', false ) ) {
			echo '{"status": false, "message": "' . esc_html__( 'Invalid nonce! Refresh your page and try again.', 'wc-frontend-manager' ) . '"}';
			wp_die();
		}
  	
  	if ( !current_user_can( 'manage_woocommerce' ) && !current_user_can( 'wcfm_vendor' ) && !current_user_can( 'seller' ) && !current_user_can( 'vendor' ) && !current_user_can( 'shop_staff' ) && !current_user_can( 'wcfm_delivery_boy' ) && !current_user_can( 'wcfm_affiliate' ) ) {
  		wp_send_json_error( esc_html__( 'You don&#8217;t have permission to do this.', 'woocommerce' ) );
			wp_die();
		}
  	
  	$messageid = absint( $_POST['messageid'] );
  	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wcfm_messages WHERE `ID` = %d", $messageid ) );
  	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wcfm_messages_modifier WHERE `message` = %d", $messageid ) );
  	
  	if( wcfm_is_vendor() || ( function_exists( 'wcfm_is_delivery_boy' ) && wcfm_is_delivery_boy() ) || ( function_exists( 'wcfm_is_affiliate' ) && wcfm_is_affiliate() ) ) {
			$message_to = apply_filters( 'wcfm_message_author', get_current_user_id() );
			$cache_key = $this->cache_group . '-message-' . $message_to;
		} else {
			$cache_key = $this->cache_group . '-message-0';
		}
		delete_transient( $cache_key );
  	
  	echo '{ "status": true }';
		die;
  }
  
  /**
   * Handle Message Bulk Delete
   *
   * @since 5.1.2
   */
  function wcfm_messages_bulk_mark_delete() {
  	global $WCFM, $wpdb, $_POST;
  	
  	if ( ! check_ajax_referer( 'wcfm_ajax_nonce', 'wcfm_ajax_nonce', false ) ) {
			echo '{"status": false, "message": "' . esc_html__( 'Invalid nonce! Refresh your page and try again.', 'wc-frontend-manager' ) . '"}';
			wp_die();
		}
  	
  	if ( !current_user_can( 'manage_woocommerce' ) && !current_user_can( 'wcfm_vendor' ) && !current_user_can( 'seller' ) && !current_user_can( 'vendor' ) && !current_user_can( 'shop_staff' ) && !current_user_can( 'wcfm_delivery_boy' ) && !current_user_can( 'wcfm_affiliate' ) ) {
  		wp_send_json_error( esc_html__( 'You don&#8217;t have permission to do this.', 'woocommerce' ) );
			wp_die();
		}
  	
  	if( isset($_POST['selected_messages']) ) {
			$selected_messages = wc_clean( $_POST['selected_messages'] );
			if( is_array( $selected_messages ) && !empty( $selected_messages ) ) {
				foreach( $selected_messages as $messageid ) {
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wcfm_messages WHERE `ID` = %d", $messageid ) );
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wcfm_messages_modifier WHERE `message` = %d", $messageid ) );
				}
				
				if( wcfm_is_vendor() || ( function_exists( 'wcfm_is_delivery_boy' ) && wcfm_is_delivery_boy() ) || ( function_exists( 'wcfm_is_affiliate' ) && wcfm_is_affiliate() ) ) {
					$message_to = apply_filters( 'wcfm_message_author', get_current_user_id() );
					$cache_key = $this->cache_group . '-message-' . $message_to;
				} else {
					$cache_key = $this->cache_group . '-message-0';
				}
				delete_transient( $cache_key );
			}
		}
  	echo '{ "status": true }';
		die;
  }
  
  public function get_wcfm_notification_icon( $type ) {
  	$notification_icon = '';
  	$message_types = get_wcfm_message_types();
  	$message_type = isset( $message_types[$type] ) ? $message_types[$type] : ucfirst($type);
  	$message_type_class = 'wcfmfa wcfm-message-type-icon text_tip wcfm-message-type-' . $type . ' ';
  	
  	switch( $type ) {
  		case 'order':
  			$message_type_class .= 'fa-shopping-cart';
  		break;
  		
  		case 'direct':
  			$message_type_class .= 'fa-telegram-plane fab';
  		break;
  		
  		case 'notice':
  			$message_type_class .= 'fa-bullhorn';
  		break;
  		
  		case 'review':
  			$message_type_class .= 'fa-comment-alt';
  		break;
  		
  		case 'product_review':
  		case 'product_lowstk':
  		case 'product_outofstk':
  			$message_type_class .= 'fa-cube';
  		break;
  		
  		case 'status-update':
  			$message_type_class .= 'fa-edit';
  		break;
  		
  		case 'withdraw-request':
  			$message_type_class .= 'fa-money-bill-alt';
  		break;
  		
  		case 'refund-request':
  			$message_type_class .= 'fa-retweet';
  		break;
  		
  		case 'new_product':
  			$message_type_class .= 'fa-cube';
  		break;
  		
  		case 'booking':
  			$message_type_class .= 'fa-calendar-check';
  		break;
  		
  		case 'appointment':
  			$message_type_class .= 'fa-clock';
  		break;
  		
  		case 'enquiry':
  			$message_type_class .= 'fa-question-circle fa-question-circle';
  		break;
  		
  		case 'support':
  			$message_type_class .= 'fa-life-ring';
  		break;
  		
  		case 'verification':
  			$message_type_class .= 'fa-angellist fab';
  		break;
  		
  		case 'registration':
  		case 'membership':
  			$message_type_class .= 'fa-user-alt';
  		break;
  		
  		case 'membership-cancel':
  		case 'membership-expired':
  			$message_type_class .= 'fa-user-times';
  		break;
  		
  		case 'membership-reminder':
  			$message_type_class .= 'fa-clock';
  		break;
  		
  		case 'vendor-disable':
  		case 'vendor-enable':
  			$message_type_class .= 'fa-user-alt';
  		break;
  		
  		case 'vendor_approval':
  			$message_type_class .= 'fa-user-plus';
  		break;
  		
  		case 'pay_for_product':
  			$message_type_class .= 'fa-cube';
  		break;
  		
  		case 'new_taxonomy_term':
  			$message_type_class .= 'fa-tags';
  		break;
  		
  		case 'new_customer':
  			$message_type_class .= 'fa-user-circle';
  		break;
  		
  		case 'new_staff':
  			$message_type_class .= 'fa-user';
  		break;
  		
  		case 'new_follower':
  			$message_type_class .= 'fa-user';
  		break;
  		
  		case 'new_delivery_boy':
  			$message_type_class .= 'fa-user';
  		break;
  		
  		case 'new_affiliate':
  		case 'affiliate_approval':
  			$message_type_class .= 'fa-user-friends';
  		break;
  		
  		case 'affiliate_commission':
  			$message_type_class .= 'fa-percent';
  		break;
  		
  		case 'affiliate_commission_paid':
  			$message_type_class .= 'fa-money-bill-alt';
  		break;
  		
  		case 'affiliate-disable':
  		case 'affiliate-enable':
  			$message_type_class .= 'fa-user-alt';
  		break;
  		
  		case 'shipment_tracking':
  		case 'shipment_received':
  		case 'delivery_boy_assign':
  		case 'delivery_complete':
  			$message_type_class .= 'fa-truck';
  		break;
  		
  		default:
  			$message_type_class = 'fa-cart';
  		break;
  	}
  	
  	$message_type_class = apply_filters( 'wcfm_message_type_class', $message_type_class, $type );
  	
  	$notification_icon = '<span class="' . $message_type_class . '" data-tip="' . $message_type . '"></span>';
  	
  	return $notification_icon;
  }
}
<?php










class WCFM_Enquiry_Form_Controller {

	public function __construct() {
		global $WCFM;

		$this->processing();
	}

	public function processing() {
		global $WCFM, $wpdb;

		$wcfm_enquiry_tab_form_data = array();
		parse_str( $_POST['wcfm_enquiry_tab_form'], $wcfm_enquiry_tab_form_data );

		$wcfm_enquiry_messages = get_wcfm_enquiry_manage_messages();
		$has_error             = false;

		 
		if ( function_exists( 'gglcptch_init' ) ) {
			if ( isset( $wcfm_enquiry_tab_form_data['g-recaptcha-response'] ) && ! empty( $wcfm_enquiry_tab_form_data['g-recaptcha-response'] ) ) {
				$_POST['g-recaptcha-response'] = $wcfm_enquiry_tab_form_data['g-recaptcha-response'];
			}
			$check_result = apply_filters( 'gglcptch_verify_recaptcha', true, 'string', 'wcfm_enquiry_form' );
			if ( true === $check_result ) {
					 
			} else {
				echo '{"status": false, "message": "' . $check_result . '"}';
				die;
			}
		} elseif ( class_exists( 'anr_captcha_class' ) && function_exists( 'anr_captcha_form_field' ) ) {
			$check_result = anr_verify_captcha( $wcfm_enquiry_tab_form_data['g-recaptcha-response'] );
			if ( true === $check_result ) {
					 
			} else {
				echo '{"status": false, "message": "' . esc_html( __( 'Captcha failed, please try again.', 'wc-frontend-manager' ) ) . '"}';
				die;
			}
		}

		if ( ! defined( 'WCFM_REST_API_CALL' ) ) {
			if ( isset( $wcfm_enquiry_tab_form_data['wcfm_nonce'] ) && ! empty( $wcfm_enquiry_tab_form_data['wcfm_nonce'] ) ) {
				if ( ! wp_verify_nonce( $wcfm_enquiry_tab_form_data['wcfm_nonce'], 'wcfm_enquiry' ) ) {
					echo '{"status": false, "message": "' . esc_html( __( 'Invalid nonce! Refresh your page and try again.', 'wc-frontend-manager' ) ) . '"}';
					die;
				}
			}
		}

		if ( isset( $wcfm_enquiry_tab_form_data['enquiry'] ) && ! empty( $wcfm_enquiry_tab_form_data['enquiry'] ) ) {

			$enquiry = apply_filters( 'wcfm_editor_content_before_save', wcfm_stripe_newline( strip_tags( wp_filter_post_kses( $wcfm_enquiry_tab_form_data['enquiry'] ) ) ) );
			$reply   = '';

			$author_id  = 0;
			$product_id = 0;
			if ( isset( $wcfm_enquiry_tab_form_data['product_id'] ) && ! empty( $wcfm_enquiry_tab_form_data['product_id'] ) ) {
				$product_id = absint( $wcfm_enquiry_tab_form_data['product_id'] );
				if ( $product_id ) {
					$product_post = get_post( $product_id );
					$author_id    = $product_post->post_author;
				}
			}

			$vendor_id = 0;
			if ( isset( $wcfm_enquiry_tab_form_data['vendor_id'] ) && ! empty( $wcfm_enquiry_tab_form_data['vendor_id'] ) ) {
				$vendor_id = absint( $wcfm_enquiry_tab_form_data['vendor_id'] );
				$author_id = $vendor_id;
			} elseif ( $author_id && wcfm_is_vendor( $author_id ) ) {
				$vendor_id = $author_id;
			}

			if ( ! is_user_logged_in() ) {
				$customer_id    = 0;
				$customer_name  = esc_sql( wc_clean( $wcfm_enquiry_tab_form_data['customer_name'] ) );
				$customer_email = sanitize_email( $wcfm_enquiry_tab_form_data['customer_email'] );
			} else {
				$customer_id  = get_current_user_id();
				$userdata     = get_userdata( $customer_id );
				$first_name   = $userdata->first_name;
				$last_name    = $userdata->last_name;
				$display_name = $userdata->display_name;
				if ( $first_name ) {
					$customer_name = esc_sql( $first_name . ' ' . $last_name );
				} else {
					$customer_name = esc_sql( $display_name );
				}
				$customer_email = $userdata->user_email;
			}

			$enquiry      = apply_filters( 'wcfm_enquiry_content', $enquiry, $product_id, $vendor_id, $customer_id );
			$enquiry_mail = $enquiry;
			$enquiry      = esc_sql( $enquiry );

			if ( ! defined( 'DOING_WCFM_EMAIL' ) ) {
				define( 'DOING_WCFM_EMAIL', true );
			}

			$reply_by     = 0;
			$is_private   = 1;
			$current_time = date( 'Y-m-d H:i:s', current_time( 'timestamp', 0 ) );

			$wcfm_create_enquiry = $wpdb->prepare(
				"INSERT into {$wpdb->prefix}wcfm_enquiries 
																(`enquiry`, `reply`, `author_id`, `product_id`, `vendor_id`, `customer_id`, `customer_name`, `customer_email`, `reply_by`, `is_private`, `posted`, `replied`)
																VALUES
																(%s, %s, %d, %d, %d, %d, %s, %s, %d, %d, %s, %s)",
				$enquiry,
				$reply,
				$author_id,
				$product_id,
				$vendor_id,
				$customer_id,
				$customer_name,
				$customer_email,
				$reply_by,
				$is_private,
				$current_time,
				$current_time
			);

				$wpdb->query( $wcfm_create_enquiry );
				$enquiry_id = $wpdb->insert_id;

			if ( $enquiry_id ) {

				$additional_info            = '';
				$wcfm_options               = $WCFM->wcfm_options;
				$wcfm_enquiry_custom_fields = isset( $wcfm_options['wcfm_enquiry_custom_fields'] ) ? $wcfm_options['wcfm_enquiry_custom_fields'] : array();
				$wcfm_enquiry_meta_values   = array();
				if ( isset( $wcfm_enquiry_tab_form_data['wcfm_enquiry_meta'] ) ) {
					$wcfm_enquiry_meta_values = wc_clean( $wcfm_enquiry_tab_form_data['wcfm_enquiry_meta'] );
				}
				if ( ! empty( $wcfm_enquiry_custom_fields ) && ! empty( $wcfm_enquiry_meta_values ) ) {
					foreach ( $wcfm_enquiry_custom_fields as $wcfm_enquiry_custom_field ) {
						if ( ! isset( $wcfm_enquiry_custom_field['enable'] ) ) {
							continue;
						}
						if ( ! $wcfm_enquiry_custom_field['label'] ) {
							continue;
						}
						$wcfm_enquiry_custom_field['name'] = sanitize_title( $wcfm_enquiry_custom_field['label'] );
						if ( isset( $wcfm_enquiry_meta_values[ $wcfm_enquiry_custom_field['name'] ] ) ) {
							$wcfm_create_enquiry_meta = $wpdb->prepare(
								"INSERT into {$wpdb->prefix}wcfm_enquiries_meta 
																							(`enquiry_id`, `key`, `value`)
																							VALUES
																							(%d, %s, %s)",
								$enquiry_id,
								$wcfm_enquiry_custom_field['label'],
								$wcfm_enquiry_meta_values[ $wcfm_enquiry_custom_field['name'] ]
							);
							$wpdb->query( $wcfm_create_enquiry_meta );
							$additional_info .= '<tr><td>' . __( $wcfm_enquiry_custom_field['label'], 'wc-frontend-manager' ) . '</td><td>' . $wcfm_enquiry_meta_values[ $wcfm_enquiry_custom_field['name'] ] . '</td>';
						}
					}
				}
				if ( $additional_info ) {
					$additional_info = '<u>' . __( 'Additional Info', 'wc-frontend-manager' ) . ':-</u><table border="1">' . $additional_info . '</table><br /><br />';
				}

				$enquiry_for_label = __( 'Store', 'wc-frontend-manager' );
				if ( $vendor_id ) {
					$enquiry_for_label = wcfm_get_vendor_store_name( $vendor_id ) . ' ' . __( 'Store', 'wc-frontend-manager' );
				}
				if ( $product_id ) {
					$enquiry_for_label = get_the_title( $product_id );
				}

				 
				 
				 

				



































				 
				if ( apply_filters( 'wcfm_is_allow_notification_email', true, 'enquiry', 0 ) ) {
					$wcfm_email = WC()->mailer()->emails['WCFM_Email_New_enquiry'];
					if ( $wcfm_email ) {
						$wcfm_email->trigger(
							array(
								'enquiry_id'      => $enquiry_id,
								'product_id'      => $product_id,
								'vendor_id'       => $vendor_id,
								'enquiry'         => $enquiry_mail,
								'additional_info' => $additional_info,
								'customer_name'   => $customer_name,
								'customer_email'  => $customer_email,
								'is_admin'        => true,
							)
						);
					}
				}

				 
				if ( apply_filters( 'wcfm_is_allow_notification_message', true, 'enquiry', 0 ) ) {
					$wcfm_messages = sprintf( __( 'New Inquiry <b>%1$s</b> received for <b>%2$s</b>', 'wc-frontend-manager' ), '<a target="_blank" class="wcfm_dashboard_item_title" href="' . esc_url( get_wcfm_enquiry_manage_url( $enquiry_id ) ) . '">#' . sprintf( '%06u', $enquiry_id ) . '</a>', $enquiry_for_label );
					$WCFM->wcfm_notification->wcfm_send_direct_message( -2, 0, 1, 0, $wcfm_messages, 'enquiry', false );
				}

				 
				if ( wcfm_is_marketplace() ) {
					if ( $vendor_id ) {
						$is_allow_enquiry = wcfm_vendor_has_capability( $vendor_id, 'enquiry' );
						if ( $is_allow_enquiry && apply_filters( 'wcfm_is_allow_enquiry_vendor_notification', true ) ) {
							$vendor_email = wcfm_get_vendor_store_email_by_vendor( $vendor_id );
							if ( $vendor_email && apply_filters( 'wcfm_is_allow_notification_email', true, 'enquiry', $vendor_id ) ) {
								$wcfm_email = WC()->mailer()->emails['WCFM_Email_New_enquiry'];
								if ( $wcfm_email ) {
									$wcfm_email->trigger(
										array(
											'enquiry_id' => $enquiry_id,
											'product_id' => $product_id,
											'vendor_id'  => $vendor_id,
											'enquiry'    => $enquiry_mail,
											'additional_info' => $additional_info,
											'customer_name' => $customer_name,
											'customer_email' => $customer_email,
											'is_admin'   => false,
										)
									);
								}
								




							}

							 
							if ( apply_filters( 'wcfm_is_allow_notification_message', true, 'enquiry', $vendor_id ) ) {
								$wcfm_messages = sprintf( __( 'New Inquiry <b>%1$s</b> received for <b>%2$s</b>', 'wc-frontend-manager' ), '<a target="_blank" class="wcfm_dashboard_item_title" href="' . esc_url( get_wcfm_enquiry_manage_url( $enquiry_id ) ) . '">#' . sprintf( '%06u', $enquiry_id ) . '</a>', $enquiry_for_label );
								$WCFM->wcfm_notification->wcfm_send_direct_message( -1, $vendor_id, 1, 0, $wcfm_messages, 'enquiry', false );
							}
						}

						 
						$cache_key = 'wcfm-notification-enquiry-' . $vendor_id;
						delete_transient( $cache_key );
					}
				}

				 
				$cache_key = 'wcfm-notification-enquiry-0';
				delete_transient( $cache_key );

				do_action( 'wcfm_after_enquiry_submit', $enquiry_id, $customer_id, $product_id, $vendor_id, $enquiry, $wcfm_enquiry_tab_form_data );
			}

				echo '{"status": true, "message": "' . esc_html( $wcfm_enquiry_messages['enquiry_saved'] ) . '"}';
		} else {
			echo '{"status": false, "message": "' . esc_html( $wcfm_enquiry_messages['no_enquiry'] ) . '"}';
		}

		die;
	}
}

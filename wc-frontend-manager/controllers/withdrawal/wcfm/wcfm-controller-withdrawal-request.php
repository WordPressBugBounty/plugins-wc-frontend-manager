<?php










class WCFM_Withdrawal_Request_Controller {

	public function __construct() {
		global $WCFM;

		$this->processing();
	}

	public function processing() {
		global $WCFM, $wpdb, $_POST, $WCFMmp;

		$wcfm_withdrawal_manage_form_data = array();
		parse_str( $_POST['wcfm_withdrawal_manage_form'], $wcfm_withdrawal_manage_form_data );

		$commissions = array();
		if ( isset( $wcfm_withdrawal_manage_form_data['commissions'] ) && ! empty( $wcfm_withdrawal_manage_form_data['commissions'] ) ) {
			$commissions = $wcfm_withdrawal_manage_form_data['commissions'];

			 
			$custom_validation_results = apply_filters( 'wcfm_form_custom_validation', $wcfm_withdrawal_manage_form_data, 'withdrawal_manage' );
			if ( isset( $custom_validation_results['has_error'] ) && ! empty( $custom_validation_results['has_error'] ) ) {
				$custom_validation_error = __( 'There has some error in submitted data.', 'wc-frontend-manager' );
				if ( isset( $custom_validation_results['message'] ) && ! empty( $custom_validation_results['message'] ) ) {
					$custom_validation_error = $custom_validation_results['message']; }
				echo '{"status": false, "message": "' . $custom_validation_error . '"}';
				die;
			}

			$vendor_id = $WCFMmp->vendor_id;

			 
			 
			if ( ! wcfm_user_can_perform_request( $vendor_id, 'withdrawal_request' ) ) {
				echo '{"status": false, "message": "' . __( 'You don&#8217;t have permission to do this.', 'woocommerce' ) . '"}';
				die;
			}

			$payment_method = $WCFMmp->wcfmmp_vendor->get_vendor_payment_method( $vendor_id );
			if ( $payment_method ) {
				if ( array_key_exists( $payment_method, $WCFMmp->wcfmmp_gateways->payment_gateways ) ) {
					$order_ids           = '';
					$commission_ids      = '';
					$total_commission    = 0;
					$withdraw_charges    = 0;
					$claimed_commissions = array();

					$requested_commissions = array();
					foreach ( (array) $commissions as $commission_id ) {
						if ( is_scalar( $commission_id ) && absint( $commission_id ) ) {
							$requested_commissions[] = absint( $commission_id );
						}
					}
					$requested_commissions = array_unique( $requested_commissions );

					 
					 
					 
					 
					$commission_infos = array();
					if ( ! empty( $requested_commissions ) ) {
						$withdrawal_thresold = $WCFMmp->wcfmmp_withdraw->get_withdrawal_thresold( $vendor_id );
						$id_placeholders     = implode( ', ', array_fill( 0, count( $requested_commissions ), '%d' ) );

						$sql  = 'SELECT ID, order_id, item_id, total_commission, withdraw_charges FROM ' . $wpdb->prefix . 'wcfm_marketplace_orders AS commission';
						$sql .= ' WHERE 1=1';
						$sql .= ' AND `vendor_id` = %d';
						$sql .= " AND commission.ID IN ( {$id_placeholders} )";
						$sql .= apply_filters( 'wcfm_order_status_condition', '', 'commission' );
						$sql .= " AND commission.withdraw_status IN ('pending', 'cancelled')";
						$sql .= " AND commission.refund_status != 'requested'";
						$sql .= ' AND `is_withdrawable` = 1 AND `is_auto_withdrawal` = 0 AND `is_refunded` = 0 AND `is_trashed` = 0';
						if ( $withdrawal_thresold ) {
							$sql .= $wpdb->prepare( ' AND commission.created <= NOW() - INTERVAL %s DAY', $withdrawal_thresold );
						}

						$commission_infos = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( array( $vendor_id ), $requested_commissions ) ) );
					}

					foreach ( $commission_infos as $commission_info ) {
						$order = wc_get_order( $commission_info->order_id );
						if ( ! is_a( $order, 'WC_Order' ) ) {
							continue;
						}

						try {
							$line_item = new WC_Order_Item_Product( absint( $commission_info->item_id ) );

							 
							if ( $refunded_qty = $order->get_qty_refunded_for_item( absint( $commission_info->item_id ) ) ) {
								$refunded_qty = $refunded_qty * -1;
								if ( $line_item->get_quantity() == $refunded_qty ) {
									continue;
								}
							}
						} catch ( Exception $e ) {
							continue;
						}

						if ( apply_filters( 'wcfm_is_show_commission_restrict_check', false, $commission_info->order_id, $commission_info ) ) {
							continue;
						}

						 
						 
						 
						$is_claimed = $wpdb->query(
							$wpdb->prepare(
								"UPDATE {$wpdb->prefix}wcfm_marketplace_orders SET withdraw_status = 'requested' WHERE ID = %d AND `vendor_id` = %d AND withdraw_status IN ('pending', 'cancelled')",
								$commission_info->ID,
								$vendor_id
							)
						);
						if ( ! $is_claimed ) {
							continue;
						}
						$claimed_commissions[] = $commission_info;

						if ( $commission_ids ) {
							$commission_ids .= ',';
						}
						$commission_ids .= $commission_info->ID;

						if ( $order_ids ) {
							$order_ids .= ',';
						}
						$order_ids .= $commission_info->order_id;

						$total_commission += (float) $commission_info->total_commission;
					}

					if ( empty( $claimed_commissions ) ) {
						echo '{"status": false, "message": "' . __( 'No commission selected for withdrawal', 'wc-frontend-manager' ) . '"}';
						die;
					}

					$no_of_commission = count( $claimed_commissions );

					 
					$withdraw_charges = $WCFMmp->wcfmmp_withdraw->calculate_withdrawal_charges( $total_commission, $vendor_id );
					if ( $withdraw_charges ) {
						$withdraw_charge_per_commission = (float) $withdraw_charges / $no_of_commission;
						foreach ( $claimed_commissions as $commission_info ) {
							$wpdb->update( "{$wpdb->prefix}wcfm_marketplace_orders", array( 'withdraw_charges' => round( $withdraw_charge_per_commission, 2 ) ), array( 'ID' => $commission_info->ID ), array( '%s' ), array( '%d' ) );
						}
					}

					$withdraw_request_id = $WCFMmp->wcfmmp_withdraw->wcfmmp_withdrawal_processed( $vendor_id, $order_ids, $commission_ids, $payment_method, 0, $total_commission, $withdraw_charges, 'requested', 'by_request' );

					if ( $withdraw_request_id && ! is_wp_error( $withdraw_request_id ) ) {

						 
						$is_auto_approve = $WCFMmp->wcfmmp_withdraw->is_withdrawal_auto_approve( $vendor_id );
						if ( $is_auto_approve ) {
							$payment_processesing_status = $WCFMmp->wcfmmp_withdraw->wcfmmp_withdrawal_payment_processesing( $withdraw_request_id, $vendor_id, $payment_method, $total_commission, $withdraw_charges );
							if ( $payment_processesing_status ) {
								echo '{"status": true, "message": "' . __( 'Withdrawal Request successfully processed.', 'wc-frontend-manager' ) . ': #' . sprintf( '%06u', $withdraw_request_id ) . '"}';
							} else {
								echo '{"status": false, "message": "' . __( 'Withdrawal Request processing failed, please contact Store Admin.', 'wc-frontend-manager' ) . ': #' . sprintf( '%06u', $withdraw_request_id ) . '"}';
							}
						} else {
							 
							$shop_name     = wcfm_get_vendor_store( absint( $vendor_id ) );
							$wcfm_messages = sprintf( __( 'Vendor <b>%1$s</b> has placed a Withdrawal Request #%2$s.', 'wc-frontend-manager' ), $shop_name, '<a target="_blank" class="wcfm_dashboard_item_title" href="' . add_query_arg( 'transaction_id', $withdraw_request_id, wcfm_withdrawal_requests_url() ) . '">' . sprintf( '%06u', $withdraw_request_id ) . '</a>' );

							$raw_message = array(
								'l10n' => array(
									'text'    => 'Vendor <b>%s</b> has placed a Withdrawal Request #%s.',
									'domain'  => 'wc-frontend-manager',
									'wrapper' => array(
										'function' => 'sprintf',
										'args'     => array(
											$shop_name,
											'<a target="_blank" class="wcfm_dashboard_item_title" href="' . add_query_arg( 'transaction_id', $withdraw_request_id, wcfm_withdrawal_requests_url() ) . '">' . sprintf( '%06u', $withdraw_request_id ) . '</a>',
										),
									),
								),
							);

							$WCFM->wcfm_notification->wcfm_send_direct_message( $vendor_id, 0, 0, 1, $wcfm_messages, 'withdraw-request', true, $raw_message );
							echo '{"status": true, "message": "' . __( 'Your withdrawal request successfully sent.', 'wc-frontend-manager' ) . ': #' . sprintf( '%06u', $withdraw_request_id ) . '"}';
						}

						do_action( 'wcfmmp_withdrawal_request_submited', $withdraw_request_id, $vendor_id );
					} else {
						 
						foreach ( $claimed_commissions as $commission_info ) {
							$wpdb->update(
								"{$wpdb->prefix}wcfm_marketplace_orders",
								array(
									'withdraw_status'  => 'pending',
									'withdraw_charges' => $commission_info->withdraw_charges,
								),
								array( 'ID' => $commission_info->ID ),
								array( '%s', '%s' ),
								array( '%d' )
							);
						}
						echo '{"status": false, "message": "' . __( 'Your withdrawal request failed, please try after sometime.', 'wc-frontend-manager' ) . '"}';
					}
				} else {
					echo '{"status": false, "message": "' . __( 'No payment method selected for withdrawal commission', 'wc-frontend-manager' ) . '"}';
				}
			} else {
				echo '{"status": false, "message": "' . __( 'No payment method selected for withdrawal commission', 'wc-frontend-manager' ) . '"}';
			}
		} else {
			echo '{"status": false, "message": "' . __( 'No commission selected for withdrawal', 'wc-frontend-manager' ) . '"}';
		}

		die;
	}
}

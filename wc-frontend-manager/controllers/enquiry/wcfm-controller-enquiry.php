<?php

/**
 * WCFM plugin controllers
 *
 * Plugin Enquiry Controller
 *
 * @author 		WC Lovers
 * @package 	wcfm/controllers/enquiry
 * @version   3.0.6
 */

class WCFM_Enquiry_Controller {

	public function __construct() {
		global $WCFM;

		if (!defined('WCFM_REST_API_CALL')) {
			$this->processing();
		}
	}

	public function processing() {
		global $WCFM, $wpdb, $_POST;

		$length = absint($_POST['length']);
		$offset = absint($_POST['start']);

		$vendor_id = apply_filters('wcfm_current_vendor_id', get_current_user_id());

		$enquiry_product = '';
		if (!empty($_POST['enquiry_product'])) {
			$enquiry_product = absint($_POST['enquiry_product']);
		}

		$enquiry_vendor = '';
		if (!empty($_POST['enquiry_vendor'])) {
			$enquiry_vendor = absint($_POST['enquiry_vendor']);
		}

		$is_private = '';
		if (!empty($_POST['is_private'])) {
			$is_private = wc_clean($_POST['is_private']);
		}

		$time_filter = '';

		$the_orderby = !empty($_POST['orderby']) ? sanitize_sql_orderby($_POST['orderby']) : 'ID';
		$the_order   = (!empty($_POST['order']) && 'asc' === wc_clean($_POST['order'])) ? 'ASC' : 'DESC';

		$items_per_page = $length;

		$sql = "SELECT count(ID) FROM {$wpdb->prefix}wcfm_enquiries AS commission";
		$sql .= " WHERE 1 = 1";

		if ($enquiry_product) {
			$sql .= " AND `product_id` = %d";
			$sql = $wpdb->prepare($sql, $enquiry_product);
		}

		if ($is_private) {
			$sql .= " AND `is_private` = %d";
			$sql = $wpdb->prepare($sql, $is_private);
		}

		if (wcfm_is_vendor()) {
			$sql .= " AND `vendor_id` = %d";
			$sql = $wpdb->prepare($sql, $vendor_id);
		} elseif (!empty($_POST['enquiry_vendor'])) {
			$sql .= " AND `vendor_id` = %d";
			$sql = $wpdb->prepare($sql, $enquiry_vendor);
		}
		if (!empty($_POST['filter_date_form']) && !empty($_POST['filter_date_to'])) {
			$start_date = date('Y-m-d', strtotime(wc_clean($_POST['filter_date_form'])));
			$end_date = date('Y-m-d', strtotime(wc_clean($_POST['filter_date_to'])));
			$time_filter = " AND DATE( commission.posted ) BETWEEN %s AND %s";
			$sql .= $time_filter;
			$sql = $wpdb->prepare($sql, $start_date, $end_date);
		}
		$sql = apply_filters('wcfm_enquery_count_query', $sql);

		$total_enquiries = $wpdb->get_var($sql);

		$enquiry_query = "SELECT * FROM {$wpdb->prefix}wcfm_enquiries AS commission";
		$enquiry_query .= " WHERE 1 = 1";

		if ($enquiry_product) {
			$enquiry_query .= " AND `product_id` = %d";
			$enquiry_query = $wpdb->prepare($enquiry_query, $enquiry_product);
		}

		if ($is_private) {
			$enquiry_query .= " AND `is_private` = %d";
			$enquiry_query = $wpdb->prepare($enquiry_query, $is_private);
		}

		if (wcfm_is_vendor()) {
			$enquiry_query .= " AND `vendor_id` = %d";
			$enquiry_query = $wpdb->prepare($enquiry_query, $vendor_id);
		} elseif (!empty($_POST['enquiry_vendor'])) {
			$enquiry_query .= " AND `vendor_id` = %d";
			$enquiry_query = $wpdb->prepare($enquiry_query, $enquiry_vendor);
		}
		if (!empty($_POST['filter_date_form']) && !empty($_POST['filter_date_to'])) {
			$enquiry_query .= $time_filter;
			$enquiry_query = $wpdb->prepare($enquiry_query, $start_date, $end_date);
		}
		$enquiry_query = apply_filters('wcfm_enquery_list_query', $enquiry_query);

		$enquiry_query .= " ORDER BY commission.`{$the_orderby}` {$the_order} LIMIT %d OFFSET %d";
		$enquiry_query = $wpdb->prepare($enquiry_query, [
			$items_per_page,
			$offset
		]);

		$wcfm_enquirys_array = $wpdb->get_results($enquiry_query);

		if (defined('WCFM_REST_API_CALL')) {
			return $wcfm_enquirys_array;
		}

		// Generate Enquiry DataTable JSON
		$datatable_json = [
			'draw'				=> (int) wc_clean($_POST['draw']),
			'recordsTotal'		=> (int) $total_enquiries,
			'recordsFiltered'	=> (int) $total_enquiries,
			'data'				=> []
		];

		if (!empty($wcfm_enquirys_array)) {
			$index = 0;
			foreach ($wcfm_enquirys_array as $wcfm_enquirys_single) {
				$datatable_json['data'][$index][] =  '<input type="checkbox" class="wcfm-checkbox bulk_action_checkbox_single" name="bulk_action_checkbox[]" value="' . $wcfm_enquirys_single->ID . '" />';
				// Enquiry
				$datatable_json['data'][$index][] =  '<a href="' . esc_url(get_wcfm_enquiry_manage_url($wcfm_enquirys_single->ID)) . '" class="wcfm_dashboard_item_title">' . apply_filters('wcfm_enquiry_message_display', $wcfm_enquirys_single->enquiry, $wcfm_enquirys_single->ID) . '</a>';

				// Product
				if ($wcfm_enquirys_single->product_id) {
					$datatable_json['data'][$index][] =  '<a class="wcfm-enquiry-product" target="_blank" href="' . esc_url(get_permalink($wcfm_enquirys_single->product_id)) . '">' . get_the_title($wcfm_enquirys_single->product_id) . '</a>';
				} else {
					$datatable_json['data'][$index][] =  '&ndash;';
				}

				// Customer
				$customer_details = '';
				if (apply_filters('wcfm_allow_view_customer_name', true)) {
					if ($wcfm_enquirys_single->customer_id && apply_filters('wcfm_is_allow_view_customer', true)) {
						$customer_details =  '<a target="_blank" href="' . esc_url(get_wcfm_customers_details_url($wcfm_enquirys_single->customer_id)) . '" class="wcfm_inquiry_by_customer">' . $wcfm_enquirys_single->customer_name . '</a>';
					} else {
						$customer_details =  $wcfm_enquirys_single->customer_name;
					}
					if (apply_filters('wcfm_allow_view_customer_email', true)) {
						$customer_details .= "<br />" . $wcfm_enquirys_single->customer_email;
					}
				}
				$datatable_json['data'][$index][] =  apply_filters('wcfm_enquiry_customer_name_display', $customer_details, $wcfm_enquirys_single->customer_id, $wcfm_enquirys_single->ID);


				// Vendor
				$vendor_name = '&ndash;';
				if (!$WCFM->is_marketplace || wcfm_is_vendor()) {
					$datatable_json['data'][$index][] =  $vendor_name;
				} elseif ($wcfm_enquirys_single->vendor_id) {
					$store_name = wcfm_get_vendor_store($wcfm_enquirys_single->vendor_id);
					if ($store_name) {
						$vendor_name = $store_name;
					}
					$datatable_json['data'][$index][] =  $vendor_name;
				} else {
					$datatable_json['data'][$index][] =  $vendor_name;
				}

				// Additional Info
				$additional_info = '';
				$wcfm_enquiry_meta_values = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wcfm_enquiries_meta WHERE `enquiry_id` = %d", $wcfm_enquirys_single->ID));
				if (!empty($wcfm_enquiry_meta_values)) {
					foreach ($wcfm_enquiry_meta_values as $wcfm_enquiry_meta_value) {
						$additional_info .= __($wcfm_enquiry_meta_value->key, 'wc-frontend-manager') . ': ' . $wcfm_enquiry_meta_value->value . '<br />';
					}
				} else {
					$additional_info = '&ndash;';
				}
				$datatable_json['data'][$index][] =  $additional_info;

				// Replies
				$wcfm_enquiry_replies = $wpdb->get_results($wpdb->prepare("SELECT `reply_by` from {$wpdb->prefix}wcfm_enquiries_response WHERE `enquiry_id` = %d", $wcfm_enquirys_single->ID));
				$replies = '<span class="reply_view_count">' . count($wcfm_enquiry_replies) . '</span>';
				if ((count($wcfm_enquiry_replies) > 0) && $wcfm_enquirys_single->reply_by) {
					$enquiry_last_reply_by = '';
					if (apply_filters('wcfm_allow_view_customer_name', true)) {
						if (wcfm_is_vendor($wcfm_enquirys_single->reply_by)) {
							$enquiry_last_reply_by = wcfm_get_vendor_store_name($wcfm_enquirys_single->reply_by);
						} elseif ($wcfm_enquirys_single->reply_by != $wcfm_enquirys_single->customer_id) {
							$enquiry_last_reply_by = get_bloginfo('name');
						} else {
							$userdata = get_userdata($wcfm_enquirys_single->reply_by);
							$first_name = $userdata->first_name;
							$last_name  = $userdata->last_name;
							$display_name  = $userdata->display_name;
							if ($first_name) {
								$enquiry_last_reply_by = $first_name . ' ' . $last_name;
							} else {
								$enquiry_last_reply_by = $display_name;
							}
						}
					}
					$replies .= "<br /><span class='last_reply_by text_tip' data-tip='" . __('Last Reply By', 'wc-frontend-manager') . "'>(" . $enquiry_last_reply_by . ")</span>";
				}
				$datatable_json['data'][$index][] = $replies;

				// Date
				$datatable_json['data'][$index][] = date_i18n(wc_date_format() . ' ' . wc_time_format(), strtotime($wcfm_enquirys_single->posted));

				// Action
				$actions = '<a class="wcfm-action-icon" href="' . esc_url(get_wcfm_enquiry_manage_url($wcfm_enquirys_single->ID)) . '"><span class="wcfmfa fa-reply-all text_tip" data-tip="' . esc_attr__('Reply', 'wc-frontend-manager') . '"></span></a>';

				if (apply_filters('wcfm_is_allow_eniquiry_delete', true)) {
					$actions .= '<a class="wcfm_enquiry_delete wcfm-action-icon" href="#" data-enquiryid="' . $wcfm_enquirys_single->ID . '"><span class="wcfmfa fa-trash-alt text_tip" data-tip="' . esc_attr__('Delete', 'wc-frontend-manager') . '"></span></a>';
				}

				$datatable_json['data'][$index][] = apply_filters('wcfm_enquiry_actions', $actions, $wcfm_enquirys_single);

				$index++;
			}
		}
													
		wp_send_json( $datatable_json );
	}
}

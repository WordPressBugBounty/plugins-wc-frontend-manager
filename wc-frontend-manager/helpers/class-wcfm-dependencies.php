<?php





class WCFM_Dependencies {

	private static $active_plugins;

	


	static function active_plugins() {
		if ( ! self::$active_plugins ) {
			self::$active_plugins = (array) get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				self::$active_plugins = array_merge( self::$active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}
		}
		return self::$active_plugins;
	}

	static function woocommerce_plugin_active_check() {
		return in_array( 'woocommerce/woocommerce.php', self::active_plugins() ) || array_key_exists( 'woocommerce/woocommerce.php', self::active_plugins() );
	}

	 
	static function wcfmu_plugin_active_check() {
		return in_array( 'wc-frontend-manager-ultimate/wc_frontend_manager_ultimate.php', self::active_plugins() ) || array_key_exists( 'wc-frontend-manager-ultimate/wc_frontend_manager_ultimate.php', self::active_plugins() );
	}

	 
	static function wcfmgs_plugin_active_check() {
		return in_array( 'wc-frontend-manager-groups-staffs/wc_frontend_manager_groups_staffs.php', self::active_plugins() ) || array_key_exists( 'wc-frontend-manager-groups-staffs/wc_frontend_manager_groups_staffs.php', self::active_plugins() );
	}

	 
	static function wcfma_plugin_active_check() {
		return in_array( 'wc-frontend-manager-analytics/wc_frontend_manager_analytics.php', self::active_plugins() ) || array_key_exists( 'wc-frontend-manager-analytics/wc_frontend_manager_analytics.php', self::active_plugins() );
	}

	 
	static function wcfmvm_plugin_active_check() {
		return in_array( 'wc-multivendor-membership/wc-multivendor-membership.php', self::active_plugins() ) || array_key_exists( 'wc-multivendor-membership/wc-multivendor-membership.php', self::active_plugins() );
	}

	 
	static function wcfmd_plugin_active_check() {
		return in_array( 'wc-frontend-manager-delivery/wc-frontend-manager-delivery.php', self::active_plugins() ) || array_key_exists( 'wc-frontend-manager-delivery/wc-frontend-manager-delivery.php', self::active_plugins() );
	}

	 
	static function wcvpro_plugin_active_check() {
		return in_array( 'wc-vendors-pro/wcvendors-pro.php', self::active_plugins() ) || array_key_exists( 'wc-vendors-pro/wcvendors-pro.php', self::active_plugins() ) || class_exists( 'WCVendors_Pro' );
	}

	 
	static function wcfm_bookings_plugin_active_check() {
		return in_array( 'woocommerce-bookings/woocommerce-bookings.php', self::active_plugins() ) || array_key_exists( 'woocommerce-bookings/woocommerce-bookings.php', self::active_plugins() );
	}

	 
	static function wcfm_subscriptions_plugin_active_check() {
		return in_array( 'woocommerce-subscriptions/woocommerce-subscriptions.php', self::active_plugins() ) || array_key_exists( 'woocommerce-subscriptions/woocommerce-subscriptions.php', self::active_plugins() );
	}

	 
	static function wcfm_xa_subscriptions_plugin_active_check() {
		return in_array( 'xa-woocommerce-subscriptions/xa-woocommerce-subscriptions.php', self::active_plugins() ) || array_key_exists( 'xa-woocommerce-subscriptions/xa-woocommerce-subscriptions.php', self::active_plugins() );
	}

	 
	static function wcfm_yoast_plugin_active_check() {
		return in_array( 'wordpress-seo/wp-seo.php', self::active_plugins() ) || array_key_exists( 'wordpress-seo/wp-seo.php', self::active_plugins() );
	}

	 
	static function wcfm_yoast_premium_plugin_active_check() {
		return in_array( 'wordpress-seo-premium/wp-seo-premium.php', self::active_plugins() ) || array_key_exists( 'wordpress-seo-premium/wp-seo-premium.php', self::active_plugins() );
	}

	 
	static function wcfm_wc_tabs_lite_plugin_active_check() {
		return in_array( 'woocommerce-custom-product-tabs-lite/woocommerce-custom-product-tabs-lite.php', self::active_plugins() ) || array_key_exists( 'woocommerce-custom-product-tabs-lite/woocommerce-custom-product-tabs-lite.php', self::active_plugins() );
	}

	 
	static function wcfm_wc_barcode_isbn_plugin_active_check() {
		return in_array( 'woocommerce-barcode-isbn/AG-barcode-ISBN.php', self::active_plugins() ) || array_key_exists( 'woocommerce-barcode-isbn/AG-barcode-ISBN.php', self::active_plugins() );
	}

	 
	static function wcfm_wc_msrp_pricing_plugin_active_check() {
		return in_array( 'woocommerce-msrp-pricing/woocommerce-msrp.php', self::active_plugins() ) || array_key_exists( 'woocommerce-msrp-pricing/woocommerce-msrp.php', self::active_plugins() );
	}

	 
	static function wcfm_wc_quantities_units_plugin_active_check() {
		return in_array( 'quantities-and-units-for-woocommerce/quantites-and-units.php', self::active_plugins() ) || array_key_exists( 'quantities-and-units-for-woocommerce/quantites-and-units.php', self::active_plugins() );
	}

	 
	static function wcfm_wp_job_manager_plugin_active_check() {
		return in_array( 'wp-job-manager/wp-job-manager.php', self::active_plugins() ) || array_key_exists( 'wp-job-manager/wp-job-manager.php', self::active_plugins() ) || class_exists( 'WP_Job_Manager' );
	}

	 
	static function wcfm_wp_job_manager_applications_plugin_active_check() {
		return in_array( 'wp-job-manager-applications/wp-job-manager-applications.php', self::active_plugins() ) || array_key_exists( 'wp-job-manager-applications/wp-job-manager-applications.php', self::active_plugins() );
	}

	 
	static function wcfm_wc_pdf_invoices_packing_slips_plugin_active_check() {
		return in_array( 'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php', self::active_plugins() ) || array_key_exists( 'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php', self::active_plugins() );
	}

	 
	static function wcfm_geo_my_wp_plugin_active_check() {
		return in_array( 'geo-my-wp/geo-my-wp.php', self::active_plugins() ) || array_key_exists( 'geo-my-wp/geo-my-wp.php', self::active_plugins() ) || class_exists( 'GEO_MY_WP' );
	}

	 
	static function wcfm_wc_paid_listing_active_check() {
		return in_array( 'wp-job-manager-wc-paid-listings/wp-job-manager-wc-paid-listings.php', self::active_plugins() ) || array_key_exists( 'wp-job-manager-wc-paid-listings/wp-job-manager-wc-paid-listings.php', self::active_plugins() );
	}

	 
	static function wcfm_wc_product_fees_plugin_active_check() {
		return in_array( 'woocommerce-product-fees/woocommerce-product-fees.php', self::active_plugins() ) || array_key_exists( 'woocommerce-product-fees/woocommerce-product-fees.php', self::active_plugins() );
	}

	 
	static function wcfm_wc_bulk_discount_plugin_active_check() {
		return in_array( 'woocommerce-bulk-discount/woocommerce-bulk-discount.php', self::active_plugins() ) || array_key_exists( 'woocommerce-bulk-discount/woocommerce-bulk-discount.php', self::active_plugins() );
	}

	 
	static function wcfm_wc_rental_active_check() {
		return in_array( 'booking-and-rental-system-woocommerce/redq-rental-and-bookings.php', self::active_plugins() ) || array_key_exists( 'booking-and-rental-system-woocommerce/redq-rental-and-bookings.php', self::active_plugins() );
	}

	 
	static function wcfm_yith_auction_free_active_check() {
		return in_array( 'yith-auctions-for-woocommerce/init.php', self::active_plugins() ) || array_key_exists( 'yith-auctions-for-woocommerce/init.php', self::active_plugins() );
	}

	 
	static function wcfm_wc_table_rates_active_check() {
		return in_array( 'woocommerce-table-rate-shipping/woocommerce-table-rate-shipping.php', self::active_plugins() ) || array_key_exists( 'woocommerce-table-rate-shipping/woocommerce-table-rate-shipping.php', self::active_plugins() );
	}

	 
	static function wcfm_wcmp_advanced_shipping_active_check() {
		return in_array( 'wcmp-advance-shipping/wcmp-advance-shipping.php', self::active_plugins() ) || array_key_exists( 'wcmp-advance-shipping/wcmp-advance-shipping.php', self::active_plugins() );
	}

	 
	static function wcfm_wcmp_stripe_connect_active_check() {
		return in_array( 'marketplace-stripe-gateway/marketplace-stripe-gateway.php', self::active_plugins() ) || array_key_exists( 'marketplace-stripe-gateway/marketplace-stripe-gateway.php', self::active_plugins() );
	}

	 
	static function wcfm_wc_role_based_price_active_check() {
		return in_array( 'woocommerce-role-based-price/woocommerce-role-based-price.php', self::active_plugins() ) || array_key_exists( 'woocommerce-role-based-price/woocommerce-role-based-price.php', self::active_plugins() );
	}

	 
	static function wcfm_wc_dhl_shipping_active_check() {
		 
		return false;
	}

	 
	static function wcfm_wc_fedex_shipping_active_check() {
		 
		return false;
	}

	 
	static function wcfm_wc_easypost_shipping_active_check() {
		 
		return false;
	}

	 
	static function dokanpro_plugin_active_check() {
		return in_array( 'dokan-pro/dokan-pro.php', self::active_plugins() ) || array_key_exists( 'dokan-pro/dokan-pro.php', self::active_plugins() ) || class_exists( 'Dokan_Pro' );
	}

	 
	static function wcfm_woocommerce_germanized_plugin_active_check() {
		return in_array( 'woocommerce-germanized/woocommerce-germanized.php', self::active_plugins() ) || array_key_exists( 'woocommerce-germanized/woocommerce-germanized.php', self::active_plugins() );
	}

	 
	static function wcfm_biddypress_plugin_active_check() {
		return in_array( 'buddypress/bp-loader.php', self::active_plugins() ) || array_key_exists( 'buddypress/bp-loader.php', self::active_plugins() );
	}

	 
	static function wcfm_wc_mangopay_plugin_active_check() {
		return in_array( 'mangopay-woocommerce/mangopay-woocommerce.php', self::active_plugins() ) || array_key_exists( 'mangopay-woocommerce/mangopay-woocommerce.php', self::active_plugins() );
	}

	 
	static function wcfm_wcv_stripe_plugin_active_check() {
		return in_array( 'wc-vendors-gateway-stripe-connect/gateway-stripe.php', self::active_plugins() ) || array_key_exists( 'wc-vendors-gateway-stripe-connect/gateway-stripe.php', self::active_plugins() );
	}

	 
	static function wcfm_wc_product_voucher_plugin_active_check() {
		return in_array( 'woocommerce-pdf-product-vouchers/woocommerce-pdf-product-vouchers.php', self::active_plugins() ) || array_key_exists( 'woocommerce-pdf-product-vouchers/woocommerce-pdf-product-vouchers.php', self::active_plugins() );
	}

	 
	static function wcfm_wc_sku_generator_plugin_active_check() {
		return in_array( 'woocommerce-product-sku-generator/woocommerce-product-sku-generator.php', self::active_plugins() ) || array_key_exists( 'woocommerce-product-sku-generator/woocommerce-product-sku-generator.php', self::active_plugins() );
	}

	 
	static function wcfm_epeken_plugin_active_check() {
		return in_array( 'epeken-all-kurir/epeken_courier.php', self::active_plugins() ) || array_key_exists( 'epeken-all-kurir/epeken_courier.php', self::active_plugins() );
	}

	 
	static function wcfm_products_listings_active_check() {
		return in_array( 'wp-job-manager-products/wp-job-manager-products.php', self::active_plugins() ) || array_key_exists( 'wp-job-manager-products/wp-job-manager-products.php', self::active_plugins() );
	}

	 
	static function wcfm_products_mylistings_active_check() {
		return in_array( 'my-listing-addons/my-listing-addons.php', self::active_plugins() ) || array_key_exists( 'my-listing-addons/my-listing-addons.php', self::active_plugins() );
	}

	 
	static function wcfm_wdm_scheduler_active_check() {
		return in_array( 'woocommerce-scheduler/woocommerce-scheduler.php', self::active_plugins() ) || array_key_exists( 'woocommerce-scheduler/woocommerce-scheduler.php', self::active_plugins() );
	}

	 
	static function wcfm_tych_booking_active_check() {
		return in_array( 'woocommerce-booking/woocommerce-booking.php', self::active_plugins() ) || array_key_exists( 'woocommerce-booking/woocommerce-booking.php', self::active_plugins() );
	}

	 
	static function wcfm_wc_product_scheduler_active_check() {
		return in_array( 'woo-product-availability-scheduler/woocommerce-product-availability-scheduler.php', self::active_plugins() ) || array_key_exists( 'woo-product-availability-scheduler/woocommerce-product-availability-scheduler.php', self::active_plugins() );
	}

	






	static function wcfm_wc_tiered_price_table_active_check() {
		$is_wp_org_free_version = function_exists( 'tpt_fs' ) && ! tpt_fs()->is_premium();
		$free_plugin_main_file  = 'tier-pricing-table/tier-pricing-table.php';

		$found_plugin_folder = (
			in_array( $free_plugin_main_file, self::active_plugins() ) ||
			array_key_exists( $free_plugin_main_file, self::active_plugins() )
		);

		return $found_plugin_folder && $is_wp_org_free_version;
	}

	










	static function wcfm_wc_tiered_price_table_premium_active_check() {
		$is_wp_org_free_version = function_exists( 'tpt_fs' ) && ! tpt_fs()->is_premium();

		 
		$free_plugin_main_file = 'tier-pricing-table/tier-pricing-table.php';

		 
		$premium_plugin_main_file = 'tier-pricing-table-premium/tier-pricing-table.php';

		$found_plugin_folder = (
			in_array( $free_plugin_main_file, self::active_plugins() ) ||
			array_key_exists( $free_plugin_main_file, self::active_plugins() )
		) || (
			in_array( $premium_plugin_main_file, self::active_plugins() ) ||
			array_key_exists( $premium_plugin_main_file, self::active_plugins() )
		);

		return $found_plugin_folder && ! $is_wp_org_free_version;
	}

	 
	static function wcfm_all_in_one_seo_plugin_active_check() {
		return in_array( 'all-in-one-seo-pack/all_in_one_seo_pack.php', self::active_plugins() ) || array_key_exists( 'all-in-one-seo-pack/all_in_one_seo_pack.php', self::active_plugins() );
	}

	 
	static function wcfm_all_in_one_seo_pro_plugin_active_check() {
		return in_array( 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php', self::active_plugins() ) || array_key_exists( 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php', self::active_plugins() );
	}

	 
	static function wcfm_rankmath_seo_plugin_active_check() {
		return in_array( 'seo-by-rank-math/rank-math.php', self::active_plugins() ) || array_key_exists( 'seo-by-rank-math/rank-math.php', self::active_plugins() );
	}

	






	static function wcfm_woo_product_size_chart_plugin_active_check() {
		return in_array( 'woo-advanced-product-size-chart/size-chart-for-woocommerce.php', self::active_plugins() ) || array_key_exists( 'woo-advanced-product-size-chart/size-chart-for-woocommerce.php', self::active_plugins() );
	}

	 
	static function wcfm_post_expirator_plugin_active_check() {
		return in_array( 'post-expirator/post-expirator.php', self::active_plugins() ) || array_key_exists( 'post-expirator/post-expirator.php', self::active_plugins() );
	}

	 
	static function wcfm_wc_german_market_plugin_active_check() {
		return in_array( 'woocommerce-german-market/WooCommerce-German-Market.php', self::active_plugins() ) || array_key_exists( 'woocommerce-german-market/WooCommerce-German-Market.php', self::active_plugins() );
	}

	 
	static function wcfm_woo_country_based_restriction_active_check() {
		return in_array( 'woo-product-country-base-restrictions/woocommerce-product-country-base-restrictions.php', self::active_plugins() ) || array_key_exists( 'woo-product-country-base-restrictions/woocommerce-product-country-base-restrictions.php', self::active_plugins() );
	}
}

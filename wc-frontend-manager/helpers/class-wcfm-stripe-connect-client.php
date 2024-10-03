<?php

use Stripe\Stripe;
use \Stripe\StripeClient;

class WCFM_Stripe_Connect_Client {

    protected $client_id;
    protected $secret_key;
    protected $stripe;
    protected $user_id;

    public function __construct($client_id = "", $secret_key = "") {
        $this->client_id = $client_id;
        $this->secret_key = $secret_key;
        // $this->stripe = new StripeClient($secret_key);
        $this->stripe = new StripeClient([
            'client_id' => $client_id,
            'api_key'   => $secret_key,
        ]);
    }

    public function set_user_id( $user_id ) {
        $this->user_id = $user_id;
    }

    public function is_connected_to_stripe() {
        $is_connected = false;

        $admin_client_id = get_user_meta($this->user_id, 'admin_client_id', true);

        // Check if current_client_id matches the client id in DB
        $this->verify_stripe_credentials();
        
        $stripe_user_id = get_user_meta($this->user_id, 'stripe_user_id', true);

        // Check if stripe_user_id exists
        if ($stripe_user_id && ($admin_client_id == $this->client_id)) {
            // Check if details_sumitted == true
            $is_connected = $this->verify_details_submitted($stripe_user_id);
        }
        
        return $is_connected;
    }

    public function verify_stripe_credentials() {
        $admin_client_id = get_user_meta($this->user_id, 'admin_client_id', true);
        
        if ($admin_client_id != $this->client_id) {
            $this->delete_stripe_data();
        }
    }

    /**
     *  Prepare data for Stripe account creation
     *  
     *  @return array $stripe_connect_args 
     * 
     *  @link https://docs.stripe.com/api/accounts/create
     */
    public function get_stripe_accounts_args() {
        $user       = get_user_by('id', $this->user_id);
        $store_name = wcfm_get_vendor_store_name($this->user_id);
        $store_name = empty($store_name) ? $user->display_name : $store_name;
        $store_info = get_user_meta( $this->user_id, 'wcfmmp_profile_settings', true );

        $shop_description   = isset($store_info['shop_description']) ? $store_info['shop_description'] : '';

        // Address
        $phone      = isset($store_info['phone']) ? $store_info['phone'] : '';
        $email      = isset($store_info['store_email']) ? $store_info['store_email'] : '';
        $address    = isset($store_info['address']) ? $store_info['address'] : [];
        $street_1   = isset($address['street_1']) ? $address['street_1'] : '';
        $street_2   = isset($address['street_2']) ? $address['street_2'] : '';
        $city       = isset($address['city']) ? $address['city'] : '';
        $zip        = isset($address['zip']) ? $address['zip'] : '';
        $country    = isset($address['country']) ? $address['country'] : '';
        $state      = isset($address['state']) ? $address['state'] : '';

        // Customer Support
        $customer_support               = isset($store_info['customer_support']) ? $store_info['customer_support'] : [];
        $customer_supporty_phone        = isset($customer_support['phone']) ? $customer_support['phone'] : '';
        $customer_supporty_email        = isset($customer_support['email']) ? $customer_support['email'] : '';
        $customer_supporty_address1     = isset($customer_support['address1']) ? $customer_support['address1'] : '';
        $customer_supporty_address2     = isset($customer_support['address2']) ? $customer_support['address2'] : '';
        $customer_supporty_country      = isset($customer_support['country']) ? $customer_support['country'] : '';
        $customer_supporty_city         = isset($customer_support['city']) ? $customer_support['city'] : '';
        $customer_supporty_state        = isset($customer_support['state']) ? $customer_support['state'] : '';
        $customer_supporty_zip          = isset($customer_support['zip']) ? $customer_support['zip'] : '';

        $stripe_connect_args = [];
        if (apply_filters('wcfm_is_allow_stripe_express_api', true)) {
            // Before you begin: https://stripe.com/docs/connect/express-accounts#prerequisites-for-using-express
            // Express account prefill information

            /**
             *  @link https://docs.stripe.com/api/accounts/create#create_account-type
             */
            $stripe_connect_args['type'] = \Stripe\Account::TYPE_EXPRESS;
        } else {
            // Standard account prefill information

            /**
             *  @link https://docs.stripe.com/api/accounts/create#create_account-type
             */
            $stripe_connect_args['type'] = \Stripe\Account::TYPE_STANDARD;
        }

        /**
         *  @link https://docs.stripe.com/api/accounts/create#create_account-country
         */
        if (
            ! empty($country) &&
            (in_array($country, $this->get_supported_transfer_countries()) ||
            $this->needs_cross_border_payment($country))
        ) {
            $stripe_connect_args['country'] = $country;
        }

        $stripe_connect_args['capabilities'] = [
            'card_payments'       => [
                'requested' => true,
            ],
            // 'ideal_payments'      => [
            //     'requested' => true,
            // ],
            // 'sepa_debit_payments' => [
            //     'requested' => true,
            // ],
            'transfers'           => [
                'requested' => true,
            ],
        ];

        if(!empty($stripe_connect_args['country']) && $this->needs_cross_border_payment($stripe_connect_args['country'])) {
            // Unset all payments capabilities.
            unset(
                $stripe_connect_args['capabilities']['card_payments'],
                $stripe_connect_args['capabilities']['ideal_payments'],
                $stripe_connect_args['capabilities']['sepa_debit_payments']
            );
            // Set the `transfers` ability to `requested`.
            $stripe_connect_args['tos_acceptance'] = [
                'service_agreement' => 'recipient',
            ];
        }

        /**
         *  @link https://docs.stripe.com/api/accounts/create#create_account-business_type
         */
        $stripe_connect_args['business_type'] = \Stripe\Account::BUSINESS_TYPE_INDIVIDUAL;

        /**
         *  @link https://docs.stripe.com/api/accounts/create#create_account-individual
         */
        $stripe_connect_args['individual'] = [
            'email'         => $email,
            'first_name'    => $user->first_name,
            'last_name'     => $user->last_name,
            'phone'         => $phone,
            'address'   => [
                'line1'         => $street_1,
                'line2'         => $street_2,
                'city'          => $city,
                'state'         => $state,
                'country'       => $country,
                'postal_code'   => $zip,
            ],
        ];

        /**
         *  @link https://docs.stripe.com/api/accounts/create#create_account-business_profile
         */
        $stripe_connect_args['business_profile'] = [
            'name'  => $store_name,
            /** 
             *  Will not work for localhost
             *  Stripe throws an error for invalid_url
             *  Try in live site
             */
            // 'url' => wcfmmp_get_store_url($this->user_id),
            'product_description'   => wp_strip_all_tags($shop_description),
            'support_address' => [
                'line1'         => $customer_supporty_address1,
                'line2'         => $customer_supporty_address2,
                'city'          => $customer_supporty_city,
                'state'         => $customer_supporty_state,
                'country'       => $customer_supporty_country,
                'postal_code'   => $customer_supporty_zip,
            ],
            'support_email' => $customer_supporty_email,
            'support_phone' => $customer_supporty_phone,
        ];

        /**
         *  @link https://docs.stripe.com/api/accounts/create#create_account-settings
         */
        $stripe_connect_args['settings'] = [
            'payments' => [
                // @TODO between 5-22 chars
                // The statement descriptor must be at most 22 characters.
                'statement_descriptor' => substr(trim($store_name), 0, 22),
            ]
        ];

        unset($stripe_connect_args['individual']);
        unset($stripe_connect_args['business_profile']);
        unset($stripe_connect_args['settings']);

        return apply_filters('wcfm_stripe_accounts_args', $stripe_connect_args);
    }

    public function get_stripe_account_links_args($stripe_user_id) {
        return apply_filters('wcfm_stripe_account_links_args', [
            'account' => $stripe_user_id,
            'refresh_url' => add_query_arg('stripe_action', 'refresh', get_wcfm_settings_url()),
            'return_url' => get_wcfm_settings_url(),
            'type' => 'account_onboarding',
        ]);
    }

    /**
     *  @return boolean $deleted
     */
    public function delete_account($stripe_user_id, $params = null, $opts = null) {
        $account = null;

        try {
            $account = $this->stripe->accounts->delete($stripe_user_id, $params, $opts);
        } catch (\Stripe\Exception\ApiErrorException $api_error) {
            wcfm_stripe_log('Can not delete account. Reason: ' . $api_error->getMessage(), 'error');
            wcfm_stripe_log('Error Details: ' . $api_error->getHttpBody());
        }

        if (isset($account->deleted) && $account->deleted) {
            $this->delete_stripe_data();

            $vendor_data = get_user_meta($this->user_id, 'wcfmmp_profile_settings', true);
            $vendor_data['payment']['method'] = '';
            update_user_meta($this->user_id, 'wcfmmp_profile_settings', $vendor_data);

            return true;
        }

        return false;
    }

    /**
     *  @return null|/Stripe/Account
     */
    public function create_account($params = null, $opts = null) {
        $account = null;

        try {
            $account = $this->stripe->accounts->create($params, $opts);
        } catch (\Stripe\Exception\ApiErrorException $api_error) {
            wcfm_stripe_log('Can not create account. Reason: ' . $api_error->getMessage(), 'error');
            wcfm_stripe_log('Error Details: ' . $api_error->getHttpBody());
        }

        if ($account && isset($account->id)) {
            $stripe_account_id = $account->id;
            $vendor_data = get_user_meta($this->user_id, 'wcfmmp_profile_settings', true);
            $vendor_data['payment']['method'] = 'stripe';
            
            update_user_meta($this->user_id, 'wcfmmp_profile_settings', $vendor_data);
            update_user_meta($this->user_id, 'admin_client_id', $this->client_id);
            update_user_meta($this->user_id, 'stripe_user_id', $stripe_account_id);
            update_user_meta($this->user_id, 'stripe_connect_type', $params['type']);
        }

        return $account;
    }

    /**
     *  @return boolean|string
     */
    public function create_account_link($params = null, $opts = null) {
        $account_link = null;

        // if stripe_connect_type changed
        $saved_connect_type = get_user_meta($this->user_id, 'stripe_connect_type', true);
        
        if ( $saved_connect_type ) {
            $current_connect_type = apply_filters('wcfm_is_allow_stripe_express_api', true) ? 'express' : 'standard';

            if ( $saved_connect_type !== $current_connect_type ) {
                // delete account
                if ( is_array($params) && $params['account'] ) {
                    $this->delete_account($params['account']);
                }

                // create new account
                $account = $this->create_account($this->get_stripe_accounts_args());
                $params['account'] = $account->id;
            }
        }

        try {
            $account_link = $this->stripe->accountLinks->create($params, $opts);
        } catch (\Stripe\Exception\ApiErrorException $api_error) {
            wcfm_stripe_log('Can not create account link. Reason: ' . $api_error->getMessage(), 'error');
            wcfm_stripe_log('Error Details: ' . $api_error->getHttpBody());
        }

        // get the stripe connect url (expires in 5 minutes)
        if ($account_link && isset($account_link->url)) {
            return $account_link->url;
        }

        return false;
    }

    /**
     *  @return boolean $details_submitted
     */
    public function verify_details_submitted($stripe_user_id, $params = null, $opts = null) {
        $account = null;

        if (get_user_meta($this->user_id, 'stripe_details_submitted', true)) {
            return true;
        }

        try {
            $account = $this->stripe->accounts->retrieve($stripe_user_id, $params, $opts);
        } catch (\Stripe\Exception\ApiErrorException $api_error) {
            wcfm_stripe_log('Can not verify details submitted. Reason: ' . $api_error->getMessage(), 'error');
            wcfm_stripe_log('Error Details: ' . $api_error->getHttpBody());

            if ('account_invalid' == $api_error->getStripeCode()) {
                $this->delete_stripe_data();
            }
        }

        if (isset($account->details_submitted) && $account->details_submitted) {
            update_user_meta($this->user_id, 'vendor_connected', true);
            update_user_meta($this->user_id, 'admin_client_id', $this->client_id);
            update_user_meta($this->user_id, 'stripe_details_submitted', $account->details_submitted);

            if (isset($account->capabilities) && isset($account->capabilities->card_payments) && $account->capabilities->card_payments === 'active') {
                update_user_meta($this->user_id, 'stripe_card_payments_enabled', true);
            }

            return true;
        }

        return false;
    }

    /**
     *  List of countries, does not support stripe card_payments 
     * 
     *  i.e. accounts can receive funds from admin business [Transfer Charge]
     *  but accounts can not accept payments from their own customers [Direct Charge, Destination Charge]
     * 
     *  @return array $countries
     * 
     *  @link https://dashboard.stripe.com/test/settings/connect/onboarding-options/countries
     */
    public function wcfm_stripe_card_payments_restricted_countries() {

        return apply_filters('wcfm_stripe_card_payments_restricted_countries', [
            'AL' => __( 'Albania', 'woocommerce' ),
            'DZ' => __( 'Algeria', 'woocommerce' ),
            'AO' => __( 'Angola', 'woocommerce' ),
            'AG' => __( 'Antigua and Barbuda', 'woocommerce' ),
            'AR' => __( 'Argentina', 'woocommerce' ),
            'AM' => __( 'Armenia', 'woocommerce' ),
            'AZ' => __( 'Azerbaijan', 'woocommerce' ),
            'BS' => __( 'Bahamas', 'woocommerce' ),
            'BH' => __( 'Bahrain', 'woocommerce' ),
            'BD' => __( 'Bangladesh', 'woocommerce' ),
            'BJ' => __( 'Benin', 'woocommerce' ),
            'BT' => __( 'Bhutan', 'woocommerce' ),
            'BO' => __( 'Bolivia', 'woocommerce' ),
            'BA' => __( 'Bosnia and Herzegovina', 'woocommerce' ),
            'BW' => __( 'Botswana', 'woocommerce' ),
            'BN' => __( 'Brunei', 'woocommerce' ),
            'KH' => __( 'Cambodia', 'woocommerce' ),
            'CL' => __( 'Chile', 'woocommerce' ),
            'CO' => __( 'Colombia', 'woocommerce' ),
            'CR' => __( 'Costa Rica', 'woocommerce' ),
            'DO' => __( 'Dominican Republic', 'woocommerce' ),
            'EC' => __( 'Ecuador', 'woocommerce' ),
            'EG' => __( 'Egypt', 'woocommerce' ),
            'SV' => __( 'El Salvador', 'woocommerce' ),
            'ET' => __( 'Ethiopia', 'woocommerce' ),
            'GA' => __( 'Gabon', 'woocommerce' ),
            'GM' => __( 'Gambia', 'woocommerce' ),
            'GH' => __( 'Ghana', 'woocommerce' ),
            'GT' => __( 'Guatemala', 'woocommerce' ),
            'GY' => __( 'Guyana', 'woocommerce' ),
            'IS' => __( 'Iceland', 'woocommerce' ),
            'IN' => __( 'India', 'woocommerce' ),
            'ID' => __( 'Indonesia', 'woocommerce' ),
            'IL' => __( 'Israel', 'woocommerce' ),
            'JM' => __( 'Jamaica', 'woocommerce' ),
            'JO' => __( 'Jordan', 'woocommerce' ),
            'KZ' => __( 'Kazakhstan', 'woocommerce' ),
            'KE' => __( 'Kenya', 'woocommerce' ),
            'KW' => __( 'Kuwait', 'woocommerce' ),
            'LA' => __( 'Laos', 'woocommerce' ),
            'MO' => __( 'Macao', 'woocommerce' ),
            'MG' => __( 'Madagascar', 'woocommerce' ),
            'MY' => __( 'Malaysia', 'woocommerce' ),
            'MU' => __( 'Mauritius', 'woocommerce' ),
            'MD' => __( 'Moldova', 'woocommerce' ),
            'MC' => __( 'Monaco', 'woocommerce' ),
            'MN' => __( 'Mongolia', 'woocommerce' ),
            'MA' => __( 'Morocco', 'woocommerce' ),
            'MZ' => __( 'Mozambique', 'woocommerce' ),
            'NA' => __( 'Namibia', 'woocommerce' ),
            'NE' => __( 'Niger', 'woocommerce' ),
            'NG' => __( 'Nigeria', 'woocommerce' ),
            'OM' => __( 'Oman', 'woocommerce' ),
            'PK' => __( 'Pakistan', 'woocommerce' ),
            'PA' => __( 'Panama', 'woocommerce' ),
            'PY' => __( 'Paraguay', 'woocommerce' ),
            'PE' => __( 'Peru', 'woocommerce' ),
            'PH' => __( 'Philippines', 'woocommerce' ),
            'QA' => __( 'Qatar', 'woocommerce' ),
            'RW' => __( 'Rwanda', 'woocommerce' ),
            'SM' => __( 'San Marino', 'woocommerce' ),
            'SA' => __( 'Saudi Arabia', 'woocommerce' ),
            'SN' => __( 'Senegal', 'woocommerce' ),
            'RS' => __( 'Serbia', 'woocommerce' ),
            'ZA' => __( 'South Africa', 'woocommerce' ),
            'KR' => __( 'South Korea', 'woocommerce' ),
            'LK' => __( 'Sri Lanka', 'woocommerce' ),
            'TW' => __( 'Taiwan', 'woocommerce' ),
            'TZ' => __( 'Tanzania', 'woocommerce' ),
            'TT' => __( 'Trinidad and Tobago', 'woocommerce' ),
            'TN' => __( 'Tunisia', 'woocommerce' ),
            'TR' => __( 'Turkey', 'woocommerce' ),
            'UY' => __( 'Uruguay', 'woocommerce' ),
            'UZ' => __( 'Uzbekistan', 'woocommerce' ),
            'VN' => __( 'Vietnam', 'woocommerce' ),
        ]);

    }

    /**
     * Retrives account data of the platform.
     *
     * @return \Stripe\Account|false
     */
    public function get_platform_data() {
        try {
            $cache_key   = "stripe_express_get_platform_data";
            $platform    = get_transient( $cache_key );

            if ( false === $platform || apply_filters('wcfm_stripe_force_reload_transients', true) ) {
                $platform = $this->stripe->accounts->retrieve();
                set_transient( $cache_key, $platform, WEEK_IN_SECONDS );
            }
        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            wcfm_stripe_log(sprintf( __( 'Could not retrieve platform data: %s', 'wc-frontend-manager' ), $e->getMessage() ), 'error');
            wcfm_stripe_log('Error Details: ' . $e->getHttpBody());
            return false;
        }

        return $platform;
    }

    /**
     * Retrives the country of the platform.
     *
     * @return string|false The two-letter ISO code of the country or `false` if no data found.
     */
    public function get_platform_country() {
        $platform = $this->get_platform_data();

        if ( ! $platform ) {
            return false;
        }

        return $platform->country;
    }

    /**
     * Retrieves supported transfer countries based on the marketplace country.
     * Currently only the EU countries are supported for each other.
     *
     * @param string $country_code (Optional) The two-letter ISO code of the country of the marketplace.
     *
     * @return string[] List of two-letter ISO codes of the supported transfer countries.
     */
    public function get_supported_transfer_countries( $country_code = null ) {
        try {
            if ( empty( $country_code ) ) {
                $country_code = $this->get_platform_country();
            }

            // Get the list of EU countries.
            $eu_countries = $this->get_european_countries();

            // Apply the feature for EU countries and US only.
            if ( ! ( 'US' === $country_code || in_array( $country_code, $eu_countries, true )) ) {
                return [];
            }

            $cache_key     = "stripe_express_get_specs_for_$country_code";
            $country_specs = get_transient( $cache_key );

            if ( false === $country_specs || apply_filters('wcfm_stripe_force_reload_transients', true) ) {
                $country_specs = $this->stripe->countrySpecs->retrieve($country_code);
                set_transient( $cache_key, $country_specs );
            }

            if ( ! isset( $country_specs->supported_transfer_countries ) ) {
                return [];
            }

            return $country_specs->supported_transfer_countries;
        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            wcfm_stripe_log(sprintf( __( 'Could not retrieve countryspec: %s', 'wc-frontend-manager' ), $e->getMessage() ), 'error');
            wcfm_stripe_log('Error Details: ' . $e->getHttpBody());
            return [];
        }
    }

    /**
     * Retrieves the supported European countries.
     *
     * @return array
     */
    public function get_european_countries() {
        $eu_countries = \WC()->countries->get_european_union_countries();
        $non_eu_sepa_countries = [ 'AD', 'CH', 'GB', 'MC', 'SM', 'VA' ];
        return array_merge( $eu_countries, $non_eu_sepa_countries );
    }

    /**
     *  @return \Stripe\Account
     */
    public function retrieve_account($params = null, $opts = null) {
        $account = null;

        $stripe_user_id = get_user_meta($this->user_id, 'stripe_user_id', true);

        if ($stripe_user_id) {
            try {
                $account = $this->stripe->accounts->retrieve($stripe_user_id, $params, $opts);
            } catch (\Stripe\Exception\ApiErrorException $api_error) {
                wcfm_stripe_log('Can not fetch stripe account. Reason: ' . $api_error->getMessage(), 'error');
                wcfm_stripe_log('Error Details: ' . $api_error->getHttpBody());
            }
        }

        return $account;
    }

    /**
     *  deletes all stripe related info from DB
     */
    public function delete_stripe_data() {
        delete_user_meta($this->user_id, 'vendor_connected');
        delete_user_meta($this->user_id, 'admin_client_id');
        delete_user_meta($this->user_id, 'stripe_user_id');
        delete_user_meta($this->user_id, 'stripe_connect_type');
        delete_user_meta($this->user_id, 'stripe_card_payments_enabled');
        delete_user_meta($this->user_id, 'stripe_details_submitted');
        delete_user_meta($this->user_id, 'stripe_account_capabilities');
    }

    public function needs_cross_border_payment($country) {
        $platform_country = $this->get_platform_country();
        $cross_border_supported_countries = array_keys($this->get_cross_border_supported_countries());
        $allow_cross_border_payment = ($platform_country === 'US') && in_array($country, $cross_border_supported_countries);

        return apply_filters('wcfmmp_stripe_is_allow_cross_border_payment', $allow_cross_border_payment, $country, $platform_country);
    }

    /**
     *  @link https://docs.stripe.com/connect/cross-border-payouts#supported-countries
     */
    public function get_cross_border_supported_countries() {
        return apply_filters('wcfm_stripe_cross_border_supported_countries', [
            'AL' => __( 'Albania', 'woocommerce' ),
            'DZ' => __( 'Algeria', 'woocommerce' ),
            'AO' => __( 'Angola', 'woocommerce' ),
            'AG' => __( 'Antigua and Barbuda', 'woocommerce' ),
            'AR' => __( 'Argentina', 'woocommerce' ),
            'AM' => __( 'Armenia', 'woocommerce' ),
            'AU' => __( 'Australia', 'woocommerce' ),
            'AT' => __( 'Austria', 'woocommerce' ),
            'AZ' => __( 'Azerbaijan', 'woocommerce' ),
            'BS' => __( 'Bahamas', 'woocommerce' ),
            'BH' => __( 'Bahrain', 'woocommerce' ),
            'BD' => __( 'Bangladesh', 'woocommerce' ),
            'BE' => __( 'Belgium', 'woocommerce' ),
            'BJ' => __( 'Benin', 'woocommerce' ),
            'BT' => __( 'Bhutan', 'woocommerce' ),
            'BO' => __( 'Bolivia', 'woocommerce' ),
            'BA' => __( 'Bosnia and Herzegovina', 'woocommerce' ),
            'BW' => __( 'Botswana', 'woocommerce' ),
            'BN' => __( 'Brunei', 'woocommerce' ),
            'BG' => __( 'Bulgaria', 'woocommerce' ),
            'KH' => __( 'Cambodia', 'woocommerce' ),
            'CA' => __( 'Canada', 'woocommerce' ),
            'CL' => __( 'Chile', 'woocommerce' ),
            'CO' => __( 'Colombia', 'woocommerce' ),
            'CR' => __( 'Costa Rica', 'woocommerce' ),
            'CI' => __( 'Ivory Coast', 'woocommerce' ),
            'HR' => __( 'Croatia', 'woocommerce' ),
            'CY' => __( 'Cyprus', 'woocommerce' ),
            'CZ' => __( 'Czech Republic', 'woocommerce' ),
            'DK' => __( 'Denmark', 'woocommerce' ),
            'DO' => __( 'Dominican Republic', 'woocommerce' ),
            'EC' => __( 'Ecuador', 'woocommerce' ),
            'EG' => __( 'Egypt', 'woocommerce' ),
            'SV' => __( 'El Salvador', 'woocommerce' ),
            'EE' => __( 'Estonia', 'woocommerce' ),
            'ET' => __( 'Ethiopia', 'woocommerce' ),
            'FI' => __( 'Finland', 'woocommerce' ),
            'FR' => __( 'France', 'woocommerce' ),
            'GA' => __( 'Gabon', 'woocommerce' ),
            'GM' => __( 'Gambia', 'woocommerce' ),
            'DE' => __( 'Germany', 'woocommerce' ),
            'GH' => __( 'Ghana', 'woocommerce' ),
            'GR' => __( 'Greece', 'woocommerce' ),
            'GT' => __( 'Guatemala', 'woocommerce' ),
            'GY' => __( 'Guyana', 'woocommerce' ),
            'HK' => __( 'Hong Kong', 'woocommerce' ),
            'HU' => __( 'Hungary', 'woocommerce' ),
            'IS' => __( 'Iceland', 'woocommerce' ),
            'IN' => __( 'India', 'woocommerce' ),
            'ID' => __( 'Indonesia', 'woocommerce' ),
            'IE' => __( 'Ireland', 'woocommerce' ),
            'IL' => __( 'Israel', 'woocommerce' ),
            'IT' => __( 'Italy', 'woocommerce' ),
            'JM' => __( 'Jamaica', 'woocommerce' ),
            'JP' => __( 'Japan', 'woocommerce' ),
            'JO' => __( 'Jordan', 'woocommerce' ),
            'KZ' => __( 'Kazakhstan', 'woocommerce' ),
            'KE' => __( 'Kenya', 'woocommerce' ),
            'KW' => __( 'Kuwait', 'woocommerce' ),
            'LA' => __( 'Laos', 'woocommerce' ),
            'LV' => __( 'Latvia', 'woocommerce' ),
            'LI' => __( 'Liechtenstein', 'woocommerce' ),
            'LT' => __( 'Lithuania', 'woocommerce' ),
            'LU' => __( 'Luxembourg', 'woocommerce' ),
            'MO' => __( 'Macao', 'woocommerce' ),
            'MG' => __( 'Madagascar', 'woocommerce' ),
            'MY' => __( 'Malaysia', 'woocommerce' ),
            'MT' => __( 'Malta', 'woocommerce' ),
            'MU' => __( 'Mauritius', 'woocommerce' ),
            'MX' => __( 'Mexico', 'woocommerce' ),
            'MD' => __( 'Moldova', 'woocommerce' ),
            'MC' => __( 'Monaco', 'woocommerce' ),
            'MN' => __( 'Mongolia', 'woocommerce' ),
            'MA' => __( 'Morocco', 'woocommerce' ),
            'MZ' => __( 'Mozambique', 'woocommerce' ),
            'NA' => __( 'Namibia', 'woocommerce' ),
            'NL' => __( 'Netherlands', 'woocommerce' ),
            'NZ' => __( 'New Zealand', 'woocommerce' ),
            'NE' => __( 'Niger', 'woocommerce' ),
            'NG' => __( 'Nigeria', 'woocommerce' ),
            'MK' => __( 'North Macedonia', 'woocommerce' ),
            'NO' => __( 'Norway', 'woocommerce' ),
            'OM' => __( 'Oman', 'woocommerce' ),
            'PK' => __( 'Pakistan', 'woocommerce' ),
            'PA' => __( 'Panama', 'woocommerce' ),
            'PY' => __( 'Paraguay', 'woocommerce' ),
            'PE' => __( 'Peru', 'woocommerce' ),
            'PH' => __( 'Philippines', 'woocommerce' ),
            'PL' => __( 'Poland', 'woocommerce' ),
            'PT' => __( 'Portugal', 'woocommerce' ),
            'QA' => __( 'Qatar', 'woocommerce' ),
            'RO' => __( 'Romania', 'woocommerce' ),
            'RW' => __( 'Rwanda', 'woocommerce' ),
            'SM' => __( 'San Marino', 'woocommerce' ),
            'SA' => __( 'Saudi Arabia', 'woocommerce' ),
            'SN' => __( 'Senegal', 'woocommerce' ),
            'RS' => __( 'Serbia', 'woocommerce' ),
            'SG' => __( 'Singapore', 'woocommerce' ),
            'SK' => __( 'Slovakia', 'woocommerce' ),
            'SI' => __( 'Slovenia', 'woocommerce' ),
            'ZA' => __( 'South Africa', 'woocommerce' ),
            'KR' => __( 'South Korea', 'woocommerce' ),
            'ES' => __( 'Spain', 'woocommerce' ),
            'LK' => __( 'Sri Lanka', 'woocommerce' ),
            'LC' => __( 'Saint Lucia', 'woocommerce' ),
            'SE' => __( 'Sweden', 'woocommerce' ),
            'CH' => __( 'Switzerland', 'woocommerce' ),
            'TW' => __( 'Taiwan', 'woocommerce' ),
            'TZ' => __( 'Tanzania', 'woocommerce' ),
            'TH' => __( 'Thailand', 'woocommerce' ),
            'TT' => __( 'Trinidad and Tobago', 'woocommerce' ),
            'TN' => __( 'Tunisia', 'woocommerce' ),
            'TR' => __( 'Turkey', 'woocommerce' ),
            'AE' => __( 'United Arab Emirates', 'woocommerce' ),
            'GB' => __( 'United Kingdom', 'woocommerce' ),
            'UY' => __( 'Uruguay', 'woocommerce' ),
            'UZ' => __( 'Uzbekistan', 'woocommerce' ),
            'VN' => __( 'Vietnam', 'woocommerce' ),
        ]);
    }
}

<?php

use \Stripe\StripeClient;

class WCFM_Stripe_Connect_Client {

    protected $client_id;
    protected $secret_key;
    protected $stripe;
    protected $user_id;

    public function __construct($client_id = "", $secret_key = "") {
        $this->client_id = $client_id;
        $this->secret_key = $secret_key;
        $this->stripe = new StripeClient($secret_key);
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
            // delete all stripe related info from DB
            delete_user_meta($this->user_id, 'vendor_connected');
            delete_user_meta($this->user_id, 'admin_client_id');
            delete_user_meta($this->user_id, 'stripe_user_id');
            delete_user_meta($this->user_id, 'stripe_connect_type');
        }
    }

    public function get_stripe_accounts_args() {
        $user       = get_user_by('id', $this->user_id);
        $store_name = wcfm_get_vendor_store_name($this->user_id);
        $store_name = empty($store_name) ? $user->display_name : $store_name;
        
        $stripe_connect_args = [];
        if (apply_filters('wcfm_is_allow_stripe_express_api', true)) {
            // Before you begin: https://stripe.com/docs/connect/express-accounts#prerequisites-for-using-express
            // Express account prefill information
            $stripe_connect_args['type'] = 'express';
            $stripe_connect_args['capabilities'] = [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ];
        } else {
            // Standard account prefill information
            $stripe_connect_args['type'] = 'standard';
        }

        $stripe_connect_args['business_type'] = 'individual';
        $stripe_connect_args['business_profile'] = [
            'name' => $store_name,
            //'url' => wcfmmp_get_store_url($this->user_id)
        ];

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
            wcfm_log('Can not delete account. Reason: ' . $api_error->getMessage(), 'error');
        }

        if (isset($account->deleted) && $account->deleted) {
            delete_user_meta($this->user_id, 'vendor_connected');
            delete_user_meta($this->user_id, 'admin_client_id');
            delete_user_meta($this->user_id, 'stripe_user_id');
            delete_user_meta($this->user_id, 'stripe_connect_type');

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
            wcfm_log('Can not create account. Reason: ' . $api_error->getMessage(), 'error');
        }

        if ($account && isset($account->id)) {
            $vendor_data = get_user_meta($this->user_id, 'wcfmmp_profile_settings', true);
            $vendor_data['payment']['method'] = 'stripe';
            
            update_user_meta($this->user_id, 'wcfmmp_profile_settings', $vendor_data);
            update_user_meta($this->user_id, 'admin_client_id', $this->client_id);
            update_user_meta($this->user_id, 'stripe_user_id', $account->id);
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
            wcfm_log('Can not create account link. Reason: ' . $api_error->getMessage(), 'error');
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

        try {
            $account = $this->stripe->accounts->retrieve($stripe_user_id, $params, $opts);
        } catch (\Stripe\Exception\ApiErrorException $api_error) {
            wcfm_log('Can not verify details submitted. Reason: ' . $api_error->getMessage(), 'error');
        }

        if (isset($account->details_submitted) && $account->details_submitted) {
            update_user_meta($this->user_id, 'vendor_connected', true);
            update_user_meta($this->user_id, 'admin_client_id', $this->client_id);

            return true;
        }

        return false;
    }
}

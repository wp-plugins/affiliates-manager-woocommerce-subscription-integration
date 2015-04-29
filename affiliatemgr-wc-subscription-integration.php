<?php

/*
  Plugin Name: Affiliates Manager WooCommerce Subscription Integration
  Plugin URI: https://wpaffiliatemanager.com/affiliates-manager-woocommerce-subscription-integration/
  Description: Process an affiliate commission via Affiliates Manager after a WooCommerce subscription payment.
  Version: 1.0.2
  Author: wp.insider, affmngr
  Author URI: https://wpaffiliatemanager.com
 */

add_action('processed_subscription_payments_for_order', 'wpam_handle_woocommerce_subscription_payment');  //Triggered when a subscription payment is made

function wpam_handle_woocommerce_subscription_payment($order) {
    if (!is_object($order)) {
        $order = new WC_Order($order);
    }

    $order_id = $order->id;
    $total = $order->order_total;
    $shipping = $order->get_total_shipping();
    $tax = $order->get_total_tax();
    WPAM_Logger::log_debug('WooCommerce Subscription Integration - Total amount: ' . $total . ', Total shipping: ' . $shipping . ', Total tax: ' . $tax);
    $purchaseAmount = $total - $shipping - $tax;
    $wpam_refkey = get_post_meta($order_id, '_wpam_refkey', true);
    if (empty($wpam_refkey)) {
        WPAM_Logger::log_debug("WooCommerce Subscription Integration - could not get wpam_refkey from cookie. This is not an affiliate sale");
        return;
    }
    $order_status = $order->status;
    WPAM_Logger::log_debug("WooCommerce Subscription Integration - Order status: " . $order_status);
    if (strtolower($order_status) != "completed" && strtolower($order_status) != "processing") {
        WPAM_Logger::log_debug("WooCommerce Subscription Integration - Order status for this transaction is not in a 'completed' or 'processing' state. Commission will not be awarded at this stage.");
        WPAM_Logger::log_debug("WooCommerce Subscription Integration - Commission for this transaciton will be awarded when you set the order status to completed or processing.");
        return;
    }
    $requestTracker = new WPAM_Tracking_RequestTracker();
    WPAM_Logger::log_debug('WooCommerce Subscription Integration - awarding commission for order ID: ' . $order_id . '. Purchase amount: ' . $purchaseAmount);
    $requestTracker->handleCheckoutWithRefKey($order_id, $purchaseAmount, $wpam_refkey);
}



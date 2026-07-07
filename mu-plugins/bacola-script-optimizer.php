<?php
/**
 * Plugin Name: Bacola Performance — Script Optimizer
 * Description: Moves third-party plugin scripts to load only on pages that need them.
 * Version:     1.0.0
 *
 * Deploy to: wp-content/mu-plugins/bacola-script-optimizer.php
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'bacola_perf_dequeue_plugin_scripts', 99 );

function bacola_perf_dequeue_plugin_scripts(): void {
    $is_checkout = function_exists( 'is_checkout' ) && is_checkout();
    $is_cart     = function_exists( 'is_cart' )     && is_cart();
    $is_account  = function_exists( 'is_account_page' ) && is_account_page();
    $is_product  = function_exists( 'is_product' )  && is_product();

    // Revolut payment gateway: 61.8 KB from external origin, only needed on checkout.
    if ( ! $is_checkout ) {
        bacola_dequeue_by_src( 'merchant.revolut.com' );
    }

    // WooCommerce Loyalty & Rewards (WPLoyalty): alertify + wlr-main.
    // Needed only on pages where rewards are visible or applied.
    if ( ! ( $is_product || $is_cart || $is_checkout || $is_account ) ) {
        bacola_dequeue_by_src( 'alertify' );
        bacola_dequeue_by_src( 'wlr-' );
    }
}

function bacola_dequeue_by_src( string $fragment ): void {
    global $wp_scripts;

    if ( empty( $wp_scripts->queue ) ) {
        return;
    }

    foreach ( $wp_scripts->queue as $handle ) {
        if ( ! isset( $wp_scripts->registered[ $handle ] ) ) {
            continue;
        }

        $src = $wp_scripts->registered[ $handle ]->src;

        if ( is_string( $src ) && strpos( $src, $fragment ) !== false ) {
            wp_dequeue_script( $handle );
        }
    }
}

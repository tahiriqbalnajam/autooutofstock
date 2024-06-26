<?php
/*
Plugin Name: WooCommerce Auto Hide Out of Stock Products
Description: Automatically hides products that have been out of stock for a specified number of days.
Version: 1.0
Author: Your Name
*/
add_action('woocommerce_update_product', 'set_outofstock_date_meta', 10, 2);
function set_outofstock_date_meta($product_id, $product) {
    if ($product->get_stock_status() === 'outofstock') {
        $current_date = current_time('mysql');
        update_post_meta($product_id, '_outofstock_date', $current_date);
    } else {
        delete_post_meta($product_id, '_outofstock_date');
    }
}
// Add date meta when product is set to out of stock
function action_woocommerce_no_stock( $wc_get_product ) {
    // Retrieves the date, in localized format.
    $date = current_time('mysql');;
    
    // Update meta
    $wc_get_product->update_meta_data( '_outofstock_date', $date );

    // Save
    $wc_get_product->save();
}
add_action( 'woocommerce_no_stock', 'action_woocommerce_no_stock', 10, 1 );

function action_woocommerce_admin_process_product_object( $product ) {
    // Get stock quantity
    $stock_quantity = $product->get_stock_quantity();
    
    // Greater than or equal to
    if ( $stock_quantity >= 1 ) {
        // Get meta value
        $no_stock_date = $product->get_meta( '_outofstock_date' );

        // NOT empty
        if ( ! empty ( $no_stock_date ) ) {
            // Update
            $product->update_meta_data( '_outofstock_date', '' );
        }
    }
}
add_action( 'woocommerce_admin_process_product_object', 'action_woocommerce_admin_process_product_object', 10, 1 ); 


function action_woocommerce_product_query( $q, $query ) {
    // Returns true when on the product archive page (shop).
    if ( is_shop() ) {
        // Retrieves the date, in localized format.
        $date = wp_date( 'Y-m-d' );
        
        // Date - 10 days
        $date = wp_date( 'Y-m-d', strtotime( $date . ' 10 days' ) );
        
        // Get any existing meta query
        $meta_query = $q->get( 'meta_query' );
        
        // Define an additional meta query 
        $meta_query[] = array(
            'relation'    => 'OR',
            array(
                'key'     => '_outofstock_date',
                'value'   => $date,
                'compare' => '>',
                'type'    => 'date',
            ),
            array(
                'key'     => '_outofstock_date',
                'compare' => 'NOT EXISTS',
                'type'    => 'date',
            )
        );

        // Set the new merged meta query
        $q->set( 'meta_query', $meta_query );
    }
}
add_action( 'woocommerce_product_query', 'action_woocommerce_product_query', 10, 2 );

// // Schedule the cron event
// add_action('wp', 'schedule_hide_outofstock_products_cron');
// function schedule_hide_outofstock_products_cron() {
//     if (!wp_next_scheduled('hide_outofstock_products_cron_event')) {
//         wp_schedule_event(time(), 'daily', 'hide_outofstock_products_cron_event');
//     }
// }

// // Cron event callback using WC_Product_Query
// add_action('hide_outofstock_products_cron_event', 'hide_outofstock_products');
// function hide_outofstock_products() {
//     $days_to_hide = 30; // Number of days after which the product should be hidden
//     $date_threshold = date('Y-m-d H:i:s', strtotime("-$days_to_hide days"));

//     $query = new WC_Product_Query(array(
//         'limit' => -1,
//         'status' => 'publish',
//         'meta_query' => array(
//             array(
//                 'key'     => '_outofstock_date',
//                 'value'   => $date_threshold,
//                 'compare' => '<=',
//                 'type'    => 'DATETIME'
//             )
//         )
//     ));

//     $products = $query->get_products();

//     foreach ($products as $product) {
//         $product->set_status('private');
//         $product->save();
//     }
// }

// // Unschedule the cron event on plugin deactivation
// register_deactivation_hook(__FILE__, 'unschedule_hide_outofstock_products_cron');
// function unschedule_hide_outofstock_products_cron() {
//     $timestamp = wp_next_scheduled('hide_outofstock_products_cron_event');
//     wp_unschedule_event($timestamp, 'hide_outofstock_products_cron_event');
// }

<?php
/**
 * Runs on Uninstall of Software License Manager
 * https://businessbloomer.com/woocommerce-easily-get-order-info-total-items-etc-from-order-object/
 * @package   Software License Manager
 * @author    Michel Velis
 * @license   GPL-2.0+
 * @link      http://epikly.com
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

global $post, $woocommerce, $product;


add_action('woocommerce_checkout_update_order_meta', 'slm_add_lic_key_meta_update');
add_action('woocommerce_admin_order_data_after_billing_address', 'slm_add_lic_key_meta_display', 10, 1);
add_action('woocommerce_order_status_completed', 'slm_order_completed', 81);
add_action('woocommerce_order_status_completed', 'wc_slm_access_expiration', 82);
add_action('woocommerce_order_details_after_order_table', 'slm_order_details', 10, 1);
add_action('woocommerce_thankyou', 'slm_show_msg', 80 );
add_action('woocommerce_order_status_completed', 'wc_slm_on_complete_purchase', 10);

function wc_slm_on_complete_purchase($order_id) {
	//SLM_Helper_Class::write_log('loading wc_slm_on_complete_purchase');
	if ( SLM_SITE_URL != '' && WOO_SLM_API_SECRET != '') {
		wc_slm_create_license_keys($order_id);
	}
}

function wc_slm_create_license_keys($order_id) {

	//SLM_Helper_Class::write_log('loading wc_slm_create_license_keys');

	$order_id 			= wc_get_order($order_id);
	$purchase_id_ 		= $order_id->get_id();
	$variations_items_ 	= $order_id->get_items();

	//SLM_Helper_Class::write_log('purchase_id_ -- '.$purchase_id_ );	

	global $user_id;

	$user_id 									= $order_id->get_user_id();
	$user_info 									= get_userdata($user_id);
	$get_user_meta 								= get_user_meta($user_id);
	$payment_meta['user_info']['first_name'] 	= $get_user_meta['billing_first_name'][0];
	$payment_meta['user_info']['last_name']  	= $get_user_meta['billing_last_name'][0];
	$payment_meta['user_info']['email'] 	 	= $get_user_meta['billing_email'][0];
	$payment_meta['user_info']['company'] 	 	= $get_user_meta['billing_company'][0];

	//SLM_Helper_Class::write_log('user_id -- '.$user_id  );

	// Collect license keys
	$licenses = array();
	$items = $order_id->get_items();
    //SLM_Helper_Class::write_log('order_id -- '.$order_id  );


	foreach ($items as $item => $values) {
        //SLM_Helper_Class::write_log('Entered foreach -- ' );
		$download_id 	= $product_id = $values['product_id'];
		$product 		= new WC_Product($product_id);

		$download_quantity = absint($values['qty']);
        //SLM_Helper_Class::write_log('download_quantity -- '.$download_quantity  );

		for ($i = 1; $i <= $download_quantity; $i++) {
			/**
			 * Calculate Expire date
			 * @since 1.0.3
			 */
			$expiration = '';

			$renewal_period = (int) wc_slm_get_licensing_renewal_period($product_id);
			$renewal_term 	= wc_slm_get_licensing_renewal_period_term($product_id);

			if ($renewal_period == 'onetime') {
				$expiration = '0000-00-00';
			}
			// elseif ($renewal_period == 30) {
			// 	$renewal_period = date('Y-m-d', strtotime('+' . 31 . ' days'));
			// }
			else {
				$expiration = date('Y-m-d', strtotime('+' . $renewal_period .' '. $renewal_term));
			}
			// SLM_Helper_Class::write_log('renewal_period -- '.$renewal_period  );
			// SLM_Helper_Class::write_log('exp -- ' . $expiration);
			// SLM_Helper_Class::write_log('term -- ' . $renewal_term);

			// Sites allowed get license meta from variation
			$sites_allowed 			= wc_slm_get_sites_allowed($product_id);
			$devices_allowed 		= wc_slm_get_devices_allowed($product_id);
			$amount_of_licenses 	= wc_slm_get_licenses_qty($product_id);

            //SLM_Helper_Class::write_log('sites_allowed -- '.$sites_allowed  );
            //SLM_Helper_Class::write_log('devices_allowed -- '.$devices_allowed  );
            //SLM_Helper_Class::write_log('amount_of_licenses -- '.$amount_of_licenses  );

			if (!$sites_allowed) {
				$sites_allowed_error = __('License could not be created: Invalid sites allowed number.', 'softwarelicensemanager');
				$int = wc_insert_payment_note($purchase_id_, $sites_allowed_error);
				break;
			}

			// Get an instance of the WC_Order object (same as before)
			$order = new WC_Order( $order_id );

			// Get the order ID
			$order_id = $order->get_id();
            //SLM_Helper_Class::write_log('order_id -- '.$order_id  );

			// Get the custumer ID
			// $user_id = $order->get_user_id();
			$order_data = $order->get_data(); // The Order data

			// Iterating through each WC_Order_Item objects
			foreach( $order-> get_items() as $item_key => $item_values ){
                //SLM_Helper_Class::write_log('Entered foreach -- '.$order  );
				## Using WC_Order_Item methods ##
				$item_id 			= $item_values->get_id();
				$item_name 			= $item_values->get_name();
				$item_type 			= $item_values->get_type();

				## Access Order Items data properties (in an array of values) ##
				$item_data 			= $item_values->get_data();
				$product_name 		= $item_data['name'];
				$product_id 		= $item_data['product_id'];
				// $variation_id 		= $item_data['variation_id'];
				// $quantity 			= $item_data['quantity'];
				// $tax_class 			= $item_data['tax_class'];
				// $line_subtotal 		= $item_data['subtotal'];
				// $line_subtotal_tax 	= $item_data['subtotal_tax'];
				// $line_total 		= $item_data['total'];
				// $line_total_tax 	= $item_data['total_tax'];
				// $post_object 		= get_post($variation_id);

				$amount_of_licenses 		= wc_slm_get_sites_allowed($product_id);
				$_license_current_version 	= get_post_meta( $product_id, '_license_current_version', true );
				$_license_until_version 	= get_post_meta($product_id, '_license_until_version', true);
				$amount_of_licenses_devices = wc_slm_get_devices_allowed($product_id);
				$current_version 			= (int)get_post_meta( $product_id, '_license_current_version', true);
				$license_type 				= get_post_meta( $product_id, '_license_type', true );
			}

			// Transaction id
			$transaction_id = wc_get_payment_transaction_id($product_id);
            //SLM_Helper_Class::write_log('transaction_id -- '.$transaction_id  );

			// Build item name
			$item_name = $product->get_title();
            //SLM_Helper_Class::write_log('item_name -- '.$item_name  );

			// Build parameters
			$api_params = array();
			$api_params['slm_action'] 			= 'slm_create_new';
			$api_params['secret_key'] 			= KEY_API;
			$api_params['first_name'] 			= (isset($payment_meta['user_info']['first_name'])) ? $payment_meta['user_info']['first_name'] : '';
			$api_params['last_name'] 			= (isset($payment_meta['user_info']['last_name'])) ? $payment_meta['user_info']['last_name'] : '';
			$api_params['email'] 				= (isset($payment_meta['user_info']['email'])) ? $payment_meta['user_info']['email'] : '';
			$api_params['company_name'] 		= $payment_meta['user_info']['company'];
			$api_params['purchase_id_'] 		= $purchase_id_;
			$api_params['product_ref'] 			= $product_id; // TODO: get product id
			$api_params['txn_id'] 				= $purchase_id_;
			$api_params['max_allowed_domains'] 	= $amount_of_licenses;
			$api_params['max_allowed_devices'] 	= $amount_of_licenses_devices;
			$api_params['date_created'] 		= date('Y-m-d');
			$api_params['date_expiry'] 			= $expiration;
			$api_params['until'] 				= $_license_until_version;
			$api_params['current_ver'] 			= $_license_current_version;
			$api_params['subscr_id'] 			= $order->get_customer_id();
			$api_params['lic_type'] 			= $license_type;

			//access_expires
			//SLM_Helper_Class::write_log('license_type -- ' . $license_type );
			// Send query to the license manager server
			$url 			= SLM_SITE_URL . '?' . http_build_query($api_params);
			$url 			= str_replace(array('http://', 'https://'), '', $url);
			$url 			= 'http://' . $url;
            error_log("url to call: ${url}");
			$response 		= wp_remote_get($url, array('timeout' => 20, 'sslverify' => false));              
            
			$license_key 	= wc_slm_get_license_key($response);
            error_log("license_key: ${license_key}");
			// Collect license keys
			if ($license_key) {
				$licenses[] = array(
					'item' 		=>	$item_name,
					'key' 		=>	$license_key,
					'expires' 	=>	$expiration,
					'type' 		=>	$license_type,
					'status' 	=>	'pending',
					'version' 	=>	$_license_current_version,
					'until' 	=>	$_license_until_version
			);
			}
		}
	}

	// Payment note
	wc_slm_payment_note($order_id, $licenses);

	// Assign licenses
	wc_slm_assign_licenses($order_id, $licenses);
}

function wc_slm_get_license_key($response) {

	// Check for error in the response
	if (is_wp_error($response)) {
		return false;
	}
    error_log("the response is:");
    error_log( print_r($response , true));
    
	// Get License data
    $testBody = wp_remote_retrieve_body($response);
    error_log("the testBody is:");
    error_log( print_r($testBody , true));

    $json = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', utf8_encode(wp_remote_retrieve_body($response)));
    error_log("the json is:");
    error_log( print_r($json , true));
	$license_data = json_decode($json);
    error_log("license_data:");
    error_log( print_r($license_data , true));

	if (!isset($license_data->key)) {
		return false;
	}
	// Prepare note text
	return $license_data->key;
}

function wc_slm_get_license_id($license){
	global $wpdb;
	$license_id = $wpdb->get_row("SELECT ID, license_key FROM ". $wpdb->prefix . "lic_key_tbl" . " WHERE license_key = '".$license."' ORDER BY id DESC LIMIT 0,1");
	return $license_id->ID;
}

function wc_slm_payment_note($order_id, $licenses) {
	if ($licenses && count($licenses) != 0) {
		$message = __('License Key(s) generated', 'softwarelicensemanager');

		foreach ($licenses as $license) {
			$license_key = $license['key'];
			$message .= '<br />' . $license['item'] . ': <a href="'. get_admin_url() . 'admin.php?page=slm_manage_license&edit_record=' . wc_slm_get_license_id($license_key).'">' . $license_key . '</a>';

			add_post_meta($order_id, 'slm_wc_license_order_key', 	$license_key);
			add_post_meta($order_id, 'slm_wc_license_expires', 		$license[ 'expires']);
			add_post_meta($order_id, 'slm_wc_license_type', 		$license[ 'type']);
			add_post_meta($order_id, 'slm_wc_license_status', 		$license['status']);
			add_post_meta($order_id, 'slm_wc_license_version', 		$license[ 'version']);
			add_post_meta($order_id, 'slm_wc_until_version', 		$license['until']);

			//SLM_Helper_Class::write_log($license_key);
		}
	}
	else {
		$message = __('License Key(s) could not be created.', 'softwarelicensemanager');
	}

	// Save note
	$int = wc_insert_payment_note($order_id, $message);
}


function wc_slm_access_expiration($order_id, $lic_expiry = ''){
	global $wpdb, $post;

	$order_id 		= wc_get_order($order_id);
	$purchase_id_ 	= $order_id->get_id();
	$order 			= wc_get_order($order_id);
	$order_data 	= $order->get_meta('slm_wc_license_expires');
	$lic_expiry 	= $order_data;

	$query = "UPDATE " . $wpdb->prefix ."woocommerce_downloadable_product_permissions SET access_expires = '". $lic_expiry ."' WHERE order_id = ". $purchase_id_ .";";

	$wpdb->query($query);
	//SLM_Helper_Class::write_log('log:'  . $query );

}

function wc_slm_assign_licenses($order_id, $licenses) {
	if (count($licenses) != 0) {
		add_post_meta($order_id, '_wc_slm_payment_licenses', $licenses);
	}
}


function wc_slm_get_sites_allowed($product_id) {
	$wc_slm_sites_allowed = absint(get_post_meta($product_id, '_domain_licenses', true));
	if (empty($wc_slm_sites_allowed)) {
		return false;
	}
	return $wc_slm_sites_allowed;
}

function wc_slm_get_lic_type($product_id) {
	$_license_type = absint(get_post_meta($product_id, '_license_type', true));
	if (empty($_license_type)) {
		return false;
	}
	return $_license_type;
}

function wc_slm_get_devices_allowed($product_id) {
	$_devices_licenses = absint(get_post_meta($product_id, '_devices_licenses', true));
	if (empty($_devices_licenses)) {
		return false;
	}
	return $_devices_licenses;
}

function wc_slm_get_licenses_qty($product_id) {
	$amount_of_licenses = absint(get_post_meta($product_id, '_amount_of_licenses', true));
	if (empty($amount_of_licenses)) {
		return false;
	}
	return $amount_of_licenses;
}

function wc_slm_get_licensing_renewal_period($product_id) {
	$_license_renewal_period = absint(get_post_meta($product_id, '_license_renewal_period', true));
	if (empty($_license_renewal_period)) {
		return 0;
	}
	return $_license_renewal_period;
}
//_license_renewal_period_term

function wc_slm_get_licensing_renewal_period_term($product_id)
{
	$term = get_post_meta($product_id, '_license_renewal_period_term', true);
	return $term;
}

function wc_slm_is_licensing_enabled($download_id) {
	$licensing_enabled = absint(get_post_meta($download_id, '_wc_slm_licensing_enabled', true));
	// Set defaults
	if ($licensing_enabled) {
		return true;
	}
	else {
		return false;
	}
}

function wc_insert_payment_note($order_id, $msg) {
	$order = new WC_Order($order_id);
	$order->add_order_note($msg);
}

function wc_get_payment_transaction_id($order_id) {
	return get_post_meta($order_id, '_transaction_id', true);
}

function slm_order_completed( $order_id ) {

	global $user_id, $wpdb;
	$order 					= wc_get_order($order_id);
	$purchase_id_ 			= $order->get_id();
	$order_data 			= $order->get_data(); // The Order data
	$order_billing_email 	= $order_data['billing']['email'];
	$billing_address 		= $order_billing_email;
	$message 				= 'error: 000 null';
	$get_user_meta 			= get_user_meta($user_id);
    $headers 				= 'From: '. get_bloginfo( 'name' ).' <'.get_bloginfo('admin_email').'>' . "\r\n";
    //wp_mail( $billing_address, 'License details', $message, $headers );

	// The text for the note
	$note = __("Order confirmation email sent to: <a href='mailto:". $billing_address ."'>" . $billing_address . "</a>" );
	// Add the note
	$order->add_order_note( $note );
	// Save the data
	$order->save();
	//SLM_Helper_Class::write_log($order_billing_email . 'License details'. $message . $headers );
}

function slm_show_msg( $order_id ) {
	$order_id 		=  new WC_Order( $order_id );
	$purchase_id_ 	= $order_id->get_id();
	$order 			= wc_get_order( $order_id );
	$items 			= $order->get_items();

	foreach ( $items as $item ) {
	    $product_name 			= $item->get_name();
	    $product_id 			= $item->get_product_id();
	    $product_variation_id 	= $item->get_variation_id();
	    $amount_of_licenses     = wc_slm_get_sites_allowed($product_id);

	    // is a licensed product
	    //var_dump(get_post_meta($product_id));

	    if ($amount_of_licenses) {
			echo '<div class="woocommerce-order-details"> <h2 class="woocommerce-order-details__title">My subscriptions</h2> <table class="woocommerce-table woocommerce-table--order-details shop_table order_details"> <thead> <tr> <th class="woocommerce-table__product-name product-name">My Account</th> </tr> </thead> <tbody> <tr class="woocommerce-table__line-item order_item"> <td class="woocommerce-table__product-name product-name" > You can see and manage your licenses inside your account. <a href="/my-account/my-licenses/">Manage Licenses</a></td> </tr> </tbody> </table> </div>';
		}
	}
}

/**
 * Update the order meta with field value
 */

function slm_add_lic_key_meta_update($order_id)
{
	if (!empty($_POST['slm_wc_license_order_key'])) {
		update_post_meta($order_id, 'slm_wc_license_order_key', sanitize_text_field($_POST['slm_wc_license_order_key']));
	}
	if (!empty($_POST['slm_wc_license_expires'])) {
		update_post_meta($order_id, 'slm_wc_license_expires', sanitize_text_field($_POST['slm_wc_license_expires']));
	}
	if (!empty($_POST['slm_wc_license_type'])) {
		update_post_meta($order_id, 'slm_wc_license_type', sanitize_text_field($_POST['slm_wc_license_type']));
	}

	if (!empty($_POST['slm_wc_license_status'])) {
		update_post_meta($order_id, 'slm_wc_license_status', sanitize_text_field($_POST['slm_wc_license_status']));
	}

	if (!empty($_POST['slm_wc_license_version'])) {
		update_post_meta($order_id, 'slm_wc_license_version', sanitize_text_field($_POST['slm_wc_license_version']));
	}
}
/**
 * Display field value on the order edit page
 */

function slm_add_lic_key_meta_display($order){
	echo '<p><strong>' . __('License key') . ':</strong> <br/>' . get_post_meta($order->get_id(), 'slm_wc_license_order_key', true) . '</p>';
	echo '<p><strong>' . __('License expiration') . ':</strong> <br/>' . get_post_meta($order->get_id(), 'slm_wc_license_expires', true) . '</p>';
	echo '<p><strong>' . __('License type') . ':</strong> <br/>' . get_post_meta($order->get_id(), 'slm_wc_license_type', true) . '</p>';
	echo '<p><strong>' . __('License status') . ':</strong> <br/>' . get_post_meta($order->get_id(), 'slm_wc_license_status', true) . '</p>';
	echo '<p><strong>' . __('License current version') . ':</strong> <br/>' . get_post_meta($order->get_id(), 'slm_wc_license_version', true) . '</p>';
	echo '<p><strong>' . __('Supported until version') . ':</strong> <br/>' . get_post_meta($order->get_id(), 'slm_wc_until_version', true) . '</p>';
}

/**
 * Display values on the order details page
 */

function slm_order_details($order){
	if(!empty( get_post_meta($order->get_id(), 'slm_wc_license_order_key', true))){
		echo '
			<h2 class="woocommerce-order-details__title">License details</h2>
			<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
				<thead>
					<tr>
						<th class="woocommerce-table__product-name product-name">License key</th>
						<th class="woocommerce-table__product-table product-total">Type</th>
					</tr>
				</thead>
				<tbody>
					<tr class="woocommerce-table__line-item order_item">
						<td class="woocommerce-table__product-name product-name">
							' . get_post_meta($order->get_id(), 'slm_wc_license_order_key', true) . '
						</td>
						<td class="woocommerce-table__product-total product-total">
							' . get_post_meta($order->get_id(), 'slm_wc_license_type', true) . '
						</td>
					</tr>
				</tbody>
			</table>
		';
	}
}

/**
 * @snippet       Add Content to the Customer Processing Order Email - WooCommerce
 * https://businessbloomer.com/woocommerce-add-extra-content-order-email/
 */
add_action('woocommerce_email_before_order_table', 'slm_add_license_to_order_confirmation', 20, 4);

function slm_add_license_to_order_confirmation($order, $sent_to_admin, $plain_text, $email)
{
	if ($email->id == 'customer_completed_order') {
        $license_key = get_post_meta($order->get_id(), 'slm_wc_license_order_key', true);
        
		echo '
		<table class="td" cellspacing="0" cellpadding="6" border="1" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family:"Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;"> <thead> <tr> <th class="td" scope="col" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left;"> License key</th> </tr> </thead> <tbody> <tr> <td class="td" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left;"> ' . get_post_meta($order->get_id(), 'slm_wc_license_order_key', true) . ' </td> </tr> </tbody> </table><br><br>
		';
	}
}


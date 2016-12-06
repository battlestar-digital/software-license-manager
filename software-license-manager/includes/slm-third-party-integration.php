<?php

/* * ********************************* */
/* * * WP eStore Plugin Integration ** */
/* * ********************************* */
add_filter('eStore_notification_email_body_filter', 'slm_handle_estore_email_body_filter', 10, 3); //Standard sale notification email
add_filter('eStore_squeeze_form_email_body_filter', 'slm_handle_estore_email_body_filter', 10, 3); //Squeeze form email

function slm_handle_estore_email_body_filter($body, $payment_data, $cart_items) {
    global $slm_debug_logger, $wpdb;
    $slm_debug_logger->log_debug("WP eStore integration - checking if a license key needs to be created for this transaction.");
    $products_table_name = $wpdb->prefix . "wp_eStore_tbl";
    $slm_data = "";

    foreach ($cart_items as $current_cart_item) {
        $prod_id = $current_cart_item['item_number'];
        $retrieved_product = $wpdb->get_row("SELECT * FROM $products_table_name WHERE id = '$prod_id'", OBJECT);
        $package_product = eStore_is_package_product($retrieved_product);
        if ($package_product) {
            $slm_debug_logger->log_debug('Checking license key generation for package/bundle product.');
            $product_ids = explode(',', $retrieved_product->product_download_url);
            foreach ($product_ids as $id) {
                $id = trim($id);
                $retrieved_product_for_specific_id = $wpdb->get_row("SELECT * FROM $products_table_name WHERE id = '$id'", OBJECT);
                $slm_data .= slm_estore_check_and_generate_key($retrieved_product_for_specific_id, $payment_data, $cart_items);
            }
        } else {
            $slm_debug_logger->log_debug('Checking license key generation for single item product.');
            $slm_data .= slm_estore_check_and_generate_key($retrieved_product, $payment_data, $cart_items);
        }
    }

    $body = str_replace("{slm_data}", $slm_data, $body);
    return $body;
}

function slm_estore_check_and_generate_key($retrieved_product, $payment_data, $cart_items) {
    global $slm_debug_logger;
    $license_data = '';

    if ($retrieved_product->create_license == 1) {
        $slm_debug_logger->log_debug('Need to create a license key for this product (' . $retrieved_product->id . ')');
        $slm_key = slm_estore_create_license($retrieved_product, $payment_data, $cart_items);
        $license_data = "\n" . __('Item Name: ', 'slm') . $retrieved_product->name . " - " . __('License Key: ', 'slm') . $slm_key;
        $slm_debug_logger->log_debug('Liense data: ' . $license_data);
        $license_data = apply_filters('slm_estore_item_license_data', $license_data);
    }
    return $license_data;
}

function slm_estore_create_license($retrieved_product, $payment_data, $cart_items) {
    global $slm_debug_logger;
    global $wpdb;
    $product_meta_table_name = WP_ESTORE_PRODUCTS_META_TABLE_NAME;
    
    //Retrieve the default settings values.
    $options = get_option('slm_plugin_options');
    $lic_key_prefix = $options['lic_prefix'];
    $max_domains = $options['default_max_domains'];

    //Lets check any product specific configuration.
    $prod_id = $retrieved_product->id;
    $product_meta = $wpdb->get_row("SELECT * FROM $product_meta_table_name WHERE prod_id = '$prod_id' AND meta_key='slm_max_allowed_domains'", OBJECT);
    if ($product_meta) {
        //Found product specific SLM config data.
        $max_domains = $product_meta->meta_value;
    } else {
        //Use the default value from settings
    }    
    
    $fields = array();
    $fields['license_key'] = uniqid($lic_key_prefix);
    $fields['lic_status'] = 'pending';
    $fields['first_name'] = $payment_data['first_name'];
    $fields['last_name'] = $payment_data['last_name'];
    $fields['email'] = $payment_data['payer_email'];
    $fields['company_name'] = $payment_data['company_name'];
    $fields['txn_id'] = $payment_data['txn_id'];
    $fields['date_created'] = date("Y-m-d"); //Today's date
    $fields['max_allowed_domains'] = $max_domains;

    $slm_debug_logger->log_debug('Inserting license data into the license manager DB table.');
    $fields = array_filter($fields); //Remove any null values.


    $tbl_name = SLM_TBL_LICENSE_KEYS;
    $result = $wpdb->insert($tbl_name, $fields);
    if (!$result) {
        $slm_debug_logger->log_debug('Notice! initial database table insert failed on license key table (User Email: ' . $fields['email'] . '). Trying again by converting charset', true);
        //Convert the default PayPal IPN charset to UTF-8 format
        $first_name = mb_convert_encoding($fields['first_name'], "UTF-8", "windows-1252");
        $fields['first_name'] = esc_sql($first_name);
        $last_name = mb_convert_encoding($fields['last_name'], "UTF-8", "windows-1252");
        $fields['last_name'] = esc_sql($last_name);
        $company_name = mb_convert_encoding($fields['company_name'], "UTF-8", "windows-1252");
        $fields['company_name'] = esc_sql($company_name);

        $result = $wpdb->insert($tbl_name, $fields);
        if (!$result) {
            $slm_debug_logger->log_debug('Error! Failed to update license key table. DB insert query failed.', false);
        }
    }
    //SLM_API_Utility::insert_license_data_internal($fields);

    return $fields['license_key'];
}

/* Code to handle the eStore's product add/edit interface for SLM specific product configuration */
add_filter('eStore_addon_product_settings_filter', 'slm_estore_product_configuration_html', 10, 2); //Render the product add/edit HTML
add_action('eStore_new_product_added', 'slm_estore_new_product_added', 10, 2); //Handle the DB insert after a product add.
add_action('eStore_product_updated', 'slm_estore_product_updated', 10, 2); //Handle the DB update after a product edit.
add_action('eStore_product_deleted', 'slm_estore_product_deleted'); //Handle the DB delete after a product delete.

function slm_estore_product_configuration_html($product_config_html, $prod_id) {
    global $wpdb;
    $product_meta_table_name = WP_ESTORE_PRODUCTS_META_TABLE_NAME;
    
    if (empty($prod_id)) {
        //New product add
        $slm_max_allowed_domains = "";
    } else {
        //Existing product edit
        $product_meta = $wpdb->get_row("SELECT * FROM $product_meta_table_name WHERE prod_id = '$prod_id' AND meta_key='slm_max_allowed_domains'", OBJECT);
        if ($product_meta) {
            $slm_max_allowed_domains = $product_meta->meta_value;
        } else {
            $slm_max_allowed_domains = "";
        }
    }

    $product_config_html .= '<div class="msg_head">Software License Manager Plugin (Click to Expand)</div><div class="msg_body"><table class="form-table">';

    $product_config_html .= '<tr valign="top"><th scope="row">Maximum Allowed Domains</th><td>';
    $product_config_html .= '<input name="slm_max_allowed_domains" type="text" id="slm_max_allowed_domains" value="' . $slm_max_allowed_domains . '" size="10" />';
    $product_config_html .= '<p class="description">Number of domains/installs in which this license can be used. Leave blank if you wish to use the default value set in the license manager plugin settings.</p>';
    $product_config_html .= '</td></tr>';

    $product_config_html .= '</table></div>';

    return $product_config_html;
}

function slm_estore_new_product_added($prod_dat_array, $prod_id) {
    global $wpdb;
    $product_meta_table_name = WP_ESTORE_PRODUCTS_META_TABLE_NAME;

    $fields = array();
    $fields['prod_id'] = $prod_id;
    $fields['meta_key'] = 'slm_max_allowed_domains';
    $fields['meta_value'] = $prod_dat_array['slm_max_allowed_domains'];

    $result = $wpdb->insert($product_meta_table_name, $fields);
    if (!$result) {
        //insert query failed
    }
}

function slm_estore_product_updated($prod_dat_array, $prod_id) {
    global $wpdb;
    $product_meta_table_name = WP_ESTORE_PRODUCTS_META_TABLE_NAME;

    $slm_max_allowed_domains = $prod_dat_array['slm_max_allowed_domains'];
    if (empty($slm_max_allowed_domains)) {
        //If blank value was submitted then delete the data from the table 
        $result = $wpdb->delete($product_meta_table_name, array('prod_id' => $prod_id, 'meta_key' => 'slm_max_allowed_domains'));
        return;
    }

    //Find the existing value for this field (for the given product)
    $product_meta = $wpdb->get_row("SELECT * FROM $product_meta_table_name WHERE prod_id = '$prod_id' AND meta_key='slm_max_allowed_domains'", OBJECT);
    if ($product_meta) {
        //Found existing value so lets update it
        $fields = array();
        $fields['meta_key'] = 'slm_max_allowed_domains';
        $fields['meta_value'] = $prod_dat_array['slm_max_allowed_domains'];
        $result = $wpdb->update($product_meta_table_name, $fields, array('prod_id' => $prod_id));   
        
    } else {
        //No value for this field was there so lets insert one.
        $fields = array();
        $fields['prod_id'] = $prod_id;
        $fields['meta_key'] = 'slm_max_allowed_domains';
        $fields['meta_value'] = $prod_dat_array['slm_max_allowed_domains'];
        $result = $wpdb->insert($product_meta_table_name, $fields);        
    }
}

function slm_estore_product_deleted($prod_id) {
    global $wpdb;
    $product_meta_table_name = WP_ESTORE_PRODUCTS_META_TABLE_NAME;
    $result = $wpdb->delete($product_meta_table_name, array('prod_id' => $prod_id, 'meta_key' => 'slm_max_allowed_domains'));
}

/************************************/
/*** End of WP eStore integration ***/
/************************************/
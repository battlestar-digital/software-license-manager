<?php

function slm_add_licenses_menu()
{
    global $wpdb;
    $slm_options    = get_option('slm_plugin_options');

    //initialise some variables
    $id             = '';
    $license_key    = '';
    $max_domains    = SLM_Helper_Class::slm_get_option('default_max_domains');
    $max_devices    = SLM_Helper_Class::slm_get_option('default_max_devices');
    $license_status = '';
    $first_name     = '';
    $last_name      = '';
    $email          = '';
    $company_name   = '';
    $txn_id         = '';
    $reset_count    = '';
    $purchase_id_   = '';
    $created_date   = '';
    $renewed_date   = '';
    $expiry_date    = '';
    $until          = '';
    $current_ver    = '';
    $product_ref    = '';
    $subscr_id      = '';
    $lic_type       = '';
    $reg_domains    = '';
    $reg_devices    = '';
    $class_hide     = '';
    $current_date   = (date("Y-m-d"));
    $current_date_plus_1year = date('Y-m-d', strtotime('+1 year'));

    echo '<div class="wrap">';
    // echo '<h2>Add/Edit Licenses</h2>';
    echo '<div id="poststuff"><div id="post-body">';

    //If product is being edited, grab current product info
    if (isset($_GET['edit_record'])) {
        $errors = '';
        $id = $_GET['edit_record'];
        $lk_table       = SLM_TBL_LICENSE_KEYS;
        $sql_prep       = $wpdb->prepare("SELECT * FROM $lk_table WHERE id = %s", $id);
        $record         = $wpdb->get_row($sql_prep, OBJECT);
        $license_key    = $record->license_key;
        $max_domains    = $record->max_allowed_domains;
        $max_devices    = $record->max_allowed_devices;
        $license_status = $record->lic_status;
        $first_name     = $record->first_name;
        $last_name      = $record->last_name;
        $email          = $record->email;
        $company_name   = $record->company_name;
        $txn_id         = $record->txn_id;
        $reset_count    = $record->manual_reset_count;
        $purchase_id_   = $record->purchase_id_;
        $created_date   = $record->date_created;
        $renewed_date   = $record->date_renewed;
        $activated_date = $record->date_activated;
        $expiry_date    = $record->date_expiry;
        $product_ref    = $record->product_ref;
        $until          = $record->until;
        $current_ver    = $record->current_ver;
        $subscr_id      = $record->subscr_id;
        $lic_type       = $record->lic_type;
    }
    if (isset($_POST['save_record'])) {

        //Check nonce
        if (!isset($_POST['slm_add_edit_nonce_val']) || !wp_verify_nonce($_POST['slm_add_edit_nonce_val'], 'slm_add_edit_nonce_action')) {
            //Nonce check failed.
            wp_die("Error! Nonce verification failed for license save action.");
        }

        do_action('slm_add_edit_interface_save_submission');

        //TODO - do some validation
        $license_key    = $_POST['license_key'];
        $max_domains    = $_POST['max_allowed_domains'];
        $max_devices    = $_POST['max_allowed_devices'];
        $license_status = $_POST['lic_status'];
        $first_name     = $_POST['first_name'];
        $last_name      = $_POST['last_name'];
        $email          = $_POST['email'];
        $company_name   = $_POST['company_name'];
        $txn_id         = $_POST['txn_id'];
        $reset_count    = $_POST['manual_reset_count'];
        $purchase_id_   = $_POST['purchase_id_'];
        $created_date   = $_POST['date_created'];
        $renewed_date   = $_POST['date_renewed'];
        $activated_date = $_POST['date_activated'];
        $expiry_date    = $_POST['date_expiry'];
        $product_ref    = $_POST['product_ref'];
        $until          = $_POST['until'];
        $current_ver    = $_POST['current_ver'];
        $subscr_id      = $_POST['subscr_id'];
        $lic_type       = $_POST['lic_type'];

        if (empty($created_date)) {
            $created_date = $current_date;
        }
        if (empty($renewed_date)) {
            $renewed_date = $current_date;
        }
        if (empty($expiry_date)) {
            $expiry_date = $current_date_plus_1year;
        }

        //Save the entry to the database
        $fields = array();
        $fields['license_key']  = $license_key;
        $fields['max_allowed_domains'] = $max_domains;
        $fields['max_allowed_devices'] = $max_devices;
        $fields['lic_status']   = $license_status;
        $fields['first_name']   = $first_name;
        $fields['last_name']    = $last_name;
        $fields['email']        = $email;
        $fields['company_name'] = $company_name;
        $fields['txn_id']       = $txn_id;
        $fields['manual_reset_count'] = $reset_count;
        $fields['purchase_id_'] = $purchase_id_;
        $fields['date_created'] = $created_date;
        $fields['date_renewed'] = $renewed_date;
        $fields['date_activated'] = $activated_date;
        $fields['date_expiry']  = $expiry_date;
        $fields['product_ref']  = $product_ref;
        $fields['until']        = $until;
        $fields['current_ver']  = $current_ver;
        $subscr_id              = $_POST['subscr_id'];
        $lic_type               = $_POST['lic_type'];


        $id                     = isset($_POST['edit_record']) ? $_POST['edit_record'] : '';
        $lk_table               = SLM_TBL_LICENSE_KEYS;

        if (empty($id)) {
            //Insert into database
            $result = $wpdb->insert($lk_table, $fields);
            $id = $wpdb->insert_id;
            if ($result === false) {
                $errors .= __('Record could not be inserted into the database!', 'softwarelicensemanager');
            }
        } else {
            //Update record
            $where = array('id' => $id);
            $updated = $wpdb->update($lk_table, $fields, $where);
            if ($updated === false) {
                //TODO - log error
                $errors .= __('Update of the license key table failed!', 'softwarelicensemanager');
            }
        }

        if (empty($errors)) {
            $message = "Record successfully saved!";
            echo '<div id="message" class="updated fade"><p>';
            echo $message;
            echo '</div></div>';
        } else {
            echo '<div id="message" class="error">' . $errors . '</div>';
        }

        $data = array('row_id' => $id, 'key' => $license_key);
        do_action('slm_add_edit_interface_save_record_processed', $data);
    }
    ?>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

    <style>
        .wp-admin select {
            height: calc(2.25rem + 2px);
        }
    </style>

    <div id="container-2" class="container slm-container">
        <div class="mx-auto" style="">
            <div class="row pb-4">
                <div class="heading col-md-10">
                    <h1 class="woocommerce-order-data__heading">
                        <?php _e('Software License Manager', 'softwarelicensemanager'); ?>
                    </h1>
                    <p class="lead">
                        <?php _e('You can add a new license or edit an existing one from this interface.', 'softwarelicensemanager'); ?>
                    </p>
                </div>
            </div>
        </div>

        <?php
        //save_record - messages
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            echo '<div class="alert alert-primary" role="alert"> <strong>Done!</strong> License was successfully generated <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <span aria-hidden="true">&times;</span> </button></div>';
        }
        //edit
        elseif (isset($_GET['edit_record'])) {
            echo '<div class="alert alert-warning" role="alert"> Edit the information below to update your license key </div>';
        }
        // new
        else {
            echo '<div class="alert alert-info" role="alert"> Fill the information below to generate your license key </div>';
        }
        ?>
        <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            <div id="woocommerce-order-data">
                <div id="woocommerce-order-data">
                    <div class="inside">
                        <div class="panel-wrap woocommerce">
                            <div id="order_data" class="panel woocommerce-order-data">

                                <div class="clear"></div>
                                <div id="error_box">
                                    <div id="summary">
                                        <div class="error_slm alert alert-info" style="display:none">
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="clear"></div>


                                <div class="order_data_column_container">
                                    <div class="order_data_column row">

                                        <div class="col-3 sml-col-right">

                                            <ul class="nav flex-column nav-pills" aria-orientation="vertical" id="slm_manage_license" role="tablist">

                                                <li class="nav-item">
                                                    <a class="nav-link active" id="license-tab" data-toggle="tab" href="#license" role="tab" aria-controls="license" aria-selected="false"><span class="dashicons dashicons-lock"></span> <?php _e('License key and status', 'softwarelicensemanager'); ?></a>
                                                </li>

                                                <li class="nav-item">
                                                    <a class="nav-link" id="userinfo-tab" data-toggle="tab" href="#userinfo" role="tab" aria-controls="userinfo" aria-selected="false"><span class="dashicons dashicons-admin-users"></span> <?php _e('User information', 'softwarelicensemanager'); ?></a>
                                                </li>

                                                <?php
                                                if (isset($_GET['edit_record'])) : ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" id="devicesinfo-tab" data-toggle="tab" href="#devicesinfo" role="tab" aria-controls="devicesinfo" aria-selected="false"><span class="dashicons dashicons-admin-site-alt2"></span> <?php _e('Devices & Domains', 'softwarelicensemanager'); ?></a>
                                                    </li>
                                                <?php endif; ?>

                                                <li class="nav-item">
                                                    <a class="nav-link" id="transaction-tab" data-toggle="tab" href="#transaction" role="tab" aria-controls="transaction" aria-selected="false"><span class="dashicons dashicons-media-text"></span> <?php _e('Transaction', 'softwarelicensemanager'); ?></a>
                                                </li>

                                                <li class="nav-item">
                                                    <a class="nav-link" id="productinfo-tab" data-toggle="tab" href="#productinfo" role="tab" aria-controls="productinfo" aria-selected="false"><span class="dashicons dashicons-store"></span> <?php _e('Product', 'softwarelicensemanager'); ?></a>
                                                </li>

                                                <?php
                                                if (isset($_GET['edit_record'])) : ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" id="activity-log-tab" data-toggle="tab" href="#activity-log" role="tab" aria-controls="activity-log" aria-selected="false"><span class="dashicons dashicons-media-text"></span> <?php _e('Activity log ', 'softwarelicensemanager'); ?></a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php
                                                if (isset($_GET['edit_record'])) : ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" id="export-license-tab" data-toggle="tab" href="#export-license" role="tab" aria-controls="export-license" aria-selected="false"><span class="dashicons dashicons-external"></span> <?php _e('Export ', 'softwarelicensemanager'); ?></a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>

                                        <div class="col-9 sml-col-left">
                                            <form method="post" class="slm_license_form row" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                                                <?php
                                                wp_nonce_field('slm_add_edit_nonce_action', 'slm_add_edit_nonce_val');

                                                if ($id != '') {
                                                    echo '<input name="edit_record" type="hidden" value="' . $id . '" />';
                                                } else {
                                                    if (!isset($editing_record)) {
                                                        $editing_record = new stdClass();
                                                    }
                                                    $lic_key_prefix = $slm_options['lic_prefix'];

                                                    if (!empty($lic_key_prefix)) {
                                                        $license_key = slm_get_license($lic_key_prefix);
                                                    } else {
                                                        $license_key =  slm_get_license($lic_key_prefix);
                                                    }
                                                }
                                                ?>
                                                <div class="tab-content col-md-12" id="slm_manage_licenseContent">
                                                    <div class="tab-pane fade show active" id="license" role="tabpanel" aria-labelledby="license-tab">
                                                        <div class="license col-full">
                                                            <h3>License key and status</h3>
                                                            <div class="form-group">
                                                                <label for="license_key">License Key</label>
                                                                <input name="license_key" class="form-control" aria-describedby="licInfo" type="text" id="license_key" value="<?php echo $license_key; ?>" readonly />
                                                                <small id="licInfo" class="form-text text-muted">The unique license key.</small>
                                                            </div>

                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="lic_status">License Status</label>
                                                                    <select name="lic_status" class="form-control">
                                                                        <option value="pending" <?php if ($license_status == 'pending') {
                                                                                                    echo 'selected="selected"';
                                                                                                } ?>>Pending</option>
                                                                        <option value="active" <?php if ($license_status == 'active') {
                                                                                                    echo 'selected="selected"';
                                                                                                } ?>>Active</option>
                                                                        <?php
                                                                        if (isset($_GET['edit_record'])) : ?>
                                                                            <option value="blocked" <?php if ($license_status == 'blocked') {
                                                                                                        echo 'selected="selected"';
                                                                                                    } ?>>Blocked</option>
                                                                            <option value="expired" <?php if ($license_status == 'expired') {
                                                                                                        echo 'selected="selected"';
                                                                                                    } ?>>Expired</option>
                                                                        <?php endif; ?>

                                                                    </select>
                                                                </div>

                                                                <div class="form-group col-md-6">
                                                                    <label for="email">License type</label>
                                                                    <select name="lic_type" class="form-control">
                                                                        <option value="subscription" <?php if ($lic_type == 'subscription') {
                                                                                                            echo 'selected="selected"';
                                                                                                        } ?>>Subscription</option>
                                                                        <option value="lifetime" <?php if ($lic_type == 'lifetime') {
                                                                                                        echo 'selected="selected"';
                                                                                                    } ?>>Life-time</option>
                                                                    </select>

                                                                    <small class="form-text text-muted">type of license: subscription base or lifetime</small>
                                                                </div>
                                                            </div>
                                                            <div class="clear"></div>
                                                        </div>
                                                    </div>


                                                    <div class="tab-pane fade show" id="userinfo" role="tabpanel" aria-labelledby="userinfo-tab">
                                                        <div class="col-full">
                                                            <h3>User Information</h3>
                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="first_name">First Name</label>
                                                                    <input name="first_name" type="text" id="first_name" value="<?php echo $first_name; ?>" class="form-control required" required />
                                                                    <small class="form-text text-muted">License user's first name </small>
                                                                </div>

                                                                <div class="form-group col-md-6">
                                                                    <label for="last_name"> Last Name</label>
                                                                    <input name="last_name" type="text" id="last_name" value="<?php echo $last_name; ?>" class="form-control required" required />
                                                                    <small class="form-text text-muted">License user's last name </small>
                                                                </div>
                                                            </div>
                                                            <div class="clear"></div>

                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="email">Subscriber ID</label>
                                                                    <input name="subscr_id" class="form-control" type=" text" id="subscr_id" value="<?php echo $subscr_id; ?>" />
                                                                    <small class="form-text text-muted">The Subscriber ID (if any). Can be useful if you are using the license key with a recurring payment plan.</small>
                                                                </div>


                                                                <div class="form-group col-md-6">
                                                                    <label for="email">Email Address</label>
                                                                    <input name="email" type="email" class="form-control" id="email" value="<?php echo $email; ?>" class="form-control required" required />
                                                                    <?php
                                                                    if (isset($_GET['edit_record'])) : ?>
                                                                        <small class="form-text text-muted">License user's email address. <a href="<?php echo admin_url('admin.php?page=slm_subscribers&slm_subscriber_edit=true&manage_subscriber=' . $subscr_id . '&email=' . $email . '') ?>">View all licenses</a> registered to this email address.</small>
                                                                    <?php else : ?>
                                                                        <small class="form-text text-muted">License user's email address</small>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="clear"></div>

                                                                <div class="form-group col-md-12">
                                                                    <label for="company_name">Company Name</label>
                                                                    <input name="company_name" class="form-control" type="text" id="company_name" value="<?php echo $company_name; ?>" />
                                                                    <small class="form-text text-muted">License user's company name</small>
                                                                </div>
                                                            </div>
                                                            <div class="clear"></div>

                                                        </div>
                                                    </div>

                                                    <div class="tab-pane fade show " id="devicesinfo" role="tabpanel" aria-labelledby="devicesinfo-tab">
                                                        <div class="devicesinfo col-full">
                                                            <h3>Allowed Activations</h3>
                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="max_allowed_domains">Maximum Allowed Domains</label>
                                                                    <input name="max_allowed_domains" class="form-control" type=" text" id="max_allowed_domains" value="<?php echo $max_domains; ?>" />
                                                                    <small class="form-text text-muted">Number of domains/installs in which this license can be used</small>

                                                                    <div class="table">
                                                                        <?php
                                                                        if ($id != '') {
                                                                            global $wpdb;
                                                                            $reg_table = SLM_TBL_LIC_DOMAIN;
                                                                            $sql_prep = $wpdb->prepare("SELECT * FROM $reg_table WHERE lic_key_id = %s", $id);
                                                                            $reg_domains = $wpdb->get_results($sql_prep, OBJECT);
                                                                        }
                                                                        if (count($reg_domains) > 0) : ?>
                                                                            <label>Registered Domains</label>
                                                                            <div style="background: red;width: 100px;color:white; font-weight: bold;padding-left: 10px;" id="reg_del_msg"></div>
                                                                            <div class="devices-info">
                                                                                <table cellpadding="0" cellspacing="0" class="table">
                                                                                    <?php
                                                                                    $count = 0;
                                                                                    foreach ($reg_domains as $reg_domain) : ?>
                                                                                        <tr <?php echo ($count % 2) ? 'class="alternate"' : ''; ?>>
                                                                                            <td height="5"><?php echo $reg_domain->registered_domain; ?></td>
                                                                                            <td height="5"><span class="del" id=<?php echo $reg_domain->id ?>>X</span></td>
                                                                                        </tr>
                                                                                        <?php $count++; ?>
                                                                                    <?php endforeach; ?>
                                                                                </table>
                                                                            </div>
                                                                        <?php else : ?>
                                                                            <?php echo '<div class="alert alert-danger" role="alert">Not registered yet</div>'; ?>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label for="max_allowed_devices">Maximum Allowed Devices</label>
                                                                    <input name="max_allowed_devices" class="form-control" type="text" id="max_allowed_devices" value="<?php echo $max_devices; ?>" />
                                                                    <small class="form-text text-muted">Number of domains/installs in which this license can be used</small>

                                                                    <?php
                                                                    if ($id != '') {
                                                                        global $wpdb;
                                                                        $devices_table  = SLM_TBL_LIC_DEVICES;
                                                                        $sql_prep2      = $wpdb->prepare("SELECT * FROM `$devices_table` WHERE `lic_key_id` = '%s'", $id);
                                                                        $reg_devices    = $wpdb->get_results($sql_prep2, OBJECT);
                                                                    }
                                                                    if (count($reg_devices) > 0) : ?>
                                                                        <label for="order_date">Registered Devices</label>
                                                                        <div style="background: red;width: 100px;color:white; font-weight: bold;padding-left: 10px;" id="reg_del_msg"></div>
                                                                        <div class="devices-info">
                                                                            <table cellpadding="0" cellspacing="0" class="table">
                                                                                <?php
                                                                                $count_ = 0;
                                                                                foreach ($reg_devices as $reg_device) : ?>
                                                                                    <tr <?php echo ($count_ % 2) ? 'class="alternate"' : ''; ?>>
                                                                                        <td height="5"><?php echo $reg_device->registered_devices; ?></td>
                                                                                        <td height="5"><span class="del_device" id=<?php echo $reg_device->id ?>>X</span></td>
                                                                                    </tr>
                                                                                    <?php $count_++; ?>
                                                                                <?php endforeach; ?>
                                                                            </table>
                                                                        </div>
                                                                    <?php else : ?>
                                                                        <?php echo '<div class="alert alert-danger" role="alert">Not registered yet</div>'; ?>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="clear"></div>
                                                        </div>
                                                        <div class="clear"></div>
                                                    </div>


                                                    <div class="tab-pane fade show " id="transaction" role="tabpanel" aria-labelledby="transaction-tab">

                                                        <div class="col-full">
                                                            <h3>Advanced Details</h3>
                                                            <div class="form-group">
                                                                <label for="order_date">Manual Reset Count</label>
                                                                <input name="manual_reset_count" class="form-control" type="text" id="manual_reset_count" value="<?php echo $reset_count; ?>" />
                                                                <small class="form-text text-muted">The number of times this license has been manually reset by the admin (use it if you want to keep track of it). It can be helpful for the admin to keep track of manual reset counts</small>

                                                            </div>

                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="order_date">Date Created</label>
                                                                    <input type="date" name="date_created" id="date_created" class="form-control wplm_pick_date" value="<?php echo $created_date; ?>">

                                                                    <small class="form-text text-muted">Creation date of license</small>
                                                                </div>

                                                                <div class="form-group col-md-6">
                                                                    <label for="date_expiry">Expiration Date</label>
                                                                    <input name="date_expiry" type="date" id="date_expiry" class="form-control wplm_pick_date" value="<?php echo $expiry_date; ?>" />
                                                                    <small class="form-text text-muted">Expiry date of license</small>
                                                                </div>

                                                                <div class="form-group col-md-6">
                                                                    <label for="date_renewed">Date Renewed</label>
                                                                    <input name="date_renewed" type="date" id="date_renewed" class="form-control wplm_pick_date" value="<?php echo $renewed_date; ?>" />
                                                                    <small class="form-text text-muted">Renewal date of license</small>
                                                                </div>

                                                                <div class="form-group col-md-6">
                                                                    <label for="date_activated">Date activated</label>
                                                                    <input name="date_activated" type="date" id="date_activated" class="form-control wplm_pick_date" value="form-control <?php echo $activated_date; ?>" />
                                                                    <small class="form-text text-muted">Activation date</small>
                                                                </div>

                                                                <div class="clear"></div>
                                                            </div>
                                                        </div>
                                                        <div class="clear"></div>
                                                    </div>

                                                    <div class="tab-pane fade show " id="productinfo" role="tabpanel" aria-labelledby="productinfo-tab">

                                                        <div class="col-full">
                                                            <h3>Product Information</h3>
                                                            <div class="form-group">
                                                                <label for="product_ref">Product</label>
                                                                <input name="product_ref" class="form-control" type="text" id="product_ref" value="<?php echo $product_ref; ?>" />
                                                                <small class="form-text text-muted">The product that this license gives access to</small>
                                                            </div>

                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="txn_id">Unique Transaction ID</label>
                                                                    <input name="txn_id" type="text" class="form-control" id="txn_id" value="<?php echo $txn_id; ?>" />
                                                                    <small class="form-text text-muted">The unique transaction ID associated with this license key</small>
                                                                </div>

                                                                <div class="form-group  col-md-6">
                                                                    <label for="purchase_id_">Purchase Order ID #</label>
                                                                    <input name="purchase_id_" class="form-control" type="text" id="purchase_id_" value="<?php echo $purchase_id_; ?>" size="8" />
                                                                    <?php
                                                                    if (!empty($purchase_id_)) : ?>
                                                                        <small class="form-text text-muted">This is associated with the purchase ID woocommerce support. <a href="<?php echo admin_url() . 'post.php?post=' . $purchase_id_; ?>&action=edit">View Order </a></small>
                                                                    <?php else : ?>
                                                                        <small class="form-text text-muted"> No order found yet</small>
                                                                    <?php endif; ?>

                                                                </div>
                                                            </div>
                                                            <div class="clear"></div>

                                                            <div class="form-group">
                                                                <label for="until">Supported Until</label>
                                                                <input name="until" type="text" class="form-control" id="until" value="<?php echo $until; ?>" />
                                                                <small class="form-text text-muted">Until what version this product is supported</small>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="current_ver">Current Version</label>
                                                                <input name="current_ver" type="text" class="form-control" id="current_ver" value="<?php echo $current_ver; ?>" />
                                                                <small class="form-text text-muted">What is the current version of this product</small>
                                                            </div>
                                                            <div class="clear"></div>

                                                        </div>
                                                        <div class="clear"></div>
                                                    </div>

                                                    <?php
                                                    if (isset($_GET['edit_record'])) : ?>
                                                        <div class="tab-pane fade show " id="export-license" role="tabpanel" aria-labelledby="export-license-tab">

                                                            <div class="export-license col-full">
                                                                <div class="license_export_info" style="min-width: 100%; max-width: 900px">
                                                                    <?php
                                                                    $api_params = array(
                                                                        'slm_action'    =>  'slm_check',
                                                                        'secret_key'    =>  SLM_Helper_Class::slm_get_option('lic_verification_secret'),
                                                                        'license_key'   =>  $license_key,
                                                                    );
                                                                    // Send query to the license manager server
                                                                    $response = wp_remote_get(add_query_arg($api_params, SLM_SITE_URL), array('timeout' => 20, 'sslverify' => false));

                                                                    $data = $response['body'];

                                                                    // parsing json
                                                                    $arr = json_decode($data, true);

                                                                    // removing the value
                                                                    unset($arr['result']);
                                                                    unset($arr['code']);
                                                                    unset($arr['message']);

                                                                    // and back to json
                                                                    $response = utf8_encode(json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                                                                    echo '<figure class="highlight"><pre><code id="lic-json-data">' . $response . '</code></pre></figure>';

                                                                    ?>
                                                                    <a href="#" class="button-secondary" onclick="slm_exportlicense()">Export License</a>
                                                                </div>
                                                            </div>

                                                        </div>

                                                        <div class="tab-pane fade show " id="activity-log" role="tabpanel" aria-labelledby="activity-log-tab">

                                                            <div class="activity-log col-full">
                                                                <div class="lic-activity-log" style="min-height: 325px; min-width: 100%; max-width: 900px">
                                                                    <?php
                                                                    SLM_Utility::get_lic_activity($license_key);
                                                                    ?>

                                                                </div>
                                                            </div>

                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="output-msg">
                                                        <?php
                                                        $data = array('row_id' => $id, 'key' => $license_key);
                                                        $extra_output = apply_filters('slm_add_edit_interface_above_submit', '', $data);
                                                        if (!empty($extra_output)) {
                                                            echo $extra_output;
                                                        }
                                                        ?>
                                                    </div>

                                                    <div class="submit form_actions">
                                                        <input type="submit" class="button btn btn-primary save_lic" name="save_record" value="Save License" />
                                                        <a href="admin.php?page=<?php echo SLM_MAIN_MENU_SLUG; ?>" class="btn btn-link">Manage Licenses</a>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- end of form -->
                                        <div class="clear"></div>


                                    </div>
                                </div>
                                <!-- end of tabbed form -->


                                <div class="clear"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="clear"></div>
    </div>
    </div>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function() {

            jQuery(".save_lic").click(function(event) {

                // Fetch form to apply custom Bootstrap validation
                var form = jQuery(".slm_license_form")

                if (form[0].checkValidity() === false) {
                    event.preventDefault()
                    event.stopPropagation()
                }

                form.addClass('was-validated');
                // Perform ajax submit here...

            });


            jQuery('.del').click(function() {
                jQuery('#reg_del_msg').html('Loading ...');

                jQuery.get('<?php echo get_bloginfo("wpurl"); ?>' + '/wp-admin/admin-ajax.php?action=del_reistered_domain&id=' + jQuery(this).attr('id'), function(data) {
                    if (data == 'success') {
                        jQuery('#reg_del_msg').html('Deleted');
                        jQuery(this).parent().parent().remove();
                    } else {
                        jQuery('#reg_del_msg').html('Failed');
                    }
                });
            });

            jQuery('.del_device').click(function() {
                jQuery('#reg_device_del_msg').html('Loading ...');
                jQuery.get('<?php echo get_bloginfo("wpurl"); ?>' + '/wp-admin/admin-ajax.php?action=del_reistered_devices&id=' + jQuery(this).attr('id'), function(data) {
                    if (data == 'success') {
                        jQuery('#reg_device_del_msg').html('Deleted');
                        jQuery(this).parent().parent().remove();
                    } else {
                        jQuery('#reg_device_del_msg').html('Failed');
                    }
                });
            });
        });
    </script>
<?php
}

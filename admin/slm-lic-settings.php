<?php

if (!defined('WPINC')) {
    die;
}

function slm_settings_menu()
{
    slm_general_settings();
}

function slm_general_settings()
{

    ?>
    <?php

        if (isset($_REQUEST['slm_reset_log'])) {
            $slm_logger = new SLM_Debug_Logger();
            global $slm_debug_logger;
            $slm_debug_logger->reset_log_file("log.txt");
            $slm_debug_logger->reset_log_file("log-cron-job.txt");
            echo '<div id="message" class="updated fade"><p>Debug log files have been reset!</p></div>';
        }

        if (isset($_POST['slm_save_settings'])) {

            if (!is_numeric($_POST["default_max_domains"])) {
                //Set it to one by default if incorrect value is entered
                $_POST["default_max_domains"] = '2';
            }
            if (!is_numeric($_POST["default_max_devices"])) {
                //Set it to one by default if incorrect value is entered
                $_POST["default_max_devices"] = '2';
            }

            $options = array(
                'lic_creation_secret'       => trim($_POST["lic_creation_secret"]),
                'lic_prefix'                => trim($_POST["lic_prefix"]),
                'default_max_domains'       => trim($_POST["default_max_domains"]),
                'default_max_devices'       => trim($_POST["default_max_devices"]),
                'lic_verification_secret'   => trim($_POST["lic_verification_secret"]),
                'enable_auto_key_expiration' => isset($_POST['enable_auto_key_expiration']) ? '1' : '',
                'enable_debug'              => isset($_POST['enable_debug']) ? '1' : '',
                'slm_woo'                   => isset($_POST['slm_woo']) ? '1' : '',
                'slm_woo_downloads'         => isset($_POST['slm_woo_downloads']) ? '1' : '',
                'slm_stats'                 => isset($_POST['slm_stats']) ? '1' : '',
                'slm_adminbar'                 => isset($_POST['slm_adminbar']) ? '1' : '',
                'slm_wpestores'             => isset($_POST['slm_wpestores']) ? '1' : '',
                'slm_dl_manager'            => isset($_POST['slm_dl_manager']) ? '1' : '',
                'expiration_reminder_text'  => sanitize_text_field($_POST['expiration_reminder_text'])
            );
            update_option('slm_plugin_options', $options);

            echo '
        <div id="message" class="updated fade">
            <p>Options Updated!</p>
        </div>';
        }

        $options    = get_option('slm_plugin_options');
        $secret_key = $options['lic_creation_secret'];

        if (empty($secret_key)) {
            //$secret_key = md5(uniqid('', true));
            $secret_key = SLM_Utility::create_secret_keys();
        }

        $secret_verification_key = $options['lic_verification_secret'];
        if (empty($secret_verification_key)) {
            //$secret_verification_key = md5(uniqid('', true));
            $secret_verification_key = SLM_Utility::create_secret_keys();
        }
        $tab = ""; //Initialization value;
        if (isset($_REQUEST['tab'])) {
            $tab = $_REQUEST['tab'];
        } else {
            $tab = 'general_settings';
        }

        ?>
    <div class="wrap">
        <h1>Settings - Software License Manager </h1>

        <div id="icon-options-general" class="icon32"></div>
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo admin_url('admin.php?page=' . 'slm_settings') ?>" class="nav-tab <?php echo ($tab == 'general_settings') ? 'nav-tab-active' : '' ?>">
                <?php echo __('General Settings', 'slm'); ?>
            </a>

            <a href="<?php echo admin_url('admin.php?page=' . 'slm_settings' . '&tab=integrations') ?>" class="nav-tab <?php echo ($tab == 'integrations') ? 'nav-tab-active' : '' ?>">
                <?php echo __('Integrations', 'slm'); ?>
            </a>

            <a href="<?php echo admin_url('admin.php?page=' . 'slm_settings' . '&tab=debug') ?>" class="nav-tab <?php echo ($tab == 'debug') ? 'nav-tab-active' : '' ?>">
                <?php echo __('Debugging settings', 'slm'); ?>
            </a>

            <a href="<?php echo admin_url('admin.php?page=' . 'slm_settings' . '&tab=emails') ?>" class="nav-tab <?php echo ($tab == 'emails') ? 'nav-tab-active' : '' ?>">
                <?php echo __('Emails', 'slm'); ?>
            </a>

        </h2>

        <style>
            .hidepanel {
                display: none;
            }

            .showpanel {
                display: block !important
            }

            #wpbody-content {
                padding-bottom: 8px;
                ;
            }
        </style>

        <div class="metabox-holder has-right-sidebar">

            <form method="post" action="" class="wrap">

                <div class="slm-postbox wrap general_settings hidepanel <?php echo ($tab == 'general_settings') ? 'showpanel' : '' ?>">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><?php _e('Secret Key for License Creation', 'softwarelicensemanager'); ?></th>
                            <td><textarea name="lic_creation_secret" rows="2" cols="50"><?php echo $secret_key; ?>
                            </textarea>
                                <p class=" description"><?php _e('This secret key will be used to authenticate any license creation request. You can change it with something random.', 'softwarelicensemanager'); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Secret Key for License Verification Requests', 'softwarelicensemanager'); ?></th>
                            <td><textarea name="lic_verification_secret" rows="2" cols="50"><?php echo $secret_verification_key; ?></textarea>
                                <p class="description"><?php _e('This secret key will be used to authenticate any license verification request from customer\'s site. Important! Do not change this value once your customers start to use your product(s)!', 'softwarelicensemanager'); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('License Key Prefix', 'softwarelicensemanager'); ?></th>
                            <td><input type="text" name="lic_prefix" value="<?php echo $options['lic_prefix']; ?>" size="6" />
                                <p class="description"><?php _e('You can optionaly specify a prefix for the license keys. This prefix will be added to the uniquely generated license keys.', 'softwarelicensemanager'); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Maximum Allowed Domains', 'softwarelicensemanager'); ?></th>
                            <td><input type="text" name="default_max_domains" value="<?php echo $options['default_max_domains']; ?>" size="6" />
                                <p class="description"><?php _e('Maximum number of domains/installs which each license is valid for (default value).', 'softwarelicensemanager'); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Maximum Allowed Devices', 'softwarelicensemanager'); ?></th>
                            <td><input type="text" name="default_max_devices" value="<?php echo $options['default_max_devices']; ?>" size="6" />
                                <p class="description"><?php _e('Maximum number of devices which each license is valid for (default value).', 'softwarelicensemanager'); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Auto Expire License Keys', 'softwarelicensemanager'); ?></th>
                            <td><input name="enable_auto_key_expiration" type="checkbox" <?php if (isset($options['enable_auto_key_expiration']) && $options['enable_auto_key_expiration'] != '') echo ' checked="checked"'; ?> value="1" />
                                <?php _e('Enable auto expiration ', 'softwarelicensemanager '); ?>
                                <p class="description"><?php _e(' When enabled, it will automatically set the status of a license key to "Expired" when the expiry date value  of the key is reached. It doesn\'t remotely deactivate a key. It simply changes the status of the key in your database to expired.', 'softwarelicensemanager'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('General settings', 'softwarelicensemanager'); ?></th>
                            <td>
                                <input name="slm_stats" type="checkbox" <?php if ($options['slm_stats'] != '') echo ' checked="checked"'; ?> value="1" />
                                <?php _e('Disable stats in licenses overview page.', 'softwarelicensemanager'); ?></td>
                        </tr>

                        <tr>
                            <th scope="row"></th>
                            <td>
                                <input name="slm_adminbar" type="checkbox" <?php if ($options['slm_adminbar'] != '') echo ' checked="checked"'; ?> value="1" />
                                <?php _e('Disable stats in licenses overview page.', 'softwarelicensemanager'); ?></td>
                        </tr>
                    </table>
                </div>



                <div class="slm-postbox wrap integrations hidepanel <?php echo ($tab == 'integrations') ? 'showpanel' : '' ?>">
                    <div class="inside">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"> <?php _e('Woocommerce Support', 'softwarelicensemanager'); ?></th>
                                <td>
                                    <input name="slm_woo" type="checkbox" <?php if ($options['slm_woo'] != '') echo ' checked="checked"'; ?> value="1" />
                                    <?php _e('A fully customizable, open source eCommerce platform built for WordPress.', 'softwarelicensemanager'); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"></th>
                                <td>
                                    <input name="slm_woo_downloads" type="checkbox" <?php if ($options['slm_woo_downloads'] != '') echo ' checked="checked"'; ?> value="1" />
                                    <?php _e('Disable woocommerce download page. Proccess downloads though license order info page.', 'softwarelicensemanager'); ?></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"> <?php _e('Download Manager Support', 'softwarelicensemanager'); ?></th>
                                <td>
                                    <input name="slm_dl_manager" type="checkbox" <?php if ($options['slm_dl_manager'] != '') echo ' checked="checked"'; ?> value="1" />
                                    <?php _e('Download Manager Plugin – Adds a simple download manager to your WordPress blog.', 'softwarelicensemanager'); ?>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"> <?php _e('WP eStores Support', 'softwarelicensemanager'); ?></th>
                                <td>
                                    <input name="slm_wpestores" type="checkbox" <?php if ($options['slm_wpestores'] != '') echo ' checked="checked"'; ?> value="1" />
                                    <?php _e('WordPress eStore Plugin – Complete Solution to Sell Digital Products from Your WordPress Blog Securely', 'softwarelicensemanager'); ?>

                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="slm-postbox wrap debug hidepanel <?php echo ($tab == 'debug') ? 'showpanel' : '' ?>">
                    <div class=" inside">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"> <?php _e('Enable Debug Logging', 'softwarelicensemanager'); ?></th>
                                <td>
                                    <p class="description"><input name="enable_debug" type="checkbox" <?php if ($options['enable_debug'] != '') echo ' checked="checked"'; ?> value="1" />
                                        <?php _e('If checked, debug output will be written to log files (keep it disabled unless you are troubleshooting).', ' softwarelicensemanager '); ?></p>
                                    - <?php _e('View debug log file by clicking', 'softwarelicensemanager'); ?> <a href="<?php echo SLM_URL . '/public/logs/log.txt'; ?>" target="_blank"><?php _e('here', ' softwarelicensemanager '); ?></a>..
                                    - <?php _e('Reset debug log file by clicking', 'softwarelicensemanager'); ?> <a href="admin.php?page=slm_settings&slm_reset_log=1" target="_blank"><?php _e('here', ' softwarelicensemanager '); ?></a>.
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="slm-postbox wrap debug hidepanel <?php echo ($tab == 'emails') ? 'showpanel' : '' ?>">
                    <div class=" inside">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"> <?php _e('Expiration reminder', 'softwarelicensemanager'); ?></th>
                                <td>
                                    <textarea name="expiration_reminder_text" id="expiration_reminder_text" cols="80" rows="20"> <?php echo esc_html($options['expiration_reminder_text']); ?> </textarea>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="submit">
                    <input type="submit" class="button-primary" name="slm_save_settings" value=" <?php _e('Update Options', 'softwarelicensemanager', 'softwarelicensemanager'); ?>" />
                </div>
            </form>
        </div>

    <?php
    }

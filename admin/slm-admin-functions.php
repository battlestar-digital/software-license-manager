<?php

if (!defined('WPINC')) {
    die;
}


function slm_admin_tools_menu()
{

    echo '<div class="wrap">';
    echo '<h2 class="imgh2"><img src="' . SLM_ASSETS_URL . 'images/slm_logo.svg" alt="slm logo"> Admin Tools</h2>';
    echo '<div id="poststuff"><div id="post-body">';

    $slm_options = get_option('slm_plugin_options');

    if (isset($_POST['send_deactivation_request'])) {
        $postURL = $_POST['slm_deactivation_req_url'];
        $secretKeyForVerification = $slm_options['lic_verification_secret'];
        $data = array();
        $data['secret_key'] = $secretKeyForVerification;

        $ch = curl_init($postURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $returnValue = curl_exec($ch);

        $msg = "";
        if ($returnValue == "Success") {
            $msg .= "Success message returned from the remote host.";
        }
        echo '<div id="message" class="updated fade"><p>';
        echo 'Request sent to the specified URL!';
        echo '<br />' . $msg;
        echo '</p></div>';
    }
    ?>
    <br />
    <div class="postbox">
        <h3 class="hndle"><label for="title"><?php _e('Send Deactivation Message for a License', 'softwarelicensemanager'); ?></label></h3>
        <div class="inside">
            <br /><strong><?php _e('Enter the URL where the license deactivation message will be sent to', 'softwarelicensemanager'); ?></strong>
            <br /><br />
            <form method="post" action="">

                <input name="slm_deactivation_req_url" type="text" size="100" value="<?php isset($_POST['slm_deactivation_req_url']) ? $_POST['slm_deactivation_req_url'] : ''; ?>" />
                <div class="submit">
                    <input type="submit" name="send_deactivation_request" value="Send Request" class="button" />
                </div>
            </form>
        </div>
    </div>
    <?php
    echo '</div></div>';
    echo '</div>';
}

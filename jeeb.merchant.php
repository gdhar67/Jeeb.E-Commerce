<?php

// Check version requirement dependencies
if (false !== jeeb_requirements_check()) {
    throw new \Exception('Your server does not meet the minimum requirements to use the jeeb payment plugin. The requirements check returned this error message: ' . jeeb_requirements_check());
}

// Load upgrade file
require_once ABSPATH.'wp-admin/includes/upgrade.php';

// Load Javascript from jeeb.js and jquery
function jeeb_js_init()
{
    wp_register_script('jquery', "//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js");
    wp_register_script('jquery-ui', "//code.jquery.com/ui/1.11.1/jquery-ui.js");
    wp_register_style('jquery-ui-css', "//code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css");
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui');
    wp_enqueue_style('jquery-ui-css');
}

add_action('admin_enqueue_scripts', 'jeeb_js_init');

$nzshpcrt_gateways[$num] = array(
        'name'                                    => __('Bitcoin Payments by Jeeb', 'wpsc'),
        'api_version'                             => 1.0,
        'image'                                   => WPSC_URL.'/wpsc-merchants/jeeb/assets/img/logo.png',
        'has_recurring_billing'                   => false,
        'wp_admin_cannot_cancel'                  => true,
        'display_name'                            => __('Bitcoin', 'wpsc'),
        'user_defined_name[wpsc_merchant_jeeb]' => 'Bitcoin',
        'requirements'                            => array('php_version' => 5.4),
        'internalname'                            => 'wpsc_merchant_jeeb',
        'form'                                    => 'form_jeeb',
        'submit_function'                         => 'submit_jeeb',
        'function'                                => 'gateway_jeeb',
        );

function debug_log($contents)
{
    if (true === isset($contents)) {
        if (true === is_resource($contents)) {
            error_log(serialize($contents));
        } else {
            error_log(var_export($contents, true));
        }
    }
}

function create_jeeb_table()
{
    // Access to Wordpress Database
    global $wpdb;

    // Query for creating Keys Table
    $sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "jeeb_keys` (
       `id` int(11) not null auto_increment,
       `orderid` varchar(1000) not NULL,
       `sessionid` varchar(1000) not null,
       `token` varchar(250) not null,
       PRIMARY KEY (`id`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

    try {
        // execute SQL statement
        dbDelta($sql);

    } catch (\Exception $e) {
        error_log('[Error] In jeeb plugin, create_table() function on line ' . $e->getLine() . ', with the error "' . $e->getMessage() . '" .');
        throw $e;
    }
}

function form_jeeb()
{
    // Access to Wordpress Database
    global $wpdb;

    try {
        if (get_option('jeeb_error') != null) {
            $output = '<div style="color:#A94442;background-color:#F2DEDE;background-color:#EBCCD1;text-align:center;padding:15px;border:1px solid transparent;border-radius:4px">'.get_option('jeeb_error').'</div>';
            update_option('jeeb_error', null);
        }

        // Create table for jeeb Storage
        create_jeeb_table();

        // Get Current user's ids
        $user_id = get_current_user_id();

        // Load table storing the tokens
        $table_name = $wpdb->prefix.'jeeb_keys';

        // Load the tokens paired by the current user.
        // $tablerows1 = $wpdb->get_results("SELECT * FROM {$table_name} WHERE `user_id` = {$user_id}");


        $rows = array();

        $rows[] = array(
            'Live/Test',
            '<select name="network"><option value="Livenet">Live</option><option value="Testnet">Test</option></select>',
            '<p class="description">Testnet is used for Testing and Debugging purposes.</p>',
        );

        $rows[] = array(
            'Signature',
            '<input name="signature" type="text" value="" placeholder="Enter your signature"/>',
            '<p class="description">Signature is a unique string provided by Jeeb to the merchant.</p>',
        );

        // Allows the merchant to specify a URL to redirect to upon the customer completing payment on the jeeb.com
        // invoice page. This is typcially the "Transaction Results" page.
        $rows[] = array(
                        'Redirect URL',
                        '<input name="jeeb_redirect" type="text" value="" />',
                        '<p class="description"><strong>Important!</strong> Put the URL that you want the buyer to be redirected to after payment. This is usually a "Thanks for your order!" page.</p>',
                       );

        $output .= '<tr>' .
            '<td colspan="2">' .
                '<p class="description">' .
                    '<img src="' . WPSC_URL . '/wpsc-merchants/jeeb/assets/img/bitcoin.png" /><br /><strong>The minimum price of the product sold in your market should be atleast 10,000 IRR.<br>'.
                    'Have more questions? Need assistance? Please visit our website <a href="https://jeeb.io" target="_blank">https://jeeb.io</a> or send an email to <a href="mailto:support@jeeb.io" target="_blank">support@jeeb.com</a> for prompt attention. Thank you for choosing jeeb!</strong>' .
                '</p>' .
            '</td>' .
        '</tr>';

        foreach ($rows as $r) {
            $output .= '<tr> <td>' . $r[0] . '</td> <td>' . $r[1];

            if (true === isset($r[2])) {
                $output .= $r[2];
            }

            $output .= '</td></tr>';
        }

        return $output;

    } catch (\Exception $e) {
        error_log('[Error] In jeeb plugin, form_jeeb() function on line ' . $e->getLine() . ', with the error "' . $e->getMessage() . '" .');
        throw $e;
    }
}

function submit_jeeb()
{
    global $wpdb;

    try {
        if (true  === isset($_POST['submit'])              &&
            false !== stristr($_POST['submit'], 'Update'))
        {
            $params = array(
                            'signature',
                            'network',
                            'jeeb_redirect',
                           );

            foreach ($params as $p) {
                if ($_POST[$p] != null) {
                    update_option($p, $_POST[$p]);
                } else {
                    add_settings_error($p, 'error', __('The setting '. $p.' cannot be blank! Please enter a value for this field', 'wpse'), 'error');
                }
            }
        }

        return true;

    } catch (\Exception $e) {
        error_log('[Error] In jeeb plugin, form_jeeb() function on line ' . $e->getLine() . ', with the error "' . $e->getMessage() . '" .');
        throw $e;
    }
}

function convertIrrToBtc($url, $amount, $signature) {

    // return Jeeb::convert_irr_to_btc($url, $amount, $signature);
    $ch = curl_init($url.'api/convert/'.$signature.'/'.$amount.'/irr/btc');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json')
  );

  $result = curl_exec($ch);
  $data = json_decode( $result , true);
  error_log('data = '.$data["result"]);
  // Return the equivalent bitcoin value acquired from Jeeb server.
  return (float) $data["result"];

  }


  function createInvoice($url, $amount, $options = array(), $signature) {

      $post = json_encode($options);

      $ch = curl_init($url.'api/bitcoin/issue/'.$signature);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'Content-Length: ' . strlen($post))
      );

      $result = curl_exec($ch);
      $data = json_decode( $result , true);
      error_log('data = '.$data['result']['token']);

      return $data['result']['token'];

  }

  function redirectPayment($url, $token) {
    debug_log("Entered into auto submit-form");
    // Using Auto-submit form to redirect user with the token
    echo "<form id='form' method='post' action='".$url."invoice/payment'>".
            "<input type='hidden' autocomplete='off' name='token' value='".$token."'/>".
           "</form>".
           "<script type='text/javascript'>".
                "document.getElementById('form').submit();".
           "</script>";
  }


function gateway_jeeb($seperator, $sessionid)
{
    global $wpdb;
    global $wpsc_cart;

    try {
        // This grabs the purchase log id from
        // the database that refers to the $sessionid
        $purchase_log = $wpdb->get_row(
                                       "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS .
                                       "` WHERE `sessionid`= " . $sessionid . " LIMIT 1",
                                       ARRAY_A
                                       );

        // price
        $price = number_format($wpsc_cart->total_price, 2, '.', '');

        // Configure the rest of the invoice
        $purchase_log = $wpdb->get_row("SELECT * FROM `" .WPSC_TABLE_PURCHASE_LOGS. "` WHERE `sessionid`= " . $sessionid. " LIMIT 1", ARRAY_A);

        if (true === is_null(get_option('jeeb_redirect'))) {
            update_option('jeeb_redirect', get_site_url());
        }

        $baseUri = get_option('network')=="Testnet" ? "http://test.jeeb.io:9876/" : "https://jeeb.io/" ;
        $signature = get_option('signature'); // Signature
        $callBack = get_option('jeeb_redirect'); // Callback Url
        $notification = get_option('siteurl').'/?jeeb_callback=true';  // Notification Url
        $order_total = $price;  // Total price in irr

        debug_log(" ".$baseUri." ".$signature." ".$callBack." ".$notification);
        debug_log("Cost = ". $price);

        $btc = convertIrrToBtc($baseUri, $order_total, $signature);

        $params = array(
          'orderNo'          => $purchase_log['id'],
          'requestAmount'    => (float) $btc,
          'notificationUrl'  => $notification,
          'callBackUrl'       => $callBack,
          'allowReject'      => get_option('jeeb_redirect')=="Testnet" ? false : true
        );

        $token = createInvoice($baseUri, $btc, $params, $signature);

        $table_name = $wpdb->prefix.'jeeb_keys';

        $data = array(
            'orderid' => $purchase_log['id'],
            'sessionid' => $sessionid,
            'token'  => $token
        );

        $wpdb->insert($table_name, $data);

        redirectPayment($baseUri, $token);

    } catch (\Exception $e) {
        error_log('[Error] In jeeb plugin, form_jeeb() function on line ' . $e->getLine() . ', with the error "' . $e->getMessage() . '" .');
        throw $e;
    }
}

function jeeb_callback()
{
    global $wpdb;
    global $wpsc_cart;

      $postdata = file_get_contents("php://input");
      $json = json_decode($postdata, true);

      if($json['orderNo']){
        debug_log("hey".$json['orderNo']);
      $table_name = $wpdb->prefix.'jeeb_keys';

      $orderNo = $json['orderNo'];

      $row = $wpdb->get_results("SELECT * FROM {$table_name} WHERE `orderid` = {$orderNo}", ARRAY_A);
      debug_log("hello".print_r($row, TRUE) );
      debug_log("Session ID : ".$row[0]['sessionid']);

      $purchase_log = $wpdb->get_row("SELECT * FROM `" .WPSC_TABLE_PURCHASE_LOGS. "` WHERE `sessionid`= " . $row[0]['sessionid']. " LIMIT 1", ARRAY_A);

      $email_form_field = $wpdb->get_var("SELECT `id` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `type` IN ('email') AND `active` = '1' ORDER BY `checkout_order` ASC LIMIT 1");
      $email            = $wpdb->get_var($wpdb->prepare("SELECT `value` FROM `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` WHERE `log_id` = %d AND `form_id` = %d LIMIT 1", $purchase_log['id'], $email_form_field));

      // get cart contents
      $sql           = "SELECT * FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid`=" . $purchase_log['id'];
      $cart_contents = $wpdb->get_results($sql, ARRAY_A);


      // Call Jeeb
      if (get_option("network") == "Testnet")
      {
          $network_uri = "http://test.jeeb.io:9876/";
      }
      else
      {
          $network_uri = "https://jeeb.io/";
      }


      debug_log("Entered Jeeb-Notification");
      if ( $json['stateId']== 2 ) {
        debug_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);

        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'Invoice created.' WHERE `sessionid`=" . $row[0]['sessionid'];
        $wpdb->query($sql);


        transaction_results($row[0]['sessionid'], false);
      }
      else if ( $json['stateId']== 3 ) {
        debug_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '2' WHERE `sessionid`=" . $row[0]['sessionid'];
        $wpdb->query($sql);

        $message  = 'Thank you! Your payment has been received, but the transaction has not been confirmed on the bitcoin network. You will receive another email when the transaction has been confirmed.';

        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'The payment has been received, but the transaction has not been confirmed on the bitcoin network. This will be updated when the transaction has been confirmed.' WHERE `sessionid`=" . $row[0]["sessionid"];
        $wpdb->query($sql);

        if (wp_mail($email, 'Payment Received', $message)) {
            $mail_sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `email_sent`= '1' WHERE `sessionid`=" . $row[0]['sessionid'];
            $wpdb->query($mail_sql);
        }

        transaction_results($row[0]['sessionid'], false);
      }
      else if ( $json['stateId']== 4 ) {
        debug_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
        $data = array(
          "token" => $json["token"]
        );

        $data_string = json_encode($data);
        $api_key = get_option("signature");
        $url = $network_uri.'api/bitcoin/confirm/'.$api_key;
        debug_log("Signature:".$api_key." Base-Url:".$network_uri." Url:".$url);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
        );

        $result = curl_exec($ch);
        $data = json_decode( $result , true);
        debug_log("data = ".var_export($data, TRUE));


        if($data['result']['isConfirmed']){
          debug_log('Payment confirmed by jeeb');
          $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '3' WHERE `sessionid`=" . $row[0]['sessionid'];
          $wpdb->query($sql);

          $message  = 'Thank you! Your payment has been confirmed by Jeeb';

          $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'The payment has been confirmed by Jeeb. You are now safe to deliver the order.' WHERE `sessionid`=" . $row[0]["sessionid"];
          $wpdb->query($sql);

          if (wp_mail($email, 'Payment Confirmed', $message)) {
              $mail_sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `email_sent`= '1' WHERE `sessionid`=" . $row[0]['sessionid'];
              $wpdb->query($mail_sql);
          }
          $wpsc_cart->empty_cart();
          transaction_results($row[0]["sessionid"], false);
        }
        else {
          debug_log('Payment rejected by jeeb');
        }
      }
      else if ( $json['stateId']== 5 ) {
        debug_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '5' WHERE `sessionid`=" . $row[0]['sessionid'];
        $wpdb->query($sql);

        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'Invoice was expired and the transaction failed.' WHERE `sessionid`=" . $row[0]["sessionid"];
        $wpdb->query($sql);

        transaction_results($row[0]["sessionid"], false);

      }
      else if ( $json['stateId']== 6 ) {
        debug_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '5' WHERE `sessionid`=" . $row[0]['sessionid'];
        $wpdb->query($sql);

        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'Invoice was over paid and the transaction was rejected by Jeeb' WHERE `sessionid`=" . $row[0]["sessionid"];
        $wpdb->query($sql);

        transaction_results($row[0]["sessionid"], false);

      }
      else if ( $json['stateId']== 7 ) {
        debug_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '5' WHERE `sessionid`=" . $row[0]['sessionid'];
        $wpdb->query($sql);

        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'Invoice was partially paid and the transaction was rejected by Jeeb' WHERE `sessionid`=" . $row[0]["sessionid"];
        $wpdb->query($sql);

        transaction_results($row[0]["sessionid"], false);
      }
      else{
        debug_log('Cannot read state id sent by Jeeb');
      }
    }
}

function jeeb_requirements_check()
{
    global $wp_version;

    $errors = array();

    // PHP 5.4+ required
    if (true === version_compare(PHP_VERSION, '5.4.0', '<')) {
        $errors[] = 'Your PHP version is too old. The jeeb payment plugin requires PHP 5.4 or higher to function. Please contact your web server administrator for assistance.';
    }

    // Wordpress 3.9+ required
    if (true === version_compare($wp_version, '3.9', '<')) {
        $errors[] = 'Your WordPress version is too old. The jeeb payment plugin requires Wordpress 3.9 or higher to function. Please contact your web server administrator for assistance.';
    }

    // GMP or BCMath required
    if (false === extension_loaded('gmp') && false === extension_loaded('bcmath')) {
        $errors[] = 'The jeeb payment plugin requires the GMP or BC Math extension for PHP in order to function. Please contact your web server administrator for assistance.';
    }

    if (false === empty($errors)) {
        return implode("<br>\n", $errors);
    } else {
        return false;
    }
}

add_action('init', 'jeeb_callback');

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

function form_jeeb()
{
    // Access to Wordpress Database
    global $wpdb;

    $output = NULL;

    try {
        if (get_option('jeeb_error') != null) {
            $output = '<div style="color:#A94442;background-color:#F2DEDE;background-color:#EBCCD1;text-align:center;padding:15px;border:1px solid transparent;border-radius:4px">'.get_option('jeeb_error').'</div>';
            update_option('jeeb_error', null);
        }

        // Get Current user's ids
        $user_id = get_current_user_id();

        // Load table storing the tokens
        $table_name = $wpdb->prefix.'jeeb_keys';

        // Load the tokens paired by the current user.
        // $tablerows1 = $wpdb->get_results("SELECT * FROM {$table_name} WHERE `user_id` = {$user_id}");


        $rows = array();

        $test = $live = "";
        get_option("network") == "Testnet" ? $test = "selected" : $live = "selected" ;
        $rows[] = array(
            'Live/Test',
            '<select name="network"><option value="Livenet" '. $live .'>Live</option><option value="Testnet" '.$test.'>Test</option></select>',
            '<p class="description">Testnet is used for Testing and Debugging purposes.</p>',
        );

        $signature = get_option("signature");
        $rows[] = array(
            'Signature',
            '<input name="signature" type="text" value="'.$signature.'" placeholder="Enter your signature"/>',
            '<p class="description">Signature is a unique string provided by Jeeb to the merchant.</p>',
        );

        $btc = $eur = $irr = $usd = $toman = "";
        get_option("basecoin") == "btc" ? $btc = "selected" : $btc = "" ;
        get_option("basecoin") == "eur" ? $eur = "selected" : $eur = "" ;
        get_option("basecoin") == "irr" ? $irr = "selected" : $irr = "" ;
        get_option("basecoin") == "toman" ? $toman = "selected" : $toman = "" ;
        get_option("basecoin") == "usd" ? $usd = "selected" : $usd = "" ;
        $rows[] = array(
            'Basecoin',
            '<select name="basecoin"><option value="btc" '.$btc.'>BTC</option><option value="eur" '.$eur.'>EUR</option><option value="irr" '.$irr.'>IRR</option><option value="toman" '.$toman.'>TOMAN</option><option value="usd" '.$usd.'>USD</option></select>',
            '<p class="description">The base currency of your website.</p>',
        );

        $btc = $eth = $xrp = $xmr = $bch = $ltc = $test_btc = "";
        get_option("btc") == "btc" ? $btc = "checked" : $btc = "";
        get_option("eth") == "eth" ? $eth = "checked" : $eth = "";
        get_option("xrp") == "xrp" ? $xrp = "checked" : $xrp = "";
        get_option("xmr") == "xmr" ? $xmr = "checked" : $xmr = "";
        get_option("bch") == "bch" ? $bch = "checked" : $bch = "";
        get_option("ltc") == "ltc" ? $ltc = "checked" : $ltc = "";
        get_option("test-btc") == "test-btc" ? $test_btc = "checked" : $test_btc = "";
        $rows[] = array(
            'Targetcoin',
            '<input type="checkbox" name="btc" value="btc" '.$btc.'>BTC<br>
            <input type="checkbox" name="eth" value="eth" '.$eth.'>ETH<br>
            <input type="checkbox" name="xrp" value="xrp" '.$xrp.'>XRP<br>
            <input type="checkbox" name="xmr" value="xmr" '.$xmr.'>XMR<br>
            <input type="checkbox" name="bch" value="bch" '.$bch.'>BCH<br>
            <input type="checkbox" name="ltc" value="ltc" '.$ltc.'>LTC<br>
            <input type="checkbox" name="test-btc" value="test-btc" '.$test_btc.'>TEST-BTC<br>',
            '<p class="description">The target currency to which your base currency will get converted.</p>',
            );

        $auto_select = $eng = $persian = "";
        get_option("lang") == "none" ? $auto_select = "selected" : $auto_select = "" ;
        get_option("lang") == "en" ? $eng = "selected" : $eng = "" ;
        get_option("lang") == "fa" ? $persian = "selected" : $persian = "" ;
        $rows[] = array(
            'Language',
            '<select name="lang"><option value="none" '.$auto_select.'>Auto-Select</option><option value="en" '.$eng.'>English</option><option value="fa" '.$persian.'>Persian</option></select>',
            '<p class="description">Set the language of the payment portal.</p>',
        );

        $jeeb_redirect = get_option("jeeb_redirect");
        // Allows the merchant to specify a URL to redirect to upon the customer completing payment on the jeeb.io
        // invoice page. This is typcially the "Transaction Results" page.
        $rows[] = array(
                        'Redirect URL',
                        '<input name="jeeb_redirect" type="text" value="'.$jeeb_redirect.'" />',
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
                            'basecoin',
                            'btc',
                            'xrp',
                            'xmr',
                            'ltc',
                            'bch',
                            'eth',
                            'test-btc',
                            'lang'
                           );

            foreach ($params as $p) {
                if($_POST[$p]){
                  if ($_POST[$p] != null) {
                    update_option($p, $_POST[$p]);
                    debug_log($_POST[$p]);
                  }
                  else {
                    if($p!='btc'&&$p!='xrp'&&$p!='xmr'&&$p!='ltc'&&$p!='bch'&&$p!='eth'&&$p!='test-btc'){
                      add_settings_error($p, 'error', __('The setting '. $p.' cannot be blank! Please enter a value for this field', 'wpse'), 'error');
                  }
                }
              }
              else{
                update_option($p, NULL);
                debug_log(get_option($p));
              }
            }
        }

        return true;

    } catch (\Exception $e) {
        error_log('[Error] In jeeb plugin, form_jeeb() function on line ' . $e->getLine() . ', with the error "' . $e->getMessage() . '" .');
        throw $e;
    }
}

function convertBaseToTarget($url, $amount, $signature, $baseCur) {
    debug_log("Entered into Convert Base To Target");
    debug_log($url.'currency?'.$signature.'&value='.$amount.'&base='.$baseCur.'&target=btc');
    $ch = curl_init($url.'currency?'.$signature.'&value='.$amount.'&base='.$baseCur.'&target=btc');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json')
  );

  $result = curl_exec($ch);
  $data = json_decode( $result , true);
  debug_log('Response =>'. var_export($data, TRUE));
  // Return the equivalent bitcoin value acquired from Jeeb server.
  return (float) $data["result"];

  }


  function createInvoice($url, $amount, $options = array(), $signature) {
      debug_log("Entered into Create Invoice");
      $post = json_encode($options);

      $ch = curl_init($url.'payments/' . $signature . '/issue/');
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'Content-Length: ' . strlen($post))
      );

      $result = curl_exec($ch);
      $data = json_decode( $result , true);
      debug_log('Response =>'. var_export($data, TRUE));

      return $data['result']['token'];

  }

  function redirectPayment($url, $token) {
    debug_log("Entered into auto submit-form");
    // Using Auto-submit form to redirect user with the token
    echo "<form id='form' method='post' action='".$url."payments/invoice'>".
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

        $baseUri      = "https://core.jeeb.io/api/" ;
        $baseCur      = get_option('basecoin');
        $signature    = get_option('signature'); // Signature
        $callBack     = get_option('jeeb_redirect'); // Callback Url
        $notification = get_option('siteurl').'/?jeeb_callback=true';  // Notification Url
        $order_total  = $price;  // Total price in irr
        $params = array(
                        'btc',
                        'xrp',
                        'xmr',
                        'ltc',
                        'bch',
                        'eth',
                        'test-btc'
                       );

        foreach ($params as $p) {
          get_option($p) != NULL ? $target_cur .= get_option($p) . "/" : get_option($p) ;
        }

        debug_log("Session Id => " . $sessionid . " " . $purchase_log['id']);

        if($baseCur=='toman'){
          $baseCur='irr';
          $order_total *= 10;
        }

        $amount = convertBaseToTarget($baseUri, $order_total, $signature, $baseCur);

        $params = array(
          'orderNo'      => $purchase_log['id'],
          'value'        => (float) $amount,
          'webhookUrl'   => $notification,
          'callBackUrl'  => $callBack,
          'allowReject'  => get_option("network") == "Testnet" ? false : true,
          "coins"        => $target_cur,
          "allowTestNet" => get_option("network") == "Testnet" ? true : false,
          "language"     => get_option("lang") == "none" ? NUll : get_option("lang")
        );

        debug_log("Requesting with Params => " . json_encode($params));

        $token = createInvoice($baseUri, $amount, $params, $signature);

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

      if($json['signature'] == get_option("signature")){
        debug_log("Entered into Notification");
        debug_log("Response =>". var_export($json, TRUE));
      $table_name = $wpdb->prefix.'jeeb_keys';

      $orderNo = $json['orderNo'];

      $purchase_log = $wpdb->get_row("SELECT * FROM `" .WPSC_TABLE_PURCHASE_LOGS. "` WHERE `id`= " . $orderNo. " LIMIT 1", ARRAY_A);

      // Call Jeeb
      $network_uri = "https://core.jeeb.io/api/";
      if ( $json['stateId']== 2 ) {

        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'Invoice created.' WHERE `id`=". $orderNo;
        $wpdb->query($sql);


        transaction_results($purchase_log['sessionid'], false);
      }
      else if ( $json['stateId']== 3 ) {
        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '2' WHERE `id`=". $orderNo;
        $wpdb->query($sql);

        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'The payment has been received, but the transaction has not been confirmed on the blockchain network. This will be updated when the transaction has been confirmed.' WHERE `id`=". $orderNo;
        $wpdb->query($sql);

        $wpsc_cart->empty_cart();

        transaction_results($purchase_log['sessionid'], false);
      }
      else if ( $json['stateId']== 4 ) {
        $data = array(
          "token" => $json["token"]
        );

        $data_string = json_encode($data);
        $api_key = get_option("signature");
        $url = $network_uri.'payments/' . $api_key . '/confirm';
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
          $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '3' WHERE `id`=". $orderNo;
          $wpdb->query($sql);

          $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'The payment has been confirmed by Jeeb. You are now safe to deliver the order.' WHERE `id`=". $orderNo;
          $wpdb->query($sql);

          transaction_results($purchase_log['sessionid'], false);
        }
        else {
          debug_log('Payment rejected by jeeb');
        }
      }
      else if ( $json['stateId']== 5 ) {
        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '5' WHERE `id`=". $orderNo;
        $wpdb->query($sql);

        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'Invoice was expired and the transaction failed.' WHERE `id`=". $orderNo;
        $wpdb->query($sql);

        transaction_results($purchase_log['sessionid'], false);

      }
      else if ( $json['stateId']== 6 ) {
        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '5' WHERE `id`=". $orderNo;
        $wpdb->query($sql);

        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'Invoice was over paid and the transaction was rejected by Jeeb' WHERE `id`=". $orderNo;
        $wpdb->query($sql);

        transaction_results($purchase_log['sessionid'], false);

      }
      else if ( $json['stateId']== 7 ) {
        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '5' WHERE `id`=". $orderNo;
        $wpdb->query($sql);

        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'Invoice was partially paid and the transaction was rejected by Jeeb' WHERE `id`=". $orderNo;
        $wpdb->query($sql);

        transaction_results($purchase_log['sessionid'], false);
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

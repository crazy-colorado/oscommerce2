<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2008 osCommerce

  Released under the GNU General Public License
*/

  $login_request = true;

  require('includes/application_top.php');
  require('includes/functions/password_funcs.php');
  require('includes/classes/Yubico.php');

  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

// prepare to logout an active administrator if the login page is accessed again
  if (tep_session_is_registered('admin')) {
    $action = 'logoff';
  }

  if (tep_not_null($action)) {
    switch ($action) {
      case 'process':
        $username = tep_db_prepare_input($HTTP_POST_VARS['username']);
        $password = tep_db_prepare_input($HTTP_POST_VARS['password']);

        $otp = $username;
        $otp_error = false;

        // validate yubikey otp          
        $yubi = &new Auth_Yubico(1, '');
	    try {
	      $auth = $yubi->verify($otp);
	      if (PEAR::isError($auth)) {
	        $messageStack->add_session('YubiKey OTP Authentication error', 'error');
	        $otp_error = true;
          } 
	    } catch (Exception $e) {
	      $messageStack->add_session('YubiKey OTP Authentication failure', 'error');
	      $otp_error = true;
	    }
        
        if ($otp_error){
          tep_redirect(tep_href_link(FILENAME_DEFAULT));
          break;
        }
          
        // user YubiKey Token ID will act as the user name 
        $username = substr($otp, 0, 12);
        
        $check_query = tep_db_query("select id, user_name, user_password, admin_firstname from " . TABLE_ADMINISTRATORS . " where user_name = '" . tep_db_input($username) . "'");

        if (tep_db_num_rows($check_query) == 1) {
          $check = tep_db_fetch_array($check_query);

          if (tep_validate_password($password, $check['user_password'])) {
            tep_session_register('admin');

            $admin = array('id' => $check['id'],
                           'username' => $check['user_name'],
                           'admin_firstname' => $check['admin_firstname']);

            if (tep_session_is_registered('redirect_origin')) {
  /* Yubico - Bug fix
   * Bug Description:
   *   When a session expires, the last request details are saved i.e. request url and GET parameters.
   *   And when administrator logs in, he/she is redirected to the last request
   *   However, while saving or retrieving last request details, POST parameters are not considered and
   *   this causes a problem if the last request was a POST request.
   *   Also, a redirect is a GET request and not a POST request.
   * Bug Date: 08/07/2009
   * Solution: 
   *   If the last request was POST request, simply redirect user to the base page and not to 
   *   the last request
   */
              $page = $redirect_origin['page'];
              $get_string = '';
              $post_string = '';

              if (function_exists('http_build_query')) {
                $get_string = http_build_query($redirect_origin['get']);
                $post_string = http_build_query($redirect_origin['post']);
              }

              tep_session_unregister('redirect_origin');

              if(isset($post_string) && tep_not_null($post_string)) {
              	tep_redirect(tep_href_link($page));
              } else {
              	tep_redirect(tep_href_link($page, $get_string));
              }
            } else {
              tep_redirect(tep_href_link(FILENAME_DEFAULT));
            }
          }
        }

        $messageStack->add(ERROR_INVALID_ADMINISTRATOR, 'error');

        break;

      case 'logoff':
        tep_session_unregister('selected_box');
        tep_session_unregister('admin');

        if (isset($HTTP_SERVER_VARS['PHP_AUTH_USER']) && !empty($HTTP_SERVER_VARS['PHP_AUTH_USER']) && isset($HTTP_SERVER_VARS['PHP_AUTH_PW']) && !empty($HTTP_SERVER_VARS['PHP_AUTH_PW'])) {
          tep_session_register('auth_ignore');
          $auth_ignore = true;
        }

        tep_redirect(tep_href_link(FILENAME_DEFAULT));

        break;

      case 'create':
        $check_query = tep_db_query("select id from " . TABLE_ADMINISTRATORS . " limit 1");

        if (tep_db_num_rows($check_query) == 0) {
          $username = tep_db_prepare_input($HTTP_POST_VARS['username']);
          $password = tep_db_prepare_input($HTTP_POST_VARS['password']);
          $fname = tep_db_prepare_input($HTTP_POST_VARS['admin_fname']);
          $lname = tep_db_prepare_input($HTTP_POST_VARS['admin_lname']);
          

          $otp = $username;
          $otp_error = false;

          // validate yubikey otp          
          $yubi = &new Auth_Yubico(1, '');
	      try {
	        $auth = $yubi->verify($otp);
	        if (PEAR::isError($auth)) {
	          $messageStack->add_session('YubiKey OTP Authentication error', 'error');
	          $otp_error = true;
            } 
	      } catch (Exception $e) {
	        $messageStack->add_session('YubiKey OTP Authentication failure', 'error');
	        $otp_error = true;
	      }
        
          if ($otp_error){
            tep_redirect(tep_href_link(FILENAME_DEFAULT));
            break;
          }
          
          // user YubiKey Token ID will act as the user name 
          $username = substr($otp, 0, 12);
        
          tep_db_query('insert into ' . TABLE_ADMINISTRATORS . ' (user_name, user_password, admin_firstname, admin_lastname) values ("' . $username . '", "' . tep_encrypt_password($password) . '", "' . tep_db_input($fname) . '", "' . tep_db_input($lname) . '" )');
        }

        tep_redirect(tep_href_link(FILENAME_LOGIN));

        break;
    }
  }

  $languages = tep_get_languages();
  $languages_array = array();
  $languages_selected = DEFAULT_LANGUAGE;
  for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
    $languages_array[] = array('id' => $languages[$i]['code'],
                               'text' => $languages[$i]['name']);
    if ($languages[$i]['directory'] == $language) {
      $languages_selected = $languages[$i]['code'];
    }
  }

  $admins_check_query = tep_db_query("select id from " . TABLE_ADMINISTRATORS . " limit 1");
  if (tep_db_num_rows($admins_check_query) < 1) {
    $messageStack->add(TEXT_CREATE_FIRST_ADMINISTRATOR, 'warning');
  }
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<meta name="robots" content="noindex,nofollow">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onload="SetFocus();">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td><table border="0" width="100%" cellspacing="0" cellpadding="0" height="40">
      <tr>
        <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
        <td class="pageHeading" align="right"><?php echo tep_draw_form('adminlanguage', FILENAME_DEFAULT, '', 'get') . tep_draw_pull_down_menu('language', $languages_array, $languages_selected, 'onChange="this.form.submit();"') . tep_hide_session_id() . '</form>'; ?></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td>

<?php
  $heading = array();
  $contents = array();

  if (tep_db_num_rows($admins_check_query) > 0) {
    $heading[] = array('text' => '<b>' . HEADING_TITLE . '</b>');

    $contents = array('form' => tep_draw_form('login', FILENAME_LOGIN, 'action=process', 'post', 'onsubmit="return performPrePostChecks();"'));
    $contents[] = array('text' => 'YubiKey OTP:<br>' . tep_draw_input_field('username','','class="yubiKeyInput" onKeyPress="javascript:stopEnter(event);"'));
    $contents[] = array('text' => '<br>' . TEXT_PASSWORD . '<br>' . tep_draw_password_field('password'));
    $contents[] = array('align' => 'center', 'text' => '<br><input type="submit" value="' . BUTTON_LOGIN . '" />');
  } else {
    $heading[] = array('text' => '<b>' . HEADING_TITLE . '</b>');

    $contents = array('form' => tep_draw_form('login', FILENAME_LOGIN, 'action=create', 'post', '"onsubmit="return performPrePostChecks();"'));
    $contents[] = array('text' => TEXT_CREATE_FIRST_ADMINISTRATOR);
    $contents[] = array('text' => '<br>'.TEXT_INFO_FIRST_NAME.'<br>' . tep_draw_input_field('admin_fname',$aInfo->admin_firstname));
    $contents[] = array('text' => '<br>'.TEXT_INFO_LAST_NAME.'<br>' . tep_draw_input_field('admin_lname',$aInfo->admin_lastname));
    $contents[] = array('text' => '<br>YubiKey OTP:<br>' . tep_draw_input_field('username','','class="yubiKeyInput" onKeyPress="javascript:stopEnter(event);"'));
    $contents[] = array('text' => '<br>' . TEXT_PASSWORD . '<br>' . tep_draw_password_field('password'));
    $contents[] = array('align' => 'center', 'text' => '<br><input type="submit" value="' . BUTTON_CREATE_ADMINISTRATOR . '" />');
  }

  $box = new box;
  echo $box->infoBox($heading, $contents);
?>

    </td>
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>

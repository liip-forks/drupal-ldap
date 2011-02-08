<?php
// $Id$

/**
 * @file
 * ldap_authn provides authentication against ldap server.
 */

/**
 * helper function for ldap_authn_form_user_login_block_alter and ldap_authn_form_user_login_alter
 *
 * hook_user is gone in drupal 7 so functionality can be replaced by
 * altering login form submit and validate functions
 * http://drupal.org/update/modules/6/7#remove_op
 *
 * if form is being generated on non https and is set in preferences, set warning and end form development
 *   add submit functions to form
 *   - make sure submit function is in the correct order; that is if ldap precedes drupal, make _ldap_authn_login_form_submit first.
 *   do not remove other authentication submit functions, just reorder.
 */
function _ldap_authentication_login_form_alter(&$form, &$form_state, $form_id) {

   /**
    * make sure ldap_autentication is configured and valid first
   */

  if (!$auth_conf = ldap_authentication_get_valid_conf()) {
    return;
  } 
  elseif (!$auth_conf->enabled_servers()) {
    return;
  }

   /**
    *
    * add validate function to test for ldap authentication
    * should be placed after user_login_authenticate_validate
    * 1. user_login_name_validate
    * 2. user_login_authenticate_validate
    * 3. external authentication validate functions
    * 4. user_login_final_validate
    * 
    * as articulated above user_login_default_validators() in user.module
    * 
    * without any other external authentication modules, this array will start out as:
    *    array('user_login_name_validate', 'user_login_authenticate_validate', 'user_login_final_validate')
    */

  if (@in_array('user_login_authenticate_validate', $form['#validate'])) {
    $new_validation_sequence = array();
    foreach ($form['#validate'] as $validate_function_name) {
      if ($validate_function_name == 'user_login_authenticate_validate') {
        if ($auth_conf->authenticationMode == LDAP_AUTHENTICATION_MIXED) {
         // if mixed mode, allow drupal authentication first
          $new_validation_sequence[] = 'user_login_authenticate_validate';
          $new_validation_sequence[] = 'ldap_authentication_user_login_authenticate_validate';
        }
        elseif ($auth_conf->authenticationMode == LDAP_AUTHENTICATION_EXCLUSIVE) { 
         // see drupal.org/node/1009990 and drupal.org/node/1022362 change back when fixed.
          $new_validation_sequence[] = 'user_login_authenticate_validate';
          $new_validation_sequence[] = 'ldap_authentication_user_login_authenticate_validate';
        }
        else { // misconfigured ldap authentication, restore to original validation sequence
        $new_validation_sequence[] = 'user_login_authenticate_validate';
        }
      } 
      else {
        $new_validation_sequence[] = $validate_function_name;
      }
    }
    $form['#validate'] = $new_validation_sequence;
  }

  if ($form_id == 'user_login_block') {
    $user_register = variable_get('user_register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL);
    $vars = array(
      'show_reset_pwd' => ldap_authentication_show_reset_pwd(),
      'auth_conf' => $auth_conf,
    );

    $form['links']['#markup'] = theme('ldap_authentication_user_login_block_links', $vars);

  }

  ldap_servers_disable_http_check($form);

}


  

function _ldap_authentication_form_user_profile_form_alter(&$form, $form_state) {
  // keep in mind admin may be editing another users profile form.  don't assume current global $user 
  $auth_conf = ldap_authentication_get_valid_conf();
  if ($auth_conf && ldap_authentication_ldap_authenticated($form['#user'])) {
    if ($auth_conf->emailOption == LDAP_AUTHENTICATION_EMAIL_FIELD_REMOVE) {
    $form['account']['mail']['#type'] = 'hidden';
    }
    elseif ($auth_conf->emailOption == LDAP_AUTHENTICATION_EMAIL_FIELD_DISABLE) {
      $form['account']['mail']['#disabled'] = TRUE;
      $form['account']['mail']['#description'] = t('This email address is automatically set and may not be changed.');
    }

    if (!ldap_authentication_show_reset_pwd($form['#user'])) {
      $form['account']['current_pass']['#disabled'] = TRUE;
      $form['account']['pass']['#disabled'] = TRUE;
    }
  } 
}


/**
 * user form validation will take care of username, pwd fields
 *
 * this may validate if the user exists in ldap in the case of using
 * ldap authentication exclusively
 */
function _ldap_authentication_user_login_authenticate_validate(&$form_state) {
  
  $detailed_watchdog_log = FALSE;  // this is for debugging and will go in RC1
  
  $name = $form_state['values']['name'];
  $pass = $form_state['values']['pass'];
  $watchdog_detail[0] = 'ldap_authentication';
  $pre = "$name: ";
  $watchdog_detail[3] = WATCHDOG_DEBUG;
  
  if (!$auth_conf = ldap_authentication_get_valid_conf()) {
    $error = 'Failed to get valid ldap authentication configuration.';
    watchdog('ldap_authentication', $error, WATCHDOG_ERROR);
    form_set_error('name', 'Server Error: ' . $error);
    return FALSE;
  } 

  // if already succeeded at authentication, see if LDAP Exclusive is set
  if (isset($form_state['uid']) && is_integer($form_state['uid'])) {
    if ($auth_conf->authenticationMode == LDAP_AUTHENTICATION_MIXED || $form_state['uid'] == 1) {
      if ($detailed_watchdog_log) {
        watchdog('ldap_authentication', $pre . 'Previously authenticated in mixed mode or uid=1', WATCHDOG_DEBUG);
        }
      return;  // already passed previous authentication validation
    }
    elseif ($auth_conf->authenticationMode == LDAP_AUTHENTICATION_EXCLUSIVE) {
      if ($detailed_watchdog_log) {
        watchdog('ldap_authentication', $pre . 'Previously authenticated in exclusive mode or uid is not 1.  Clear uid
          in form_state and attempt ldap authentication.', WATCHDOG_DEBUG);
      }
      $form_state['uid'] = NULL;  // passed previous authentication, but only ldap should be used
    } 
  }
  
  if (!count($auth_conf->servers)) {
    $error = 'No LDAP servers configured.';
    watchdog('ldap_authentication', $error, WATCHDOG_ERROR);
    form_set_error('name', 'Server Error: ' . $error);
  }

  $account = user_load_by_name($name);
  if (is_object($account)) {
    if ($account->uid == 1) {
      if ($detailed_watchdog_log) {
      watchdog('ldap_authentication', $pre . 'Drupal username maps to user 1, so do not authenticate with ldap', WATCHDOG_DEBUG);
      }
      return FALSE;  // user 1 must use drupal authentication
    }
    else {
      $account_exists = TRUE;
      $user_data = $account->data;
      $authmaps = user_get_authmaps($name);
      $ldap_authentication_authmap = isset($authmaps['ldap_authentication']);
      $no_authmaps = (boolean)(count($authmaps));
      if ($detailed_watchdog_log) {
        watchdog('ldap_authentication', $pre . 'Drupal User Account found.  Continuing on to attempt ldap authentication', WATCHDOG_DEBUG);
      }  
    }
  } 
  else {
    $account_exists = FALSE;
    if ($auth_conf->createLDAPAccounts == FALSE) {
      return FALSE; // don't bother authenticating if account doesn't exists and account creation not allowed
    }
    if ($detailed_watchdog_log) {
      watchdog('ldap_authentication', $pre . 'Exisitng Drupal User Account not found.  Continuing on to attempt ldap authentication', WATCHDOG_DEBUG);
    }
  }

  foreach ($auth_conf->servers as $sid => $ldap_server) {
    $authentication_result = LDAP_AUTHENTICATION_RESULT_FAIL_GENERIC;
    $result = $ldap_server->connect();
    if ($result != LDAP_SUCCESS) {
      $authentication_result = LDAP_AUTHENTICATION_RESULT_FAIL_CONNECT;
      continue; // next server, please
    }

    $result = $ldap_server->bind();
    if ($result != LDAP_SUCCESS) {
      $authentication_result = LDAP_AUTHENTICATION_RESULT_FAIL_BIND;
      continue; // next server, please
    }

     /**
     * does user exist in ldap?
     */
    $ldap_user = $ldap_server->user_lookup($name);
    if (!$ldap_user) {
      $authentication_result = LDAP_AUTHENTICATION_RESULT_FAIL_FIND;
      continue; // next server, please
    }

    /**
     * check allowed and excluded list and php for allowed users
     */
    if (!$auth_conf->allowUser($name, $ldap_user)) {
      $authentication_result = LDAP_AUTHENTICATION_RESULT_FAIL_DISALLOWED;
      break;  // regardless of how many servers, disallowed user fails
    }

    /**
     * test password
     */
    $result = $ldap_server->bind($ldap_user['dn'], $pass);
    if ($result != LDAP_SUCCESS) {
      $authentication_result = LDAP_AUTHENTICATION_RESULT_FAIL_CREDENTIALS;
      continue; // next server, please
    } 
    else {
      $authentication_result = LDAP_AUTHENTICATION_RESULT_SUCCESS;
      break; //success
    }
  }  // end loop through servers
  
  if ($detailed_watchdog_log) {
    watchdog('ldap_authentication', $pre . 'Authentication result id=' . $authentication_result . 
      '(' . _ldap_authentication_err_text($authentication_result) . ')', WATCHDOG_DEBUG);
    }

  if ($authentication_result != LDAP_AUTHENTICATION_RESULT_SUCCESS) {
    $error_message = _ldap_authentication_err_text($authentication_result);
   // fail scenario 1.  ldap auth exclusive and failed  throw error
    if ($auth_conf->authenticationMode == LDAP_AUTHENTICATION_EXCLUSIVE) {
      if ($detailed_watchdog_log) {
        watchdog('ldap_authentication', $pre . 'setting error because failed at ldap and 
          LDAP_AUTHENTICATION_EXCLUSIVE is set to true.  So need to stop authentication of Drupal user that is not user 1', WATCHDOG_DEBUG);
      }
      form_set_error('name', $error_message);
    } 
    else {
   // fail scenario 2.  simply fails ldap.  return false. 
   // don't show user message, may be using other authentication after this that may succeed.
      watchdog('ldap_authentication', 
        t('Failed ldap authentication.  
          User may have authenticated successfully by other means in a mixed authentication site.
          LDAP Authentication Error #: %authentication_result  %error_message  ', 
          array('%authentication_result' => $authentication_result, '%error_message' => $error_message)
        ), 
        WATCHDOG_DEBUG
      );
    }
    return FALSE;
  }
  
   /**
    * case 1: previously drupal authenticated user authenticated successfully on ldap
    *
    */
  if (!$account_exists) {  

    if ($account_with_same_email = user_load_by_mail($ldap_user['mail'])) {  
      /** 
       * username does not exist but email does.  Since user_external_login_register does not deal with
       * mail attribute and the email conflict error needs to be caught beforehand, need to throw error here
       */
      watchdog('ldap_authentication', 'LDAP user with DN %dn has email address (%mail) conflict with a drupal user %name', array('%name' => $account_with_same_email->name, '%mail' => $ldap_user['mail']), WATCHDOG_ERROR);
      drupal_set_message(t('Another user already exists in the system with the same email address. You should contact the system administrator in order to solve this conflict.'), 'error');
      return FALSE;
      
    } 
    /** 
     * 
     * new ldap_authentication provisioned account could let user_external_login_register create the account and set authmaps, but would need 
     * to add mail and any other user->data data in hook_user_presave which would mean requerying ldap
     * or having a global variable.  At this point the account does not exist, so there is no
     * reason not to create it here.
     * 
     * @todo create patch for user_external_login_register to deal with new external accounts
     *       a little tweak to add user->data and mail etc as parameters would make it more useful
     *       for external authentication modules
     */
    
    $edit = array(
      'name' => $name, 
      'pass' => user_password(20), 
      'mail' => $ldap_user['mail'], 
      'init' => $ldap_user['mail'], 
      'status' => 1,
    );

    // save 'init' data to know the origin of the ldap authentication provisioned account
    $edit['data']['ldap_authentication']['init'] = array(
      'sid'  => $sid, 
      'dn'   => $ldap_user['dn'], 
      'mail' => $ldap_user['mail'],
    );

    if (!$account = user_save( NULL, $edit)) {
      drupal_set_message(t('User account creation failed because of system problems.'), 'error');
      return FALSE;
    } 
    else {  
      user_set_authmaps($account, array('authname_ldap_authentication' => $name));
    }
  }
  else {  // account already exists
    if ($ldap_authentication_authmap == FALSE) {  // LDAP_authen.AC.disallow.ldap.drupal
      if ($auth_conf->loginConflictResolve == LDAP_AUTHENTICATION_CONFLICT_LOG) {
        watchdog('ldap_authentication', 'LDAP user with DN %dn has a naming conflict with a local drupal user %name', array('%dn' => $ldap_user['dn'], '%name' => $account->name), WATCHDOG_ERROR);
        drupal_set_message(t('Another user already exists in the system with the same login name. You should contact the system administrator in order to solve this conflict.'), 'error');
        return FALSE;
      } 
      else { // LDAP_authen.AC.disallow.ldap.drupal
      // add ldap_authentication authmap to user.  account name is fine here, though cn could be used
        user_set_authmaps($account, array('authname_ldap_authentication' => $name));
        watchdog('ldap_authentication', $pre . 'set authmap of authname_ldap_authentication', WATCHDOG_DEBUG);
      }
    } 
  }

/**
 * we now have valid, ldap authenticated username with an account authmapped to ldap_authentication.
 * since user_external_login_register can't deal with user mail attribute and doesn't do much else, it is not
 * being used here.
 */


  $fake_form_state['uid'] = $account->uid;
  if ($detailed_watchdog_log) {watchdog('fake_form_state uid=', WATCHDOG_DEBUG);}
      
  user_login_submit(array(), $fake_form_state);
  global $user;
  $form_state['uid'] = $user->uid;
  if ($detailed_watchdog_log) {watchdog('actual uid=', $user->uid);}
  return;

}

function _ldap_authentication_err_text($error) {

  $msg = t('unknown error: ' . $error);
  switch ($error) {
    case LDAP_AUTHENTICATION_RESULT_FAIL_CONNECT:
    $msg = "Failed to connect to ldap server";
    break;

    case LDAP_AUTHENTICATION_RESULT_FAIL_BIND:
    $msg = "Failed to bind to ldap server";
    break;     

    case LDAP_AUTHENTICATION_RESULT_FAIL_FIND:
    $msg =  t('Sorry, unrecognized username or password.');
    break;    

    case LDAP_AUTHENTICATION_RESULT_FAIL_DISALLOWED:
    $msg = "User disallowed";
    break;      


    //   form_set_error('name', t('Sorry, unrecognized username or password. <a href="@password">Have you forgotten your password?</a>', array('@password' => url('user/password'))));
    case LDAP_AUTHENTICATION_RESULT_FAIL_CREDENTIALS:
    $msg =  t('Sorry, unrecognized username or password.');
    break; 

    case LDAP_AUTHENTICATION_RESULT_FAIL_GENERIC:
    $msg = t('Sorry, unrecognized username or password.');
    break; 
  }

  return $msg;
}

function ldap_authentication_redirect_to_ldap_help() {

  if ($auth_conf = ldap_authentication_get_valid_conf() && $auth_conf->ldapUserHelpLinkUrl) {
    drupal_goto($auth_conf->ldapUserHelpLinkUrl);
  } 
  else {
    return "Misconfigured LDAP Help Link";
  }

}
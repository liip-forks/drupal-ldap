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
function _ldap_authentication_login_form_alter($form, $form_state, $form_id) {
  $form['#validate'] = array('ldap_authentication_login_form_validate');
  $form['submit'][] = 'ldap_authentication_login_form_submit'; 
  
}

/**
 * user form validation will take care of username, pwd fields
 *
 * this may validate if the user exists in ldap in the case of using
 * ldap authentication exclusively
 */
function _ldap_authentication_login_form_validate($values) {
   

}


/**
 * _ldap_authentication_login_form_submit
 *      submit function added to login forms
 *
 * basic flow thoughts
 * -- (1) check if user has already succeeded with drupal or other type of authenction
 * --   if so, exit
 * -- (2) if not, see if user is valid for ldap authentication
 * --   any restrictions placed in ldap_authn configuration such as specifc user attributes or OUs.
 * --   invoke hook_ldap_authn_valid_user($user, $ldap_data) 
 * --     which returns boolean and error message signifying why user is not valid.
 * -- (3) if user is not valid, end ldap_authn process and allow other authn functions to continue
 * --     or grind login process to a halt
 * -- (4) if user is valid, test credentials against ldap
 * -- (5) if fail, end ldap_authn process and allow other authn functions to continue
 * --     or grind login process to a halt
 * -- (6) on success, user_external_login_register($name, 'ldap_authentication') which will create account if needed
 * --       and it will call user_set_authmaps() and set authmaps
**/

function _ldap_authentication_login_form_submit() {
 

}

function _ldap_authentication($user, $form_values) {
  global $user;

  $uid = $form_values['name'];
  $passwd = trim($form_values['pass']);

  // Search for user using authmap
  $account = user_external_load($identity);

  //But failover to Drupal internal
  if ($account = 0) {
    $account = user_load($identity);
  }

  if (isset($account->uid)) {
    if (!variable_get('user_email_verification', TRUE) || $account->login) {
     // in d7, use user_external_login_register
    //  user_external_login($account, $_SESSION['ldap']['user_login_values']);
    user_external_login_register($name, 'ldap_authentication');
    // note that this function does not address storing cn of user.
    // and ldap related user data should go in user.data field
    }
    else {
      drupal_set_message(t('You must validate your email address for this account before logging.'));
    }
  }
  elseif (variable_get('user_register', 1)) {
    // Get Info from LDAP
    $response = _ldap_authentication_user_load()

    // Register new user
    $form_state['redirect'] = NULL;
    $form_state['values']['name'] = 
    $form_state['values']['mail'] = (empty($response['openid.sreg.email'])) ? '' : $response['openid.sreg.email'];
    $form_state['values']['pass']  = user_password();
    $form_state['values']['status'] = variable_get('user_register', 1) == 1;
    $form_state['values']['response'] = $response;
    $form_state['values']['auth_openid'] = $identity;

    if (empty($response['openid.sreg.email']) && empty($response['openid.sreg.nickname'])) {
      drupal_set_message(t('Please complete the registration by filling out the form below. If you already have an account, you can <a href="@login">log in</a> now and add your OpenID under "My account".', array('@login' => url('user/login'))), 'warning');
      $success = FALSE;
    }
    else {
      $form = drupal_retrieve_form('user_register', $form_state);
      drupal_prepare_form('user_register', $form, $form_state);
      drupal_validate_form('user_register', $form, $form_state);
      $success = !form_get_errors();
      if (!$success) {
        drupal_set_message(t('Account registration using the information provided by your OpenID provider failed due to the reasons listed below. Please complete the registration by filling out the form below. If you already have an account, you can <a href="@login">log in</a> now and add your OpenID under "My account".', array('@login' => url('user/login'))), 'warning');
        // Append form validation errors below the above warning.
        $messages = drupal_get_messages('error');
        foreach ($messages['error'] as $message) {
          drupal_set_message( $message, 'error');
        }
      }
    }
    if (!$success) {
      // We were unable to register a valid new user, redirect to standard
      // user/register and prefill with the values we received.
      $_SESSION['openid']['values'] = $form_state['values'];
      // We'll want to redirect back to the same place.
      $destination = drupal_get_destination();
      unset($_REQUEST['destination']);
      drupal_goto('user/register', $destination);
    }
    else {
      unset($form_state['values']['response']);
      $account = user_save('', $form_state['values']);
      // Terminate if an error occured during user_save().
      if (!$account) {
        drupal_set_message(t("Error saving user account."), 'error');
        drupal_goto();
      }
      user_external_login($account);
    }
    drupal_redirect_form($form, $form_state['redirect']);
  }
  else {
    drupal_set_message(t('Only site administrators can create new user accounts.'), 'error');
  }
  drupal_goto();
}





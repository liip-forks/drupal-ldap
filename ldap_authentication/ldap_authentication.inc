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
  } elseif (!$auth_conf->enabled_servers()) {
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
    */
    
     if (@in_array('user_login_authenticate_validate', $form['#validate'])) {
      $new_validation_sequence = array();
      foreach ($form['#validate'] as $validate_function_name) {
       $new_validation_sequence[] = $validate_function_name;
       if ($validate_function_name == 'user_login_authenticate_validate') {
         if ($auth_conf->authenticationMode == LDAP_AUTHENTICATION_MIXED) {
           // if mixed mode, allow drupal authentication first
           $new_validation_sequence[] = 'user_login_authenticate_validate';
           $new_validation_sequence[] = 'ldap_authentication_user_login_authenticate_validate';
         }
         elseif ($auth_conf->authenticationMode == LDAP_AUTHENTICATION_EXCLUSIVE) { 
           // if ldap exclusive do ldap authentication first and don't allow to proceed unless uid = 1
           $new_validation_sequence[] = 'ldap_authentication_user_login_authenticate_validate';
           $new_validation_sequence[] = 'user_login_authenticate_validate';
         }
         else { // misconfigured ldap authentication, restore to original validation sequence
          $new_validation_sequence[] = 'user_login_authenticate_validate';
         }
         
       }
      }
      $form['#validate'] = $new_validation_sequence;
     }
     


   
   switch ($form_id) {
    
    case  'user_login':
     
    break;
    
    case 'user_login_block':
     
     if ($auth_conf->logonFormHideResetPassword || $auth_conf->logonFormHideCreateAccount) {
      $items = array();
      if (!$auth_conf->logonFormHideCreateAccount) {
        $items[] = l(t('Create new account'), 'user/register', array('attributes' => array('title' => t('Create a new user account.'))));
      }
      if (!$auth_conf->logonFormHideResetPassword) {
          $items[] = l(t('Request new password'), 'user/password', array('attributes' => array('title' => t('Request new password via e-mail.'))));
      }
      $form['links']['#markup'] = theme('item_list', array('items' => $items));
     }
     
    break;
    
    
   }
 
  ldap_servers_disable_http_check($form);
 
 

 
/**
 * @todo remove the following after we're sure its not needed.
 * 
 *
 *  user_login_submit function finalizes login process.
 *  so ldap_authentication_login_form_submit must come before user_login_submit
 *  in order to create users and enter in authmap.  Otherwise
 *  we will not be able to leverage standard user login flow.
 *
 *  user_login_submit will call user_login_finalize and user_load
 *    which is where we will use hooks to add any ldap attibutes to user data/object
 */  

//  $form['#submit'] = array_merge(array('ldap_authentication_login_form_submit'),$form['#submit']); 
  
}

/**
 * user form validation will take care of username, pwd fields
 *
 * this may validate if the user exists in ldap in the case of using
 * ldap authentication exclusively
 */
function _ldap_authentication_user_login_authenticate_validate(&$form_state) {
 
 if ($form_state['uid']) {
   return;  // already passed previous authentication validation
 }
 if (!$auth_conf = ldap_authentication_get_valid_conf()) {
   return FALSE;
 } 
 
 $name = $form_state['values']['name'];
 $pass = $form_state['values']['pass'];
 $account = user_load_by_name($name);
 if (is_object($account)) {
   if ($account->uid == 1) {
     return FALSE;
   } else {
     $account_exists = TRUE;
     $user_data = $account->data;
     $authmaps = user_get_authmaps($name);
     $ldap_authentication_authmap = (boolean)(isset($authmaps['ldap_authentication']));
     $no_authmaps = (boolean)(count($authmaps));
   }
 } else {
   $account_exists = FALSE;
 }

 /**
  *
  * if LDAP_AUTHENTICATION_MIXED return  the Drupal built in authentication will
  * have already been validated (and failed) so if uid == 1 then throw error
  *
  **/
 
 if ($auth_conf->authenticationMode ==  LDAP_AUTHENTICATION_MIXED) {
  if ($account_exists && $account->uid == 1) {
   form_set_error('name', 'Failed Authentication');
   return FALSE;
  }
 }
 //print "<pre>"; print_r($auth_conf->servers); die;
 foreach ($auth_conf->servers as $sid => $ldap_server) {
  
  $result = $ldap_server->connect();
  if ($result != LDAP_SUCCESS) {
    drupal_set_message('Failed to connect to authentication server.', 'error');
    continue; // next server, please
  }
  
  $result = $ldap_server->bind();
  if ($result != LDAP_SUCCESS) {
    drupal_set_message('Failed to connect to authentication server.', 'error');
    continue; // next server, please
  }
  
   /**
   * does user exist in ldap?
   */
  $ldap_user = $ldap_server->user_lookup($name);
  if (!$ldap_user) {
    continue; // next server, please
  }
  

  /**
   * check allowed and excluded list and php for allowed users
   */
  if (!$auth_conf->allowUser($name, $ldap_user)) {
    drupal_set_message('User not permitted.', 'warning');
    return FALSE;
  }
  
  /**
   * test password
   */
  $result = $ldap_server->bind($ldap_user['dn'], $pass);
  if ($result != LDAP_SUCCESS) {
    continue; // next server, please
  }
  
   /**
    * previously drupal authenticated user authenticated successfully on ldap
    *
    */
  if (!$account_exists) {  

    if ($account_with_same_email = user_load_by_mail($ldap_user['mail'])) {  
      /** 
       * username exists but email does not.  Since user_external_login_register does not deal with
       * mail attribute and the email conflict error needs to be caught beforehand, need to throw error here
       * @todo this may be a configuration option to allow email conflict resolution, but would likely
       *   be very problematic and insecure.
       * @todo could continue on to next server here, but better behaviour is to grind to a screaching halt
       *   since two ldap servers with same user and credentials will unlikely have 2 different derived email addresses
       */
       watchdog('ldap_authentication', 'LDAP user with DN %dn has email address (%mail) conflict with a drupal user %name', array('%name' => $account_with_same_email->name, '%mail' => $ldap_user['mail']), WATCHDOG_ERROR);
       drupal_set_message(t('Another user already exists in the system with the same email address. You should contact the system administrator in order to solve this conflict.'), 'error');
       return FALSE;
      
    } 
    /** 
     * 
     * new ldap_authenticaiton provisioned accountcould let user_external_login_register create the account and set authmaps, but would need 
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
        drupal_set_message('User account creation failed because of system problems.', 'error');
        return FALSE;
      } else {  
        user_set_authmaps($account, array('authname_ldap_authentication' => $name));
      }
      
    }
   else {  // account already exists
    if ($ldap_authentication_authmap == FALSE) {
      if ($auth_conf->loginConflictResolve == LDAP_AUTHENTICATION_CONFLICT_LOG) {
        watchdog('ldap_authentication', 'LDAP user with DN %dn has a naming conflict with a local drupal user %name', array('%dn' => $ldap_user['dn'], '%name' => $account->name), WATCHDOG_ERROR);
        drupal_set_message(t('Another user already exists in the system with the same login name. You should contact the system administrator in order to solve this conflict.'), 'error');
        return FALSE;
      } 
      else { // add ldap_authentication authmap to user.  account name is fine here, though cn could be used
        user_set_authmaps($account, array('authname_ldap_authentication' => $name));
      } 
    }
  }  


  /**
   * we now have valid, ldap authenticated username with an account authmapped to ldap_authentication.
   * since user_external_login_register can't deal with user mail attribute and doesn't do much else, it is not
   * being used here.
   */

  
    $fake_form_state['uid'] = $account->uid;
    user_login_submit(array(), $fake_form_state);
    global $user;
    $form_state['uid'] = $user->uid;
    return TRUE;
   
 } // next ldap server
 
 return FALSE;
}


/**
 * @todo remove the following after we're sure its not needed.
 * 
 * _ldap_authentication_login_form_submit
 *      submit function added to login forms
 *
 * -- (6) on success, user_external_login_register($name, 'ldap_authentication') which will create account if needed
 * --       and it will call user_set_authmaps() and set authmaps


function _ldap_authentication_login_form_submit(&$form, &$form_state) {


}
**/

function _ldap_authentication_form_user_profile_form_alter(&$form, $form_state) {

 if ($auth_conf = ldap_authentication_get_valid_conf()) {

    if ($auth_conf->emailOption == LDAP_AUTHENTICATION_EMAIL_FIELD_REMOVE) {
      unset($form['account']['mail']);
    }
    elseif ($auth_conf->emailOption == LDAP_AUTHENTICATION_EMAIL_FIELD_DISABLE) {
     $form['account']['mail']['#disabled'] = TRUE;
     $form['account']['mail']['#description'] = t('This email address is automatically set and may not be changed.');
    }
 
  

  } 
  
}



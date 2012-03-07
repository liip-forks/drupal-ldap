<?php

/**
 * @file
 * Functions provided by ldap_user to be used by other modules
 */



/**
 * function to provision (create) an ldap identified drupal user account.
 * -- normally a user_save() call would take care of this with ldap related fields
 *    being populated automatically. use cases for user_save are:
 *      - provisioning via feeds,
 *      - manual creation of accounts
 *      - other situations where username mapping to ldap server and ldap entry is not ambiguous
 *
 * -- use cases for this functions:
 *      - when relationship between drupal user name and ldap server and entry is ambiguous
 *      - when error handling of user_save() is not adequate for workflow such as creation on user logon
 *
 *
 * @param array $user_fields mix of fields for drupal user object a
 *   $user_fields['name'] - drupal username
 *   $user_fields['pass'] - drupal password.  should be left empty to be replaced by random one
 *   $user_fields['mail'] - email address
 *   $user_fields['init']
 *   $user_fields['status'] - defaults to 1, but could be 0 (disabled) in some scenarios
 *
 *   The following will be put into $user entity fields of the same name
 *   $user_fields['ldap_user_provisioned_sid']
 *   $user_fields['ldap_user_puid']
 *   $user_fields['ldap_user_puid_property']
 *   $user_fields['ldap_user_dn']
 *
 * @param user std class object $account.  to store any account data that would go into $user_save() $account parameter.
 *   $account->is_new should be true, $account->uid should not be populated
 *

 * @return new drupal user account if created, otherwise integer error message such
 * as LDAP_CREATE_ACCOUNT_ALREADY_EXISTS, LDAP_CREATE_ERROR
 *
 */
function ldap_user_provision_drupal_account($user_fields, $account = NULL, $provisioning_sid = NULL, $ldap_entry = NULL) {

  //@todo.  implement hook_ldap_user_provision_attributes to get all attributes to query when provisioning accounts
  //@todo query ldap for user entry with those fields
  //@todo override any attributes in $ldap_user_fields

  $drupal_user_fields['name'] = $user_fields['name'];
  $drupal_user_fields['pass'] = user_password(20);
  $drupal_user_fields['mail'] = $user_fields['mail'];
  $drupal_user_fields['init'] = $user_fields['mail'];
  $drupal_user_fields['status'] = 1;

  // save 'init' data to know the origin of the ldap authentication provisioned account
  $drupal_user_fields['data']['ldap_authentication']['init'] = array(
    'sid'  => $sid,
    'dn'   => $user_fields['dn'],
    'mail' => $user_fields['mail'],
  );
 // $drupal_user_fields['ldap_user_provisioned_sid'] = $provisioning_sid;
  //$drupal_user_fields['ldap_user_puid_property'] = current property
  //$drupal_user_fields['ldap_user_puid'] = $user_fields['current property']
 // $drupal_user_fields['ldap_user_dn'] = $user_fields['dn'];
  // @todo populate
  if (!$account = user_save( NULL, $drupal_user_fields)) {
    drupal_set_message(t('User account creation failed because of system problems.'), 'error');
    return FALSE;
  }
  else {
    user_set_authmaps($account, array('authname_ldap_authentication' => $user_fields['name']));
  }

  return $account;
}


/**
 * Form API validate callback for the ldap_user form
 */
function ldap_user_edit_form_validate(&$form, &$form_state) {

}


/**
 * Form API submit callback for the ldap_user form.
 *
 * @todo remove hard-coded link
 */
function ldap_user_edit_form_submit(&$form, &$form_state) {

}


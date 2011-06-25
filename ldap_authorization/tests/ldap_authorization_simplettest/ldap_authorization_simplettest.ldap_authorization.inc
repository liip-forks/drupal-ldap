<?php
/**
 * @file
 * ldap_authorization_simplettest.ldap_authorization.inc
 */

/**
 * Implementation of hook_default_ldap_authorization().
 */
function ldap_authorization_simplettest_default_ldap_authorization() {
  $export = array();

  $ldap_authorization_conf = new stdClass;
  $ldap_authorization_conf->disabled = FALSE; /* Edit this to true to make a default ldap_authorization_conf disabled initially */
  $ldap_authorization_conf->api_version = 1;
  $ldap_authorization_conf->sid = 'uiuc_ad';
  $ldap_authorization_conf->consumer_type = 'drupal_role';
  $ldap_authorization_conf->consumer_module = 'ldap_authorization_drupal_role';
  $ldap_authorization_conf->description = 'UIUC AD';
  $ldap_authorization_conf->status = FALSE;
  $ldap_authorization_conf->only_ldap_authenticated = TRUE;
  $ldap_authorization_conf->derive_from_dn = FALSE;
  $ldap_authorization_conf->derive_from_dn_attr = 'ou';
  $ldap_authorization_conf->derive_from_attr = TRUE;
  $ldap_authorization_conf->derive_from_attr_attr = 'memberOf';
  $ldap_authorization_conf->derive_from_entry = FALSE;
  $ldap_authorization_conf->derive_from_entry_entries = '';
  $ldap_authorization_conf->derive_from_entry_attr = '';
  $ldap_authorization_conf->mappings = 'CN=ED IT NAG Staff,OU=PeopleGroups,OU=Education,DC=ad,DC=uiuc,DC=edu|administrator
CN=Ed Drupal Campus Drupal Install,OU=drupal,OU=Education,DC=ad,DC=uiuc,DC=edu|administrator
Campus Accounts|campus accounts
';
  $ldap_authorization_conf->use_filter = TRUE;
  $ldap_authorization_conf->synch_to_ldap = FALSE;
  $ldap_authorization_conf->synch_on_logon = TRUE;
  $ldap_authorization_conf->revoke_ldap_provisioned = TRUE;
  $ldap_authorization_conf->create_consumers = TRUE;
  $ldap_authorization_conf->regrant_ldap_provisioned = TRUE;
  $export['drupal_role'] = $ldap_authorization_conf;

  return $export;
}

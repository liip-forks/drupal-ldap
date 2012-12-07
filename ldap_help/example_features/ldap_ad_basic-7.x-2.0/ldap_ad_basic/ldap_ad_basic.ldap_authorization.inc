<?php
/**
 * @file
 * ldap_ad_basic.ldap_authorization.inc
 */

/**
 * Implements hook_default_ldap_authorization().
 */
function ldap_ad_basic_default_ldap_authorization() {
  $export = array();

  $consumer_type = new stdClass();
  $consumer_type->disabled = FALSE; /* Edit this to true to make a default consumer_type disabled initially */
  $consumer_type->api_version = 1;
  $consumer_type->sid = 'hogwarts_ad';
  $consumer_type->consumer_type = 'drupal_role';
  $consumer_type->consumer_module = 'ldap_authorization_drupal_role';
  $consumer_type->status = TRUE;
  $consumer_type->only_ldap_authenticated = TRUE;
  $consumer_type->use_first_attr_as_groupid = FALSE;
  $consumer_type->mappings = 'a:4:{i:0;a:6:{s:12:"user_entered";s:8:"students";s:4:"from";s:40:"cn=students,ou=groups,dc=hogwarts,dc=edu";s:10:"normalized";s:8:"students";s:10:"simplified";s:8:"students";s:5:"valid";b:0;s:13:"error_message";s:96:"Role <em class="placeholder">students</em>_name does not exist and role creation is not enabled.";}i:1;a:6:{s:12:"user_entered";s:7:"faculty";s:4:"from";s:39:"cn=faculty,ou=groups,dc=hogwarts,dc=edu";s:10:"normalized";s:7:"faculty";s:10:"simplified";s:7:"faculty";s:5:"valid";b:0;s:13:"error_message";s:95:"Role <em class="placeholder">faculty</em>_name does not exist and role creation is not enabled.";}i:2;a:6:{s:12:"user_entered";s:5:"users";s:4:"from";s:37:"cn=users,ou=groups,dc=hogwarts,dc=edu";s:10:"normalized";s:5:"users";s:10:"simplified";s:5:"users";s:5:"valid";b:0;s:13:"error_message";s:93:"Role <em class="placeholder">users</em>_name does not exist and role creation is not enabled.";}i:3;a:6:{s:12:"user_entered";s:5:"staff";s:4:"from";s:37:"cn=staff,ou=groups,dc=hogwarts,dc=edu";s:10:"normalized";s:5:"staff";s:10:"simplified";s:5:"staff";s:5:"valid";b:0;s:13:"error_message";s:93:"Role <em class="placeholder">staff</em>_name does not exist and role creation is not enabled.";}}';
  $consumer_type->use_filter = TRUE;
  $consumer_type->synch_to_ldap = FALSE;
  $consumer_type->synch_on_logon = TRUE;
  $consumer_type->revoke_ldap_provisioned = TRUE;
  $consumer_type->create_consumers = FALSE;
  $consumer_type->regrant_ldap_provisioned = TRUE;
  $export['drupal_role'] = $consumer_type;

  return $export;
}

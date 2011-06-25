<?php
/**
 * @file
 * ldap_authorization_simplettest.ldap_servers.inc
 */

/**
 * Implementation of hook_default_ldap_servers().
 */
function ldap_authorization_simplettest_default_ldap_servers() {
  $export = array();

  $ldap_servers_conf = new stdClass;
  $ldap_servers_conf->disabled = FALSE; /* Edit this to true to make a default ldap_servers_conf disabled initially */
  $ldap_servers_conf->api_version = 1;
  $ldap_servers_conf->sid = 'uiuc_ad';
  $ldap_servers_conf->name = 'UIUC AD';
  $ldap_servers_conf->status = TRUE;
  $ldap_servers_conf->type = 'Normal';
  $ldap_servers_conf->address = 'ad.uiuc.edu';
  $ldap_servers_conf->port = 389;
  $ldap_servers_conf->tls = FALSE;
  $ldap_servers_conf->bind_method = TRUE;
  $ldap_servers_conf->basedn = 'a:2:{i:0;s:39:"ou=campus accounts,dc=ad,dc=uiuc,dc=edu";i:1;s:33:"ou=education,dc=ad,dc=uiuc,dc=edu";}';
  $ldap_servers_conf->binddn = 'cn=ed-public-ldap,ou=webs,ou=education,dc=ad,dc=uiuc,dc=edu';
  $ldap_servers_conf->user_dn_expression = '';
  $ldap_servers_conf->user_attr = 'sAMAccountName';
  $ldap_servers_conf->mail_attr = 'mail';
  $ldap_servers_conf->ldap_to_drupal_user = '';
  $ldap_servers_conf->testing_drupal_username = 'jbarclay';
  $ldap_servers_conf->weight = 0;
  $export['uiuc_ad'] = $ldap_servers_conf;

  return $export;
}

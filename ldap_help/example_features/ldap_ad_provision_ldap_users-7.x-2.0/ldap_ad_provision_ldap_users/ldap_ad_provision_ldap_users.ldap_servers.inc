<?php
/**
 * @file
 * ldap_ad_provision_ldap_users.ldap_servers.inc
 */

/**
 * Implements hook_default_ldap_servers().
 */
function ldap_ad_provision_ldap_users_default_ldap_servers() {
  $export = array();

  $ldap_servers_conf = new stdClass();
  $ldap_servers_conf->disabled = FALSE; /* Edit this to true to make a default ldap_servers_conf disabled initially */
  $ldap_servers_conf->api_version = 1;
  $ldap_servers_conf->sid = 'hogwarts_ad';
  $ldap_servers_conf->name = 'Hogwarts Active Directory';
  $ldap_servers_conf->status = TRUE;
  $ldap_servers_conf->ldap_type = 'ad';
  $ldap_servers_conf->address = 'ldap.hogwarts.edu';
  $ldap_servers_conf->port = 389;
  $ldap_servers_conf->tls = TRUE;
  $ldap_servers_conf->bind_method = 1;
  $ldap_servers_conf->binddn = 'cn=service-account,ou=people,dc=hogwarts,dc=edu';
  $ldap_servers_conf->bindpw = 'dSbA2cS9zzfoAl3KhEOf';
  $ldap_servers_conf->basedn = 'a:1:{i:0;s:26:"dc=ldap,dc=hogwarts,dc=edu";}';
  $ldap_servers_conf->user_attr = 'samaccountname';
  $ldap_servers_conf->account_name_attr = 'samaccountname';
  $ldap_servers_conf->mail_attr = 'mail';
  $ldap_servers_conf->mail_template = '';
  $ldap_servers_conf->unique_persistent_attr = 'guid';
  $ldap_servers_conf->unique_persistent_attr_binary = FALSE;
  $ldap_servers_conf->user_dn_expression = '';
  $ldap_servers_conf->ldap_to_drupal_user = '';
  $ldap_servers_conf->testing_drupal_username = 'hpotter';
  $ldap_servers_conf->testing_drupal_user_dn = 'cn=hpotter,ou=people,dc=hogwarts,dc=edu';
  $ldap_servers_conf->grp_unused = TRUE;
  $ldap_servers_conf->grp_object_cat = '';
  $ldap_servers_conf->grp_nested = FALSE;
  $ldap_servers_conf->grp_user_memb_attr_exists = FALSE;
  $ldap_servers_conf->grp_user_memb_attr = 'memberof';
  $ldap_servers_conf->grp_memb_attr = '';
  $ldap_servers_conf->grp_memb_attr_match_user_attr = '';
  $ldap_servers_conf->grp_derive_from_dn = FALSE;
  $ldap_servers_conf->grp_derive_from_dn_attr = 'ou';
  $ldap_servers_conf->grp_test_grp_dn = '';
  $ldap_servers_conf->grp_test_grp_dn_writeable = '';
  $ldap_servers_conf->search_pagination = FALSE;
  $ldap_servers_conf->search_page_size = 1000;
  $ldap_servers_conf->weight = 0;
  $export['hogwarts_ad'] = $ldap_servers_conf;

  return $export;
}

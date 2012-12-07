<?php
/**
 * @file
 * ldap_ad_provision_ldap_users.strongarm.inc
 */

/**
 * Implements hook_strongarm().
 */
function ldap_ad_provision_ldap_users_strongarm() {
  $export = array();

  $strongarm = new stdClass();
  $strongarm->disabled = FALSE; /* Edit this to true to make a default strongarm disabled initially */
  $strongarm->api_version = 1;
  $strongarm->name = 'ldap_servers_encryption';
  $strongarm->value = '6';
  $export['ldap_servers_encryption'] = $strongarm;

  $strongarm = new stdClass();
  $strongarm->disabled = FALSE; /* Edit this to true to make a default strongarm disabled initially */
  $strongarm->api_version = 1;
  $strongarm->name = 'ldap_servers_encrypt_key';
  $strongarm->value = '2G4MIGd/9W';
  $export['ldap_servers_encrypt_key'] = $strongarm;

  $strongarm = new stdClass();
  $strongarm->disabled = FALSE; /* Edit this to true to make a default strongarm disabled initially */
  $strongarm->api_version = 1;
  $strongarm->name = 'ldap_servers_require_ssl_for_credentails';
  $strongarm->value = 1;
  $export['ldap_servers_require_ssl_for_credentails'] = $strongarm;

  $strongarm = new stdClass();
  $strongarm->disabled = FALSE; /* Edit this to true to make a default strongarm disabled initially */
  $strongarm->api_version = 1;
  $strongarm->name = 'ldap_user_conf';
  $strongarm->value = array(
    'drupalAcctProvisionServer' => 0,
    'ldapEntryProvisionServer' => 'hogwarts_ad',
    'drupalAcctProvisionTriggers' => array(
      2 => 0,
      1 => 0,
      3 => 0,
    ),
    'ldapEntryProvisionTriggers' => array(
      6 => '6',
      7 => '7',
      8 => '8',
    ),
    'orphanedDrupalAcctBehavior' => 'ldap_user_orphan_do_not_check',
    'orphanedCheckQty' => '',
    'userConflictResolve' => 2,
    'manualAccountConflict' => '1',
    'acctCreation' => 4,
    'ldapUserSynchMappings' => array(
      2 => array(
        '[dn]' => array(
          'ldap_attr' => '[dn]',
          'user_attr' => 'user_tokens',
          'convert' => 0,
          'direction' => '2',
          'user_tokens' => 'cn=[property.name],ou=people,dc=hogwarts,dc=edu',
          'config_module' => 'ldap_user',
          'prov_module' => 'ldap_user',
          'enabled' => 1,
          'prov_events' => array(
            0 => 4,
            1 => 3,
          ),
        ),
        ' [objectclass:0]' => array(
          'ldap_attr' => ' [objectclass:0]',
          'user_attr' => 'user_tokens',
          'convert' => 0,
          'direction' => '2',
          'user_tokens' => 'top',
          'config_module' => 'ldap_user',
          'prov_module' => 'ldap_user',
          'enabled' => 1,
          'prov_events' => array(
            0 => 4,
            1 => 3,
          ),
        ),
        '[objectclass:1]' => array(
          'ldap_attr' => '[objectclass:1]',
          'user_attr' => 'user_tokens',
          'convert' => 0,
          'direction' => '2',
          'user_tokens' => 'person',
          'config_module' => 'ldap_user',
          'prov_module' => 'ldap_user',
          'enabled' => 1,
          'prov_events' => array(
            0 => 4,
            1 => 3,
          ),
        ),
        '[objectclass:2]' => array(
          'ldap_attr' => '[objectclass:2]',
          'user_attr' => 'user_tokens',
          'convert' => 0,
          'direction' => '2',
          'user_tokens' => 'organizationalPerson',
          'config_module' => 'ldap_user',
          'prov_module' => 'ldap_user',
          'enabled' => 1,
          'prov_events' => array(
            0 => 4,
            1 => 3,
          ),
        ),
        '[objectclass:3]' => array(
          'ldap_attr' => '[objectclass:3]',
          'user_attr' => 'user_tokens',
          'convert' => 0,
          'direction' => '2',
          'user_tokens' => 'user',
          'config_module' => 'ldap_user',
          'prov_module' => 'ldap_user',
          'enabled' => 1,
          'prov_events' => array(
            0 => 4,
            1 => 3,
          ),
        ),
        '[sn]' => array(
          'ldap_attr' => '[sn]',
          'user_attr' => '[field.field_last_name]',
          'convert' => 0,
          'direction' => '2',
          'user_tokens' => '',
          'config_module' => 'ldap_user',
          'prov_module' => 'ldap_user',
          'enabled' => 1,
          'prov_events' => array(
            0 => 4,
            1 => 3,
          ),
        ),
        '[givenName]' => array(
          'ldap_attr' => '[givenName]',
          'user_attr' => 'user_tokens',
          'convert' => 0,
          'direction' => '2',
          'user_tokens' => '[property.name]',
          'config_module' => 'ldap_user',
          'prov_module' => 'ldap_user',
          'enabled' => 1,
          'prov_events' => array(
            0 => 4,
            1 => 3,
          ),
        ),
        '[mail]' => array(
          'ldap_attr' => '[mail]',
          'user_attr' => '[property.mail]',
          'convert' => 0,
          'direction' => '2',
          'user_tokens' => '',
          'config_module' => 'ldap_user',
          'prov_module' => 'ldap_user',
          'enabled' => 1,
          'prov_events' => array(
            0 => 4,
            1 => 3,
          ),
        ),
        '[password]' => array(
          'ldap_attr' => '[password]',
          'user_attr' => '[password.user-random]',
          'convert' => 0,
          'direction' => '2',
          'user_tokens' => '',
          'config_module' => 'ldap_user',
          'prov_module' => 'ldap_user',
          'enabled' => 1,
          'prov_events' => array(
            0 => 4,
            1 => 3,
          ),
        ),
        '[sAMAccountName]' => array(
          'ldap_attr' => '[sAMAccountName]',
          'user_attr' => '[property.name]',
          'convert' => 0,
          'direction' => '2',
          'user_tokens' => 'from drupal',
          'config_module' => 'ldap_user',
          'prov_module' => 'ldap_user',
          'enabled' => 1,
          'prov_events' => array(
            0 => 4,
            1 => 3,
          ),
        ),
      ),
    ),
  );
  $export['ldap_user_conf'] = $strongarm;

  $strongarm = new stdClass();
  $strongarm->disabled = FALSE; /* Edit this to true to make a default strongarm disabled initially */
  $strongarm->api_version = 1;
  $strongarm->name = 'ldap_user_cron_last_orphan_checked';
  $strongarm->value = 0;
  $export['ldap_user_cron_last_orphan_checked'] = $strongarm;

  $strongarm = new stdClass();
  $strongarm->disabled = FALSE; /* Edit this to true to make a default strongarm disabled initially */
  $strongarm->api_version = 1;
  $strongarm->name = 'ldap_user_cron_last_uid_checked';
  $strongarm->value = 1;
  $export['ldap_user_cron_last_uid_checked'] = $strongarm;

  return $export;
}

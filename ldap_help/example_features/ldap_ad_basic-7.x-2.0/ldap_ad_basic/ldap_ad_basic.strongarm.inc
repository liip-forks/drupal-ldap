<?php
/**
 * @file
 * ldap_ad_basic.strongarm.inc
 */

/**
 * Implements hook_strongarm().
 */
function ldap_ad_basic_strongarm() {
  $export = array();

  $strongarm = new stdClass();
  $strongarm->disabled = FALSE; /* Edit this to true to make a default strongarm disabled initially */
  $strongarm->api_version = 1;
  $strongarm->name = 'ldap_authentication_conf';
  $strongarm->value = array(
    'sids' => array(
      'hogwarts_ad' => 'hogwarts_ad',
    ),
    'authenticationMode' => 1,
    'loginUIUsernameTxt' => 'Hogwarts Network ID',
    'loginUIPasswordTxt' => 'Hogwarts Network ID Password',
    'ldapUserHelpLinkUrl' => 'https://passwords.hogwarts.edu/help',
    'ldapUserHelpLinkText' => 'Logon Help',
    'emailOption' => 3,
    'emailUpdate' => 1,
    'allowOnlyIfTextInDn' => array(),
    'excludeIfTextInDn' => array(
      0 => 'ou=banned',
    ),
    'allowTestPhp' => '',
    'excludeIfNoAuthorizations' => NULL,
    'ssoRemoteUserStripDomainName' => NULL,
    'ssoExcludedPaths' => array(),
    'seamlessLogin' => NULL,
    'ldapImplementation' => 'mod_auth_sspi',
    'cookieExpire' => NULL,
  );
  $export['ldap_authentication_conf'] = $strongarm;

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
    'drupalAcctProvisionServer' => 'hogwarts_ad',
    'ldapEntryProvisionServer' => 0,
    'drupalAcctProvisionTriggers' => array(
      2 => '2',
      1 => 0,
      3 => 0,
    ),
    'ldapEntryProvisionTriggers' => array(
      6 => '6',
      7 => 0,
      8 => 0,
    ),
    'orphanedDrupalAcctBehavior' => 'user_cancel_block',
    'orphanedCheckQty' => '100',
    'userConflictResolve' => 2,
    'manualAccountConflict' => '2',
    'acctCreation' => 4,
    'ldapUserSynchMappings' => array(
      1 => array(
        '[field.field_last_name]' => array(
          'ldap_attr' => '[sn]',
          'user_attr' => '[field.field_last_name]',
          'convert' => 0,
          'direction' => '1',
          'user_tokens' => NULL,
          'config_module' => 'ldap_user',
          'prov_module' => 'ldap_user',
          'enabled' => 1,
          'prov_events' => array(
            0 => 2,
            1 => 1,
          ),
        ),
        '[field.field_first_name]' => array(
          'ldap_attr' => '[givenname]',
          'user_attr' => '[field.field_first_name]',
          'convert' => 0,
          'direction' => '1',
          'user_tokens' => NULL,
          'config_module' => 'ldap_user',
          'prov_module' => 'ldap_user',
          'enabled' => 1,
          'prov_events' => array(
            0 => 2,
            1 => 1,
          ),
        ),
      ),
      2 => array(
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

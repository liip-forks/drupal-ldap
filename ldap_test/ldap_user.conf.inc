<?php
// $Id$

/**
 * @file
 * simpltest ldap user config
 *
 * should only contain saveable properties of ldapUserConf class.
 */

function ldap_test_ldap_user_data() {

  $conf['default']  = array(
      'sids' => array(
        'activedirectory' => 'activedirectory',
      ),
      'provisionTargetServers' => array(
        'activedirectory' => 0
      ),
      'drupalAcctProvisionEvents' => array(
        LDAP_USER_PROV_ON_LOGON => 1,
        LDAP_USER_PROV_ON_MANUAL_ACCT_CREATE  => 1,
        LDAP_USER_PROV_ON_ALL_USER_CREATION  => 1,
        LDAP_USER_PROV_CANCEL_DRUPAL_USER_ON_LDAP_ENTRY_MISSING  => 1,
        LDAP_USER_PROV_DELETE_DRUPAL_USER_ON_LDAP_ENTRY_MISSING  => 1,
        ),
      'ldapEntryProvisionEvents' => array(
        LDAP_USER_LDAP_ENTRY_CREATION_ON_CREATE  => 1,
        LDAP_USER_LDAP_ENTRY_DELETION_ON_DELETE => 1,
        ),
      'userConflictResolve' => LDAP_USER_CONFLICT_LOG,
      'acctCreation'  => LDAP_USER_ACCT_CREATION_LDAP_BEHAVIOR,
      'wsEnabled'  => 0,
      'wsActions'  => array('create' => 0,'synch' => 0,'disable' => 0,'delete' => 0),
      'wsUserIps' => array('1.2.3.4'),
      'wsKey'  => 'sdafjlksjadsdafjkksdafjsdfsdjfsdf',
      'ldapUserSynchMappings' => array(
        'activedirectory' => array(),
      )
    );

  return $conf;

}

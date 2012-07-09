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
    'drupalAcctProvisionServers' => array(
      'activedirectory' => 'activedirectory',
    ),
    'ldapEntryProvisionServers' => array(
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

  $conf['provision1']  = array(
    'drupalAcctProvisionServers' => array(
      'activedirectory' => 0,
    ),
    'ldapEntryProvisionServers' => array(
      'activedirectory' =>  'activedirectory'
    ),
    'drupalAcctProvisionEvents' => array(),
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
      'activedirectory' => array(
          '[dn]' => array(
            'sid' => 'activedirectory',
            'ldap_attr' => '[dn]',
            'user_attr' => 'user_tokens',
            'convert' => 0,
            'direction' => LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY,
            'user_tokens' => 'cn=[property.name],ou=people,dc=ldap,dc=mycompany,dc=com',
            'config_module' => 'ldap_user',
            'synch_module' => 'ldap_user',
            'enabled' => 1,
            'contexts' => array(0 => LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, 1 => LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER),
          ),
          '[cn]' => array (
            'sid' =>  'activedirectory',
            'ldap_attr' => '[cn]',
            'user_attr' => '[property.name]',
            'convert' => 0,
            'direction' => LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY,
            'user_tokens' => '',
            'config_module' => 'ldap_user',
            'synch_module' => 'ldap_user',
            'enabled' => 1,
            'contexts' => array(0 => LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, 1 => LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER),
          ),
          '[displayName]' => array(
            'sid' => 'activedirectory',
            'ldap_attr' => '[displayName]',
            'user_attr' => 'user_tokens',
            'convert' => 0,
            'direction' => LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY,
            'user_tokens' => '[field.field_fname] [field.field_lname]',
            'config_module' => 'ldap_user',
            'synch_module' => 'ldap_user',
            'enabled' => 1,
            'contexts' => array(0 => LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, 1 => LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER),
          ),
          '[guid]' => array(
            'sid' => 'activedirectory',
            'ldap_attr' => '[guid]',
            'user_attr' => 'user_tokens',
            'convert' => 0,
            'direction' => LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY,
            'user_tokens' => 'drupal.mycompany.com.uid.[property.uid]',
            'config_module' => 'ldap_user',
            'synch_module' => 'ldap_user',
            'enabled' => 1,
            'contexts' => array('0' => LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER),
          ),
          '[provisionSource]' => array(
            'sid' => 'activedirectory',
            'ldap_attr' => '[provisionSource]',
            'user_attr' => 'user_tokens',
            'convert' => 0,
            'direction' => LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY,
            'user_tokens' => 'drupal.mycompany.com',
            'config_module' => 'ldap_user',
            'synch_module' => 'ldap_user',
            'enabled' => 1,
            'contexts' =>  array('0' => LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER),
          ),
        )
      )
    );

  return $conf;

}

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
      LDAP_USER_DRUPAL_USER_CREATE_ON_LOGON,
      LDAP_USER_DRUPAL_USER_CREATE_ON_MANUAL_ACCT_CREATE,
      LDAP_USER_DRUPAL_USER_CREATE_ON_ALL_USER_CREATION,
      LDAP_USER_DRUPAL_USER_CANCEL_ON_LDAP_ENTRY_MISSING,
      LDAP_USER_DRUPAL_USER_DELETE_ON_LDAP_ENTRY_MISSING,
      ),
    'ldapEntryProvisionEvents' => array(
      LDAP_USER_LDAP_ENTRY_CREATE_ON_USER_CREATE,
      LDAP_USER_LDAP_ENTRY_DELETE_ON_USER_DELETE,
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
      LDAP_USER_LDAP_ENTRY_CREATE_ON_USER_CREATE,
      LDAP_USER_LDAP_ENTRY_UPDATE_ON_USER_UPDATE,
      LDAP_USER_LDAP_ENTRY_DELETE_ON_USER_DELETE,
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
            'user_tokens' => 'cn=[property.name],ou=people,dc=activedirectory,dc=ldap,dc=pixotech,dc=com',
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
          '[sAMAccountName]' => array(
            'sid' => 'activedirectory',
            'ldap_attr' => '[sAMAccountName]',
            'user_attr' => '[property.name]',
            'convert' => 0,
            'direction' => LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY,
            'user_tokens' => '',
            'config_module' => 'ldap_user',
            'synch_module' => 'ldap_user',
            'enabled' => 1,
            'contexts' => array(0 => LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, 1 => LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER),
          ),
          '[sn]' => array(
            'sid' => 'activedirectory',
            'ldap_attr' => '[sn]',
            'user_attr' => '[field.field_lname]',
            'convert' => 0,
            'direction' => LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY,
            'user_tokens' => '',
            'config_module' => 'ldap_user',
            'synch_module' => 'ldap_user',
            'enabled' => 1,
            'contexts' => array(0 => LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, 1 => LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER),
          ),
          '[givenName]' => array(
            'sid' => 'activedirectory',
            'ldap_attr' => '[givenName]',
            'user_attr' => '[field.field_fname]',
            'convert' => 0,
            'direction' => LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY,
            'user_tokens' => '',
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
            'user_tokens' => 'ldap.pixotech.com.uid.[property.uid]',
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
            'user_tokens' => 'drupal.pixotech.com',
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

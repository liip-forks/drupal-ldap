<?php
// $Id$

/**
 * @file
 * simpltest authorization config
 */

function ldap_test_ldap_user_data() {

  $conf['default']  = array(
      'sids' => array(
        'activedirectory' => 1,
      ),
      'provisionMethods' => array(
        LDAP_USER_PROV_ON_LOGON,
        LDAP_USER_PROV_ON_MANUAL_ACCT_CREATE,
        LDAP_USER_PROV_ON_ALL_USER_CREATION,
        ),
      'userConflictResolve'  => LDAP_USER_CONFLICT_LOG,
      'acctCreation'  => LDAP_USER_ACCT_CREATION_LDAP_BEHAVIOR,
      'wsEnabled'  => 1,
      'wsActions'  => array('create','synch','disable','delete'),
      'wsUserIps' => array(),
      'wsKey'  => 'sdafjlksjadsdafjkksdafjsdfsdjfsdf',
      'synchMapping' => array(),
    );

  return $conf;

}

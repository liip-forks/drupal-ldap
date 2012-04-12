<?php
// $Id$

/**
 * @file
 * simpltest authorization config
 */

$sid = 'activedirectory';

$conf['ldap_servers'][$sid]['conf']  = array(
    'name'  => 'Test LDAP Server for LDAP Authorization',
    'inDatabase' => TRUE,
    'status'  => 1,
    'ldap_type'  => 'ad',
    'address'  => 'ad.myuniversity.edu',
    'port'  => 389,
    'tls'  => FALSE,
    'bind_method' => LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT,
    'basedn'  => array(
       'ou=campus accounts,dc=ad,dc=myuniversity,dc=edu',
       'ou=education,dc=ad,dc=myuniversity,dc=edu',
       'ou=guest accounts,dc=ad,dc=myuniversity,dc=edu',
      ),
    'binddn'  => 'cn=service-account,dc=ad,dc=myuniversity,dc=edu',
    'bindpw' => 'goodpwd',
    'user_dn_expression' => 'user_dn_expression',
    'user_attr'  => 'sAMAccountName',
    'mail_attr'  => 'mail',
    'ldapToDrupalUserPhp'  => NULL,
    'testingDrupalUsername'  => 'jdoe',
    'groupObjectClass' => 'group',
  );

$conf['ldap_user']  = array(
    'provisionServers'  => array($sid),
    'provisionMethods' => array(
      LDAP_USER_PROV_ON_LOGON,
      LDAP_USER_PROV_ON_MANUAL_ACCT_CREATE,
      LDAP_USER_PROV_ON_ALL_USER_CREATION,
      ),
    'loginConflictResolve'  => LDAP_USER_CONFLICT_LOG,
    'acctCreation'  => LDAP_USER_ACCT_CREATION_LDAP_BEHAVIOR,
    'wsEnabled'  => 1,
    'wsSid'  => array($sid),
    'wsActions'  => array('create','synch','disable','delete'),
    'wsUserIps' => array(),
    'wsURLs'  => array(
       'ou=campus accounts,dc=ad,dc=myuniversity,dc=edu',
       'ou=education,dc=ad,dc=myuniversity,dc=edu',
       'ou=guest accounts,dc=ad,dc=myuniversity,dc=edu',
      ),
    'wsKey'  => 'sdafjlksjadsdafjkksdafjsdfsdjfsdf',
  );



$conf['ldap_servers'][$sid]['methodResponses']['connect'] = LDAP_SUCCESS;

$conf['ldap_servers'][$sid]['search_results']['(member=cn=verykool,ou=special guests,ou=guest accounts,dc=ad,dc=myuniversity,dc=edu)']['ou=groups,dc=ad,dc=myuniversity,dc=edu'] = array(
    0 => array('count' => 1, 'dn' => 'cn=content editors,ou=groups,dc=ad,dc=myuniversity,dc=edu'),
    1 => array('count' => 1, 'dn' => 'cn=content approvers,ou=groups,dc=ad,dc=myuniversity,dc=edu'),
    'count' => 2,
  );

$conf['ldap_servers'][$sid]['search_results']['(member=cn=jkool,ou=guest accounts,dc=ad,dc=myuniversity,dc=edu)']['ou=groups,dc=ad,dc=myuniversity,dc=edu'] = array(
    0 => array('count' => 1, 'dn' => 'cn=content editors,ou=groups,dc=ad,dc=myuniversity,dc=edu'),
    'count' => 1,
  );

<?php

/*** activedirectory is default Active Directory server config **/
function ldap_test_ldap_servers_data() {

$conf['activedirectory']['properties'] = array(
    'name'  => 'Default LDAP Server for Active Directory',
    'inDatabase' => TRUE,
    'status'  => 1,
    'ldap_type'  => 'ad',
    'address'  => 'activedirectory.ldap.pixotech.com',
    'port'  => 389,
    'tls'  => FALSE,
    'bind_method' => LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT,
    'basedn'  => array(
       'DC=activedirectory,DC=ldap,DC=pixotech,DC=com',
      ),
    'binddn' => 'cn=service-account,DC=activedirectory,DC=ldap,DC=pixotech,DC=com',
    'bindpw' => 'goodpwd',
    'user_dn_expression' => NULL,
    'user_attr'  => 'sAMAccountName',
    'mail_attr'  => 'mail',
    'mail_template'  => NULL,
    'unique_persistent_attr' => 'pixotechcomregistryuniqueid',
    'ldap_to_drupal_user' => FALSE,
    'ldapToDrupalUserPhp'  => NULL,
    'testing_drupal_username'  => 'jdoe',
    'group_object_category' => 'group',
    'search_pagination' => 0,
    'searchPageSize' => NULL,
  );


$conf['activedirectory']['methodResponses']['connect'] = LDAP_SUCCESS;


$conf['activedirectory']['search_results']['(member=cn=verykool,ou=special guests,ou=guest accounts,dc=ad,dc=myuniversity,dc=edu)']['ou=groups,dc=ad,dc=myuniversity,dc=edu'] = array(
    0 => array('count' => 1, 'dn' => 'cn=content editors,ou=groups,dc=ad,dc=myuniversity,dc=edu'),
    1 => array('count' => 1, 'dn' => 'cn=content approvers,ou=groups,dc=ad,dc=myuniversity,dc=edu'),
    'count' => 2,
  );

$conf['activedirectory']['search_results']['(member=cn=jkool,ou=guest accounts,dc=ad,dc=myuniversity,dc=edu)']['ou=groups,dc=ad,dc=myuniversity,dc=edu'] = array(
    0 => array('count' => 1, 'dn' => 'cn=content editors,ou=groups,dc=ad,dc=myuniversity,dc=edu'),
    'count' => 1,
  );



$conf['ldapauthen1']['properties']  = array(

    'sid' => 'ldapauthen1',
    'name'  => 'Test LDAP Server 1 for LDAP Authentication' ,
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
    'user_dn_expression' => NULL,
    'user_attr'  => 'sAMAccountName',
    'mail_attr'  => 'mail',
    'mail_template'  => NULL,
    'ldap_to_drupal_user' => FALSE,
    'ldapToDrupalUserPhp'  => NULL,
    'testingDrupalUsername'  => 'jdoe',
    'groupObjectClass' => 'group',
    'search_pagination' => 0,
    'searchPageSize' => NULL,

  );


$conf['ldapauthen1']['methodResponses']['connect'] = LDAP_SUCCESS;

$conf['ldapauthen1']['search_results']['member=cn=verykool,ou=special guests,ou=guest accounts,dc=ad,dc=myuniversity,dc=edu']['ou=groups,dc=ad,dc=myuniversity,dc=edu'] = array(
    0 => array('count' => 1, 'dn' => 'cn=content editors,ou=groups,dc=ad,dc=myuniversity,dc=edu'),
    1 => array('count' => 1, 'dn' => 'cn=content approvers,ou=groups,dc=ad,dc=myuniversity,dc=edu'),
    'count' => 2,
  );

$conf['ldapauthen1']['search_results']['member=cn=jkool,ou=guest accounts,dc=ad,dc=myuniversity,dc=edu']['ou=groups,dc=ad,dc=myuniversity,dc=edu'] = array(
    0 => array('count' => 1, 'dn' => 'cn=content editors,ou=groups,dc=ad,dc=myuniversity,dc=edu'),
    'count' => 1,
  );

return $conf;
}

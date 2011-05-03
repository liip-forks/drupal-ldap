<?php
// $Id: LdapServerTest.class.inc,v 1.4.2.1 2011/02/08 06:01:00 johnbarclay Exp $

/**
 * @file
 * test configurations for LdapServerTest.class.php
 */

$test_data = array();

$test_data['properties']  = array(

    'sid' => 'ldapauthor1',
    'name'  => 'Test LDAP Server 1 for LDAP Authorization' ,
    'inDatabase' => TRUE,
    'status'  => 1,
    'type'  => 'ad',
    'address'  => 'ad.myuniveristy.edu',
    'port'  => 389,
    'tls'  => FALSE,
    'bind_method' => LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT,
    'basedn'  => array("ou=campus accounts,dc=ad,dc=myuniveristy,dc=edu",
       "ou=education,dc=ad,dc=myuniveristy,dc=edu"),
    'binddn'  => "cn=service-account,dc=ad,dc=myuniveristy,dc=edu",
    'bindpw' => 'goodpwd',
    'user_dn_expression' => 'user_dn_expression',
    'user_attr'  => 'sAMAccountName',
    'mail_attr'  => 'mail',
    'ldapToDrupalUserPhp'  => NULL,
    'testingDrupalUsername'  => 'jdoe'

  );

/** method responses are array of where keys are:
 * <method_name>
 * parameter1,
 * parameter2,
 * ...
 *
 * and value is the response fake ldap server is expected to return
 */

$test_data['methodResponses']['connect'] = LDAP_SUCCESS;


/** fake user data array below 'attr' should mimick ldap user result data
 */
$test_data['users']['cn=jdoe,ou=campus accounts,dc=ad,dc=myuniveristy,dc=edu']['attr'] = array(
    'mail' => array( 0 => 'jdoe@myuniversity.edu', 'count' => 1),
    'sAMAccountName' => array( 0 => 'jdoe', 'count' => 1),
    'password' => array( 0 => 'goodpwd', 'count' => 1),
  );

// test users include service account
$test_data['users']['cn=service-account,dc=ad,dc=myuniveristy,dc=edu']['attr'] = array(
    'mail' => array( 0 => 'service-account@myuniversity.edu', 'count' => 1),
    'sAMAccountName' => array( 0 =>'service-account', 'count' => 1),
    'memberOf' => array(
      0 => 'CN=service_accounts,OU=ServiceAccountGroups,DC=ad,DC=myuniveristy,DC=edu',
      'count' => 2,
      ),
    'password' => array( 0 => 'goodpwd', 'count' => 1),
  );

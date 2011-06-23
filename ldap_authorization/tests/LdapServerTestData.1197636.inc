<?php
// $Id: LdapServerTest.class.inc,v 1.4.2.1 2011/02/08 06:01:00 johnbarclay Exp $

/**
 * @file
 * test configurations for LdapServerTest.class.php
 * file name should be of form LdapServerTestData.<sid>.inc
 * where sid is the server id data is used for.
 *
 */

$test_data = array();

/**
 * $test_data['properties'] are all the initial properties of the instantiated LdapServerTest object
 */

$test_data['servers']['1197636']['properties']  = array(

    'sid' => '1197636',
    'name'  => 'Test LDAP Server 1 for LDAP Authorization' ,
    'inDatabase' => TRUE,
    'status'  => 1,
    'type'  => 'ad',
    'address'  => 'ad.myuniveristy.edu',
    'port'  => 389,
    'tls'  => FALSE,
    'bind_method' => LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT,
    'basedn'  => array(
       'ou=campus accounts,dc=ad,dc=myuniveristy,dc=edu',
       'ou=education,dc=ad,dc=myuniveristy,dc=edu',
       'ou=guest accounts,dc=ad,dc=myuniveristy,dc=edu',
      ),
    'binddn'  => 'cn=service-account,dc=ad,dc=myuniveristy,dc=edu',
    'bindpw' => 'goodpwd',
    'user_dn_expression' => 'user_dn_expression',
    'user_attr'  => 'sAMAccountName',
    'mail_attr'  => 'mail',
    'ldapToDrupalUserPhp'  => NULL,
    'testingDrupalUsername'  => 'jdoe'

  );



$test_data['ldap_authentication'] = array(
    'sids' => array('1197636' => '1197636'),
    'authenticationMode' => LDAP_AUTHENTICATION_EXCLUSIVE,
    'loginConflictResolve' => LDAP_AUTHENTICATION_CONFLICT_LOG,
    'acctCreation' => LDAP_AUTHENTICATION_ACCT_CREATION_LDAP_BEHAVIOR,
    'ldapUserHelpLinkUrl' => '',
    'ldapUserHelpLinkText' => '',
    'emailOption' => LDAP_AUTHENTICATION_EMAIL_FIELD_DISABLE,
    'emailUpdate' => LDAP_AUTHENTICATION_EMAIL_UPDATE_ON_LDAP_CHANGE_DISABLE,
    'allowOnlyIfTextInDn' => NULL,
    'excludeIfTextInDn' => NULL,
    'allowTestPhp' => NULL,
  );


/**
 *
 * method responses are stored in array $test_data['methodResponses']
 * where keys are:
 * <method_name>
 * parameter1,
 * parameter2,
 * ...
 *
 * and value is the response test ldap server is expected to return. values
 * can be scalar, array, object, etc, depending on what the method being mimicked
 * is expected to return
 */

$test_data['servers']['1197636']['methodResponses']['connect'] = LDAP_SUCCESS;

$test_data['servers']['1197636']['search_results']['member=cn=verykool,ou=special guests,ou=guest accounts,dc=ad,dc=myuniveristy,dc=edu']['ou=groups,dc=ad,dc=myuniveristy,dc=edu'] = array(
    0 => array('count' => 1, 'dn' => 'ou=content editors,ou=groups,dc=ad,dc=myuniveristy,dc=edu'),
    1 => array('count' => 1, 'dn' => 'ou=content approvers,ou=groups,dc=ad,dc=myuniveristy,dc=edu'),
    'count' => 2,
  );

$test_data['servers']['1197636']['users']['cn=verykool,ou=special guests,ou=guest accounts,dc=ad,dc=myuniveristy,dc=edu']['attr'] = array(
    'dn' => 'cn=verykool,ou=special guests,ou=guest accounts,dc=ad,dc=myuniveristy,dc=edu',
    'mail' => array( 0 => 'verykool@myuniversity.edu', 'count' => 1),
    'sAMAccountName' => array( 0 => 'verykool', 'count' => 1),
    'password' => array( 0 => 'goodpwd', 'count' => 1),
    'memberOf' => array(
      0 => 'cn=netadmins,ou=it,dc=ad,dc=myuniveristy,dc=edu',
      'count' => 1,
      ),
  );

/**
 * test users should include service account if one is being used
 */
$test_data['servers']['1197636']['users']['cn=service-account,dc=ad,dc=myuniveristy,dc=edu']['attr'] = array(
    'dn' => 'cn=service-account,dc=ad,dc=myuniveristy,dc=edu',
    'mail' => array( 0 => 'service-account@myuniversity.edu', 'count' => 1),
    'sAMAccountName' => array( 0 => 'service-account', 'count' => 1),
    'memberOf' => array(
      0 => 'CN=service_accounts,OU=ServiceAccountGroups,DC=ad,DC=myuniveristy,DC=edu',
      'count' => 1,
      ),
    'password' => array( 0 => 'goodpwd', 'count' => 1),
  );


/**
 * ldap authorization
 */

  $test_data['ldap_authorization_conf']['consumer_conf']['sid'] = '1197636';

  $test_data['ldap_authorization_conf']['consumer_conf']['consumerType'] = 'drupal_role';  // change as desired
  $test_data['ldap_authorization_conf']['consumer_conf']['consumerModule'] = 'ldap_authorization_drupal_role';

  $test_data['ldap_authorization_conf']['consumer_conf']['description'] = 'UIUC AD';
  $test_data['ldap_authorization_conf']['consumer_conf']['status'] = 1;
  $test_data['ldap_authorization_conf']['consumer_conf']['onlyApplyToLdapAuthenticated'] = 1;

  $test_data['ldap_authorization_conf']['consumer_conf']['deriveFromDn'] = 0;
  $test_data['ldap_authorization_conf']['consumer_conf']['deriveFromDnAttr'] = NULL;

  $test_data['ldap_authorization_conf']['consumer_conf']['deriveFromAttr'] = 1;
  $test_data['ldap_authorization_conf']['consumer_conf']['deriveFromAttrAttr'] = array('memberOf');

  $test_data['ldap_authorization_conf']['consumer_conf']['deriveFromEntry'] = 0;
  $test_data['ldap_authorization_conf']['consumer_conf']['deriveFromEntryEntries'] = NULL;
  $test_data['ldap_authorization_conf']['consumer_conf']['deriveFromEntryAttr'] = NULL;

  $test_data['ldap_authorization_conf']['consumer_conf']['mappings'][] = array('cn=netadmins,ou=it,dc=ad,dc=myuniveristy,dc=edu', 'netadmins');

  $test_data['ldap_authorization_conf']['consumer_conf']['useMappingsAsFilter'] = 1;

  $test_data['ldap_authorization_conf']['consumer_conf']['synchOnLogon'] = 1;
  $test_data['ldap_authorization_conf']['consumer_conf']['synchManually'] = 0;

  $test_data['ldap_authorization_conf']['consumer_conf']['revokeLdapProvisioned'] = 1;
  $test_data['ldap_authorization_conf']['consumer_conf']['createConsumers'] = 1;
  $test_data['ldap_authorization_conf']['consumer_conf']['regrantLdapProvisioned'] = 1;

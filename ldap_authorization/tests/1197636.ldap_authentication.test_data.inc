<?php
// $Id$
/**
 * @file
 * ldap_authentication test data
 */

$variables['user_register'] = USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL;
$variables['user_email_verification'] = 1;

$authentication = array(
  'authenticationMode' => LDAP_AUTHENTICATION_EXCLUSIVE,
  'ldapUserHelpLinkUrl' => '',
  'ldapUserHelpLinkText' => '',
  'emailOption' => LDAP_AUTHENTICATION_EMAIL_FIELD_DISABLE,
  'emailUpdate' => LDAP_AUTHENTICATION_EMAIL_UPDATE_ON_LDAP_CHANGE_DISABLE,
  'allowOnlyIfTextInDn' => NULL,
  'excludeIfTextInDn' => NULL,
  'allowTestPhp' => NULL,
);

$test_data['ldap_user']['WL1'] = array(
    'sids' => array('ldapauthen1' => 'ldapauthen1'),
  'loginConflictResolve' => LDAP_USER_CONFLICT_LOG,
  'acctCreation' => LDAP_USER_ACCT_CREATION_LDAP_BEHAVIOR,
  );

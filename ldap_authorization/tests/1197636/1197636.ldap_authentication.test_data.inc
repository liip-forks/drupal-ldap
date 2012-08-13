<?php
// $Id$
/**
 * @file
 * ldap_authentication test data
 */

$variables['user_register'] = USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL;
$variables['user_email_verification'] = 1;

$authentication = array(
  'sids' => array('ldapauthor1' => 'ldapauthor1'),
  'authenticationMode' => LDAP_AUTHENTICATION_EXCLUSIVE,
  'loginConflictResolve' => LDAP_AUTHENTICATION_CONFLICT_LOG,
  'ldapUserHelpLinkUrl' => '',
  'ldapUserHelpLinkText' => '',
  'emailOption' => LDAP_AUTHENTICATION_EMAIL_FIELD_DISABLE,
  'emailUpdate' => LDAP_AUTHENTICATION_EMAIL_UPDATE_ON_LDAP_CHANGE_DISABLE,
  'allowOnlyIfTextInDn' => NULL,
  'excludeIfTextInDn' => NULL,
  'allowTestPhp' => NULL,
);

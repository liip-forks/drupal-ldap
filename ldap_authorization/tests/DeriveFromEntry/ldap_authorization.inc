<?php
// $Id$

/**
 * @file
 * simpletest authorization configuration
 */

$authorization['drupal_role'] = array(
  'consumerModule' => 'ldap_authorization_drupal_role',

  'description' => 'UIUC AD',
  'status' => 1,
  'onlyApplyToLdapAuthenticated' => 1,

  'deriveFromDn' => 0,
  'deriveFromDnAttr' => NULL,
  'useFirstAttrAsGroupId' => 0,

  'mappings' => array(
    array('cn=it,cn=groups,dc=ad,dc=myuniversity,dc=edu', 'it'),
    array('cn=sysadmins,cn=groups,dc=ad,dc=myuniversity,dc=edu', 'sysadmins'),
    array('cn=developers,cn=groups,dc=ad,dc=myuniversity,dc=edu', 'developers'),
    array('cn=staff,cn=groups,dc=ad,dc=myuniversity,dc=edu', 'staff'),
    array('cn=students,cn=groups,dc=ad,dc=myuniversity,dc=edu', 'students'),
    array('cn=people,cn=groups,dc=ad,dc=myuniversity,dc=edu', 'people'),
  ),

  'useMappingsAsFilter' => 1,

  'synchOnLogon' => 1,
  'synchManually' => 1,

  'revokeLdapProvisioned' => 1,
  'createConsumers' => 1,
  'regrantLdapProvisioned' => 1,
);

<?php
// $Id$

/**
 * @file
 * simpletest authorization configuration
 */


$authorization['drupal_role'] = array(

  'consumerType' => 'drupal_role',  // change as desired
  'consumerModule' => 'ldap_authorization_drupal_role',

  'description' => 'UIUC AD',
  'status' =>  1,
  'onlyApplyToLdapAuthenticated' => 1,

  'deriveFromDn' => 0,
  'deriveFromDnAttr' => NULL,

  'useFirstAttrAsGroupId' => 0,

  'mappings' => array(
    0 => array('cn=it,cn=groups,dc=ad,dc=myuniversity,dc=edu', 'it'),
    1 => array('cn=sysadmins,cn=groups,dc=ad,dc=myuniversity,dc=edu', 'sysadmins'),
    2 => array('cn=developers,cn=groups,dc=ad,dc=myuniversity,dc=edu', 'developers'),
    3 => array('cn=staff,cn=groups,dc=ad,dc=myuniversity,dc=edu', 'staff'),
    4 => array('cn=students,cn=groups,dc=ad,dc=myuniversity,dc=edu', 'students'),
    5 => array('cn=people,cn=groups,dc=ad,dc=myuniversity,dc=edu', 'people'),
  ),

  'useMappingsAsFilter' => 1,

  'synchOnLogon' => 1,
  'synchManually' => 1,

  'revokeLdapProvisioned' => 1,
  'createConsumers' => 1,
  'regrantLdapProvisioned' => 1,

);

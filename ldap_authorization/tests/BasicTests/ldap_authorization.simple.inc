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

  'deriveFromAttr' => 0,
  'deriveFromAttrAttr' => array(),
  'deriveFromAttrNested' => 0,

  'deriveFromEntry' => 1,
  'deriveFromEntryEntries' => array('ou=groups,dc=ad,dc=myuniversity,dc=edu'),
  'deriveFromEntryAttr' => 'uniquemember',
  'deriveFromEntryNested' => 0,
  'deriveFromEntryUserLdapAttr' => 'dn',
  
  'mappings' => array(
    0 => array('cn=content editors,ou=groups,dc=ad,dc=myuniversity,dc=edu', 'content editors'),
    1 => array('cn=content approvers,ou=groups,dc=ad,dc=myuniversity,dc=edu', 'content approvers'),
  ),

  'useMappingsAsFilter' => 1,

  'synchOnLogon' => 1,
  'synchManually' => 1,

  'revokeLdapProvisioned' => 1,
  'createConsumers' => 1,
  'regrantLdapProvisioned' => 1,
);

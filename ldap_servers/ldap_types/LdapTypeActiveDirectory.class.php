<?php

/**
 * @file
 * Active Directory LDAP Implementation Details
 *
 */

require_once(drupal_get_path('module', 'ldap_servers') . '/ldap_types/LdapTypeAbstract.class.php');

class LdapTypeActiveDirectory extends LdapTypeAbstract {

  public $name = 'Active Directory LDAP';
  public $typeId = 'ActiveDirectory';
  public $description = 'Microsoft Active Directory';
  public $port = 389;
  public $tls = 1;
  public $encrypted = 0;
  public $user_attr = 'sAMAccountName';
  public $mail_attr = 'mail';

  public $groupObjectClassDefault = 'group';

  public $groupDerivationModelDefault = LDAP_SERVERS_DERIVE_GROUP_FROM_ATTRIBUTE;

  public $groupDeriveFromAttributeNameDefault = 'memberOf';
  public $groupDeriveFromAttrDnAttrDefault = 'distinguishedname';


  // other ldap implementation specific methods

}

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
  public $supportsNestGroups = FALSE;
  // other ldap implementation specific properties and their default values


  public function getNestedGroupMemberships($user_ldap_entry, $nested = FALSE) {
    if (!$this->supportsNestedGroups) {
      return FALSE;
    }
    // code for nested memebership would go here
  }


  // other ldap implementation specific methods

}

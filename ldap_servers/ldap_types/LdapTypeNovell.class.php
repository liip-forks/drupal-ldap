<?php

/**
 * @file
 * Active Directory LDAP Implementation Details
 *
 */

require_once(drupal_get_path('module', 'ldap_servers') . '/ldap_types/LdapTypeAbstract.class.php');

class LdapTypeNovell extends LdapTypeAbstract {

  public $documentation = 'http://www.novell.com/documentation/edir873/index.html?page=/documentation/edir873/edir873/data/h0000007.html';
  public $name = 'Novell eDirectory LDAP';
  public $typeId = 'Novell';
  public $description = 'Novell eDirectory LDAP';
  public $port = 389;
  public $tls = 1;
  public $encrypted = 0;
  public $user_attr = 'uid';
  public $mail_attr = 'mail';
  public $supportsNestGroups = FALSE;

  public function getNestedGroupMemberships($user_ldap_entry, $nested = FALSE) {
    if (!$this->supportsNestedGroups) {
      return FALSE;
    }
  }

}

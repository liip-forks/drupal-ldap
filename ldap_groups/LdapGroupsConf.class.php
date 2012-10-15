<?php

/**
 * @file
 * This class represents a ldap_group module's configuration
 * It is extended by LdapGroupConfAdmin for configuration and other admin functions
 */
require_once('ldap_groups.module');  // constants and such

class LdapGroupsConf {

  public $ldapProvisionServer;  
  public $ldapProvisionSid = LDAP_USER_NO_SERVER_SID;
  public $ldapProvisionGroupsBaseDn;  
  public $ldapProvisionGroupsRoleAttribute = 'cn'; 
  public $groupObjectClass;
  public $provisionGroupEntryEvents = array();
  public $provisionOptions = array();
  public $inDatabase = FALSE;
 

  public $saveable = array(
    'ldapProvisionSid',
    'ldapProvisionGroupsBaseDn',
    'ldapProvisionGroupsRoleAttribute',
    'provisionOptions',
    'provisionGroupEntryEvents'
  );
  


  function __construct() {
    $this->load();
  }

  function load() {

    if ($saved = variable_get("ldap_group_conf", FALSE)) {
      $this->inDatabase = TRUE;
      foreach ($this->saveable as $property) {
        if (isset($saved[$property])) {
          $this->{$property} = $saved[$property];
        }
      }
    }
    else {
      $this->inDatabase = FALSE;
    }

    if ($this->ldapProvisionSid) {
      $this->ldapProvisionServer = ldap_servers_get_servers($this->ldapProvisionSid, NULL, TRUE);
      $this->groupObjectClass = $this->ldapProvisionServer->groupObjectClass;
    }
  }

  /**
   * Destructor Method
   */
  function __destruct() { }
  
  public function groupDnFromRole($role) {
    if ($this->ldapProvisionGroupsRoleAttribute && $this->ldapProvisionGroupsBaseDn) {
      return $this->ldapProvisionGroupsRoleAttribute . '=' . $role . ',' . $this->ldapProvisionGroupsBaseDn;
    }
    else {
      return FALSE;
    }
 
  }
}

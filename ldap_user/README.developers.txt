
provisioning = creating or synching ... to drupal or to ldap

==========================================
Rough Summary of provisioning configuration and controls
==========================================

1. configured triggers (admin/config/people/ldap/user) or configuration of other modules
determine when provisioning happens.

// configurable drupal acct provision triggers
LDAP_USER_DRUPAL_USER_PROV_ON_USER_UPDATE_CREATE
LDAP_USER_DRUPAL_USER_PROV_ON_AUTHENTICATE
LDAP_USER_DRUPAL_USER_PROV_VIA_API

// configurable ldap entry provision triggers 
LDAP_USER_LDAP_ENTRY_PROV_ON_USER_UPDATE_CREATE
LDAP_USER_LDAP_ENTRY_PROV_ON_AUTHENTICATE
LDAP_USER_LDAP_ENTRY_DELETE_ON_USER_DELETE


2. hook_user_* functions will check if appropriate triggers are enabled and initiate calls to ldapUserConf methods:

ldapUserConf::provisionDrupalAccount()
ldapUserConf::synchToDrupalAccount()
ldapUserConf::ldapAssociateDrupalAccount()
ldapUserConf::deleteDrupalAccount()

ldapUserConf::provisionLdapEntry()
ldapUserConf::synchToLdapEntry()
ldapUserConf::deleteProvisionedLdapEntries()

3. to get mappings and determine which attributes are needed "ldap_contexts" and "prov_events" are passed into 
ldap_servers_get_user_ldap_data()
ldapUserConf::drupalUserToLdapEntry()


4.  Should provisioning happen?

------------
4.A.  Server Level: Does an ldap server configuration support provisioning?
ldapUserConf::drupalAcctProvisionServer = <sid> | LDAP_USER_NO_SERVER_SID;  // servers used for to drupal acct provisioning
ldapUserConf::ldapEntryProvisionServer =  <sid> | LDAP_USER_NO_SERVER_SID;  // servers used for provisioning to ldap

This is directly configured at config/people/ldap/user

------------
4.B.  Trigger Level: Does provisioning occur for a given trigger?
ldapUserConf::provisionEnabled($direction, $provision_trigger)
    
This method is based on the configuration of two sets of checkboxes at config/people/ldap/user

ldapUserConf::drupalAcctProvisionTriggers (see "LDAP Entry Provisioning Options"), contains:
  LDAP_USER_DRUPAL_USER_PROV_ON_AUTHENTICATE
  LDAP_USER_DRUPAL_USER_PROV_ON_USER_UPDATE_CREATE

ldapUserConf::ldapEntryProvisionTriggers (see "Drupal Account Provisioning Options"), contains:
  LDAP_USER_LDAP_ENTRY_PROV_ON_USER_UPDATE_CREATE
  LDAP_USER_LDAP_ENTRY_DELETE_ON_USER_DELETE
  LDAP_USER_LDAP_ENTRY_PROV_ON_AUTHENTICATE

configurable elsewhere or no implemented:
  LDAP_USER_DRUPAL_USER_PROV_VIA_API
  

@todo.  A hook to allow other modules to intervene here 

------------
4.C  Field Level: Does provisioning occur for a given field and ldap server for a given "prov_event" and "ldap _context"?

ldapUserConf::isSynched($field, $ldap_server, $prov_event, $direction)

This depends on: 
ldapUserConf::synchMapping[$direction][$ldap_server->sid][$field]['prov_events']
which is populated by various ldap and possibly other modules.

"ldap_contexts" (any module can provide its own context which is just a string)
  ldap_user_insert_drupal_user
  ldap_user_update_drupal_user
  ldap_authentication_authenticate
  ldap_user_delete_drupal_user
  ldap_user_disable_drupal_user
  all

"prov_events"
  LDAP_USER_EVENT_SYNCH_TO_DRUPAL_USER
  LDAP_USER_EVENT_CREATE_DRUPAL_USER
  LDAP_USER_EVENT_SYNCH_TO_LDAP_ENTRY
  LDAP_USER_EVENT_CREATE_LDAP_ENTRY
  LDAP_USER_EVENT_LDAP_ASSOCIATE_DRUPAL_ACCT
  LDAP_USER_EVENT_ALL



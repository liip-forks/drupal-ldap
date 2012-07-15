
==========================================
Summary of how synching and provisioning events are handled
==========================================

-------------------
0.  Event Handlers for Provisioning

synch_contexts are:
  LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER
  LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER
  LDAP_USER_SYNCH_CONTEXT_AUTHENTICATE_DRUPAL_USER
  LDAP_USER_SYNCH_CONTEXT_CRON
  LDAP_USER_SYNCH_CONTEXT_DELETE_DRUPAL_USER
  LDAP_USER_SYNCH_CONTEXT_DISABLE_DRUPAL_USER
  LDAP_USER_SYNCH_CONTEXT_ENABLE_DRUPAL_USER
  
The following events map to these synch contexts:

LDAP_USER_SYNCH_CONTEXT_AUTHENTICATE_DRUPAL_USER:
  -- synchToDrupalAccount()   from ldap_authentication_user_login_authenticate_validate function on logon.
  -- provisionDrupalAccount() from ldap_authentication_user_login_authenticate_validate function on logon.
  -- synchToLdapEntry()       from hook_user_login in ldap_user module
  -- provisionLdapEntry()     from hook_user_login in ldap_user module 
  
LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER:
  -- provisionLdapEntry()     from hook_user_login in ldap_user module
  -- synchToLdapEntry()       from hook_user_login in ldap_user module
  -- synchToDrupalAccount()   from hook_user_presave() when $account->is_new
  
LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER: 
  -- provisionLdapEntry()     from hook_user_update in ldap_user module
  -- synchToLdapEntry()       from hook_user_update in ldap_user module

LDAP_USER_LDAP_ENTRY_DELETE_ON_USER_DELETE:   
  -- deleteCorrespondingLdapEntry()  from hook_user_delete in ldap_user module


------------
1.  Server Level: Does an ldap server configuration support provisioning
$ldap_user_conf->drupalAcctProvisionServer = <sid> | LDAP_USER_NO_SERVER_SID;  // servers used for to drupal acct provisioning
$ldap_user_conf->ldapEntryProvisionServer =  <sid> | LDAP_USER_NO_SERVER_SID;  // servers used for provisioning to ldap

This is directly configured at config/people/ldap/user

------------
2.  Context Level: Does provisioning occur for a given synch context?
$ldap_user_conf->contextEnabled($synch_context, [synch|provision|delete_ldap_entry])
    
This method is based on the configuration of two sets of checkboxes at config/people/ldap/user

$this->drupalAcctProvisionEvents (see "LDAP Entry Provisioning Options"), contains:
  LDAP_USER_DRUPAL_USER_CREATE_ON_LOGON
  LDAP_USER_DRUPAL_USER_CREATE_ON_MANUAL_ACCT_CREATE;
  LDAP_USER_DRUPAL_USER_CREATE_ON_ALL_USER_CREATION;
  LDAP_USER_DRUPAL_USER_CANCEL_ON_LDAP_ENTRY_MISSING
  LDAP_USER_DRUPAL_USER_DELETE_ON_LDAP_ENTRY_MISSING

$this->ldapEntryProvisionEvents (see "Drupal Account Provisioning Options"), contains:
  LDAP_USER_LDAP_ENTRY_CREATE_ON_USER_CREATE
  LDAP_USER_LDAP_ENTRY_CREATE_ON_USER_STATUS_IS_1
  LDAP_USER_LDAP_ENTRY_DELETE_ON_USER_DELETE
  LDAP_USER_LDAP_ENTRY_UPDATE_ON_USER_UPDATE
  LDAP_USER_LDAP_ENTRY_UPDATE_ON_USER_AUTHENTICATE
  LDAP_USER_LDAP_ENTRY_CREATE_ON_USER_UPDATE
  LDAP_USER_LDAP_ENTRY_CREATE_ON_USER_AUTHENTICATE



------------
3.  Field Level: Does provisioning occur for a given field and ldap server for a given synch context?

$ldap_user_conf->isSynched($field, $ldap_server, $synch_context, $direction)

This depends on: 
$ldap_user_conf->synchMapping[$ldap_server->sid][$field]['contexts']
which is populated by various ldap and possibly other modules. These are visible in the tables at config/people/ldap/user


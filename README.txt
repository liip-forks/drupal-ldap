

LDAP_* Module Breakdown (Long Term Direction after 7.x-2.x)


LDAP API
--------
- general function


============
LDAP Servers
============

- General LDAP preferences
-- https
-- encyption
-- detailed logging


-- ldap server connection and binding information
---- LdapServer::status
---- LdapServer::ldap_type
---- LdapServer::address
---- LdapServer::port 
---- LdapServer::tls
---- LdapServer::bind_method
---- LdapServer::basedn
---- LdapServer::binddn 
---- LdapServer::bindpw


-- pagination
---- LdapServer::paginationEnabled
---- LdapServer::searchPagination
---- LdapServer::searchPageSize
---- LdapServer::searchPageStart
---- LdapServer::searchPageEnd

  
-- relationship between ldap user and drupal user (could belong in ldap_user)
---- LdapServer::userUsernameToLdapNameTransform()
---- LdapServer::userUserNameToExistingLdapEntry()
---- LdapServer::userUsernameFromLdapEntry()
---- LdapServer::userUsernameFromDn()
---- LdapServer::userEmailFromLdapEntry()
---- LdapServer::userPuidFromLdapEntry()
---- LdapServer::user_dn_expression
---- LdapServer::user_attr
---- LdapServer::account_name_attr
---- LdapServer::mail_attr
---- LdapServer::mail_template
---- LdapServer::unique_persistent_attr
---- LdapServer::unique_persistent_attr_binary
---- LdapServer::ldapToDrupalUserPhp
---- LdapServer::testingDrupalUsername


-- relationship between ldap groups and drupal role (could belong in ldap_groups)
---- LdapServer::groupUserMembershipsFromUserAttr()
---- LdapServer::groupUserMembershipsFromUserAttrResursive()
---- LdapServer::deriveFromEntryGroups()
---- LdapServer::groupsByEntryIsMember()
---- LdapServer::groupObjectClass


- LDAP Types
-- objects containing common defaults Active Directory, OpenLdap, etc.


- LDAP Utility Functions
-- search()
-- modifyLdapEntry()
-- createLdapEntry()
-- delete()
-- countEntries()
-- dnExists()
-- connectAndBindIfNotAlready()
-- bind()
-- connect()
-- pagedLdapQuery()
-- ldapQuery()










============
Object Related Modules
- provisioning between drupal object and ldap entry
- configuration related to said provisioning
- configuration 
============

LDAP Groups Integration (ldap_groups)

LDAP User Integration (ldap_user)

LDAP OG Integration (ldap_op)






============
Function Related Modules
============

LDAP Authorization
LDAP Authorization Drupal Roles
LDAP Authorization OG Groups

LDAP Authentication


============
Module Integation Modules
============
LDAP Views
LDAP Feeds

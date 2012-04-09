

LDAP User Module

The core functionality of this module is provisioning and storage of an ldap identified Drupal user based on ldap attributes.  In Drupal 6 this functionality was in ldap_synch, ldap_provision, ldap_profile, etc. This has been moved to one module centered on the Drupal User - LDAP User Entry data. 

-----------------
hooks relating ldap_user entities and drupal user entities
-----------------

-- hook_user_create, hook_user_update, hook_user_delete should look for ldap_user entity with matching uid and deal with ldap_user entity appropriately.
-- hook_ldap_user_create, hook_ldap_user_update, hook_ldap_user_delete should do appropriate things to user entity



----------------------
ldap user entity: for storage of attributes of LDAP identified user
* The split between ldap_user and user entities is not ideal, but neither is overloading the user entity which is used in a variety of ways by a myriad of modules.
----------------------

- Storage and provisioning mechanism for LDAP identified Drupal User
- ldap_user entity has a one to one relationship with Drupal user entity for LDAP identified users.
- ldap_user entity provides the following data:
* uid - related user->uid
* ldap_dn - most recently stored dn of user's ldap entry.  this may be out of date if users cn changes for example or OUs are moved around.
* ldap_puid -  value of users's permanent unique id.  This should never change for a given ldap identified user.
* ldap_puid_property - property specified as user's puid
* ??? ldap_puid_sid - server id of puid.  can this server a purpose
* ??? ldap_last_found_date - last
* ??? ldap_last_found_sid


- Other Modules provide their own fields:
* ldap_authorizations
* ldap_authentication_sid
* ldap_authorization_og_sid
* ldap_authorization_drupal_roles_sid


----------------------
ldap user module use cases:
----------------------

Provide interface for manually working with LDAP identified user data.
- create ldap indentified user (via ldap_user or user form).  Perhaps on submission, drupal.user is created and.
- edit ldap identified user (go directly to ldap_user entity and edit and/or add link to edit ldap_user to user forms)
- associate existing user with ldap ( add prepopulate link from user page to create ldap_user page.)


Populate/Create/Update/Remove LDAP identified Drupal users via feeds
---- point feed at ldap_user entity
---- guid field most likely ldap_user.puid field
---- on create of ldap_user, hook will create related user entity and populate ldap_user.uid with user.uid


Populate/Create/Update/Remove  with batch process
---- create ldap_query query for process
---- user ldap_server, ldap_authorization, ldap_authentication, etc rules for creating user and ldap_user



-------------
Drupal Entity References:
http://drupal.org/project/entity
http://www.trellon.com/content/blog/creating-own-entities-entity-api
http://www.istos.it/blog/drupal-entities/drupal-entities-part-3-programming-hello-drupal-entity
http://drupal.org/project/model

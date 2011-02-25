----------------------
Authorization Consumer
----------------------
The "target" or entity that membership or authorization
is being granted to.  Represented by LdapAuthorizationConsumer<consumer_type>
class which is provided by consumer modules such as ldap_authorization_drupal_role

e.g. for drupal roles, the authorization consumer would be drupal roles
e.g. for og, , the authorization consumer would be organic groups

----------------------
Consumer Type
----------------------
Id of a consumer type.  A module articulates which consumer_types
it provides in hook_ldap_authorization_consumer and must provide a class
for each consumer it provides.
e.g. drupal_role, og_group, etc.

----------------------
Authorization Consumer Module
----------------------
A module which provides an authorization consumer.
The module will provide the LdapAuthorizationConsumer<consumer_type> class and
implement the hook_ldap_authorization_consumer hook.

----------------------
Authorization Target.
----------------------
The object representing a single authorization for a given Authorization Consumer.
e.g. a drupal role, or og group

----------------------
Target ID.
----------------------
The id representing a single authorization for a given Authorization Consumer.
e.g. for drupal roles, the Target ID would be the drupal role name
e.g. for og, the Target ID might be the og name.

----------------------
Authorization Mapping.
----------------------
Configuration of how a users ldap attributes will
determine a set of Target ids the user should be granted.
Represented by LdapAuthorizationMapping and LdapAuthorizationMappingAdmin classes
and managed at /admin/config/people/ldap/authorization

---------------------
LDAP Server.
---------------------
Each Authorization Mapping will use a single ldap server configuration to bind
and query ldap.  The ldap server configuration is also used to map the drupal
username to an ldap user entry.







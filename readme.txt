; $Id$

============================================================
Summary of LDAP without LDAP API
============================================================

This is a development version of LDAP project without the LDAP API component.  
The ldap_servers module included will be replaced by the ldap_api module in time.

============================================================
Important Things to Know about This Release
============================================================

1.  This does not automatically upgrade for Drupal 6 LDAP Integration Modules.  This functionality
  will be developed when this module is out of alpha stages.

2. The included ldap_developer module is just for testing.  There is no need to enable it.

3.  This release is not for production.  The purpose of this release is allow other developers to
help with refinements, documentation, testing, and functionality such as i18n, accessibility,
exportables support, caching/performance improvements, drush integration, etc.
See http://www.gliffy.com/publish/2315088/ for a general direction for release candidates.

============================================================
Install
============================================================
Install just like any other drupal module.


LDAP Servers is required by LDAP Authentication and LDAP Authorization
LDAP Authentication and LDAP Authorization can be used independently.  Neither is required for the other to function.
LDAP Authorization needs at least one authorization consumer module; LDAP Authorization Drupal Role 
  is the one built into the LDAP project that maps ldap attributes to drupal roles.

============================================================
Configuration
============================================================

1.  Start with general setttings.  
path:  admin/config/people/ldap

2.  Configure one (or more) ldap servers.  
path:  admin/config/people/ldap/servers

3.  If using ldap for authentication, configure ldap authentication. 
path: admin/config/people/ldap/authentication

4.  If using ldap for authorization, configure one mapping for each "consumer".  In most cases
 the "consumer" will be Drupal roles and only 1 mapping will be created.  

path: /admin/config/people/ldap/authorization

============================================================
Related Resources
============================================================
LDAP Project Install And Configuration Documentation.  This will need work once the user interface is stable:
  http://drupal.org/node/997082

LDAP Project Development Path:  http://www.gliffy.com/publish/2315088/

LDAP Developers Page:  http://dev.digitalactionsproject.org/wiki/Drupal:LDAP

LDAP Module and Class Relationship Diagram: http://www.gliffy.com/publish/2376942/

LDAP Authentication Sequence Diagram:  http://www.gliffy.com/publish/2362004/

LDAP Authorization Sequence Diagram:  http://www.gliffy.com/publish/2318063/

============================================================
Revisions
============================================================

7.x-1.0-unstable3
- #1018968, #1016284 "ldap_authorization_example" text fixed
- added check for uid==1 in ldap to make sure that uid=1 is not using ldap authentication
- #1017578, #1005358  mixed mode authentication failed for user 1 fixed.
- #1017282 uninitialized array gives warning.  I'd like to get rid of all these types of warnings.
- #807420 initial exportables/features code added.  needs testing.  not sure if ldap_servers_encrypt_key variable should be exportable
- starter working with coder module cleanup (spacing, translation, etc)

7.x-1.0-unstable4
- fixed schema issue in ldap authorization #1021478
- fixed issue when ldap authentication was before drupal authentication and created false error messsage.  #1021612, #1009990
- fixed undefined $name_attr warning.  #1021636






Summary of simpletest framework for LDAP_* modules

There are a number of versions of code between the current set of modules.
This summary is for the most current approach which is implemented in ldap_servers/tests/ldap_servers_v3.test

Configuration Sources for LDAP Simpletests:

-- ldap_test/<module_name>.conf.inc (e.g. ldap_servers.conf.inc) contain functions such as ldap_test_ldap_servers_data() that return arrays of configuration data keyed a test id.
-- ldap_test/test_ldap/<ldap data id> (e.g. ldap_test/test_ldap/hogwarts) contain the data used to populate the ldap.  The stucture of the actual ldap array depends on which server configuration if driving it.  For example if the ldap server configuration has a memberof attribute, the memberof attribute will be populated in the users.


Classes for LDAP Simpletests:

-- ldap_test/LdapServerTestv2.class.php is the class the replaces the LdapServer class in simpletests.  It inherits from LdapServer class and overrides methods that deal with fake ldap data.

-- ldap_test/LdapTestCasev3.clas.php is the base test class for all ldap_* simpletests.

-- ldap_test/LdapTestFunctionsv3.clas.php is a class with utility functions.  These should go in LdapTestCasev3 at some point.


Fake LDAP Data:

The fake ldap data used by LdapServerTestv2 is stored in Drupal variables in the following form:

===========================
variable: ldap_test_servers
-----------------------
contains array of sids

===========================
variable: ldap_simpletest
-----------------------
signifies if simpletest environment.
1=version 1 of simpletests
2=version 2 of simpletests

===========================
variable: ldap_test_server__<sid>
-----------------------
contains the following key/value pairs
-- properties => array of LdapServer properties and values.  Used to populate LdapServerTextv2 object such as 'name' => 'Test Open LDAP', 'mail_attr' => 'mail', etc.
-- ldap => an array of ldap entries keyed on dn.  each entry is in ldap entry format used in ldap php extension
-- users => an array of ldap user entries keyed on dn. attributes are in sub array named 'attr' for older functions.  the 'attr' thing should be phased out.
-- groups => an array of ldap group entries keyed on dn. attributes are in sub array named 'attr' for older functions.  the 'attr' thing should be phased out.
-- csv => the csv tables in ldap_test/test_ldap/<ldap data id>/*.csv
-----------------------

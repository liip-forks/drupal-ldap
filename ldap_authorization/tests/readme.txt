
Ldap Authorization Testing Notes

===========================================
I. Creating An LDAP Authorization Simpletest:
caveat: authorization simpletests are a pain, because they involve fake ldap users, fake ldap servers, and the logon process.
==========================================

1.  Create ID and explanation in ldap_authorization/tests/ldap_authorization.tests.txt
Example: DeriveFromDN

----------
2. Create test data file with configuration parameters for authorization
Example: ldap_authorization/tests/ldap_authorization_test_data.DeriveFromDN.inc

The sid parameter determines which fake ldap server parameters to use.
Example: $test_data['ldap_authorization_conf']['consumer_conf']['sid'] = 'ldapauthor1'

----------
3. Edit or add new server configuration file.  This data is used to populate the fake ldap test class (ldap_servers/tests/LdapServerTest.class.php)
Example:  ldap_authorization/tests/LdapServerTestData.ldapauthor1.inc

----------
4. Add the test function to  ldap_authorization/tests/ldap_authorization.test
Example: testDeriveFromDN()

-- test functions should start with 'test' as with other DrupalWebTestCase simpletests
-- $conf_id will determine which file is included for test data.
-- $this->prepTestData($testid) creates authorization and server configurations.


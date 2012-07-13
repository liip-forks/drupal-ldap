

--------------------------------------------------------
Case Sensitivity and Character Escaping in LDAP Modules
--------------------------------------------------------

The function ldap_server_massage_text() should be used for dealing with case sensitivity
and character escaping consistently.

The general rule is codified in ldap_server_massage_text() which is:
- escape filter values and attribute values when querying ldap
- use unescaped, lower case attribute names when storing attribute names in arrays (as keys or values), databases, or object properties.
- use unescaped, mixed case attribute values when storing attribute values in arrays (as keys or values), databases, or object properties.

So a filter might be built as follows:

  $username = ldap_server_massage_text($username, 'attr_value', LDAP_SERVER_MASSAGE_QUERY_LDAP)
  $objectclass = ldap_server_massage_text($objectclass, 'attr_value', LDAP_SERVER_MASSAGE_QUERY_LDAP)
  $filter = "(&(cn=$username)(objectClass=$objectclass))";


The following functions are also available:
ldap_pear_escape_dn_value()
ldap_pear_unescape_dn_value()
ldap_pear_unescape_filter_value()
ldap_pear_unescape_filter_value()


--------------------------------------------------------
common variables used in ldap_* and their structures
--------------------------------------------------------

!Structure of $ldap_user and $ldap_entry are different!

-----------
$ldap_user
-----------
Associative array representing ldap data of a user. See $ldap_server->user_lookup() for example of returned value.
  'sid' => ldap server id
  'mail' => derived from ldap mail (not always populated).
  'dn'   => dn of user
  'attr' => single ldap entry array in form returned from ldap_search() extension, e.g.
    'dn' => dn of entry
    'count' => 75
    1 =>  'telephonenumber'
    'telephonenumber' =>
      'count => 2
      0 => 555-555-5555
      1 => 555-555-1232
    2 => ...

  
-----------
$ldap_entry and $ldap_*_entry.  
-----------
single ldap entry from return of ldap_search()

-----------
$ldap_entries and $ldap_*_entries 
-----------
multiple ldap entries result array as returned by ldap_search()

--------------
$user_attr_key
key of form <attr_type>.<attr_name>[:<instance>] such as field.lname, property.mail, field.aliases:2 
--------------

======================
configuration objects
======================
$ldap_user_conf
$ldap_user_conf_admin
$ldap_server [should be renamed to ldap_server_conf and ldap_server_conf_admin]
$ldap_servers [should be renamed to ldap_servers_conf and ldap_servers_conf_admin]



 

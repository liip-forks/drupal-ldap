

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
@see LdapServer::userUserNameToExistingLdapEntry() return

-----------
$ldap_entry and $ldap_*_entry.  
-----------
@see LdapServer::ldap_search() return array

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


==========================================
Structure of "*_attribute_maps" variables
==========================================

Purpose: track which attributes (and their datatype) are needed for provisioning.
These may be ldap entry or drupal user attributes mappings. Array is keyed on "source" attribute.  

structure of "*_attribute_map" variables:
$attributes[<attribute_name>]['values'][<ordinal>] = $value | NULL if not populated;
$attributes[<attribute_name>]['source_data_type'] = NULL|ldap_dn|string|binary|ldap_attr_name|ldap_attr_value|   ...NULL when data type is not known.
$attributes[<attribute_name>]['target_data_type'] = NULL|ldap_dn|string|binary|ldap_attr_name|ldap_attr_value|   ...NULL when data type is not known.
$attributes[<attribute_name>]['values'][0] = NULL when value needed, but not known. 0th value in array always exists

$attributes['dn'] = array(
  'source_data_type' => 'ldap_dn',
  'target_data_type' => 'ldap_dn',
  'values' => array(0 => NULL),
  );
  
$attributes['objectclass'] = array(
  'source_data_type' => NULL,
  'target_data_type' => NULL,
  'values' => array(
    0 => NULL,
    1 => NULL,
    2 => NULL,
    3 => NULL,
  )
);
// in this case  'top', 'person', 'organizationalPerson', 'user'),
  
$attributes['mail'] = array(
  'source_data_type' => NULL,
  'target_data_type' => NULL,
  'values' => array(0 => NULL),
  );
        
        
Functions using "*_attribute_maps" variables:
- ldap_servers_token_extract_attributes(): $attribute_maps 
- hook_ldap_attributes_needed_alter(): $attribute_maps
- LdapUserConf->getLdapUserRequiredAttributes(): $attributes_map
- $ldap_attr_in_token in ldapUserConfAdmin:validate(): $ldap_attribute_maps_in_token
- LdapServer->userUserNameToExistingLdapEntry: $attribute_maps
- LdapServer->search: $attribute_maps

 

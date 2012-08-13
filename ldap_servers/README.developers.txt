

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
- LdapUserConf->getRequiredAttributes(): $attributes_map
- $ldap_attr_in_token in ldapUserConfAdmin:validate(): $ldap_attribute_maps_in_token
- LdapServer->user_lookup: $attribute_maps
- LdapServer->search: $attribute_maps


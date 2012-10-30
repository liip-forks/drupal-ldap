<?php

/*** activedirectory is default Active Directory server config **/
function ldap_test_ldap_servers_data() {

$conf['activedirectory']['properties'] = array(
    'name'  => 'Default LDAP Server for Active Directory',
    'inDatabase' => TRUE,
    'status'  => 1,
    'ldap_type'  => 'ad',
    'address'  => 'activedirectory.ldap.pixotech.com',
    'port'  => 389,
    'tls'  => FALSE,
    'bind_method' => LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT,
    'basedn'  => array(
       'DC=activedirectory,DC=ldap,DC=pixotech,DC=com',
      ),
    'binddn' => 'cn=service-account,DC=activedirectory,DC=ldap,DC=pixotech,DC=com',
    'bindpw' => 'goodpwd',
    'user_dn_expression' => NULL,
    'user_attr'  => 'sAMAccountName',
    'mail_attr'  => 'mail',
    'mail_template'  => NULL,
    'unique_persistent_attr' => 'pixotechcomregistryuniqueid',
    'ldap_to_drupal_user' => FALSE,
    'ldapToDrupalUserPhp'  => NULL,
    'testing_drupal_username'  => 'jdoe',
    'group_object_category' => 'group',
    'search_pagination' => 0,
    'searchPageSize' => NULL,
  );


$conf['activedirectory']['methodResponses']['connect'] = LDAP_SUCCESS;


$conf['activedirectory']['search_results']['(member=cn=verykool,ou=special guests,ou=guest accounts,dc=ad,dc=myuniversity,dc=edu)']['ou=groups,dc=ad,dc=myuniversity,dc=edu'] = array(
    0 => array('count' => 1, 'dn' => 'cn=content editors,ou=groups,dc=ad,dc=myuniversity,dc=edu'),
    1 => array('count' => 1, 'dn' => 'cn=content approvers,ou=groups,dc=ad,dc=myuniversity,dc=edu'),
    'count' => 2,
  );

$conf['activedirectory']['search_results']['(member=cn=jkool,ou=guest accounts,dc=ad,dc=myuniversity,dc=edu)']['ou=groups,dc=ad,dc=myuniversity,dc=edu'] = array(
    0 => array('count' => 1, 'dn' => 'cn=content editors,ou=groups,dc=ad,dc=myuniversity,dc=edu'),
    'count' => 1,
  );



$conf['ldapauthen1']['properties']  = array(

    'sid' => 'ldapauthen1',
    'name'  => 'Test LDAP Server 1 for LDAP Authentication' ,
    'inDatabase' => TRUE,
    'status'  => 1,
    'ldap_type'  => 'ad',
    'address'  => 'ad.myuniversity.edu',
    'port'  => 389,
    'tls'  => FALSE,
    'bind_method' => LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT,
    'basedn'  => array(
       'ou=campus accounts,dc=ad,dc=myuniversity,dc=edu',
       'ou=education,dc=ad,dc=myuniversity,dc=edu',
       'ou=guest accounts,dc=ad,dc=myuniversity,dc=edu',
      ),
    'binddn'  => 'cn=service-account,dc=ad,dc=myuniversity,dc=edu',
    'bindpw' => 'goodpwd',
    'user_dn_expression' => NULL,
    'user_attr'  => 'sAMAccountName',
    'mail_attr'  => 'mail',
    'mail_template'  => NULL,
    'ldap_to_drupal_user' => FALSE,
    'ldapToDrupalUserPhp'  => NULL,
    'testingDrupalUsername'  => 'jdoe',
    'groupObjectClass' => 'group',
    'search_pagination' => 0,
    'searchPageSize' => NULL,

  );


$conf['ldapauthen1']['methodResponses']['connect'] = LDAP_SUCCESS;

$conf['ldapauthen1']['search_results']['member=cn=verykool,ou=special guests,ou=guest accounts,dc=ad,dc=myuniversity,dc=edu']['ou=groups,dc=ad,dc=myuniversity,dc=edu'] = array(
    0 => array('count' => 1, 'dn' => 'cn=content editors,ou=groups,dc=ad,dc=myuniversity,dc=edu'),
    1 => array('count' => 1, 'dn' => 'cn=content approvers,ou=groups,dc=ad,dc=myuniversity,dc=edu'),
    'count' => 2,
  );

$conf['ldapauthen1']['search_results']['member=cn=jkool,ou=guest accounts,dc=ad,dc=myuniversity,dc=edu']['ou=groups,dc=ad,dc=myuniversity,dc=edu'] = array(
    0 => array('count' => 1, 'dn' => 'cn=content editors,ou=groups,dc=ad,dc=myuniversity,dc=edu'),
    'count' => 1,
  );

$servers['ldapauthor1']['properties']  = array(
    'sid' => 'openldap1',
    'name'  => 'Test LDAP Server LDAP Authorization' ,
    'inDatabase' => TRUE,
    'status'  => 1,
    'ldap_type'  => 'openldap',
    'address'  => 'ldap.hogwarts.edu',
    'port'  => 389,
    'tls'  => FALSE,
    'bind_method' => LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT,
    'basedn'  => array(
       'dc=ldap,dc=hogwarts,dc=edu',
      ),
    'binddn'  => 'cn=service-account,ou=people,dc=hogwarts,dc=edu',
    'bindpw' => 'goodpwd',
    'user_dn_expression' => NULL,
    'user_attr'  => 'cn',
    'mail_attr'  => 'mail',
    'mail_template'  => NULL,
    'unique_persistent_attr' => 'guid',
    'ldap_to_drupal_user' => FALSE,
    'ldapToDrupalUserPhp'  => NULL,
    'groupFunctionalityUnused' => FALSE,
    'groupObjectClass' => 'groupofnames',
    'groupUserMembershipsAttrExists' => FALSE,
    'groupUserMembershipsAttr' => NULL,
    'groupMembershipsAttr' => 'member',
    'groupMembershipsAttrMatchingUserAttr' => 'dn',
    'search_pagination' => 0,
    'searchPageSize' => NULL,
  );



$conf['openldap1']['properties']  = array(
    'sid' => 'openldap1',
    'name'  => 'Test Open LDAP' ,
    'inDatabase' => TRUE,
    'status'  => 1,
    'ldap_type'  => 'openldap',
    'address'  => 'ldap.hogwarts.edu',
    'port'  => 389,
    'tls'  => FALSE,
    'bind_method' => LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT,
    'basedn'  => array(
       'dc=ldap,dc=hogwarts,dc=edu',
    ),
    'binddn'  => 'cn=service-account,ou=people,dc=hogwarts,dc=edu',
    'bindpw' => 'goodpwd',
    'user_dn_expression' => NULL,
    'user_attr'  => 'cn',
    'mail_attr'  => 'mail',
    'mail_template'  => NULL,
    'unique_persistent_attr' => 'guid',
    'unique_persistent_attr_binary' => FALSE,
    'ldap_to_drupal_user' => FALSE,
    'ldapToDrupalUserPhp'  => NULL,
    'groupFunctionalityUnused' => FALSE,
    'groupObjectClass' => 'groupofnames',
    'groupUserMembershipsAttrExists' => FALSE,
    'groupUserMembershipsAttr' => NULL,
    'groupMembershipsAttr' => 'member',
    'groupMembershipsAttrMatchingUserAttr' => 'dn',
    'search_pagination' => 0,
    'searchPageSize' => NULL,
  );

$conf['openldap1']['methodResponses']['connect'] = LDAP_SUCCESS;


$conf['openldap1']['search_results'] = array();


$conf['openldap1']['search_results']['(&(objectClass=group)(|(member=cn=gryffindor,ou=groups,dc=hogwarts,dc=edu)(member=cn=students,ou=groups,dc=hogwarts,dc=edu)(member=cn=honors students,ou=groups,dc=hogwarts,dc=edu)))']['dc=ldap,dc=hogwarts,dc=edu'] = array(
  0 => array('count' => 1, 'dn' => 'cn=users,ou=groups,dc=hogwarts,dc=edu'),
  'count' => 1,
);

$conf['openldap1']['search_results']['(cn=hpotter)']['dc=ldap,dc=hogwarts,dc=edu'] = array(
  0 => array(
    'count' => 1,
    'dn' => 'cn=hpotter,ou=people,dc=hogwarts,dc=edu',
    'FULLENTRY' => TRUE,
  ),
  'count' => 1,
);

$conf['openldap1']['search_results']['(cn=hpotter)']['dc=ldap,dc=hogwarts,dc=edu'] = array(
  0 => array(
    'count' => 1,
    'dn' => 'cn=hpotter,ou=people,dc=hogwarts,dc=edu',
    'FULLENTRY' => TRUE,
  ),
  'count' => 1,
);

$conf['openldap1']['search_results']['(cn=hpotter-granger)']['dc=ldap,dc=hogwarts,dc=edu'] = array(
  0 => array(
    'count' => 1,
    'dn' => 'cn=hpotter,ou=people,dc=hogwarts,dc=edu',
    'FULLENTRY' => TRUE,
  ),
  'count' => 1,
);

$conf['openldap1']['search_results']['(cn=ssnape)']['dc=ldap,dc=hogwarts,dc=edu'] = array(
  0 => array(
    'count' => 1,
    'dn' => 'cn=ssnape,ou=people,dc=hogwarts,dc=edu',
    'FULLENTRY' => TRUE,
  ),
  'count' => 1,
);

$conf['openldap1']['search_results']['(cn=adumbledore)']['dc=ldap,dc=hogwarts,dc=edu'] = array(
  0 => array(
    'count' => 1,
    'dn' => 'cn=adumbledore,ou=people,dc=hogwarts,dc=edu',
    'FULLENTRY' => TRUE,
  ),
  'count' => 1,
);

$conf['openldap1']['search_results']['(&(objectClass=groupofnames)(member=cn=hpotter,ou=people,dc=hogwarts,dc=edu))']['dc=ldap,dc=hogwarts,dc=edu'] = array(
  0 => array('count' => 1, 'dn' => 'cn=gryffindor,ou=groups,dc=hogwarts,dc=edu'),
  1 => array('count' => 1, 'dn' => 'cn=students,ou=groups,dc=hogwarts,dc=edu'),
  2 => array('count' => 1, 'dn' => 'cn=honors students,ou=groups,dc=hogwarts,dc=edu'),
  'count' => 3,
);

$conf['openldap1']['search_results']['(&(objectClass=groupofnames)(|(member=cn=gryffindor,ou=groups,dc=hogwarts,dc=edu)(member=cn=students,ou=groups,dc=hogwarts,dc=edu)(member=cn=honors students,ou=groups,dc=hogwarts,dc=edu)))']['dc=ldap,dc=hogwarts,dc=edu'] = array(
  0 => array('count' => 1, 'dn' => 'cn=users,ou=groups,dc=hogwarts,dc=edu'),
  'count' => 1,
);



$conf['activedirectory1']['properties']  = array(
    'sid' => 'activedirectory1',
    'name'  => 'Test Active Directory LDAP' ,
    'inDatabase' => TRUE,
    'status'  => 1,
    'ldap_type'  => 'activedirectory',
    'address'  => 'ldap.hogwarts.edu',
    'port'  => 389,
    'tls'  => FALSE,
    'bind_method' => LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT,
    'basedn'  => array(
       'dc=ldap,dc=hogwarts,dc=edu',
    ),
    'binddn'  => 'cn=service-account,ou=people,dc=hogwarts,dc=edu',
    'bindpw' => 'goodpwd',
    'user_dn_expression' => NULL,
    'user_attr'  => 'samaccountname',
    'mail_attr'  => 'mail',
    'mail_template'  => NULL,
    'unique_persistent_attr' => 'guid',
    'unique_persistent_attr_binary' => FALSE,
    'ldap_to_drupal_user' => FALSE,
    'ldapToDrupalUserPhp'  => NULL,
    'groupFunctionalityUnused' => FALSE,
    'groupNested' => 0,
    'groupObjectClass' => 'group',
    'groupUserMembershipsAttrExists' => TRUE,
    'groupUserMembershipsAttr' => 'memberof',
    'groupMembershipsAttr' => 'member',
    'groupMembershipsAttrMatchingUserAttr' => 'dn',
    'search_pagination' => 0,
    'searchPageSize' => NULL,
  );

$conf['activedirectory1']['methodResponses']['connect'] = LDAP_SUCCESS;


$conf['activedirectory1']['search_results'] = array();

$conf['activedirectory1']['search_results']['(&(objectClass=group)(member=cn=hpotter,ou=people,dc=hogwarts,dc=edu))']['dc=ldap,dc=hogwarts,dc=edu'] = array(
  0 => array('count' => 1, 'dn' => 'cn=gryffindor,ou=groups,dc=hogwarts,dc=edu'),
  1 => array('count' => 1, 'dn' => 'cn=students,ou=groups,dc=hogwarts,dc=edu'),
  2 => array('count' => 1, 'dn' => 'cn=honors students,ou=groups,dc=hogwarts,dc=edu'),
  'count' => 3,
);

$conf['activedirectory1']['search_results']['(&(objectClass=group)(|(member=cn=gryffindor,ou=groups,dc=hogwarts,dc=edu)(member=cn=students,ou=groups,dc=hogwarts,dc=edu)(member=cn=honors students,ou=groups,dc=hogwarts,dc=edu)))']['dc=ldap,dc=hogwarts,dc=edu'] = array(
  0 => array('count' => 1, 'dn' => 'cn=users,ou=groups,dc=hogwarts,dc=edu'),
  'count' => 1,
);

foreach (array('hpotter','hgrainger','ssnape') as $cn) {

  $conf['activedirectory1']['search_results']["(cn=$cn)"]['dc=ldap,dc=hogwarts,dc=edu'] = array(
    0 => array(
      'count' => 1,
      'dn' => "cn=$cn,ou=people,dc=hogwarts,dc=edu",
      'FULLENTRY' => TRUE,
    ),
    'count' => 1,
  );
  
  $conf['activedirectory1']['search_results']["(samaccountname=$cn)"]['dc=ldap,dc=hogwarts,dc=edu'] = array(
    0 => array(
      'count' => 1,
      'dn' => "cn=$cn,ou=people,dc=hogwarts,dc=edu",
      'FULLENTRY' => TRUE,
    ),
    'count' => 1,
  );

}


$conf['activedirectory1']['search_results']['(samaccountname=hpotter-granger)']['dc=ldap,dc=hogwarts,dc=edu'] = array(
  0 => array(
    'count' => 1,
    'dn' => 'cn=hpotter,ou=people,dc=hogwarts,dc=edu',
    'FULLENTRY' => TRUE,
  ),
  'count' => 1,
);



return $conf;
}

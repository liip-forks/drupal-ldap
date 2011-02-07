<?php


function theme_ldap_authorization_admin_index(&$variables) {
  extract($variables);  //

  $table = array(
    'header' => array("Mapping ID", "LDAP Server ID", "Description", "Module", "Consumer Type", "Enabled","Actions"),
    'attributes' => array('id' => 'ldap_authorization_mappings', 'class' => 'data'),
    'colgroups' => array(),
    'sticky' => FALSE,
    'empty' => '',
    'caption' => 'LDAP Authorization Mappings',
  );

  if (count($mappings)) {
  
    foreach($mappings as $mapping_id => $mapping) {
      $consumer = ldap_authorization_get_consumer_object(array('consumer_type' => $mapping->consumerType));
      if ($consumer) {
         $actions =  ' | '. l('update', LDAP_SERVERS_MENU_BASE_PATH .'/authorization/update/'. $mapping->mappingID) . ' | ' .
            l('delete', LDAP_SERVERS_MENU_BASE_PATH .'/authorization/delete/'. $mapping->mappingID) . ' | ' .
            l('test', LDAP_SERVERS_MENU_BASE_PATH .'/authorization/test/'. $mapping->mappingID) . ' | ';

      } else {
        $actions = "Failed to create consumer object for !consumer_name.  Make sure appropriate module is enabled.";
      }

      $consumer_name = (isset($consumers[$mapping->consumerType]['consumer_name'])) ? $consumers[$mapping->consumerType]['consumer_name']
        : t("module providing type %name disabled", array('%name' => $mapping->consumerType));
      $table['rows'][] = array(
        $mapping->mappingID,
        $mapping->sid,
        $mapping->description,
        $mapping->consumerModule,
        $consumer_name,
        ($mapping->status) ? t('Yes') : t('No'),
        $actions
      );
    }
  } else {
    $table['rows'] = array();
  }

  $table = theme_table($table);
  return  $table;

}


function theme_ldap_authorization_test_results($variables) {
  extract($variables);

  $table = array(
    'header' => array("Drupal Username", "Authorization Type", "Authorization IDs", "Mapping Configuration", "LDAP Server Configuration"),
    'attributes' => array('id' => 'ldap_authorization_authorizations', 'class' => 'data'),
    'colgroups' => array(),
    'sticky' => FALSE,
    'empty' => '',
    'caption' => t('LDAP Authorizations Test Results for consumer %consumer and mapping %mapping_id', array('%consumer' => $consumer->name,  '%mapping_id' => $mapping->description)),
  );
        
  if (count($data)) {
    foreach($data as $username => $result) {
      $row = array();
      if (isset($result['message'])) {
         $row = array($username, $result['message']);
      } else {
        foreach ($result as $consumer_type => $authorization) {
          foreach ($authorization as $mapping_id => $result) {
            $map_link = l($mapping->mappingID, LDAP_SERVERS_MENU_BASE_PATH .'/authorization/update/'. $mapping->mappingID);
            $server_link = l($mapping->sid, LDAP_SERVERS_MENU_BASE_PATH .'/servers/edit/'. $mapping->sid);
            if (is_array($result)) {
              $authorizations =  theme_item_list(array('items' => $result, 'title' => NULL, 'type' => 'ul', 'attributes' => array()));
            } else {
            
              switch ($result) {
                case  LDAP_AUTHORIZATION_USER_LDAP_NOT_FOUND:
                  $authorizations = "LDAP entry for drupal user not found.";
                break;
                case LDAP_AUTHORIZATION_USER_NOT_LDAP_AUTHENTICATED:
                  $authorizations = "LDAP Authorizations not applied because user is not authenticated via LDAP and authorization mapping configuration requires is ($map_link).";
                break;
                case LDAP_AUTHORIZATION_MAP_NOT_CONF_FOR_LOGON:
                  $authorizations = "LDAP Authorizations not configured to be executed on logon in  ($map_link).";
                break;
              }
            }
            if ($user = user_load_by_name($username)) {
              $username = l($username, 'user/'. $user->uid .'/edit');
            }

            $row = array($username, $consumer->name, $authorizations, $map_link, $server_link);
          }
        $table['rows'][] = $row;
        }
      }
    }
  } else {
    $table['rows'] = array();
  }
  
  $output = theme('table', $table);

  return $output;
  
  }

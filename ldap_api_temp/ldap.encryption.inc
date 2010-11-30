<?php
// $Id$

/**
 * @file 
 * Provides functions for encryption/decryption.
 *
 * http://stackoverflow.com/questions/2448256/php-mcrypt-encrypting-decrypting-file
 */

define('LDAP_API_MODE','CTR');
/**
 * Return a random salt of a given length for crypt-style passwords
 *
 * @param int length
 *   The requested length.
 *
 * @return string
 *   A (fairly) random salt of the requested length.
 *
 */
function ldap_api_random_salt( $length ) {
  $possible = '0123456789'.
              'abcdefghijklmnopqrstuvwxyz'.
              'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.
              './';
  $salt = "";

  mt_srand((double)microtime() * 1000000);
  while ( strlen( $salt ) < $length ) {
    $salt .= substr( $possible, ( rand() % strlen( $possible ) ), 1 );
  }

  return $salt;
}


function _ldap_api_encrypt_types($type = 'all') {

  $hashes = array();
  if (extension_loaded('mcrypt')) {  // only support with extension

    /**
      LDAP_API_ENC_TYPE_MD5C => 'MD5 Crypt',
      LDAP_API_ENC_TYPE_SALTED_MD5 => 'Salted MD5',
      LDAP_API_ENC_TYPE_SHA => 'SHA',
      LDAP_API_ENC_TYPE_SALTED_SHA => 'SHA Salted',
    );
    **/

   /** $encrypts = array(
      LDAP_API_ENC_TYPE_EXTENDED_DES => 'Extended DES',
      LDAP_API_ENC_TYPE_BLOWFISH => 'Blowfish',
      LDAP_API_ENC_TYPE_SALTED_CRYPT => 'Salted Crypt',
    ); **/

    $encrypts = array(
      LDAP_API_ENC_TYPE_CLEARTEXT => 'No Encyption',
      LDAP_API_ENC_TYPE_BLOWFISH => 'Blowfish'
      
    );
  }

 // $hashes[LDAP_API_ENC_TYPE_MD5] = 'MD5';
//  $encrypts[LDAP_API_ENC_TYPE_CRYPT] = 'Crypt';

  if ($type == 'encrypt') {
    return $encrypts;
  }

  if ($type == 'hash') {
    return $hashes;
  }

  return array_merge($hashes, $encrypts);


}
/**
 * Encrypt Password Method
 *
 * @param string clear_txt
 *   Plaintext password.
 *
 * @return string 
 *   Encrypted text, formatted for use as an LDAP password.
 *
 */

function _ldap_api_encrypt($clear_txt, $enc_type = NULL) {

  print "clear_txt = $clear_txt, enc_type = $enc_type";
  if (!$enc_type) {
    $enc_type = variable_get('ldap_api_encryption' , LDAP_API_ENC_TYPE_BLOWFISH);
  }
  if (! ($key = variable_get('LDAP_API_ENCRYPT_KEY', NULL))) {
    $key = ldap_api_random_salt(10);
    variable_set('LDAP_API_ENCRYPT_KEY', $key);
  }

  switch ($enc_type) {

    case LDAP_API_ENC_TYPE_BLOWFISH: // Blowfish
        print "blowfish";
        $td = mcrypt_module_open('blowfish', '', LDAP_API_CYPHER_MODE, '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $crypttext = mcrypt_generic($td, $clear_txt);
        mcrypt_generic_deinit($td);
        $cipher_txt = $iv.$crypttext;
      break;

    default: // Cleartext
      $cipher_txt = $clear_txt;
  }

  return base64_encode($cipher_txt);
}

/**
 * Encrypt Decrypt Method
 *
 * @param string $cipher_txt
 *   ciphered text.
 *
 * @return string
 *   clear text
 *
 * http://stackoverflow.com/questions/2448256/php-mcrypt-encrypting-decrypting-file
 */

function _ldap_api_decrypt($cipher_txt, $enc_type = NULL) {

  $key = variable_get('LDAP_API_ENCRYPT_KEY', NULL);

  if (!$enc_type) {
    $enc_type = variable_get('ldap_api_encryption' , LDAP_API_ENC_TYPE_BLOWFISH);
  }

  $cipher_txt = base64_decode($cipher_txt);
  switch ($enc_type) {

    case LDAP_API_ENC_TYPE_BLOWFISH: // Blowfish
      $clear_txt = "";
      $td        = mcrypt_module_open('blowfish', '', LDAP_API_CYPHER_MODE, '');
      $ivsize    = mcrypt_enc_get_iv_size($td);
      $iv        = substr($cipher_txt, 0, $ivsize);
      $cipher_txt = substr($cipher_txt, $ivsize);
      if ($iv) {
        mcrypt_generic_init($td, $key, $iv);
        $clear_txt = mdecrypt_generic($td, $cipher_txt);
      }

      break;

    default: // Cleartext
      $clear_txt = $cipher_txt;
  }
  return $clear_txt;
}

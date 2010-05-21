<?php
// $Id$

/**
 * @file 
 * Provides functions for encryption/decryption.
 *
 * @see http://datatracker.ietf.org/doc/rfc4513/
 */

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
function random_salt( $length ) {
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
function encrypt_password($clear_txt) {
  global $_ldapdata_ldap;
    
  switch ($_ldapdata_ldap->getOption('enc_type')) {
    case 1: // MD5
      $cipher_txt = '{MD5}' . base64_encode(pack('H*', md5($clear_txt)));
      break;

    case 2: // Crypt
      $cipher_txt = '{CRYPT}' . crypt($clear_txt, substr($clear_txt, 0, 2));
      break;

    case 3: // Salted Crypt
      $cipher_txt = '{CRYPT}' . crypt($clear_txt, random_salt(2));
      break;

    case 4: // Extended DES
      $cipher_txt = '{CRYPT}' . crypt($clear_txt, '_' . random_salt(8));
      break;

    case 5: // MD5Crypt
      $cipher_txt = '{CRYPT}' . crypt($clear_txt , '$1$' . random_salt(9));
      break;

    case 6: // Blowfish
      $cipher_txt = '{CRYPT}' . crypt($clear_txt , '$2a$12$' . random_salt(13));
      break;

    case 7: // Salted MD5
      mt_srand((double)microtime()*1000000);
      $salt = mhash_keygen_s2k(MHASH_MD5, $clear_txt, substr(pack("h*", md5(mt_rand())), 0, 8 ), 4 );
      $cipher_txt = "{SMD5}" . base64_encode(mhash(MHASH_MD5, $clear_txt . $salt) . $salt);
      break;

    case 8: // SHA
      if (function_exists('sha1')) {
        $cipher_txt = '{SHA}' . base64_encode(pack('H*', sha1($clear_txt)));
      }
      elseif (function_exists('mhash')) {
        $cipher_txt = '{SHA}' . base64_encode(mhash(MHASH_SHA1, $clear_txt));
      }
      break;

    case 9: // Salted SHA
      mt_srand((double)microtime()*1000000);
      $salt = mhash_keygen_s2k(MHASH_SHA1, $clear_txt, substr(pack("h*", md5(mt_rand())), 0, 8 ), 4);
      $cipher_txt = "{SSHA}" . base64_encode(mhash(MHASH_SHA1, $clear_txt . $salt) . $salt);
      break;

    default: // Cleartext
      $cipher_txt = $clear_txt;
  }

  return $cipher_txt;
}

// vim:fenc=utf-8:ft=php:ai:si:ts=2:sw=2:et:

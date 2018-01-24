<?php

class passwordHash {

    public static function unique_salt() {
        return substr(sha1(mt_rand()), 0, 22);
    }
    public static function hash($password) {
        return md5($password);
    }
    public static function check_password($hash, $password) {
        $new_hash = md5($password);
        return ($hash == $new_hash);
    }
    public static function generateRandomString() {
    $length = 8;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
    }

}

?>

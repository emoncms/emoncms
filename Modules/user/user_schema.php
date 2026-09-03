<?php

$schema['users'] = array(
    'id' => array('type' => 'int', 'Null'=>false, 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'username' => array('type' => 'varchar(30)'),
    'email' => array('type' => 'varchar(64)'),
    // Wide enough for either algorithm settings['password']['algo'] selects:
    // bcrypt is 60 characters, argon2id is 97. Sized for the larger so that
    // switching algorithm never needs a widening under a live migration, and so
    // an install that has run this update can take either. See Lib/password.php.
    'password' => array('type' => 'varchar(255)'),
    // Only used by rows still on the legacy sha256 format, which stored the
    // salt separately. bcrypt and argon2id both carry their own salt inside the
    // hash. Cleared as each account is upgraded on login, kept because
    // unupgraded rows still need it.
    'salt' => array('type' => 'varchar(32)'),
    'apikey_write' => array('type' => 'varchar(64)'),
    'apikey_read' => array('type' => 'varchar(64)'),
    'lastlogin' => array('type' => 'datetime'),
    // Via username & password login (0: no access, 1: read access, 2: write access)
    'access' => array('type' => 'int(11)', 'default'=>2),
    'admin' => array('type' => 'int', 'Null'=>false),

    // User profile fields
    'gravatar' => array('type' => 'varchar(30)', 'default'=>''),
    'name'=>array('type'=>'varchar(30)', 'default'=>''),
    'location'=>array('type'=>'varchar(30)', 'default'=>''),
    'timezone' => array('type'=>'varchar(64)', 'default'=>'UTC'),
    'language' => array('type' => 'varchar(5)', 'default'=>'en_EN'),
    'bio' => array('type' => 'text'),

    'tags' => array('type' => 'text'),
    'startingpage' => array('type'=>'varchar(64)', 'default'=>'feed/list'),
    'email_verified' => array('type' => 'int', 'default'=>0),
    'verification_key' => array('type' => 'varchar(64)', 'default'=>''),

    // Password reset. The emailed token itself is never stored, only its
    // sha256 hash, so a leak of this table does not hand over live reset
    // links. Cleared on use, and ignored once password_reset_expires passes.
    'password_reset_hash' => array('type' => 'varchar(64)', 'default'=>''),
    'password_reset_expires' => array('type' => 'int(11)', 'default'=>0),
    'uuid' => array('type' => 'varchar(36)', 'default'=>''),

    'lastactive'=> array('type' => 'int(11)'),
    'feeds'=> array('type' => 'int(11)'),
    'activefeeds'=> array('type' => 'int(11)'),
    'diskuse' => array('type' => 'bigint(20)')
);

// Indexed because every request carrying a remember me cookie looks a row up by
// (userid, persistentToken), and every logout and revocation deletes by userid.
// Unindexed this was a full scan of the whole table on each such request.
$schema['rememberme'] = array(
    'userid' => array('type' => 'int', 'Index'=>true),
    'token' => array('type' => 'varchar(64)'),
    'persistentToken' => array('type' => 'varchar(64)', 'Index'=>true),
    'expire' => array('type' => 'datetime')
);

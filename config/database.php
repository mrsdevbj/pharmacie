<?php
return [
 'driver'=>getenv('DB_DRIVER') ?: 'sqlite',
 'path'=>getenv('DB_PATH') ?: APP_ROOT.'/storage/database/olg.sqlite',
 'host'=>getenv('DB_HOST') ?: 'localhost', 'port'=>getenv('DB_PORT') ?: '3306',
 'name'=>getenv('DB_NAME') ?: 'olg', 'user'=>getenv('DB_USER') ?: '', 'password'=>getenv('DB_PASSWORD') ?: '',
];

<?php

if (php_sapi_name() !== 'cli-server') {
    die('this is only for the php development server');
}

if (is_file($_SERVER['DOCUMENT_ROOT'].'/'.$_SERVER['SCRIPT_NAME'])) {
    return false;
}

require 'index.php';

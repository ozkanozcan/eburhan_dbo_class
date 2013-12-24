<?php
    require_once 'dbo.class.php';
    $opt = require 'dbo.opt.php';
    $dbo = DBO::getInstance();
    $dbo->setOpt($opt);
    $dbo->connect_db();
    unset($opt);

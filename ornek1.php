<?php

// sınıfı ve seçenekleri yükle
require_once 'dbo.class.php';
$opt = require 'dbo.opt.php';

// sınıfı kullanıma hazırla
$dbo = DBO::getInstance();
$dbo->setOpt($opt);
$dbo->connect_db();

// sorgu gönder
$dbo->setSql('SELECT * FROM kitaplar WHERE basimyili = %u');
$dbo->setArg('2007');
$dbo->runSql();

$dbo->dump( $dbo->getAll() );
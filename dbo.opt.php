<?php
return array
(
    // VERİTABANI İLE İLGİLİ AYARLAR
    'dbase' => array
    (
        'type' => 'mysqli',
        'conf' => array(
            'name' => 'msn',
            'user' => 'root',
            'pass' => '',
            'open' => false,
        )
    ),

    // ÖNBELLEKLEME İLE İLGİLİ AYARLAR
    'cache' => array
    (
        'type' => 'xcache',             // önbellek adaptörü
        'time' => 10,                 // önbellek zamanı (saniye)
        'rows' => 0,                  // satır limiti
        'conf' => array(
            'user' => 'admin',
            'pass' => '12345'
        )
    ),

    // HATALAR İLE İLGİLİ AYARLAR
    'error' => array
    (
        'path' => './log/error/',       // hatalar nereye kaydedilsini?
        'save' => true,                 // hatalar kaydedilsin mi?
        'show' => true,                 // hatalar ekranda gösterilsin mi?
        'exit' => true                  // hata gösterildikten sonra programdan çıkılsın mı?
    ),

    // YAKALAMA MODU ile ilgili ayarlar
    'fetch' => 'obj'                    // varsayılan yakalama modu (obj, arr, num)
);
<?php

require_once 'Dbase/Exception.php';
require_once 'Dbase/Abstract.php';

/**
 * eburhan Dbase class
 * 
 * @package eburhan DBO class
 * @author eburhan
 * @version 2010
 * @access public
 */
class Dbase
{
    const DS = DIRECTORY_SEPARATOR;
    private static $_instances = array();

    /**
     * Dbase::__construct()
     * 
     * @access private
     * @return void
     */
    private function __construct(){}


    /**
     * Dbase::factory()
     * 
     * @param string wrapper ismi
     * @param array wrapper ayarları
     * @return Dbase
     */
    public static function factory($wrapper = 'mysql', array $conf = array())
    {
        // daha önceden bir örneği varsa...
        if (array_key_exists($wrapper, self::$_instances)) {
            return self::$_instances[$wrapper];
        }

        // örneği oluşturulacak sınıfın dosyasını çağır
        $class = 'Dbase_Wrapper_' . ucfirst(strtolower($wrapper));
        $path  = str_replace('_', self::DS, $class);
        $path  = dirname(__FILE__) . self::DS . $path . '.php';

        if (! file_exists($path)) {
            throw new Dbase_Exception('Invalid Wrapper Path');
        }

        // yeni bir örnek oluştur
        require_once ($path);
        $instance = new $class($conf);
        self::$_instances[$wrapper] = $instance;

        return $instance;
    }

} //sınıf sonu
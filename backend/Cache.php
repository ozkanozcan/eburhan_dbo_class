<?php
require_once 'Cache/Exception.php';
require_once 'Cache/Adapter.php';

/**
 * Cache
 * 
 * önbellekleme sınıfı
 * 
 * @package eb.Cache
 * @author Erhan BURHAN <www.eburhan.com>
 * @copyright 2010
 * @version v1.1
 * @access public
 */
class Cache
{
    const DS = DIRECTORY_SEPARATOR;
    private static $_instances = array();

    private function __construct(){}

    /**
     * istenilen adaptörden bir örnek oluşturur ve ayarlarını set eder
     *
     * @param string $adapter
     * @param array $conf
     * @return Cache_Adapter
     */
    public static function factory($adapter = 'file', array $conf = array())
    {
        // daha önceden bir örneği varsa...
        if (array_key_exists($adapter, self::$_instances))
            return self::$_instances[$adapter];

        // örneği oluşturulacak sınıfın dosyasını çağır
        $class = 'Cache_Adapter_' . ucfirst(strtolower($adapter));
        $path  = str_replace('_', self::DS, $class);
        $path  = dirname(__FILE__) . self::DS . $path . '.php';

        if (!file_exists($path)) {
            throw new Cache_Exception('Invalid Adapter Path');
        }

        // yeni bir örnek oluştur
        require_once ($path);
        $obj = new $class($conf);
        self::$_instances[$adapter] = $obj;

        return $obj;
    }

} //sınıf sonu

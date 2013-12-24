<?php
/**
 * eburhan Log class
 * 
 * ilişkisel dizi olarak kendisine set edilen
 * verileri herhangi bir dosyaya kaydeden sınıf
 *
 * örnek kullanım:
 *      $log = Log::getInstance();
 *      $log->setPath('./logs/');
 *      $log->setData(array(
 *          'isim' => 'Erhan',
 *          'yas' => 25
 *      ));
 *      $log->save();
 *
 * @package eburhan DBO class
 * @author eburhan
 * @copyright 2010
 * @version v1.0
 * @access public
 */
class Log
{
    /**
     * verilerin loglanacağı dosyanın yolu
     * @var string
     */
    private $path = './log/error/';
    
    /**
     * loglanacak olan veriler
     * @var array
     */
    private $data = array();

    /**
     * sınıfın o anki örneği
     * @var Log
     */
    private static $instance = null;


    // kullanılmasını istemediğimiz metotlar
    private function __construct() {}
    private function __clone() {}


    /**
     * Log::getInstance()
     * 
     * sınıfın yeni bir örneğini oluştur. 
     * daha önceden oluşturulmuşsa geri döndürür
     * 
     * @return Log
     */
    public static function getInstance()
    {
        if( self::$instance === null ){
            self::$instance = new Log();
        }

        return self::$instance;
    }

    /**
     * Log::setPath()
     * 
     * verilerin loglanacağı dosyanın tam yolu
     * 
     * @param string $path verilerin loglanacağı dosya yolu
     * @return Log
     */
    public function setPath($path = null)
    {
        if( is_dir(dirname($path)) ) {
            $this->path = $path;
        }

        return $this;
    }

    /**
     * Log::setData()
     * 
     * loglanacak olan veriler
     * 
     * @example array('key' => 'value');
     * @param array $data loglanacak veriler
     * @return Log
     */
    public function setData(array $data = array())
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Log::save()
     * 
     * loglama işlemini başlatan metot
     * bu metot çağrılmazsa loglama yapılmaz!
     * 
     * @return boolean
     */
    public function save()
    {
        $data = array();
        foreach($this->data as $key => $val){
            $data[] = sprintf("%s:\t%s", $key, $val);
        }
        $data = join(PHP_EOL, $data).PHP_EOL.PHP_EOL;
        return (bool) file_put_contents($this->path, $data, FILE_APPEND | LOCK_EX);
    }

} //sınıf sonu
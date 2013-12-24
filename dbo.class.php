<?php

require_once 'backend/Config.php';
require_once 'backend/Dbase.php';
require_once 'backend/Cache.php';
require_once 'backend/Log.php';

/**
 * DBO
 * 
 * veritabanına kolayca sorgular gönderip
 * sonuçlarını alabilmenizi sağlayan sınıf
 * 
 * @author eburhan
 * @copyright Gnu General Public License V2
 * @version 2010
 * @access public
 */
final class DBO
{
    // sınıf içi sabitler
    const DS = DIRECTORY_SEPARATOR;
    const DF = 'd.m.Y H:i:s'; // date format

    // instance tutan özellikler
    private static $instance = null;
    private $config = null;
    private $dbase = null;
    private $cache = null;
    private $log = null;

    // sql ve hata tutan özellikler
    private $sql = null;
    private $err = array();

    // sorgu ile ilgili özellikler
    private $queryResult = null;
    private $querySource = null;
    private $queryCount = 0;
    private $queryTime = 0;
    private $queryDate = 0;

    // sorgu sonucu ile ilgili özellikler
    private $insertID = 0;
    private $numRows = 0;
    private $affRows = 0;
    
    // diğer gerekli değişkenler
    private $cacheUniq = null;
    private $debugBack = false;

    //--------------------------------------------------------------------------
    //    Sınıfı hazırlama & Seçenek belirleme
    //--------------------------------------------------------------------------
    /**
     * kurucu metotdur. sınıf varsayılanlarını atar
     *
     * @access private
     */
    private function __construct() {
        // gerekli olan nesneleri ilklendir
        $this->config = Config::getInstance();
        $this->log = Log::getInstance();

        // DEBUG fonksiyonu kullanılabilir mi?
        $this->debugBack = function_exists('debug_backtrace');
    }

    // objenin kopyası çıkarılamasın
    private function __clone() {}
    private function __wakeup() {}

    /**
     * Sınıfın sadece tek bir örneğinin olmasını sağlar.
     * fonksiyonlar içinden erişim için de kullanılabilir
     *
     * @access public
     * @return DBO
     */
    public static function getInstance() {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * O anki veritabanı bağlantını verir
     *
     * @access public
     * @return resource
     */
    public function getLink() {
        return $this->dbase->getLink();
    }

    /**
     * sınıfın seçeneklerini alır
     *
     * @access public
     * @param string seçenek ismi
     * @return mixed
     */
    public function getOpt($opt = null) {
        return $this->config->get($opt);
    }

    /**
     * sınıfın seçeneklerini değiştir
     *
     * @access public
     * @param string seçenek ismi
     * @param string seçenek değeri
     * @return DBO
     */
    public function setOpt($key = null, $val = null) {
        // en son ayarları belirle
        $this->config->set($key, $val);

        // güncel ayarları al
        $conf = $this->config->get();

        // güncel ayarlara göre Dbase ve Cache ayarlarını güncelleştir
        $dbase = $conf['dbase'];
        $cache = $conf['cache'];
        $this->dbase = Dbase::factory($dbase['type'], $dbase['conf']);
        $this->cache = Cache::factory($cache['type'], $cache['conf']);

        return $this;
    }

    //--------------------------------------------------------------------------
    //    Bağlantı açma & kapama
    //--------------------------------------------------------------------------
    /**
     * veritabanı bağlantısı açar
     *
     * @access public
     * @return DBO
     */
    public function connect_db() {
        // güncel veritabanı ayarları al ve Dbase sınıfına aktar
        // NOT: bunu yapmazak eğer, kullanıcı DBO::setOpt() ile
        //      değiştirdiği ayarlarla yeni bir bağlantı açmak
        //      istediğinde hata alırız. ayarlar geçersiz olur
        $conf = $this->config->get('dbase.conf');
        $conf = array_merge($conf, $this->dbase->getConf());

        // elde edilen en son ayarları güncelle
        $this->dbase->setConf($conf);
        $this->config->set('dbase.conf', $conf);

        // veritabanına bağlanmaya çalış
        if (!$this->dbase->connect()) {
            $this->_errCompile(__LINE__, __FUNCTION__, $this->dbase->error());
            $this->_errControl();
            return false;
        }

        return $this;
    }

    /**
     * farklı bir veritabanı seçer
     *
     * @access public
     * @param string veritabanı adı
     * @return DBO
     */
    public function select_db($name) {
        if (!is_string($name) || empty($name))
            throw new Exception('The database name must be a string!');

        // Veritabanı İsmi ayarını yenisiyle yer değiştir
        $this->config->set('dbase.conf.name', $name);

        if (!$this->dbase->select_db($name)) {
            $this->_errCompile(__LINE__, __FUNCTION__, $this->dbase->error());
            $this->_errControl();
            return false;
        }

        return $this;
    }

    /**
     * veritabanı bağlantısını kapatır
     *
     * @access public
     * @return void
     */
    public function __destruct() {
        if ($this->dbase)
            $this->dbase->close();
    }

    //---------------------------------------------------------------------------
    //    Sorgu oluşturma
    //---------------------------------------------------------------------------
    /**
     * SQL cümleciğini belirler
     *
     * @access public
     * @param string SQL cümleciği
     * @return DBO
     */
    public function setSql($sql) {
        // sql cümlesindeki gereksiz boşlukları ve sekmeleri temizle
        $this->sql = preg_replace('/\s\s+|\t\t+/', ' ', trim($sql));

        return $this;
    }

    /**
     * SQL cümleciğindeki argümanları alır ve temizler
     *
     * @access public
     * @return DBO
     */
    public function setArg() {
        // argümanları al
        $args = func_get_args();
        $args = is_array($args[0]) ? $args[0] : $args;

        // argümanların herbirini temizlenmeye gönder :)
        $args = array_map(array($this, '_clean'), $args);

        // temizlenmiş argümanları %s ile değiştir
        $this->sql = vsprintf($this->sql, $args);

        return $this;
    }

    /**
     * SQL cümleciğine göre sorgu çalıştırır
     *
     * @access public
     * @param integer cache süresi (saniye olarak)
     * @param integer cache limiti (pozitif tamsayı)
     * @return DBO
     */
    public function runSql($time = 0, $rows = 0) {
        // buraya kadar bir hata oluştuysa...
        if (!empty($this->err) > 0)
            throw new Exception('Could not run sql, because there are problems');

        // cache seçeneklerinini al
        $cache = (object) $this->config->get('cache');

        // benzersiz cache anahtarını oluştur
        $this->cacheUniq = $this->_uniqCacheKey();

        // veriyi ilk önce önbellekte arıyoruz
        $this->queryResult = $this->_readFromCache();

        // önbellekte herhangi bir sonuç yoksa
        if (empty($this->queryResult)) {
            // herşey tamamsa veritabanından okuma yap
            $this->queryResult = $this->_readFromDbase();
            $this->querySource = 'dbase';

            // sorgu sonucu FALSE değilse önbelleğe kaydetmeye çalış
            if ($this->queryResult) {
                $time = (is_int($time) && $time > 0) ? $time : $cache->time;
                $rows = (is_int($rows) && $rows > 0) ? $rows : $cache->rows;
                $this->_saveToCache($time, $rows);
            }
        } else {
            $this->querySource = 'cache';
        }

        return $this;
    }

    //--------------------------------------------------------------------------
    //    Sorgu sonucunu alma
    //--------------------------------------------------------------------------
    /**
     * Son çalıştırılan sorgunun başarılı olup olmadığını söyler
     *
     * @access public
     * @return boolean
     */
    public function result() {
        return ($this->queryResult === false) ? false : true;
    }

    /**
     * Sorgu sonucunda elde edilen bütün verileri alır
     *
     * @access public
     * @param string veri alma modu (obj, arr, num)
     * @return mixed
     */
    public function getAll($mod = null) {
        // sorgu boşsa veya sonuç olarak geriye bir Array/Object dönmediyse
        if (empty($this->queryResult) || is_scalar($this->queryResult)) {
            return array();
        }

        // dışarıdan gelen mod geçerli değilse, varsayılanı kullan
        if ($this->_validFetchMode($mod) === false) {
            $mod = $this->config->get('fetch');
        }

        // nesne
        if ($mod === 'obj') {
            $func = create_function('&$val', 'return (object) $val;');
            return array_map($func, $this->queryResult);
        }
        // dizi
        if ($mod === 'arr')
            return $this->queryResult;
        // numaralandırılmış dizi
        if ($mod === 'num')
            return array_map('array_values', $this->queryResult);
    }

    /**
     * Tek bir satırdaki bütün verileri alır
     *
     * @access public
     * @param integer birden fazla satır geriye döndüyse kaçıncı satır alınacak?
     * @param string veri alma modu (obj, arr, num)
     * @return mixed
     */
    public function getRow($sno = 1, $mod = null) {
        // sorgu boşsa veya sonuç olarak geriye bir Array/Object dönmediyse
        if (empty($this->queryResult) || is_scalar($this->queryResult)) {
            return array();
        }

        // diziler 0'dan başladığı için 1 eksilt. böylece,
        // kullanıcı 1 girdiğinde dizinin 0. elemanı gelecek
        $sno -= 1;

        // dışarıdan gelen mod geçerli değilse, varsayılanı kullan
        if ($this->_validFetchMode($mod) === false) {
            $mod = $this->config->get('fetch');
        }

        // satır numarası, dizi limitleri dışına çıkmamalı
        if (!is_int($sno) || $sno < 0)
            return array();
        if ($sno >= $this->numRows)
            return array();

        // numaralandırılmış dizi
        if ($mod === 'num')
            return array_values($this->queryResult[$sno]);
        // dizi
        if ($mod === 'arr')
            return $this->queryResult[$sno];
        // nesne
        if ($mod === 'obj')
            return (object) $this->queryResult[$sno];
    }

    /**
     * Yalnızca bir tek veri alır. alınacak veri yoksa NULL geri döndürür
     *
     * @access public
     * @return mixed
     */
    public function getOne() {
        // sorgu boşsa veya sonuç olarak geriye bir Array/Object dönmediyse
        if (empty($this->queryResult) || is_scalar($this->queryResult)) {
            return NULL;
        }

        $arr = array_values($this->queryResult[0]);
        return $arr[0];
    }

    /**
     * SQL cümleciğinin en son halini verir
     *
     * @access public
     * @return string SQL cümleciğinin son hali
     */
    public function getSql() {
        return $this->sql;
    }

    //--------------------------------------------------------------------------
    //    İşlem sonucunu alma
    //--------------------------------------------------------------------------
    /**
     * Son sorgudan, tablodaki kaç satırın etkilendiğini verir
     *
     * @access public
     * @return integer
     */
    public function affRows() {
        return $this->affRows;
    }

    /**
     * Son sorgudan sonra elde edilen satır sayısı
     *
     * @access public
     * @return integer
     */
    public function numRows() {
        return $this->numRows;
    }

    /**
     * En son eklenen verinin ID'si
     *
     * @access public
     * @return integer
     */
    public function insertID() {
        return $this->insertID;
    }

    /**
     * Toplam sorgu sayısını verir
     *
     * @access public
     * @return integer
     */
    public function queryCount() {
        return $this->queryCount;
    }

    /**
     * Son sorgu için harcanan süre
     *
     * @access public
     * @return integer
     */
    public function queryTime() {
        return $this->queryTime;
    }

    //--------------------------------------------------------------------------
    //    Hata işleme & Bilgi alma
    //--------------------------------------------------------------------------
    /**
     * sınıf içerisinde kullanılan değişkenlerin bilgilerini verir
     *
     * @access public
     * @return object
     */
    public function giveInfo() {
        $config = $this->config;

        return array(
            'dbase' => array(
                'type' => $config->get('dbase.type'),
                'name' => $config->get('dbase.conf.name'),
                'user' => $config->get('dbase.conf.user'),
                'host' => $config->get('dbase.conf.host'),
            ),
            'query' => array(
                'time' => $this->queryTime,
                'date' => $this->queryDate,
                'count' => $this->queryCount,
                'source' => $this->querySource,
                'numRows' => $this->numRows,
                'affRows' => $this->affRows,
                'insertID' => $this->insertID,
            ),
            'cache' => $config->get('cache'),
            'last_err' => count($this->err) > 0 ? $this->err[0]['fail'] : null,
            'last_sql' => $this->sql,
            'fetchMode' => $config->get('fetch'),
            'result(0)' => isset($this->queryResult[0]) ? $this->queryResult[0] : null
        );
    }

    /**
     * sınıf içerisinde kullanılan değişkenlerin bilgilerini ekrana yazdırır
     *
     * @access public
     * @param boolean programdan çıkılsın mı?
     */
    public function dumpInfo($exit = true) {
        $this->dump($this->giveInfo());
        if ($exit) exit();
    }

    /**
     * Herhangi bir işlem sonucunu, formatlı bir şekilde ekrana yazdırır
     *
     * @access public
     * @param mixed yazdırılacak veri
     */
    public function dump($veri = null) {
        print '<pre>';
        print_r($veri);
        print '</pre>';
    }

    //--------------------------------------------------------------------------
    //    Yardımcı fonksiyonlar
    //--------------------------------------------------------------------------
    /**
     * oluşan hataları derler
     *
     * @access private
     * @param string line
     * @param string func
     * @param string fail
     */
    private function _errCompile($line, $func, $fail) {
        // debug_backtrace() fonksiyonu varsa
        if ($this->debugBack) {
            // hatayı oluştur
            $errPre = debug_backtrace();
            $errEnd = array();

            foreach ($errPre as $err) {
                // 'class' anahtarı yoksa diğer hataya geç
                if (!isset($err['class']))
                    continue;

                // oluşan hatanın sebebi bu class mı?
                if ($err['class'] === __CLASS__) {
                    array_push($errEnd, $err);
                }
            }

            // hatanın en son oluştuğu yerle ilgili bilgiler
            $errEnd = end($errEnd);
        } else {
            $errEnd = array();
            $errEnd['file'] = $this->_phpSelf();
            $errEnd['line'] = $line;
            $errEnd['function'] = $func;
        }

        array_push($this->err, array(
            'file' => $errEnd['file'],
            'line' => $errEnd['line'],
            'func' => __CLASS__ . '::' . $errEnd['function'],
            'fail' => $fail, // oluşan hata mesajı
            'sqlc' => $this->sql
        ));
    }

    /**
     * oluşan hataların kaydedilmesi ve gösterilmesi işlemlerini kontrol eder
     *
     * @access private
     */
    private function _errControl() {
        // hata seçeneklerini ve oluşan hatayı al
        $opt = (object) $this->config->get('error');
        $err = count($this->err) > 0 ? $this->err[0] : null;

        // ortada bir hata yoksa geri dön
        if (is_null($err)) return;

        // hataları dosyaya kaydet
        if ($opt->save) {
            $data = array(
                'name' => $this->dbase->getConf('name'),
                'fail' => $err['fail'],
                'func' => $err['func'],
                'line' => $err['line'],
                'file' => $err['file'],
                'time' => date(self::DF)
            );

            // log dosyasının tam adı (yol + isim)
            $path = ($opt->path) . date('d-m-Y') . '.error';

            $this->log->setPath($path)->setData($data)->save();
        }

        // hataları ekranda göster
        if ($opt->show) {
            printf('<pre class="dbo_error">' . PHP_EOL .
                    '<strong>DATABASE ERROR</strong>' . PHP_EOL .
                    'file : %s' . PHP_EOL .
                    'line : %u' . PHP_EOL .
                    'fail : %s' . PHP_EOL .
                    '</pre>%s',
                    $err['file'], $err['line'], $err['fail'], PHP_EOL
            );

            if ($opt->exit) exit();
        }
    }

    /**
     * veritabanına sorgu gönderip sonuçlarını değerlendirir
     *
     * @access private
     * @return mixed
     */
    private function _readFromDbase() {
        // veritabanı nesnemiz
        $dbase = $this->dbase;

        // veritabanı bağlantısı başlatılmamışsa...
        if (!$dbase->getLink()) {
            $this->_errCompile(__LINE__, __FUNCTION__, 'Could not connect to database');
            $this->_errControl();
            return false;
        }

        // sorguyu gerçekleştir
        $prev = microtime(true);
        $query = $dbase->query($this->sql);
        $next = microtime(true);

        // sorgu istatistikleri
        $this->queryTime = number_format(($next - $prev), 20);
        $this->queryDate = date(self::DF);
        $this->queryCount++;

        // bir önceki sorgunun bazı bilgilerini resetle
        $this->numRows = $this->insertID = $this->affRows = 0;

        // 1. sorgu başarısız ise
        if ($query === false) {
            $this->_errCompile(__LINE__, __FUNCTION__, $dbase->error());
            $this->_errControl();
            return false;
        }


        // 2. sorgu başarılı ama geriye bir sonuç döndürmüyorsa
        // INSERT, UPDATE, DELETE veya REPLACE türündeki sorgular
        if ($query === true) {
            $this->insertID = $dbase->insert_id();
            $this->affRows = $dbase->affected_rows();
            return true;
        }

        // 3. sorgu başarılı ve geriye bir sonuç döndürdüyse
        // SELECT veya SHOW türündeki sorgular
        $rows = array();
        while ($row = $dbase->fetch_assoc($query)) {
            $rows[] = $row;
        }

        $this->numRows = count($rows);
        $dbase->free_result($query);
        return $rows;
    }

    /**
     * önbellekten okuma yapar
     *
     * @access private
     * @return string
     */
    private function _readFromCache() {
        $prev = microtime(true);
        $data = $this->cache->get($this->cacheUniq);
        $next = microtime(true);

        // sorgu istatistikleri
        $this->queryTime = number_format(($next - $prev), 20);
        $this->queryDate = date(self::DF);
        $this->numRows   = count($data);

        return $data;
    }

    /**
     * önbelleğe veri kaydeder
     * @param integer maksimum süre
     * @param integer maksimum satır sayısı
     * @access private
     */
    private function _saveToCache($time, $rows) {
        // eğer SELECT ve SHOW dışında bir sorgu yapıldıysa cache yapılamaz!
        if (!preg_match('/^(select|show)\s/i', $this->sql)) return;

        // zaman ve satır satısı 0 ise cache özelliği kapalı demektir
        if ($time === 0 && $rows === 0) return;

        // bu anahtarda bir önbellek kaydı varsa önbelleğe alma (güvenlik için)
        if ($this->cache->is_set($this->cacheUniq)) return;

        // 'numRows' değeri ancak 'cache rows' değerinden büyükse cache yap
        if ($rows === 0 || ($rows <= $this->numRows)) {
            // önbelleğe ARRAY olarak kaydediyoruz. yoksa sorun çıkabiliyor
            $this->cache->set($this->cacheUniq, $this->queryResult, $time);
        }
    }

    /**
     * benzersiz bir önbellek anahtarını oluşturur
     *
     * @access private
     */
    private function _uniqCacheKey() {
        $dbase = $this->config->get('dbase');
        $type = $dbase['type'];
        $name = $dbase['conf']['name'];

        return md5($type . $name . $this->sql);
    }

    /**
     * Zararlı olabilecek verileri temizler
     *
     * @access private
     * @param string temizlenecek veri
     * @return mixed
     */
    private function _clean($data) {
        if (is_null($data)) return 'NULL';
        if (is_numeric($data)) return $data;

        if (get_magic_quotes_gpc()) {
            $data = stripslashes($data);
        }

        $data = $this->dbase->escape_string($data);

        return "'$data'";
    }

    /**
     * girilen yakalama modunun geçerli olup olmadığını söyler
     *
     * @access private
     * @return string yakalam modu (obj,arr,num)
     */
    private function _validFetchMode($mode) {
        if ($mode === null) return false;

        return in_array(
                strtolower($mode),
                array('obj', 'arr', 'num')
        );
    }

    /**
     * bu sınıfı o anda hangi sayfanın kullandığını belirler
     *
     * @access private
     * @return string geçerli sayfanın yolu
     */
    private function _phpSelf() {
        $path = strip_tags($_SERVER['PHP_SELF']);
        $path = substr($path, 0, 255);
        $path = htmlentities(trim($path), ENT_QUOTES, 'UTF-8');

        return $path;
    }

}

// DBO sınıfının sonu

<?php
class Cache_Adapter_Xcache extends Cache_Adapter
{
    // http://xcache.lighttpd.net/wiki/XcacheApi

    public function __construct(Array $opt)
    {
        if( ! extension_loaded('xcache') )
            throw new Cache_Exception('Xcache plugin not installed');

        // varsayılan seçenekler
        $def = array(
            'user' => null,
            'pass' => null
        );

        // seçenekleri birleştir
        $this->_mergeOptions($def, $opt);
    }


    // abstract metotları implement et ------------------------------ \\
    public function set($key, $data, $time = null)
    {
        if (is_null($time))
            return xcache_set($key, $data);

        return xcache_set($key, $data, $time);
    }

    public function get($key)
    {
        return xcache_get($key);
    }

    public function inc($key, $amount = 1)
    {
        return xcache_inc($key, $amount);
    }

    public function dec($key, $amount = 1)
    {
        return xcache_dec($key, $amount);
    }

    public function is_set($key)
    {
        return xcache_isset($key);
    }

    public function remove($key)
    {
        return xcache_unset($key);
    }


    /**
     * XCACHE, önbelleğindeki tüm verileri tek hamlede silebilmek için
     * authentication denilen bir kimlik doğrulaması gerektirmektedir.
     *
     * Program çalışıyorken birden bire ziyaretçilerin önüne kullanıcı adı
     * ve parola isteyen bir pencere çıkartmamak için Xcache adaptörünün
     * ayarlarında 'user' ve 'pass' değerleri girilmiş olmalıdır.
     */
    public function flush()
    {
        // yedeklemek amacıyla...
        $authUser = $authPass = null;

        // şu anda AUTH kullanıcısı ve parolası tanımlı mı?
        $issetAuthUser = isset($_SERVER['PHP_AUTH_USER']);
        $issetAuthPass = isset($_SERVER['PHP_AUTH_PW']);

        // şu an geçerli olan AUTH kullanıcısı ve parolası var mı?
        if ($issetAuthUser)   $authUser = $_SERVER['PHP_AUTH_USER'];
        if ($issetAuthPass)   $authPass = $_SERVER['PHP_AUTH_PW'];

        // Ayarlar içinde kullanıcı ve parola belirtilmişse onu kullan
        if (! $issetAuthUser && $this->user !== null){
            $_SERVER['PHP_AUTH_USER'] = $this->user;
        }
        if (! $issetAuthPass && $this->pass !== null){
            $_SERVER['PHP_AUTH_PW'] = $this->pass;
        }

        // önbellekteki bütün verileri uçur :)
        xcache_clear_cache(XC_TYPE_VAR, 0);

        // AUTH için önceki kullanıcı adı ve şifresini geri yükle...
        if ($issetAuthUser)   $_SERVER['PHP_AUTH_USER'] = $authUser;
        if ($issetAuthPass)   $_SERVER['PHP_AUTH_PW'] = $authPass;
    }

} // sınıf sonu
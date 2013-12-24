<?php

class Cache_Adapter_Memcache extends Cache_Adapter
{
    // memcache objesini tutan değişken
    private $memcache = null;

    function __construct(Array $opt)
    {
        // memcache eklentisi yüklü mü acaba?
        if (! extension_loaded('memcache'))
            throw new Cache_Exception('memcache plugin not installed');

        // varsayılan seçenekler
        $def = array(
            'servers' => array(
                // 1 numaralı server
                array(
                    'host' => 'localhost',
                    'port' => 11211,
                    'status' => true,
                    'weight' => 1,
                    'timeout' => 1, 
                    'persistent' => false, 
                    'retry_interval' => 15,
                    'failure_callback' => null, 
                )
            ),
            'compress' => 0 // data'lar sıkıştırılsın mı?
        );

        // seçenekleri birleştir
        $this->_mergeOptions($def, $opt);

        // yeni bir Memcache objesi oluştur
        $this->memcache = new Memcache;

        // kullanmak istediğimiz sunuculara bağlant
        $this->_connectToServers();
    }


    // Sadece bu sınıfa özel metotlar ------------------------------- \\
    private function _connectToServers()
    {
        foreach ($this->servers as $serv) {
            if (!$this->_addServer($serv)) {
                $msg = 'Could not connect to memcache server (%s:%u)';
                $msg = sprintf($msg, $serv['host'], $serv['port']);
                throw new Cache_Exception($msg);
            }
        }
    }

    private function _addServer(array $serv)
    {
        return $this->memcache->addServer(
            $serv['host'], 
            $serv['port'], 
            $serv['persistent'],
            $serv['weight'],
            $serv['timeout'],
            $serv['retry_interval'],
            $serv['status'], 
            $serv['failure_callback']
        );
    }    


    // abstract metotları implement et ------------------------------ \\
    public function set($key, $data, $time = null)
    {
        $time = (int)$time;

        // en fazla 30 günlüğüne önbelleğe alınabilir
        if ($time > 2592000) {
            $time = 2592000;
        }

        return $this->memcache->set($key, $data, $this->compress, $time);
    }

    public function get($key)
    {
        return $this->memcache->get($key);
    }

    public function inc($key, $amount = 1)
    {
        // Returns new item's value on success or FALSE on failure.
        return $this->memcache->increment($key, $amount);
    }

    public function dec($key, $amount = 1)
    {
        return $this->memcache->decrement($key, $amount);
    }

    public function is_set($key)
    {
        if (!$this->get($key))
            return false;

        return true;
    }

    public function remove($key)
    {
        return $this->memcache->delete($key, 0);
    }

    public function flush()
    {
        $this->memcache->flush();
    }


    function __destruct()
    {
        $this->memcache->close();
    }

} // sınıf sonu

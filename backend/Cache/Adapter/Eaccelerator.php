<?php

class Cache_Adapter_Eaccelerator extends Cache_Adapter
{
    public function __construct($kulOptions)
    {
        if( ! extension_loaded('eaccelerator') )
            throw new Cache_Exception('Eaccelerator plugin not installed');
    }


    // abstract metotları implemente et ----------------------------- \\
    // time = saniye
    public function set($key, $data, $time = null)
    {
        return eaccelerator_put($key, $data, $time);
    }

    public function get($key)
    {
        return eaccelerator_get($key);
    }

    public function inc($key, $amount = 1)
    {
        eaccelerator_lock($key);
        eaccelerator_put($key, (eaccelerator_get($key) + $amount));
        eaccelerator_unlock($key);        
    }

    public function dec($key, $amount = 1)
    {
        eaccelerator_lock($key);
        eaccelerator_put($key, (eaccelerator_get($key) - $amount));
        eaccelerator_unlock($key);
    }

    public function is_set($key)
    {
        if (eaccelerator_get($key) !== null)
            return false;

        return true;
    }

    public function remove($key)
    {
        return eaccelerator_rm($key);
    }

    public function flush()
    {
        eaccelerator_clean();
    }


    // Yardımcı metotlar -------------------------------------------- \\
    function __destruct()
    {
        // sona ermiş keyleri bellekten sil
        eaccelerator_gc();
    }

} // sınıf sonu
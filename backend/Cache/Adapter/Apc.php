<?php

class Cache_Adapter_Apc extends Cache_Adapter
{
    public function __construct($kulOptions)
    {
        if( ! extension_loaded('apc') )
            throw new Cache_Exception('APC plugin not installed');
    }


    // abstract metotları implemente et ----------------------------- \\
    public function set($key, $data, $time = null)
    {
        return apc_store($key, $data, $time);
    }

    public function get($key)
    {
        return apc_fetch($key);
    }

    function inc($key, $amount = 1) 
    {
        return apc_inc($key, $amount);
    }

    function dec($key, $amount = 1)
    {
        return apc_dec($key, $amount);
    }

    public function is_set($key)
    {
        $success = false;
        apc_fetch($key, $success);
        return $success;
    }

    public function remove($key)
    {
        return apc_delete($key);
    }

    public function flush() {
        return apc_clear_cache('user');
    }

} // sınıf sonu

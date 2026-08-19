<?php

class Database
{
    private static $connection = null;

    public static function boot()
    {
        global $config;

        if (!$config) {
            throw new Exception("Database belum terkoneksi.");
        }

        self::$connection = $config;
    }

    public static function connection()
    {
        return self::$connection;
    }

    public static function query($sql)
    {
        return mysqli_query(self::$connection, $sql);
    }

    public static function fetch($result)
    {
        return mysqli_fetch_assoc($result);
    }

    public static function escape($text)
    {
        return mysqli_real_escape_string(self::$connection, $text);
    }

    public static function lastId()
    {
        return mysqli_insert_id(self::$connection);
    }
}
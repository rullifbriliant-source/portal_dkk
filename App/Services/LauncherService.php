<?php

class LauncherService
{
    /**
     * Semua module aktif
     */
    public static function all()
    {
        return array_values(Module::enabled());
    }

    /**
     * Orbit Menu
     */
    public static function orbit($limit = 6)
    {
        return array_slice(
            self::all(),
            0,
            $limit
        );
    }

    /**
     * Berdasarkan kategori
     */
    public static function categories()
    {
        return Module::categories();
    }

    /**
     * Cari aplikasi
     */
    public static function search($keyword)
    {
        $keyword = strtolower(trim($keyword));

        return array_values(

            array_filter(

                self::all(),

                function($app) use($keyword){

                    return

                    strpos(
                        strtolower($app['name']),
                        $keyword
                    ) !== false;

                }

            )

        );
    }

}
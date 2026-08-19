<?php

class Module
{
    protected static $modules = [];

    public static function boot()
    {
        self::loadModules();
    }

    protected static function loadModules()
    {
        $folder = ROOT_PATH . "/modules";

        if (!is_dir($folder)) {
            return;
        }

        $items = scandir($folder);

        foreach ($items as $dir) {

            if ($dir == "." || $dir == "..") {
                continue;
            }

            $file = $folder . "/" . $dir . "/module.php";

            if (!file_exists($file)) {
                continue;
            }

            $module = require $file;

            if (!is_array($module)) {
                continue;
            }

            self::$modules[$module['id']] = $module;
        }
    }

    public static function all()
    {
        return self::$modules;
    }

    public static function enabled()
    {
        return array_filter(
            self::$modules,
            function ($m) {
                return !empty($m['enabled']);
            }
        );
    }

    public static function get($id)
    {
        return self::$modules[$id] ?? null;
    }

    public static function categories()
    {
        $list = [];

        foreach (self::enabled() as $m) {

            $list[$m['category']][] = $m;

        }

        ksort($list);

        return $list;
    }
}
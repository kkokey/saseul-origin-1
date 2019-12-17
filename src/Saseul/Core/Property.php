<?php

namespace Saseul\Core;

use Saseul\Constant\Role;
use Saseul\Constant\Signal;
use Saseul\System\Cache;

class Property
{
    # for consensus;
    public static function round($value = null) { return self::gs(__FUNCTION__, $value); }
    public static function roundInfo($value = null) { return self::gs(__FUNCTION__, $value); }
    public static function hashInfo($roundKey, $value = null) { return self::gs(__FUNCTION__ . $roundKey, $value); }
    public static function chunkInfo($roundKey, $value = null) { return self::gs(__FUNCTION__ . $roundKey, $value); }

    # for check;
    public static function excludeRequest($value = null) { return self::gs(__FUNCTION__, $value); }
    public static function registerRequest($value = null) { return self::gs(__FUNCTION__, $value); }
    public static function peer($value = null) { return self::gs(__FUNCTION__, $value); }
    public static function aliveNode($value = null) { return self::gs(__FUNCTION__, $value); }
    public static function aliveValidator($value = null) { return self::gs(__FUNCTION__, $value); }

    public static function iMLog($value = null) { return self::gs(__FUNCTION__, $value); }

    public static function daemonSig($value = null) { return self::gs(__FUNCTION__, $value); }
    public static function daemonPid($value = null) { return self::gs(__FUNCTION__, $value); }

    public static function role($value = null) { return self::gs(__FUNCTION__, $value); }
    public static function isForceRole($value = null) { return self::gs(__FUNCTION__, $value); }

    public static function init()
    {
        self::round([]);
        self::roundInfo([]);

        self::excludeRequest([]);
        self::registerRequest([]);
        self::peer([]);
        self::aliveNode([]);
        self::aliveValidator([]);

        self::iMLog([]);

        self::daemonSig(Signal::RUN);
        self::daemonPid(Signal::DEFAULT_PID);

        self::role(Role::LIGHT);
        self::isForceRole(false);
    }

    public static function reset()
    {
        self::peer([]);
        self::aliveNode([]);
        self::aliveValidator([]);
        self::excludeRequest([]);
        self::registerRequest([]);
    }

    public static function destroy()
    {
        self::round([]);
        self::roundInfo([]);

        self::peer([]);
        self::aliveNode([]);
        self::aliveValidator([]);
        self::excludeRequest([]);
        self::registerRequest([]);

        self::iMLog([]);

        self::daemonSig(Signal::DESTROY);
        self::daemonPid(Signal::DEFAULT_PID);

        self::role('');
        self::isForceRole(false);
    }

    public static function getAll()
    {
        $properties = self::getProperties();
        $all = [];

        foreach ($properties as $property) {
            $all[$property] = self::$property();
        }

        return $all;
    }

    public static function getProperties()
    {
        $all = get_class_methods(Property::class);
        $properties = [];
        $excludes = ['init', 'destroy', 'getAll', 'gs',
            'getCache', 'setCache', 'chunkInfo',
            'getProperties', 'hashInfo', 'reset'];

        foreach ($all as $item) {
            if (!in_array($item, $excludes)) {
                $properties[] = $item;
            }
        }

        return $properties;
    }

    private static function gs($name, $value = null)
    {
        if ($value === null) {
            return self::getCache($name);
        } else {
            self::setCache($name, $value);
        }

        return null;
    }

    private static function getCache($name)
    {
        $v = Cache::GetInstance()->get("p_{$name}");

        return $v;
    }

    private static function setCache($name, $value)
    {
        Cache::GetInstance()->set("p_{$name}", $value);
    }
}
<?php

declare (strict_types = 1);

namespace app\common\traits;

/**
 * 容器单例 trait。
 * 类中 use ContainerSingleton 后，通过 instance($params) 获取实例；
 * 首次调用固化构造参数，后续复用同一实例。
 */
trait ContainerSingleton
{
    public static function instance(array $params = []): static
    {
        $c = app();
        $k = static::class;

        if (!$c->exists($k)) {
            $c->instance($k, $c->make($k, compact('params')));
        }

        return $c->make($k);
    }
}

<?php

namespace Modules\MercadoPago\Entities;

use Modules\MercadoPago\Gateways\Once\MercadoPagoGateway;

class MercadoPago
{
    protected static array $gateways = [
        MercadoPagoGateway::class,
    ];
    public static function drivers(): array
    {
        $drivers = [];
        foreach (self::$gateways as $class) {
            if (method_exists($class, 'drivers')) {
                $drivers = array_merge($drivers, $class::drivers());
            }
        }
        return $drivers;
    }
}

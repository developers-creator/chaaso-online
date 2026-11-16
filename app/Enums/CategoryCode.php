<?php

namespace App\Enums;

class CategoryCode
{
    const HM = 'HM';
    const LS = 'LS';
    const OT = 'OT';

    public static function toArray()
    {
        return [
            self::HM,
            self::LS,
            self::OT,
        ];
    }
}

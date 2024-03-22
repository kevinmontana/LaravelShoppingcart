<?php

namespace Gloudemans\Shoppingcart\Enums;

enum CostType
{
    case Shipping;
    case Transaction;

    public function description(): string
    {
        return match($this) {
            self::Shipping => 'shipping cost',
            self::Transaction => 'transaction cost'
        };
    }
}
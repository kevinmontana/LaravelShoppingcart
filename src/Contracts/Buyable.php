<?php

namespace Gloudemans\Shoppingcart\Contracts;

interface Buyable
{
    /**
     * Get the identifier of the Buyable item.
     *
     * @return int|string
     */
    public function getBuyableIdentifier($options = null);

    /**
     * Get the description or title of the Buyable item.
     */
    public function getBuyableDescription($options = null): string;

    /**
     * Get the price of the Buyable item.
     */
    public function getBuyablePrice($options = null): float;
}
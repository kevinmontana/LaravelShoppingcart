<?php

namespace Gloudemans\Tests\Shoppingcart\Fixtures;

use Gloudemans\Shoppingcart\Contracts\Buyable;

class BuyableProduct implements Buyable
{
    /**
     * @var int|string
     */
    private $id;

    private string $name;
    private float $price;

    /**
     * BuyableProduct constructor.
     *
     * @param int|string $id
     */
    public function __construct($id = 1, string $name = 'Item name', float $price = 10.00)
    {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
    }

    /**
     * Get the identifier of the Buyable item.
     *
     * @return int|string
     */
    public function getBuyableIdentifier($options = null)
    {
        return $this->id;
    }

    /**
     * Get the description or title of the Buyable item.
     */
    public function getBuyableDescription($options = null): string
    {
        return $this->name;
    }

    /**
     * Get the price of the Buyable item.
     */
    public function getBuyablePrice($options = null): float
    {
        return $this->price;
    }
}
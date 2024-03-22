<?php

namespace Gloudemans\Shoppingcart;

trait CanBeBought
{

    /**
     * Get the identifier of the Buyable item.
     *
     * @return int|string
     */
    public function getBuyableIdentifier($options = null)
    {
        return method_exists($this, 'getKey') ? $this->getKey() : $this->id;
    }

    /**
     * Get the description or title of the Buyable item.
     */
    public function getBuyableDescription($options = null): string
    {
        if(property_exists($this, 'name')) return $this->name;
        if(property_exists($this, 'title')) return $this->title;
        if(property_exists($this, 'description')) return $this->description;

        return 'unknown';
    }

    /**
     * Get the price of the Buyable item.
     */
    public function getBuyablePrice($options = null): float
    {
        if(property_exists($this, 'price')) return $this->price;

        return 0;
    }
}
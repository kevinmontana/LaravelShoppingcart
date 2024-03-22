<?php

namespace Gloudemans\Shoppingcart;

use Illuminate\Contracts\Support\Arrayable;
use Gloudemans\Shoppingcart\Contracts\Buyable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;

class CartItem implements Arrayable, Jsonable
{
    /**
     * The rowID of the cart item.
     */
    public string $rowId;

    /**
     * The ID of the cart item.
     *
     * @var int|string
     */
    public $id;

    /**
     * The quantity for this cart item.
     */
    public int|float $qty;

    /**
     * The name of the cart item.
     */
    public string $name;

    /**
     * The price without TAX of the cart item.
     */
    public float $price;

    /**
     * The options for this cart item.
     */
    public CartItemOptions $options;

    /**
     * The FQN of the associated model.
     */
    private ?string $associatedModel = null;

    /**
     * The tax rate for the cart item.
     *
     * @var int|float
     */
    private $taxRate = 0;

    /**
     * CartItem constructor.
     *
     * @param int|string $id
     */
    public function __construct($id, ?string $name, float $price, array $options = [])
    {
        if(empty($id)) {
            throw new \InvalidArgumentException('Please supply a valid identifier.');
        }
        if(empty($name)) {
            throw new \InvalidArgumentException('Please supply a valid name.');
        }
        if($price < 0) {
            throw new \InvalidArgumentException('Please supply a valid price.');
        }

        $this->id       = $id;
        $this->name     = $name;
        $this->price    = $price;
        $this->options  = new CartItemOptions($options);
        $this->rowId = $this->generateRowId($id, $options);
    }

    /**
     * Returns the formatted price without TAX.
     */
    public function price(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return $this->numberFormat($this->price, $decimals, $decimalPoint, $thousandSeparator);
    }
    
    /**
     * Returns the formatted price with TAX.
     */
    public function priceTax(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return $this->numberFormat($this->priceTax, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Returns the formatted subtotal.
     * Subtotal is price for whole CartItem without TAX
     */
    public function subtotal(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return $this->numberFormat($this->subtotal, $decimals, $decimalPoint, $thousandSeparator);
    }
    
    /**
     * Returns the formatted total.
     * Total is price for whole CartItem with TAX
     */
    public function total(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return $this->numberFormat($this->total, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Returns the formatted tax.
     */
    public function tax(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return $this->numberFormat($this->tax, $decimals, $decimalPoint, $thousandSeparator);
    }
    
    /**
     * Returns the formatted tax.
     */
    public function taxTotal(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return $this->numberFormat($this->taxTotal, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Set the quantity for this cart item.
     */
    public function setQuantity(int|float $qty)
    {
        if($qty < 1)
            throw new \InvalidArgumentException('Please supply a valid quantity.');

        $this->qty = $qty;
    }

    /**
     * Update the cart item from a Buyable.
     */
    public function updateFromBuyable(Buyable $item): void
    {
        $this->id       = $item->getBuyableIdentifier($this->options);
        $this->name     = $item->getBuyableDescription($this->options);
        $this->price    = $item->getBuyablePrice($this->options);
        $this->priceTax = $this->price + $this->tax;
    }

    /**
     * Update the cart item from an array.
     */
    public function updateFromArray(array $attributes): void
    {
        $this->id       = Arr::get($attributes, 'id', $this->id);
        $this->qty      = Arr::get($attributes, 'qty', $this->qty);
        $this->name     = Arr::get($attributes, 'name', $this->name);
        $this->price    = Arr::get($attributes, 'price', $this->price);
        $this->priceTax = $this->price + $this->tax;
        $this->options  = new CartItemOptions(Arr::get($attributes, 'options', $this->options));
        $this->rowId    = $this->generateRowId($this->id, $this->options->all());
    }

    /**
     * Associate the cart item with the given model.
     *
     * @param mixed $model
     */
    public function associate($model): self
    {
        $this->associatedModel = is_string($model) ? $model : get_class($model);
        
        return $this;
    }

    /**
     * Set the tax rate.
     *
     * @param int|float $taxRate
     */
    public function setTaxRate($taxRate): self
    {
        $this->taxRate = $taxRate;
        
        return $this;
    }

    /**
     * Get an attribute from the cart item or get the associated model.
     *
     * @param string $attribute
     * @return mixed
     */
    public function __get($attribute)
    {
        if(property_exists($this, $attribute)) {
            return $this->{$attribute};
        }

        if($attribute === 'priceTax') {
            return number_format($this->price + $this->tax, 2, '.', '');
        }
        
        if($attribute === 'subtotal') {
            return number_format($this->qty * $this->price, 2, '.', '');
        }
        
        if($attribute === 'total') {
            return number_format($this->qty * $this->priceTax, 2, '.', '');
        }

        if($attribute === 'tax') {
            return number_format($this->price * ($this->taxRate / 100), 2, '.', '');
        }
        
        if($attribute === 'taxTotal') {
            return number_format($this->tax * $this->qty, 2, '.', '');
        }

        if($attribute === 'model' && isset($this->associatedModel)) {
            return with(new $this->associatedModel)->find($this->id);
        }

        return null;
    }

    /**
     * Create a new instance from a Buyable.
     */
    public static function fromBuyable(Buyable $item, array $options = []): CartItem
    {
        return new self(
            $item->getBuyableIdentifier($options),
            $item->getBuyableDescription($options),
            $item->getBuyablePrice($options), 
            $options
        );
    }

    /**
     * Create a new instance from the given array.
     */
    public static function fromArray(array $attributes): CartItem
    {
        $options = Arr::get($attributes, 'options', []);

        return new self($attributes['id'], $attributes['name'], $attributes['price'], $options);
    }

    /**
     * Create a new instance from the given attributes.
     *
     * @param int|string $id
     */
    public static function fromAttributes($id, ?string $name, float $price, array $options = []): CartItem
    {
        return new self($id, $name, $price, $options);
    }

    /**
     * Generate a unique id for the cart item.
     *
     * @param string $id
     */
    private function generateRowId($id, array $options): string
    {
        ksort($options);

        return md5($id . serialize($options));
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'rowId'    => $this->rowId,
            'id'       => $this->id,
            'name'     => $this->name,
            'qty'      => $this->qty,
            'price'    => $this->price,
            'options'  => $this->options->toArray(),
            'tax'      => $this->tax,
            'subtotal' => $this->subtotal
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get the formatted number.
     */
    private function numberFormat(float $value, ?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        if (is_null($decimals)) {
            $decimals = config('cart.format.decimals', 2);
        }

        if (is_null($decimalPoint)) {
            $decimalPoint = config('cart.format.decimal_point', '.');
        }

        if (is_null($thousandSeparator)) {
            $thousandSeparator = config('cart.format.thousand_seperator', ',');
        }

        return number_format($value, $decimals, $decimalPoint, $thousandSeparator);
    }
}

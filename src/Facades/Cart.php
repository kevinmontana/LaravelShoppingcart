<?php
namespace Gloudemans\Shoppingcart\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string currentInstance()
 * @method static \Gloudemans\Shoppingcart\Cart instance(?string $instance = null)
 * 
 * @method static \Gloudemans\Shoppingcart\CartItem get(string $rowId)
 * @method static \Gloudemans\Shoppingcart\CartItem|\Gloudemans\Shoppingcart\CartItem[] add($id, $name = null, $qty = null, $price = null, array $options = [])
 * @method static ?\Gloudemans\Shoppingcart\CartItem update(string $rowId, $qty)
 * @method static void remove(string $rowId)
 * 
 * @method static void destroy()
 * @method static \Illuminate\Support\Collection content()
 * @method static int|float count()
 * @method static void associate(string $rowId, $model)
 * @method static void store($identifier)
 * @method static void restore($identifier)
 * @method static \Illuminate\Support\Collection search(\Closure $search)
 * 
 * @method static void setTax(string $rowId, $taxRate)
 * 
 * @method static float total()
 * @method static string totalFormat(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null)
 * @method static float tax()
 * @method static string taxFormat(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null)
 * @method static float subtotal()
 * @method static string subtotalFormat(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null)
 * @method static void|float cost(string|\Gloudemans\Shoppingcart\Enums\CostType $name, ?float $price = null)
 * @method static string costFormat(string|\Gloudemans\Shoppingcart\Enums\CostType $name, ?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null)
 * 
 * @see \Gloudemans\Shoppingcart\Cart
 */
class Cart extends Facade {
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cart';
    }
}

<?php

namespace Gloudemans\Shoppingcart;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Session\SessionManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Events\Dispatcher;
use Gloudemans\Shoppingcart\Contracts\Buyable;
use Gloudemans\Shoppingcart\Enums\CostType;
use Gloudemans\Shoppingcart\Exceptions\UnknownModelException;
use Gloudemans\Shoppingcart\Exceptions\InvalidRowIDException;
use Gloudemans\Shoppingcart\Exceptions\CartAlreadyStoredException;

class Cart
{
    const DEFAULT_INSTANCE = 'default';

    /**
     * Instance of the session manager.
     */
    private SessionManager $session;

    /**
     * Instance of the event dispatcher.
     */
    private Dispatcher $events;

    /**
     * Holds the current cart instance.
     */
    private string $instance;

    /**
     * Holds the extra additional costs on the cart
     */
    private Collection $extraCosts;

    /**
     * Cart constructor.
     */
    public function __construct(SessionManager $session, Dispatcher $events)
    {
        $this->session = $session;
        $this->events = $events;
        $this->extraCosts = new Collection();

        $this->instance(self::DEFAULT_INSTANCE);
    }

    /**
     * Set the current cart instance.
     */
    public function instance(?string $instance = null): Cart
    {
        $instance = $instance ?: self::DEFAULT_INSTANCE;

        $this->instance = sprintf('%s.%s', 'cart', $instance);

        return $this;
    }

    /**
     * Get the current cart instance.
     */
    public function currentInstance(): string
    {
        return str_replace('cart.', '', $this->instance);
    }

    /**
     * Add an item to the cart.
     *
     * @param array|Buyable|int|string $id
     * @param mixed $name
     * @param int|float|null $qty
     * @param ?float $price
     * 
     * @return CartItem|CartItem[]
     */
    public function add($id, $name = null, $qty = null, $price = null, array $options = [])
    {
        if ($this->isMulti($id)) {
            return array_map(function ($item) {
                return $this->add($item);
            }, $id);
        }

        $cartItem = $this->createCartItem($id, $name, $qty, $price, $options);

        $content = $this->getContent();

        if ($content->has($cartItem->rowId)) {
            $cartItem->qty += $content->get($cartItem->rowId)->qty;
        }

        $content->put($cartItem->rowId, $cartItem);
        
        $this->events->dispatch('cart.added', $cartItem);

        $this->session->put($this->instance, $content);

        return $cartItem;
    }

    /**
     * Update the cart item with the given rowId.
     *
     * @param mixed  $qty
     */
    public function update(string $rowId, $qty): ?CartItem
    {
        $cartItem = $this->get($rowId);

        if ($qty instanceof Buyable) {
            $cartItem->updateFromBuyable($qty);
        } elseif (is_array($qty)) {
            $cartItem->updateFromArray($qty);
        } else {
            $cartItem->qty = $qty;
        }

        $content = $this->getContent();

        if ($rowId !== $cartItem->rowId) {
            $content->pull($rowId);

            if ($content->has($cartItem->rowId)) {
                $existingCartItem = $this->get($cartItem->rowId);
                $cartItem->setQuantity($existingCartItem->qty + $cartItem->qty);
            }
        }

        if ($cartItem->qty <= 0) {
            $this->remove($cartItem->rowId);
            return null;
        } else {
            $content->put($cartItem->rowId, $cartItem);
        }

        $this->events->dispatch('cart.updated', $cartItem);

        $this->session->put($this->instance, $content);

        return $cartItem;
    }

    /**
     * Remove the cart item with the given rowId from the cart.
     */
    public function remove(string $rowId): void
    {
        $cartItem = $this->get($rowId);

        $content = $this->getContent();

        $content->pull($cartItem->rowId);

        $this->events->dispatch('cart.removed', $cartItem);

        $this->session->put($this->instance, $content);
    }

    /**
     * Get a cart item from the cart by its rowId.
     */
    public function get(string $rowId): CartItem
    {
        $content = $this->getContent();

        if ( ! $content->has($rowId))
            throw new InvalidRowIDException("The cart does not contain rowId {$rowId}.");

        return $content->get($rowId);
    }

    /**
     * Destroy the current cart instance.
     */
    public function destroy(): void
    {
        $this->session->remove($this->instance);
    }

    /**
     * Get the content of the cart.
     */
    public function content(): Collection
    {
        if (!$this->session->has($this->instance)) {
            return new Collection();
        }

        return $this->session->get($this->instance);
    }

    /**
     * Get the number of items in the cart.
     *
     * @return int|float
     */
    public function count()
    {
        return $this->getContent()->sum('qty');
    }

    /**
     * Get the total price of the items in the cart.
     */
    public function total(): float
    {
        $content = $this->getContent();

        $total = $content->reduce(function ($total, CartItem $cartItem) {
            return $total + ($cartItem->qty * $cartItem->priceTax);
        }, 0);

        $totalCost = $this->extraCosts->reduce(function ($total, $cost) {
            return $total + $cost;
        }, 0);

        $total += $totalCost;

        return $total;
    }

    /**
     * Gets the formatted total price of the items in the cart 
     */
    public function totalFormat(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return $this->numberFormat($this->total(), $decimals, $decimalPoint, $thousandSeparator);
    }


    /**
     * Get the total tax of the items in the cart.
     */
    public function tax(): float
    {
        $content = $this->getContent();

        return $content->reduce(
            fn ($tax, CartItem $cartItem) => $tax + ($cartItem->qty * $cartItem->tax),
            0
        );
    }

    /**
     * Get the formatted total tax of the items in the cart
     */
    public function taxFormat(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return $this->numberFormat($this->tax(), $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Get the subtotal (total - tax) of the items in the cart.
     */
    public function subtotal(): float
    {
        $content = $this->getContent();

        return $content->reduce(
            fn ($subTotal, CartItem $cartItem) => $subTotal + ($cartItem->qty * $cartItem->price),
            0
        );
    }

    /**
     * Gets the formatted subtotal of the items in the cart.
     */
    public function subtotalFormat(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return $this->numberFormat($this->subtotal(), $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Get or set specific cost in the cart.
     * 
     * @return void|float
     */
    public function cost(string|CostType $type, ?float $price = null)
    {
        if (is_a($type, CostType::class))
            $type = strtolower($type->name);

        if ($price === null)
            return $this->extraCosts->get($type, 0);
        else
        {
            $oldCost = $this->extraCosts->pull($type, 0);

            $this->extraCosts->put($type, $price + $oldCost);
        }
    }

    /**
     * Format a cost in the cart
     */
    public function costFormat(string|CostType $type, ?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return $this->numberFormat($this->cost($type), $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Search the cart content for a cart item matching the given search closure.
     *
     * @param \Closure $search
     */
    public function search(Closure $search): Collection
    {
        $content = $this->getContent();

        return $content->filter($search);
    }

    /**
     * Associate the cart item with the given rowId with the given model.
     *
     * @param mixed  $model
     */
    public function associate(string $rowId, $model): void
    {
        if(is_string($model) && ! class_exists($model)) {
            throw new UnknownModelException("The supplied model {$model} does not exist.");
        }

        $cartItem = $this->get($rowId);

        $cartItem->associate($model);

        $content = $this->getContent();

        $content->put($cartItem->rowId, $cartItem);

        $this->session->put($this->instance, $content);
    }

    /**
     * Set the tax rate for the cart item with the given rowId.
     *
     * @param int|float $taxRate
     */
    public function setTax(string $rowId, $taxRate): void
    {
        $cartItem = $this->get($rowId);

        $cartItem->setTaxRate($taxRate);

        $content = $this->getContent();

        $content->put($cartItem->rowId, $cartItem);

        $this->session->put($this->instance, $content);
    }

    /**
     * Store an the current instance of the cart.
     *
     * @param mixed $identifier
     */
    public function store($identifier): void
    {
        $content = $this->getContent();

        if ($this->storedCartWithIdentifierExists($identifier)) {
            throw new CartAlreadyStoredException("A cart with identifier {$identifier} was already stored.");
        }

        $this->getConnection()->table($this->getTableName())->insert([
            'identifier' => $identifier,
            'instance' => $this->currentInstance(),
            'content' => serialize($content)
        ]);

        $this->events->dispatch('cart.stored');
    }

    /**
     * Restore the cart with the given identifier.
     *
     * @param mixed $identifier
     */
    public function restore($identifier): void
    {
        if( ! $this->storedCartWithIdentifierExists($identifier)) {
            return;
        }

        $stored = $this->getConnection()->table($this->getTableName())
            ->where('identifier', $identifier)->first();

        $storedContent = unserialize($stored->content);

        $currentInstance = $this->currentInstance();

        $this->instance($stored->instance);

        $content = $this->getContent();

        foreach ($storedContent as $cartItem) {
            $content->put($cartItem->rowId, $cartItem);
        }

        $this->events->dispatch('cart.restored');

        $this->session->put($this->instance, $content);

        $this->instance($currentInstance);

        $this->getConnection()->table($this->getTableName())
            ->where('identifier', $identifier)->delete();
    }

    /**
     * Get the carts content from session, if there is no cart content set yet, return a new empty Collection
     */
    private function getContent(): Collection
    {
        return $this->session->get($this->instance, new Collection());
    }

    /**
     * Create a new CartItem from the supplied attributes.
     *
     * @param array|Buyable|int|string $id
     * @param mixed $name
     * @param int|float $qty
     */
    private function createCartItem($id, $name, $qty, ?float $price, array $options = []): CartItem
    {
        if ($id instanceof Buyable) {
            $cartItem = CartItem::fromBuyable($id, $qty ?: []);
            $cartItem->setQuantity($name ?: 1);
            $cartItem->associate($id);
        } elseif (is_array($id)) {
            $cartItem = CartItem::fromArray($id);
            $cartItem->setQuantity($id['qty']);
        } else {
            $cartItem = CartItem::fromAttributes($id, $name, $price, $options);
            $cartItem->setQuantity($qty);
        }

        $cartItem->setTaxRate(config('cart.tax'));

        return $cartItem;
    }

    /**
     * Check if the item is a multidimensional array or an array of Buyables.
     *
     * @param mixed $item
     */
    private function isMulti($item): bool
    {
        if ( ! is_array($item)) return false;

        return is_array(head($item)) || head($item) instanceof Buyable;
    }

    /**
     * @param mixed $identifier
     */
    private function storedCartWithIdentifierExists($identifier): bool
    {
        return $this->getConnection()->table($this->getTableName())->where('identifier', $identifier)->exists();
    }

    /**
     * Get the database connection.
     */
    private function getConnection(): Connection
    {
        $connectionName = $this->getConnectionName();

        return app(DatabaseManager::class)->connection($connectionName);
    }

    /**
     * Get the database table name.
     */
    private function getTableName(): string
    {
        return config('cart.database.table', 'shoppingcart');
    }

    /**
     * Get the database connection name.
     */
    private function getConnectionName(): ?string
    {
        return config('cart.database.connection', config('database.default'));
    }

    /**
     * Get the formatted number
     */
    private function numberFormat($value, ?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        if(is_null($decimals)){
            $decimals = config('cart.format.decimals', 2);
        }
        if(is_null($decimalPoint)){
            $decimalPoint = config('cart.format.decimal_point', '.');
        }
        if(is_null($thousandSeparator)){
            $thousandSeparator = config('cart.format.thousand_seperator', ',');
        }

        return number_format($value, $decimals, $decimalPoint, $thousandSeparator);
    }
}

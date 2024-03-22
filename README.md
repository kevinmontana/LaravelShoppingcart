## LaravelShoppingcart

[![CI Status](https://github.com/kevinmontana/LaravelShoppingcart/actions/workflows/php.yml/badge.svg)](https://github.com/kevinmontana/LaravelShoppingcart/actions)
[![Total Downloads](https://poser.pugx.org/kevinmontana/shoppingcart/downloads.png)](https://packagist.org/packages/kevinmontana/shoppingcart)
[![Latest Stable Version](https://poser.pugx.org/kevinmontana/shoppingcart/v/stable)](https://packagist.org/packages/kevinmontana/shoppingcart)
[![Latest Unstable Version](https://poser.pugx.org/kevinmontana/shoppingcart/v/unstable)](https://packagist.org/packages/kevinmontana/shoppingcart)
[![License](https://poser.pugx.org/kevinmontana/shoppingcart/license)](https://packagist.org/packages/kevinmontana/shoppingcart)

A simple shoppingcart implementation for Laravel.

## Installation

Install the package through [Composer](http://getcomposer.org/).

Run the Composer require command from the Terminal:

    composer require kevinmontana/shoppingcart

### Difference between [Crinsane/LaravelShoppingcart](https://github.com/Crinsane/LaravelShoppingcart)

- Added functionality
  - `Cart::cost()`
  - `Cart::costFormat()`
  - Enum `CostType`
  - `Cart::totalFormat()`
  - `Cart::taxFormat()`
  - `Cart::subtotalFormat()`
- More recent Laravel support
- More typed parameters, returns, properties, ...
- Less usage of magic properties - really dislike this one
- Refactoring

## Overview and usage

Look at the detailed [wiki](https://github.com/kevinmontana/LaravelShoppingcart/wiki) to learn more about LaravelShoppingcart

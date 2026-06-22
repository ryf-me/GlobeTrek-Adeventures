<?php
define('CURRENCY_CODE', 'LKR');
define('CURRENCY_SYMBOL', 'Rs.');
define('CURRENCY_NAME', 'Sri Lankan Rupee');

function formatPrice(float $price, int $decimals = 0): string {
    return CURRENCY_SYMBOL . number_format($price, $decimals);
}

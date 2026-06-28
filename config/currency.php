<?php
/**
 * File: config/currency.php
 * Purpose: Currency configuration and price formatting for Sri Lankan Rupee (LKR)
 *
 * This file provides:
 *   1. Currency constants (code, symbol, name)
 *   2. A price formatting function for consistent display
 *
 * Dependencies: None (standalone config file)
 *
 * Used By:
 *   - All pages that display prices (packages, bookings, payments, etc.)
 *   - includes/notifications.php (for email price formatting)
 *   - admin/reports.php (for sales reports)
 *
 * Parent Files: None (loaded via require_once)
 * Child Files: None (no includes)
 *
 * @package GlobeTrek\Config
 */

// =============================================================================
// CURRENCY CONSTANTS
// =============================================================================

// ISO 4217 currency code for Sri Lankan Rupee
define('CURRENCY_CODE', 'LKR');

// Display symbol for prices (used in formatPrice())
define('CURRENCY_SYMBOL', 'Rs.');

// Full human-readable currency name
define('CURRENCY_NAME', 'Sri Lankan Rupee');

// =============================================================================
// PRICE FORMATTING FUNCTION
// =============================================================================
/**
 * Format a price with the currency symbol and thousands separators.
 *
 * Uses PHP's number_format() for locale-independent formatting.
 * The default is whole rupees (0 decimal places).
 *
 * @param float $price    The price value to format
 * @param int   $decimals Number of decimal places (default: 0)
 * @return string Formatted price string (e.g., "Rs. 15,000" or "Rs. 15,000.50")
 *
 * Usage:
 *   echo formatPrice(15000);        // "Rs. 15,000"
 *   echo formatPrice(15000.50, 2);  // "Rs. 15,000.50"
 */
function formatPrice(float $price, int $decimals = 0): string {
    // number_format() adds thousands separators based on the server's locale
    // No explicit locale is set, so it uses the server default
    return CURRENCY_SYMBOL . number_format($price, $decimals);
}

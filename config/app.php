<?php
/**
 * Application Configuration
 */
define('APP_NAME', 'Hotel Management System Demo');
define('APP_VERSION', '1.0.0');
define('DEFAULT_LANG', 'en');
define('SUPPORTED_LANGS', ['en', 'fil']);
define('TIMEZONE', 'Asia/Manila');
define('TAX_RATE', 0.12);          // 12% tax
define('CURRENCY_SYMBOL', '₱');
define('CURRENCY_CODE', 'PHP');
define('PER_PAGE', 10);             // Records per page for pagination

date_default_timezone_set(TIMEZONE);

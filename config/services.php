<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    "mailgun" => [
        "domain" => env("MAILGUN_DOMAIN"),
        "secret" => env("MAILGUN_SECRET"),
        "endpoint" => env("MAILGUN_ENDPOINT", "api.mailgun.net"),
        "scheme" => "https",
    ],

    "postmark" => [
        "token" => env("POSTMARK_TOKEN"),
    ],

    "ses" => [
        "key" => env("AWS_ACCESS_KEY_ID"),
        "secret" => env("AWS_SECRET_ACCESS_KEY"),
        "region" => env("AWS_DEFAULT_REGION", "us-east-1"),
    ],

    "mobile_money" => [
        "base_url" => env(
            "MOBILE_MONEY_BASE_URL",
            "https://api.mobilemoney.example.com",
        ),
        "api_key" => env("MOBILE_MONEY_API_KEY"),
        "merchant_id" => env("MOBILE_MONEY_MERCHANT_ID"),
        "timeout" => env("MOBILE_MONEY_TIMEOUT", 30),
        "max_retries" => env("MOBILE_MONEY_MAX_RETRIES", 3),
    ],
];

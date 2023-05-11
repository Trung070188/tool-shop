<?php

return [
    'whitelist' => [
        'tables' => [
            'connection_units',
            'report_transaction_types',
            'report_transaction_statuses',
            'report_mb_transaction_statuses',
            'report_pgw_transaction_statuses',
            'report_cpob_transaction_statuses',
            'transaction_types',
            'transaction_type_codes',
            'report_gateway_suspicious_accounts',
            'report_gateway_suspicious_transactions',
            'report_wallet_suspicious_accounts',
            'report_wallet_suspicious_transactions',
            'secured_accounts',
            'secured_accounts_balances',
            'secured_accounts_transactions',
            'report_wallet_suspicious_transactions',
            'wallets',
            'wallet_types',
            'wallet_transactions',
            'report_wallet_statuses',
            'report_top_customers',
            'customer_categories'
        ],
        'functions' => [
            'SUM',
            'COUNT',
            'DATE',
            'MONTH',
            'YEAR',
            'DATE_FORMAT',
            'MAX',
            'MIN'
        ],
        'operators' => [
            '=',
            '<>',
            '!=',
            '>=',
            '<=',
            '>',
            '<',
            'IN',
            'NOT IN',
            'LIKE',
            'NOT LIKE',
            'IS NULL',
            'IS NOT NULL',
        ],
        'separators' => [',', '|', ';', ':', '-', '_'],
        'args' => [
            '%W',
            '%d',
            '%e'
        ]
    ]
];

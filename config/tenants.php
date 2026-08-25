<?php

/*
|--------------------------------------------------------------------------
| Domain → Database mapping
|--------------------------------------------------------------------------
| Each key is a bare hostname (no port, no scheme).
| Each value is the MySQL database name to connect to for that domain.
|
| Add one entry per client domain. The fallback is whatever DB_DATABASE
| is set to in .env (used when no domain matches).
|
| Example:
|   'shop1.example.com' => 'lmuc_shop1',
|   'shop2.example.com' => 'lmuc_shop2',
*/

return [
    'localhost' => 'restaurant',
    'cafe1-restaurant.lumac.cc' => 'cafe1',
    'daily-restaurant.lumac.cc' => 'daily_dose',
    'rahula-restaurant.lumac.cc' => 'rahula_bakery',
];

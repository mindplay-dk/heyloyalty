<?php

use mindplay\heyloyalty\HeyLoyaltyClient;

require __DIR__ . '/_header.php';

if (! file_exists(__DIR__ . '/config.local.php')) {
    throw new RuntimeException("missing config.local.php - please create it to run the test-suite");
}

require __DIR__ . '/config.local.php';

test(
    'Testing',
    function () {
        // TODO add real tests

        $client = new HeyLoyaltyClient(HEY_LOYALTY_API_KEY, HEY_LOYALTY_API_SECRET);

        $list = $client->getList(HEY_LOYALTY_LIST_ID);

        var_dump($list);
    }
);

exit(status()); // exits with errorlevel (for CI tools etc.)

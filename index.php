<?php

declare(strict_types=1);

require_once __DIR__ . '/flight/Flight.php';

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/routes.php';

Flight::start();

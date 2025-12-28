<?php
// api/auth/logout.php

declare(strict_types=1);

require_once __DIR__ . "/_init.php";

do_logout();

json_response(["status" => "success"]);

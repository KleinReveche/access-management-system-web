<?php

include '../util/decrypt.php';

header('Content-Type: application/json');
echo decrypt($_GET['data'], "../../../");
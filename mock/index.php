<?php
/**
 * index.php
 * @author    Daniel Mason <daniel@ayeayeapi.com>
 * @copyright (c) 2016 Daniel Mason <daniel@ayeayeapi.com>
 * @license   MIT
 * @see       https://github.com/AyeAyeApi/behat-feature-context
 */


require_once "../vendor/autoload.php";

use AyeAye\Api\Api;
use AyeAye\Behat\Mock\Api\Version1;

$api = new Api(new Version1());
$api->go()->respond();

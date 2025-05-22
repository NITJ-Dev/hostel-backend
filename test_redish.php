<?php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$redis->set("test_key", "Hello, Redis!\n".session_id());
echo $redis->get("test_key");
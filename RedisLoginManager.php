<?php
class RedisLoginManager {
    private $redis;
    private $hashKey = 'active_logins';

    public function __construct($host = '127.0.0.1', $port = 6379) {
        $this->redis = new Redis();
        $this->redis->connect($host, $port);
    }

    // Set or update device ID for a roll number
    public function set($rollno, $deviceId) {
        return $this->redis->hSet($this->hashKey, $rollno, $deviceId);
    }

    // Get device ID by roll number
    public function get($rollno) {
        return $this->redis->hGet($this->hashKey, $rollno);
    }

    // Update device ID (alias for set)
    public function update($rollno, $newDeviceId) {
        return $this->set($rollno, $newDeviceId);
    }

    // Delete a roll number entry
    public function delete($rollno) {
        return $this->redis->hDel($this->hashKey, $rollno);
    }

    // Optional: Check if roll number exists
    public function exists($rollno) {
        return $this->redis->hExists($this->hashKey, $rollno);
    }

    // Optional: Get all entries
    public function getAll() {
        return $this->redis->hGetAll($this->hashKey);
    }
}

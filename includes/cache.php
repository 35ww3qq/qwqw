<?php
class Cache {
    private static $instance = null;
    private $data = [];
    private $file;
    
    private function __construct() {
        $this->file = LOGS_PATH . '/cache.json';
        $this->load();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($key, $default = null) {
        if (!CACHE_ENABLED) {
            return $default;
        }
        
        $key = $this->getCacheKey($key);
        
        if (!isset($this->data[$key])) {
            return $default;
        }
        
        $item = $this->data[$key];
        
        if ($item['expires'] < time()) {
            $this->forget($key);
            return $default;
        }
        
        return $item['value'];
    }
    
    public function put($key, $value, $minutes = 60) {
        if (!CACHE_ENABLED) {
            return;
        }
        
        $key = $this->getCacheKey($key);
        
        $this->data[$key] = [
            'value' => $value,
            'expires' => time() + ($minutes * 60)
        ];
        
        $this->save();
    }
    
    public function forever($key, $value) {
        $this->put($key, $value, 525600); // One year
    }
    
    public function forget($key) {
        $key = $this->getCacheKey($key);
        unset($this->data[$key]);
        $this->save();
    }
    
    public function flush() {
        $this->data = [];
        $this->save();
    }
    
    public function remember($key, $minutes, $callback) {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->put($key, $value, $minutes);
        
        return $value;
    }
    
    public function rememberForever($key, $callback) {
        return $this->remember($key, 525600, $callback);
    }
    
    private function getCacheKey($key) {
        return CACHE_PREFIX . $key;
    }
    
    private function load() {
        if (file_exists($this->file)) {
            $data = json_decode(file_get_contents($this->file), true);
            $this->data = $data ?: [];
            
            // Clean expired items
            foreach ($this->data as $key => $item) {
                if ($item['expires'] < time()) {
                    unset($this->data[$key]);
                }
            }
        }
    }
    
    private function save() {
        if (!is_dir(LOGS_PATH)) {
            mkdir(LOGS_PATH, 0755, true);
        }
        file_put_contents($this->file, json_encode($this->data));
    }
}
?>
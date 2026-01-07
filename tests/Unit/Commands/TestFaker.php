<?php

namespace Tests\Unit\Commands;

// Custom faker-like class for testing the fallback mechanism
class TestFaker {
    private $callCount = 0;

    public function unique() {
        return $this;
    }

    public function __get($name) {
        if ($name === 'safeEmail') {
            return $this->safeEmail();
        }
        throw new \Exception("Property $name not found");
    }

    private function safeEmail() {
        $this->callCount++;
        // Always return the same existing email to force max attempts
        return 'existing@example.com';
    }
}

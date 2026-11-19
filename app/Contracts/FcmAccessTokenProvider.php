<?php

namespace App\Contracts;

interface FcmAccessTokenProvider
{
    public function get(): string;
}

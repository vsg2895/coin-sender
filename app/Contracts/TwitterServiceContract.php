<?php

namespace App\Contracts;

interface TwitterServiceContract
{
    public function user(string $name);
    public function tweet(string $id);
    public function space(string $name);
}

<?php

if (!function_exists('generate_unique_url')) {
    /**
     * Generate unique keys
     * @param int $length
     * @param bool $factory
     * @return string
     */
    function generate_unique_url(int $length = 10, bool $factory = false): string
    {
        // have to ensure this key is unique for the merchant
        $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    
        if ($factory) {
            return substr(str_shuffle(str_repeat($pool, 50)), 0, 50);
        }
    
        do {
            $token = substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
        } while (\App\Models\PullRequest::where('url', $token)->exists());
    
        return $token;
    }

}
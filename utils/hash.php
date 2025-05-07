<?php

if (!function_exists('hashPassword')) {
    /**
     * Hashes a password using bcrypt.
     *
     * @param string $password The plain-text password.
     * @return string|false The hashed password or false on failure.
     */
    function hashPassword(string $password): string|false
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}

if (!function_exists('verifyPassword')) {
    /**
     * Verifies a password against a bcrypt hash.
     *
     * @param string $password The plain-text password.
     * @param string $hashedPassword The hashed password to verify against.
     * @return bool True if the password matches the hash, false otherwise.
     */
    function verifyPassword(string $password, string $hashedPassword): bool
    {
        return password_verify($password, $hashedPassword);
    }
}

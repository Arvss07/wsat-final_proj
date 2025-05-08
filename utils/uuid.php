<?php

if (!function_exists('generateUuidV4')) {
    /**
     * Generates a Version 4 UUID.
     *
     * @return string The generated UUID.
     */
    function generateUuidV4(): string
    {
        // Generate 16 bytes (128 bits) of random data.
        $data = random_bytes(16);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

function generate_numeric_reference() {
    $parts = [];
    for ($i = 0; $i < 4; $i++) {
        $parts[] = str_pad(strval(random_int(0, 9999)), 4, '0', STR_PAD_LEFT);
    }
    return implode('-', $parts);
}

<?php

namespace App\Core\Contracts;

interface PaymentGateway
{
    /** Human readable name shown in the admin. */
    public function name(): string;

    /**
     * Create a charge.
     *
     * @param  array{amount:int|string,currency?:string,description?:string,callback_url?:string,metadata?:array}  $payload
     * @return array{ok:bool,reference:string,invoice_url?:string,message?:string,raw?:array}
     */
    public function charge(array $payload): array;

    /**
     * Verify a callback signature.
     *
     * @return bool
     */
    public function verifyCallback(array $payload, string $signature, string $rawBody): bool;

    /** Normalise a callback payload into a common shape. */
    public function parseCallback(array $payload): array;
}

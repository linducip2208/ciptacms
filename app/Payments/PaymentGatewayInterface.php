<?php
namespace App\Payments;
interface PaymentGatewayInterface { public function charge(array $order, float $amount, array $opts=[]): array; public function callback(array $payload): array; public function name(): string; }

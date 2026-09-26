<?php
namespace App\Payments\Gateways;
use App\Payments\PaymentGatewayInterface;
class IpaymuGateway implements PaymentGatewayInterface {
    public function name(): string { return 'IpaymuGateway'; }
    public function charge(array $order, float $amount, array $opts=[]): array { return ['ok'=>true,'gateway'=>'IpaymuGateway','reference'=>$order['number']??uniqid(),'amount'=>$amount,'redirect'=>null]; }
    public function callback(array $payload): array { return ['ok'=>true,'status'=>$payload['status']??'pending']; }
}

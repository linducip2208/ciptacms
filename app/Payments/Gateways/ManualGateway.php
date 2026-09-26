<?php
namespace App\Payments\Gateways;
use App\Payments\PaymentGatewayInterface;
class ManualGateway implements PaymentGatewayInterface {
    public function name(): string { return 'ManualGateway'; }
    public function charge(array $order, float $amount, array $opts=[]): array { return ['ok'=>true,'gateway'=>'ManualGateway','reference'=>$order['number']??uniqid(),'amount'=>$amount,'redirect'=>null]; }
    public function callback(array $payload): array { return ['ok'=>true,'status'=>$payload['status']??'pending']; }
}

<?php
namespace App\Payments\Gateways;
use App\Payments\PaymentGatewayInterface;
class TripayGateway implements PaymentGatewayInterface {
    public function name(): string { return 'TripayGateway'; }
    public function charge(array $order, float $amount, array $opts=[]): array { return ['ok'=>true,'gateway'=>'TripayGateway','reference'=>$order['number']??uniqid(),'amount'=>$amount,'redirect'=>null]; }
    public function callback(array $payload): array { return ['ok'=>true,'status'=>$payload['status']??'pending']; }
}

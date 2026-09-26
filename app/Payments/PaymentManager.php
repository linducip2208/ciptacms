<?php
namespace App\Payments;
class PaymentManager {
    public function driver(?string $name=null): PaymentGatewayInterface {
        $name = $name ?: setting('payment.default','manual');
        return match($name){ 'xendit'=>new Gateways\XenditGateway(),'ipaymu'=>new Gateways\IpaymuGateway(),'tripay'=>new Gateways\TripayGateway(),'stripe'=>new Gateways\StripeGateway(), default=>new Gateways\ManualGateway() };
    }
}

<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use App\Models\{ProductCategory,Product,Customer,Coupon,Order,OrderItem,Payment,Inventory,StockMovement,Sale,SaleItem,Supplier,Purchase};
class CommerceSeeder extends Seeder {
    public function run(): void {
        $cat = ProductCategory::firstOrCreate(['slug'=>'general'],['name'=>'General']);
        $sup = Supplier::firstOrCreate(['name'=>'Supplier Utama'],['email'=>'sup@lindu.local']);
        for($i=1;$i<=8;$i++){ $p = Product::firstOrCreate(['slug'=>"produk-{$i}"],['category_id'=>$cat->id,'name'=>"Produk {$i}",'sku'=>"SKU-{$i}",'price'=>50000*$i,'cost'=>30000*$i,'stock'=>100,'is_active'=>true]); Inventory::firstOrCreate(['product_id'=>$p->id,'warehouse'=>'default'],['qty'=>100]); }
        $cust = Customer::firstOrCreate(['email'=>'budi@example.com'],['name'=>'Budi Santoso','phone'=>'0812000001','city'=>'Jakarta']);
        Coupon::firstOrCreate(['code'=>'HEMAT10'],['type'=>'percent','value'=>10,'is_active'=>true]);
        $order = Order::firstOrCreate(['number'=>'ORD-0001'],['customer_id'=>$cust->id,'status'=>'completed','payment_status'=>'paid','subtotal'=>100000,'tax'=>11000,'total'=>111000,'currency'=>'IDR','ordered_at'=>now()]);
        $prod = Product::first(); if($prod) OrderItem::firstOrCreate(['order_id'=>$order->id,'product_id'=>$prod->id],['name'=>$prod->name,'qty'=>1,'price'=>$prod->price,'total'=>$prod->price]);
        Payment::firstOrCreate(['order_id'=>$order->id],['gateway'=>'manual','method'=>'cash','amount'=>$order->total,'status'=>'paid','reference'=>'PAY-0001']);
        StockMovement::firstOrCreate(['reference'=>'INIT','product_id'=>$prod->id ?? 1],['type'=>'in','qty'=>100]);
        $sale = Sale::firstOrCreate(['number'=>'POS-0001'],['customer_id'=>$cust->id,'cashier_id'=>1,'subtotal'=>50000,'tax'=>5500,'total'=>55500,'payment_method'=>'cash','amount_paid'=>60000,'change'=>4500,'status'=>'completed','sold_at'=>now()]);
        if($prod) SaleItem::firstOrCreate(['sale_id'=>$sale->id,'product_id'=>$prod->id],['name'=>$prod->name,'qty'=>1,'price'=>$prod->price,'total'=>$prod->price]);
        Purchase::firstOrCreate(['number'=>'PO-0001'],['supplier_id'=>$sup->id,'total'=>300000,'status'=>'received','purchased_at'=>now()]);
    }
}

<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use App\Models\{Page,Post,Category,Tag,MediaFolder,Form,FormField,ContentType,Workflow,Webhook,NotificationTemplate,PageTemplate,ReusableBlock};
class CmsSeeder extends Seeder {
    public function run(): void {
        MediaFolder::firstOrCreate(['slug'=>'general'],['name'=>'General']);
        Page::firstOrCreate(['slug'=>'about'],['title'=>'About Us','body'=>'<p>Welcome to Lindu CMS.</p>','status'=>'published','published_at'=>now()]);
        Page::firstOrCreate(['slug'=>'contact'],['title'=>'Contact','body'=>'<p>Contact us.</p>','status'=>'published','published_at'=>now()]);
        Page::firstOrCreate(['slug'=>'home-hero'],['title'=>'Home Hero','body'=>'','status'=>'published','published_at'=>now(),'builder'=>['sections'=>[['type'=>'hero','heading'=>'One platform, many apps','sub'=>'Lindu CMS']]]]);
        $cat = Category::firstOrCreate(['slug'=>'news'],['name'=>'News','type'=>'post']);
        $tag = Tag::firstOrCreate(['slug'=>'lindu'],['name'=>'Lindu']);
        for($i=1;$i<=6;$i++){ $p = Post::firstOrCreate(['slug'=>"hello-lindu-{$i}"],['category_id'=>$cat->id,'author_id'=>1,'title'=>"Hello Lindu {$i}",'excerpt'=>'Demo post','body'=>'<p>Demo content for Lindu CMS.</p>','status'=>'published','published_at'=>now()]); $p->tags()->syncWithoutDetaching([$tag->id]); }
        $form = Form::firstOrCreate(['slug'=>'contact-us'],['name'=>'Contact Us','is_active'=>true]);
        foreach([['Full Name','name','text',1,1],['Email','email','email',1,1],['Message','message','textarea',1,1]] as [$l,$n,$t,$req,$o]) FormField::firstOrCreate(['form_id'=>$form->id,'name'=>$n],['label'=>$l,'type'=>$t,'is_required'=>(bool)$req,'sort_order'=>$o]);
        ContentType::firstOrCreate(['slug'=>'testimonials'],['name'=>'Testimonials','fields'=>[['name'=>'Author','slug'=>'author','type'=>'text'],['name'=>'Quote','slug'=>'quote','type'=>'long_text']]]);
        Workflow::firstOrCreate(['trigger_event'=>'form.submitted','name'=>'Notify on form'],['conditions'=>[],'actions'=>[['type'=>'send_email','to'=>'admin@lindu.local','subject'=>'New submission']],'is_active'=>true]);
        Webhook::firstOrCreate(['event'=>'order.created','name'=>'Order hook'],['url'=>'https://example.com/hooks/orders','is_active'=>false]);
        NotificationTemplate::firstOrCreate(['slug'=>'welcome-mail'],['name'=>'Welcome','channel'=>'mail','subject'=>'Welcome','body'=>'Hi {{name}}']);
        PageTemplate::firstOrCreate(['slug'=>'landing'],['name'=>'Landing','blocks'=>[['type'=>'hero'],['type'=>'features']]]);
        ReusableBlock::firstOrCreate(['slug'=>'cta-banner'],['name'=>'CTA Banner','blocks'=>[['type'=>'cta']],'is_global'=>true]);
        try{ app(\App\Core\Services\DataBuilderService::class)->createPhysicalTable(ContentType::where('slug','testimonials')->first()); }catch(\Throwable $e){}
    }
}

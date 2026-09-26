<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use App\Models\{Property,RoomType,Room,Guest,Reservation,Course,Lesson,Enrollment,Lead,Contact,Company,Pipeline,Deal,CrmActivity,MemberProfile,Vendor,Plan,Tenant,Quiz,Certificate,HousekeepingTask};
class AppsSeeder extends Seeder {
    public function run(): void {
        $prop = Property::firstOrCreate(['slug'=>'lindu-hotel'],['name'=>'Lindu Hotel','city'=>'Bandung','stars'=>4]);
        $rt = RoomType::firstOrCreate(['slug'=>'deluxe'],['property_id'=>$prop->id,'name'=>'Deluxe','base_price'=>750000,'capacity'=>2]);
        $room = Room::firstOrCreate(['property_id'=>$prop->id,'number'=>'101'],['room_type_id'=>$rt->id,'status'=>'available']);
        $guest = Guest::firstOrCreate(['email'=>'tamu@example.com'],['name'=>'Tamu Demo','phone'=>'0812000002']);
        Reservation::firstOrCreate(['number'=>'RSV-0001'],['property_id'=>$prop->id,'room_id'=>$room->id,'guest_id'=>$guest->id,'check_in'=>now()->toDateString(),'check_out'=>now()->addDay()->toDateString(),'status'=>'booked','total'=>750000,'paid'=>0]);
        HousekeepingTask::firstOrCreate(['room_id'=>$room->id,'task'=>'Cleaning'],['property_id'=>$prop->id,'status'=>'pending']);
        $course = Course::firstOrCreate(['slug'=>'laravel-dasar'],['title'=>'Laravel Dasar','description'=>'Belajar Laravel','price'=>199000,'status'=>'published']);
        $lesson = Lesson::firstOrCreate(['course_id'=>$course->id,'slug'=>'intro'],['title'=>'Intro','content'=>'<p>Halo</p>','sort_order'=>1,'is_free'=>true]);
        Quiz::firstOrCreate(['course_id'=>$course->id,'title'=>'Kuis 1'],['lesson_id'=>$lesson->id,'questions'=>[['q'=>'PHP singkatan?','options'=>['a','b'],'answer'=>0]]]);
        $lead = Lead::firstOrCreate(['email'=>'lead@example.com'],['name'=>'Lead Demo','company'=>'PT Demo','status'=>'new','value'=>5000000]);
        $comp = Company::firstOrCreate(['name'=>'PT Demo'],['email'=>'info@ptdemo.local']);
        Contact::firstOrCreate(['email'=>'kontak@ptdemo.local'],['company_id'=>$comp->id,'name'=>'Kontak Demo']);
        $pipe = Pipeline::firstOrCreate(['name'=>'Sales'],['stages'=>['new','contacted','deal','won']]);
        Deal::firstOrCreate(['title'=>'Deal Demo'],['pipeline_id'=>$pipe->id,'stage'=>'new','value'=>10000000,'status'=>'open']);
        CrmActivity::firstOrCreate(['subject'=>'Follow up'],['related_type'=>Lead::class,'related_id'=>$lead->id,'type'=>'call']);
        $member = MemberProfile::firstOrCreate(['user_id'=>3],['display_name'=>'Sinta','gender'=>'female','city'=>'Jakarta','bio'=>'Demo profile','status'=>'active']);
        Vendor::firstOrCreate(['slug'=>'toko-berkah'],['user_id'=>2,'name'=>'Toko Berkah','status'=>'approved','commission_rate'=>10]);
        Plan::firstOrCreate(['slug'=>'starter'],['name'=>'Starter','price'=>49000,'features'=>['tenants'=>1],'limits'=>['users'=>5],'is_active'=>true]);
        Plan::firstOrCreate(['slug'=>'business'],['name'=>'Business','price'=>199000,'features'=>['all modules'],'limits'=>['users'=>50],'is_active'=>true]);
    }
}

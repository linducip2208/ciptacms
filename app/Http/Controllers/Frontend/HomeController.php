<?php
namespace App\Http\Controllers\Frontend;
use App\Http\Controllers\Controller;
use App\Models\{Page,Post};
class HomeController extends Controller {
    public function index(){ $pages=[];$posts=[]; try{ $pages=Page::where('status','published')->limit(6)->get(); $posts=Post::where('status','published')->latest('published_at')->limit(6)->get(); }catch(\Throwable $e){} return view('frontend.home',compact('pages','posts')); }
    public function page(string $slug){ $page=Page::where('slug',$slug)->where('status','published')->firstOrFail(); $seo=app(\App\Core\Services\SeoService::class)->for(get_class($page),$page->id); return view('frontend.page',compact('page','seo')); }
    public function post(string $slug){ $post=Post::where('slug',$slug)->where('status','published')->firstOrFail(); try{ $post->increment('views'); }catch(\Throwable $e){} return view('frontend.post',compact('post')); }
    public function blog(){ $posts=Post::where('status','published')->latest('published_at')->paginate(12); return view('frontend.blog',compact('posts')); }
    public function sitemap(){ return response(app(\App\Core\Services\SeoService::class)->sitemap(),200,['Content-Type'=>'application/xml']); }
    public function robots(){ $d="User-agent: *\nAllow: /\nSitemap: ".url('/sitemap.xml'); return response($d,200,['Content-Type'=>'text/plain']); }
}

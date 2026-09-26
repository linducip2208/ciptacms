<?php
namespace App\Core\Services;
class BlockLibrary {
    public static function all(): array {
        return [
            ['type'=>'heading','label'=>'Heading','defaults'=>['heading'=>'Judul']],
            ['type'=>'text','label'=>'Text','defaults'=>['text'=>'Paragraf']],
            ['type'=>'image','label'=>'Image','defaults'=>['image'=>'']],
            ['type'=>'video','label'=>'Video','defaults'=>['text'=>'https://...']],
            ['type'=>'button','label'=>'Button','defaults'=>['heading'=>'Klik','link'=>'#']],
            ['type'=>'icon','label'=>'Icon','defaults'=>['heading'=>'★']],
            ['type'=>'card','label'=>'Card','defaults'=>['heading'=>'Card','text'=>'Isi']],
            ['type'=>'grid','label'=>'Grid','defaults'=>[]],
            ['type'=>'gallery','label'=>'Gallery','defaults'=>[]],
            ['type'=>'slider','label'=>'Slider','defaults'=>[]],
            ['type'=>'tabs','label'=>'Tabs','defaults'=>[]],
            ['type'=>'accordion','label'=>'Accordion','defaults'=>[]],
            ['type'=>'testimonials','label'=>'Testimonials','defaults'=>[]],
            ['type'=>'pricing','label'=>'Pricing','defaults'=>[]],
            ['type'=>'team','label'=>'Team','defaults'=>[]],
            ['type'=>'contact','label'=>'Contact','defaults'=>[]],
            ['type'=>'map','label'=>'Map','defaults'=>[]],
            ['type'=>'form','label'=>'Form','defaults'=>[]],
            ['type'=>'html','label'=>'HTML','defaults'=>['text'=>'<div></div>']],
            ['type'=>'code','label'=>'Code','defaults'=>['text'=>'// code']],
            ['type'=>'dynamic','label'=>'Dynamic content','defaults'=>['text'=>'{{latest_posts}}']],
        ];
    }
    public static function render(array $builder): string {
        $h='';
        foreach(($builder['sections']??[]) as $s){
            $h.='<section class="lindu-section">';
            foreach(($s['blocks']??[]) as $b){
                $t=$b['type']??'text'; $head=htmlspecialchars($b['heading']??''); $txt=$b['text']??'';
                $h.=match($t){
                    'heading'=>"<h2>{$head}</h2>",
                    'button'=>"<a href=\"".htmlspecialchars($b['link']??'#')."\" class=\"btn\">{$head}</a>",
                    'image'=>"<img src=\"".htmlspecialchars($b['image']??'')."\" alt=\"{$head}\">",
                    default=>"<div class=\"block-{$t}\"><b>{$head}</b><div>{$txt}</div></div>",
                };
            }
            $h.='</section>';
        }
        return $h;
    }
}

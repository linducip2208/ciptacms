<?php
namespace App\Core\Services;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
class QrService {
    public function svg(string $text, int $size=200): string {
        $renderer=new ImageRenderer(new RendererStyle($size), new SvgImageBackEnd());
        return (new Writer($renderer))->writeString($text);
    }
    public function dataUri(string $text, int $size=200): string {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($text,$size));
    }
}

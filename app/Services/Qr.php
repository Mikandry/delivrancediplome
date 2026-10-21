<?php
namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class Qr
{
    /** Compatible endroid/qr-code v5 (Builder::create) et v6 (constructeur nommé). */
    public static function dataUri(string $url, int $size = 220): string
    {
        if (method_exists(Builder::class, 'create')) {
            return Builder::create()->writer(new PngWriter())->data($url)->size($size)->margin(0)->build()->getDataUri();
        }
        $builder = new Builder(writer: new PngWriter(), data: $url, size: $size, margin: 0);
        return $builder->build()->getDataUri();
    }
}

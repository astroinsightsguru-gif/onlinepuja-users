<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PujaCategory extends Model
{
    use HasFactory;

    protected $table = 'puja_categories';
    protected $fillable = [
        'name',
        'image',
    ];

    public function pujas()
    {
        return $this->hasMany(Puja::class);
    }

    /**
     * Get the category image URL with verified sacred 200 OK fallbacks.
     */
    public function getImageAttribute($value): string
    {
        $categoryFallbacks = [
            1  => 'https://onlinepuja.live/public/storage/brain_media/brain_UggSzg4YoXyyO7ePfv_940x627.jpg',
            2  => 'https://onlinepuja.live/public/storage/brain_media/brain_FUrvDLYSVY9DFZJ9gt_940x627.jpg',
            3  => 'https://onlinepuja.live/public/storage/brain_media/brain_T2IxeHWXOutcgfwsUu_940x627.jpg',
            4  => 'https://onlinepuja.live/public/storage/brain_media/brain_S2GwG7tPc87uMlcAeM_940x627.jpg',
            5  => 'https://onlinepuja.live/public/storage/pujaCategory/pujaCategory_5_1764232801.jpg',
            6  => 'https://onlinepuja.live/public/storage/pujaCategory/pujaCategory_6_1764232830.png',
            7  => 'https://onlinepuja.live/public/storage/brain_media/brain_oeTWxOezV1EUqXbW0N_940x627.jpg',
            8  => 'https://onlinepuja.live/public/storage/brain_media/brain_50wl7KY3nnT2jSnsnF_940x627.jpg',
            9  => 'https://onlinepuja.live/public/storage/brain_media/brain_R1RC6DtXvUJS7v3GJd_940x627.jpg',
            10 => 'https://onlinepuja.live/public/storage/brain_media/brain_GFb49381IDqvcIOJzp_940x627.jpg',
            11 => 'https://onlinepuja.live/public/storage/brain_media/brain_dIuvZYDuuGALCxuEJd_940x627.jpg',
            12 => 'https://onlinepuja.live/public/storage/brain_media/brain_5NY2JcMWHprAIADLpK_940x627.jpg',
            13 => 'https://onlinepuja.live/public/storage/brain_media/brain_u38hGyJyley0LgWAln_940x627.jpg',
            14 => 'https://onlinepuja.live/public/storage/brain_media/brain_EChhsPGJdQb5BCADsk_940x627.jpg',
            15 => 'https://onlinepuja.live/public/storage/brain_media/brain_50wl7KY3nnT2jSnsnF_940x627.jpg',
        ];

        if (empty($value) || str_contains($value, 'images/pujaCategory_') || str_contains($value, 'default/image')) {
            return $categoryFallbacks[$this->id] ?? 'https://onlinepuja.live/public/frontend/homeimage/360.png';
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        return asset($value);
    }
}

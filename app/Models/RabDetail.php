<?php

namespace App\Models;

use App\Models\Concerns\TracksUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RabDetail extends Model
{
    use SoftDeletes, TracksUserActions;

    protected $fillable = [
        'rab_id',
        'nama_item',
        'jumlah_item',
        'harga_satuan',
        'subtotal',
        'keterangan',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'jumlah_item' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function rab()
    {
        return $this->belongsTo(Rab::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $item->subtotal = (float) $item->jumlah_item * (float) $item->harga_satuan;
        });
    }
}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\TracksUserActions;
class Lembaga extends Model { use SoftDeletes, TracksUserActions; protected $fillable=['cabang_id','nama_lembaga','penanggung_jawab','keterangan','status','created_by','updated_by','deleted_by']; public function cabang(){return $this->belongsTo(Cabang::class);} }

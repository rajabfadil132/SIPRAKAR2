<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\TracksUserActions;
class Lokasi extends Model { use SoftDeletes, TracksUserActions; protected $fillable=['cabang_id','nama_gedung','lantai','ruangan','keterangan','status','created_by','updated_by','deleted_by']; public function cabang(){return $this->belongsTo(Cabang::class);} }

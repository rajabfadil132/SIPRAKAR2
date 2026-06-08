<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProgressPekerjaan extends Model { protected $fillable=['pekerjaan_id','tanggal_update','progress','status','catatan','kendala','solusi','foto_sebelum','foto_proses','foto_sesudah','updated_by']; protected $casts=['tanggal_update'=>'date','progress'=>'integer']; public function pekerjaan(){return $this->belongsTo(Pekerjaan::class);} public function updater(){return $this->belongsTo(User::class,'updated_by');} }

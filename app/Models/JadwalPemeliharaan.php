<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\TracksUserActions;
class JadwalPemeliharaan extends Model { use SoftDeletes, TracksUserActions; protected $fillable=['nama_jadwal','cabang_id','lokasi_id','kategori_id','frekuensi','tanggal_mulai','tanggal_berikutnya','petugas_id','status','catatan','created_by','updated_by','deleted_by']; protected $casts=['tanggal_mulai'=>'date','tanggal_berikutnya'=>'date']; }

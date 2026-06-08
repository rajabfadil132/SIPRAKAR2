<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\TracksUserActions;
class DokumenAdministrasi extends Model { use SoftDeletes, TracksUserActions; protected $fillable=['pekerjaan_id','jenis_dokumen','nomor_dokumen','tanggal_dokumen','file_dokumen','keterangan','created_by','updated_by','deleted_by']; protected $casts=['tanggal_dokumen'=>'date']; }

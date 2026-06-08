<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\TracksUserActions;
class KategoriPekerjaan extends Model { use SoftDeletes, TracksUserActions; protected $fillable=['nama_kategori','keterangan','status','created_by','updated_by','deleted_by']; }

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\TracksUserActions;
class Vendor extends Model { use SoftDeletes, TracksUserActions; protected $fillable=['nama_vendor','jenis_vendor','kontak','email','alamat','pic','bidang_pekerjaan','status','created_by','updated_by','deleted_by']; }

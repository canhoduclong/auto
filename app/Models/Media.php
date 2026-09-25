<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Media extends Model
{
   protected $fillable = [
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'type',
        'uploaded_by',
    ];

   public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function links()
    {
        return $this->hasMany(MediaLink::class);
    }

    // Helper: Lấy URL public
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }
}

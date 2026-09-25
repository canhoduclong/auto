<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaLink extends Model
{
    protected $fillable = ['media_id', 'model_type', 'model_id', 'role'];

    public function model()
    {
        return $this->morphTo();
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    // Accessor tiện lợi để lấy URL đầy đủ
    public function getUrlAttribute()
    {
        return $this->media ? asset('storage/' . $this->media->file_path) : null;
    }
    
}

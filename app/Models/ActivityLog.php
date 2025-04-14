<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    use HasFactory;  

    // Menentukan primary key sebagai UUID  
    protected $keyType = 'string';  
    public $incrementing = false;  

    // Menentukan atribut yang dapat diisi secara massal  
    protected $fillable = [  
        'id',  
        'type',  
        'log',  
    ];  

    // Jika Anda ingin mengatur atribut yang dikembalikan dalam format JSON  
    protected $casts = [  
        'log' => 'json',  
    ];  

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid();
            }
        });
    }

}

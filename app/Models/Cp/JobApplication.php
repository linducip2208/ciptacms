<?php

namespace App\Models\Cp;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $table = 'cp_job_applications';

    protected $fillable = [
        'career_id', 'name', 'email', 'phone', 'cv_path', 'cover_letter', 'status', 'ip',
    ];

    public function career()
    {
        return $this->belongsTo(Career::class, 'career_id');
    }
}

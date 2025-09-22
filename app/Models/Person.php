<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    protected $table = 'people';

    protected $fillable = [
        'name',
        'photo_s3_key',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the face matches for this person.
     */
    public function faceMatches(): HasMany
    {
        return $this->hasMany(FaceMatch::class, 'matched_person_id');
    }
}
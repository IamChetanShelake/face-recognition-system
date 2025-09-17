<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceMatch extends Model
{
    protected $fillable = [
        'uploaded_photo_s3_url',
        'uploaded_photo_s3_key',
        'matched_person_id',
        'similarity_score',
        'is_match',
        'rekognition_response'
    ];

    protected $casts = [
        'is_match' => 'boolean',
        'similarity_score' => 'decimal:2',
        'rekognition_response' => 'array',
    ];

    /**
     * Get the person that was matched.
     */
    public function matchedPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'matched_person_id');
    }
}

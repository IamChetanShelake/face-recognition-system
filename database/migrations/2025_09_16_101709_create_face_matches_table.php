<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('face_matches', function (Blueprint $table) {
            $table->id();
            $table->string('uploaded_photo_s3_url');
            $table->string('uploaded_photo_s3_key');
            $table->foreignId('matched_person_id')->nullable()->constrained('people');
            $table->decimal('similarity_score', 5, 2)->nullable();
            $table->boolean('is_match')->default(false);
            $table->json('rekognition_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('face_matches');
    }
};

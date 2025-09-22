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
        Schema::table('face_matches', function (Blueprint $table) {
            $table->dropColumn('rekognition_response');
            $table->string('uploaded_photo_s3_key')->change();
            $table->foreignId('matched_person_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('face_matches', function (Blueprint $table) {
            $table->text('rekognition_response')->nullable();
            $table->string('uploaded_photo_s3_key')->nullable(false)->change();
            $table->foreignId('matched_person_id')->nullable(false)->change();
        });
    }
};
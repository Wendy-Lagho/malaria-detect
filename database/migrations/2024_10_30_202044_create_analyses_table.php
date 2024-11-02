<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('analyses', function (Blueprint $table) {
        $table->id();
        $table->string('image_path');
        $table->json('result_data')->nullable();
        $table->decimal('confidence_score', 5, 2)->default(0);
        $table->enum('result', ['positive', 'negative', 'inconclusive'])->nullable();
        $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
        
        // Additional useful fields for future features
        $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
        $table->timestamp('processed_at')->nullable();
        $table->string('error_message')->nullable();
        $table->integer('processing_time_ms')->nullable();
        
        // For potential report generation feature
        $table->boolean('report_generated')->default(false);
        $table->string('report_path')->nullable();
        
        // Standard timestamps
        $table->timestamps();
        $table->softDeletes();
    });
}


    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
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
        Schema::create('activity_logs', function (Blueprint $table) {  
            $table->uuid('id')->primary();
            $table->enum('type', ['Employee', 'Client', 'Project', 'Assignment', 'User']);
            $table->enum('action_type', ['Update', 'Delete', 'Create']);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('log');
            $table->timestamps();
        });  
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

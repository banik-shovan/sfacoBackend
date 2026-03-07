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
        Schema::create('member_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('date_of_birth')->nullable();
            $table->string('nid_passport_number', 100)->nullable();
            $table->string('gender', 50)->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->string('whatsapp_number', 30)->nullable()->unique();
            $table->text('current_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('articled_period_from', 7)->nullable();
            $table->string('articled_period_to', 7)->nullable();
            $table->string('principal_supervisor_name')->nullable();
            $table->string('icab_registration_no', 100)->nullable();
            $table->string('current_organization')->nullable();
            $table->string('designation')->nullable();
            $table->string('ca_status', 30)->nullable();
            $table->timestamp('profile_completed_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_profiles');
    }
};

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
        Schema::table('users', function (Blueprint $table) {
            // Composite index for common filter combinations
            $table->index(['status', 'deleted_at'], 'idx_users_status_deleted');
            $table->index(['created_at', 'deleted_at'], 'idx_users_created_deleted');
            
            // Index for last_login_at for analytics queries
            $table->index('last_login_at', 'idx_users_last_login');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_status_deleted');
            $table->dropIndex('idx_users_created_deleted');
            $table->dropIndex('idx_users_last_login');
        });
    }
};

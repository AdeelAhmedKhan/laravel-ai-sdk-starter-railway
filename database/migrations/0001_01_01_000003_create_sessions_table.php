<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sessions table — required because SESSION_DRIVER=database
 * (see .env.example / config/session.php). The web landing page
 * runs through the `web` middleware group, which starts a session;
 * without this table every web request 500s with
 * "no such table: sessions". API routes are stateless and never
 * touch it, which is why the failure only showed on GET /.
 *
 * Standard Laravel stub. user_id is a plain nullable column (no
 * foreign key): this template has no users table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};

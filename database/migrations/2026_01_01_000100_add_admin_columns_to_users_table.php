<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
            $table->string('locale', 12)->nullable()->after('is_admin');
            $table->text('two_factor_secret')->nullable()->after('locale');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->unsignedBigInteger('two_factor_last_timestamp')->nullable()->after('two_factor_confirmed_at');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamp('password_changed_at')->nullable();
        });

        // Login attempts per (hashed) e-mail address, including addresses that
        // do not exist: the behaviour is identical, so nobody can guess which
        // accounts exist.
        Schema::create('login_throttles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->unsignedInteger('failures')->default(0);
            $table->unsignedInteger('level')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_throttles');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_admin', 'locale', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
                'two_factor_last_timestamp', 'last_login_at', 'last_login_ip', 'password_changed_at',
            ]);
        });
    }
};

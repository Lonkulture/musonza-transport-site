<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->boolean('active')->default(true);
            $t->unsignedInteger('session_version')->default(1);
            $t->boolean('must_change_password')->default(true);
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('enquiries', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->string('request_key')->unique();
            $t->string('type');
            $t->string('service');
            $t->text('name');
            $t->text('email');
            $t->text('phone');
            $t->text('company')->nullable();
            $t->text('message');
            $t->text('origin')->nullable();
            $t->text('destination')->nullable();
            $t->dateTime('preferred_at')->nullable();
            $t->string('status')->default('new')->index();
            $t->string('confirmed_slot')->nullable()->unique();
            $t->text('internal_note')->nullable();
            $t->timestamps();
        });
        Schema::create('enquiry_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('enquiry_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('status');
            $t->text('note')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiry_events');
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('users');
    }
};

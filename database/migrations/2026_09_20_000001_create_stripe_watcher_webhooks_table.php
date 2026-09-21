<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('stripe-watcher.storage.table', 'stripe_watcher_webhooks'), function (Blueprint $table): void {
            $table->id();
            $table->string('event_id')->nullable()->index();
            $table->string('event_type')->nullable()->index();
            $table->string('api_version')->nullable();
            $table->boolean('livemode')->nullable();
            $table->string('status')->default('received')->index();
            $table->string('request_method', 10)->nullable();
            $table->text('request_url')->nullable();
            $table->string('request_ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_content_type')->nullable();
            $table->json('request_headers')->nullable();
            $table->longText('request_body')->nullable();
            $table->json('request_payload')->nullable();
            $table->boolean('signature_verified')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->string('exception_class')->nullable();
            $table->text('exception_message')->nullable();
            $table->longText('exception_trace')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('stripe-watcher.storage.table', 'stripe_watcher_webhooks'));
    }
};

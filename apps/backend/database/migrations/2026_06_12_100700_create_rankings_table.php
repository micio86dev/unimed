<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quizzes_completed')->default(0);
            $table->unsignedInteger('total_points')->default(0);
            $table->decimal('average_score', 5, 2)->default(0);
            $table->decimal('best_score', 5, 2)->default(0);
            $table->unsignedInteger('average_time_seconds')->default(0);
            $table->unsignedInteger('position')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index('total_points');
            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rankings');
    }
};

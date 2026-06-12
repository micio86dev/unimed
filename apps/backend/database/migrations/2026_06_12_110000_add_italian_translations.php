<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bilingual content (IT/EN). The existing text columns hold the English copy;
 * these nullable *_it columns hold the Italian translation. Resources expose
 * both so the SPA can switch language instantly, falling back to English when
 * a translation is missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->string('name_it')->nullable()->after('name');
            $table->text('description_it')->nullable()->after('description');
        });

        Schema::table('quizzes', function (Blueprint $table): void {
            $table->string('title_it')->nullable()->after('title');
            $table->text('description_it')->nullable()->after('description');
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->text('text_it')->nullable()->after('text');
            $table->text('explanation_it')->nullable()->after('explanation');
        });

        Schema::table('question_answers', function (Blueprint $table): void {
            $table->text('text_it')->nullable()->after('text');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->dropColumn(['name_it', 'description_it']);
        });

        Schema::table('quizzes', function (Blueprint $table): void {
            $table->dropColumn(['title_it', 'description_it']);
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->dropColumn(['text_it', 'explanation_it']);
        });

        Schema::table('question_answers', function (Blueprint $table): void {
            $table->dropColumn('text_it');
        });
    }
};

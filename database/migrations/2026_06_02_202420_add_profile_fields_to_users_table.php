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
            $table->string('username')->unique()->nullable()->after('name');
            $table->boolean('is_admin')->default(false)->after('username');
            $table->string('headline')->nullable()->after('is_admin');
            $table->text('bio')->nullable()->after('headline');
            $table->string('company')->nullable()->after('bio');
            $table->string('avatar_path')->nullable()->after('company');
            $table->string('website_url')->nullable()->after('avatar_path');
            $table->string('github_url')->nullable()->after('website_url');
            $table->string('twitter_url')->nullable()->after('github_url');
            $table->string('linkedin_url')->nullable()->after('twitter_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'is_admin', 'headline', 'bio', 'company',
                'avatar_path', 'website_url', 'github_url', 'twitter_url', 'linkedin_url',
            ]);
        });
    }
};

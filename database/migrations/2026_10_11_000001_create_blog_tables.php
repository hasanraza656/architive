<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Blog: categories, tags, posts (WordPress-style) and the author profile fields on users. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('phone_country');      // path under /public, e.g. uploads/avatars/x.webp
            $table->string('job_title', 120)->nullable()->after('avatar');
            $table->text('bio')->nullable()->after('job_title');
        });

        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('blog_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 80)->unique();
            $table->timestamps();
        });

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('blog_categories')->nullOnDelete();
            $table->string('title', 200);
            $table->string('slug', 190)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();                  // path under /public
            $table->string('featured_image_alt', 200)->nullable();
            $table->string('image_credit', 200)->nullable();
            $table->string('meta_title', 160)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('focus_keyword', 120)->nullable();
            $table->string('keywords', 500)->nullable();                   // comma separated, used for <meta name="keywords"> and schema
            $table->string('canonical_url')->nullable();
            $table->boolean('noindex')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->string('status', 12)->default('draft')->index();      // draft | published
            $table->dateTime('published_at')->nullable()->index();         // a future date = scheduled
            $table->unsignedSmallInteger('reading_minutes')->default(1);
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('blog_post_tag', function (Blueprint $table) {
            $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->foreignId('blog_tag_id')->constrained('blog_tags')->cascadeOnDelete();
            $table->primary(['blog_post_id', 'blog_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_tag');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('blog_tags');
        Schema::dropIfExists('blog_categories');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['avatar', 'job_title', 'bio']));
    }
};

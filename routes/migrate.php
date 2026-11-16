<?php

use App\Enums\CategoryCode;
use App\Enums\CategoryType;
use App\Enums\PostType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('category-migrate', function () {

    $oldCategories = DB::connection('mysql_old')->table('tbl_categories')->orderBy('id')->get();

    // $userId = auth()->id() ?? User::orderBy('id')->value('id');
    $userId = 2;

    if ( !$userId ) {
        return response()->json(['message' => 'No user found to assign as category owner.'], 422);
    }

    // old meta field => new meta_key
    $metaMap = [
        'meta_title'          => 'seo_title',
        'meta_description'    => 'seo_description',
        'meta_keywords'       => 'seo_keywords',
        'category_name_ar'    => 'category_name_ar',
        'description_ar'      => 'description_ar',
        'meta_title_ar'       => 'meta_title_ar',
        'meta_keywords_ar'    => 'meta_keywords_ar',
        'meta_description_ar' => 'meta_description_ar',
        'external_link'       => 'external_link',
    ];

    $inserted = 0;
    $updated = 0;
    $unknownCodes = [];

    DB::transaction(function () use ($oldCategories, $userId, $metaMap, &$inserted, &$updated, &$unknownCodes) {

        // old id => new id
        $idMap = [];

        foreach ($oldCategories as $old) {
            $existing = DB::table('categories')
                ->where('type', CategoryType::CATEGORY)
                ->where('old_id', $old->id)
                ->first();

            $code = strtoupper(trim((string) $old->category_code));
            if ( !in_array($code, CategoryCode::toArray(), true) ) {
                if ( $code !== '' ) {
                    $unknownCodes[$old->id] = $old->category_code;
                }
                $code = null;
            }

            $slug = Str::limit(trim((string) $old->category_url) ?: Str::slug($old->category_name), 150, '');
            if ( $slug === '' ) {
                $slug = 'category-' . $old->id;
            }

            $slugTaken = DB::table('categories')
                ->where('slug', $slug)
                ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
                ->exists();
            if ( $slugTaken ) {
                $slug = Str::limit($slug, 140, '') . '-' . $old->id;
            }

            $data = [
                'old_id'        => $old->id,
                'name'          => Str::limit((string) $old->category_name, 100, ''),
                'nepali_title'  => $old->nepali_title,
                'slug'          => $slug,
                'category_code' => $code,
                'type'          => CategoryType::CATEGORY,
                'description'   => $old->description,
                'menu_order'    => (int) $old->position,
                'created_at'    => $old->created_at,
                'updated_at'    => $old->updated_at,
                'deleted_at'    => $old->delete_status == '1' ? ($old->updated_at ?? now()) : null,
            ];

            if ( $existing ) {
                DB::table('categories')->where('id', $existing->id)->update($data);
                $newId = $existing->id;
                $updated++;
            } else {
                $newId = DB::table('categories')->insertGetId($data + [
                    'ulid'    => (string) Str::ulid(),
                    'user_id' => $userId,
                    'parent'  => 0,
                ]);
                $inserted++;
            }

            $idMap[$old->id] = $newId;

            foreach ($metaMap as $oldField => $metaKey) {
                $value = $old->{$oldField} ?? null;

                if ( $value === null || trim((string) $value) === '' ) {
                    DB::table('category_metas')
                        ->where('category_id', $newId)
                        ->where('meta_key', $metaKey)
                        ->delete();
                    continue;
                }

                DB::table('category_metas')->updateOrInsert(
                    ['category_id' => $newId, 'meta_key' => $metaKey],
                    ['meta_value' => $value]
                );
            }
        }

        // parents can only be resolved once every category has its new id
        foreach ($oldCategories as $old) {
            $parentOldId = (int) $old->category_id;
            $parent = ($parentOldId && $parentOldId !== (int) $old->id) ? ($idMap[$parentOldId] ?? 0) : 0;

            DB::table('categories')->where('id', $idMap[$old->id])->update(['parent' => $parent]);
        }
    });

    return response()->json([
        'total'         => $oldCategories->count(),
        'inserted'      => $inserted,
        'updated'       => $updated,
        'unknown_codes' => $unknownCodes,
    ]);

})->name('migrate.category');

Route::get('tag-migrate', function () {

    $oldTags = DB::connection('mysql_old')->table('tbl_tags')->orderBy('id')->get();

    $userId = 2;

    $inserted = 0;
    $updated = 0;

    DB::transaction(function () use ($oldTags, $userId, &$inserted, &$updated) {

        foreach ($oldTags as $old) {
            $existing = DB::table('categories')
                ->where('type', CategoryType::TAG)
                ->where('old_id', $old->id)
                ->first();

            $slug = trim((string) $old->tag_url);
            $slug = $slug !== ''
                ? mb_strtolower(trim(preg_replace('/[^\pL\pM\pN]+/u', '-', $slug), '-'))
                : Str::slug((string) $old->tag_title);
            $slug = mb_substr($slug, 0, 150);
            if ( $slug === '' ) {
                $slug = 'tag-' . $old->id;
            }

            $slugTaken = DB::table('categories')
                ->where('slug', $slug)
                ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
                ->exists();
            if ( $slugTaken ) {
                $slug = mb_substr($slug, 0, 140) . '-' . $old->id;
            }

            $data = [
                'old_id'          => $old->id,
                'name'            => mb_substr((string) $old->tag_title, 0, 100),
                'nepali_title'    => $old->nepali_title,
                'slug'            => $slug,
                'type'            => CategoryType::TAG,
                'description'     => $old->tag_body,
                'featured_status' => (int) $old->featured_status,
                'created_at'      => $old->created_at,
                'updated_at'      => $old->updated_at,
                'deleted_at'      => $old->delete_status == '1' ? ($old->updated_at ?? now()) : null,
            ];

            if ( $existing ) {
                DB::table('categories')->where('id', $existing->id)->update($data);
                $updated++;
            } else {
                DB::table('categories')->insert($data + [
                    'ulid'    => (string) Str::ulid(),
                    'user_id' => $userId,
                    'parent'  => 0,
                ]);
                $inserted++;
            }
        }
    });

    return response()->json([
        'total'    => $oldTags->count(),
        'inserted' => $inserted,
        'updated'  => $updated,
    ]);

})->name('migrate.tag');

Route::get('post-migrate', function () {

    set_time_limit(0);

    $userId = 2;

    // old meta field => new meta_key
    $metaMap = [
        'meta_title'       => 'seo_title',
        'meta_description' => 'seo_description',
        'meta_keywords'    => 'seo_keywords',
    ];

    $total = 0;
    $inserted = 0;
    $updated = 0;
    $mediaInserted = 0;

    DB::connection('mysql_old')->table('tbl_contents')->orderBy('id')
        ->chunkById(100, function ($oldPosts) use ($userId, $metaMap, &$total, &$inserted, &$updated, &$mediaInserted) {

            DB::transaction(function () use ($oldPosts, $userId, $metaMap, &$total, &$inserted, &$updated, &$mediaInserted) {

                foreach ($oldPosts as $old) {
                    $total++;

                    $existing = DB::table('posts')
                        ->where('post_type', PostType::POST)
                        ->where('old_id', $old->id)
                        ->first();

                    $slug = trim((string) $old->slug);
                    $slug = mb_substr(mb_strtolower(trim(preg_replace('/[^\pL\pM\pN]+/u', '-', $slug), '-')), 0, 200);

                    if ( $slug === '' ) {
                        $slug = (string) $old->id;
                    }

                    $slugTaken = fn ($slug) => DB::table('posts')
                        ->where('slug', $slug)
                        ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
                        ->exists();

                    $next = (int) $old->id;
                    while ( $slugTaken($slug) ) {
                        $slug = (string) ++$next;
                    }

                    $data = [
                        'old_id'         => $old->id,
                        'post_title'     => (string) $old->title,
                        'tag_line'       => $old->tag_line,
                        'second_heading' => $old->second_heading,
                        'slug'           => $slug,
                        'post_content'   => $old->content,
                        'post_excerpt'   => $old->short_content,
                        'post_status'    => $old->publish_status == '1' ? 'publish' : 'draft',
                        'post_type'      => PostType::POST,
                        'views'          => (int) $old->view_count,
                        'trending_count' => (int) $old->trending_count,
                        'show_on_front'  => $old->is_front == '1' ? 1 : 0,
                        'created_at'     => $old->created_at,
                        'updated_at'     => $old->updated_at,
                        'deleted_at'     => $old->delete_status == '1' ? ($old->updated_at ?? now()) : null,
                    ];

                    if ( $existing ) {
                        DB::table('posts')->where('id', $existing->id)->update($data);
                        $newId = $existing->id;
                        $updated++;
                    } else {
                        $newId = DB::table('posts')->insertGetId($data + [
                            'ulid'    => (string) Str::ulid(),
                            'user_id' => $userId,
                        ]);
                        $inserted++;
                    }

                    foreach ($metaMap as $oldField => $metaKey) {
                        $value = $old->{$oldField} ?? null;

                        if ( $value === null || trim((string) $value) === '' ) {
                            DB::table('post_metas')
                                ->where('post_id', $newId)
                                ->where('meta_key', $metaKey)
                                ->delete();
                            continue;
                        }

                        DB::table('post_metas')->updateOrInsert(
                            ['post_id' => $newId, 'meta_key' => $metaKey],
                            ['meta_value' => $value]
                        );
                    }

                    // featured image: "https://chaasoonline.com/uploads/x.jpg" => "uploads/x.jpg"
                    $imagePath = ltrim(rawurldecode((string) parse_url(trim((string) $old->parallex_img), PHP_URL_PATH)), '/');

                    if ( $imagePath === '' ) {
                        DB::table('post_metas')
                            ->where('post_id', $newId)
                            ->where('meta_key', 'featured_image')
                            ->delete();
                        continue;
                    }

                    $mediaId = DB::table('medias')->where('file_name', $imagePath)->value('id');

                    if ( !$mediaId ) {
                        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

                        $mediaId = DB::table('medias')->insertGetId([
                            'user_id'            => $userId,
                            'file_name'          => $imagePath,
                            'file_original_name' => pathinfo($imagePath, PATHINFO_FILENAME),
                            'file_size'          => 0,
                            'extension'          => mb_substr($extension, 0, 10),
                            'type'               => 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension),
                            'alt'                => mb_substr((string) $old->title, 0, 255),
                            'caption'            => $old->image_caption,
                            'created_at'         => $old->created_at,
                            'updated_at'         => $old->updated_at,
                        ]);
                        $mediaInserted++;
                    }

                    DB::table('post_metas')->updateOrInsert(
                        ['post_id' => $newId, 'meta_key' => 'featured_image'],
                        ['meta_value' => $mediaId]
                    );
                }
            });
        });

    return response()->json([
        'total'          => $total,
        'inserted'       => $inserted,
        'updated'        => $updated,
        'media_inserted' => $mediaInserted,
    ]);

})->name('migrate.post');

Route::get('post-category-migrate', function () {

    set_time_limit(0);

    // old id => new id
    $postMap = DB::table('posts')->where('post_type', PostType::POST)->whereNotNull('old_id')->pluck('id', 'old_id');
    $categoryMap = DB::table('categories')->where('type', CategoryType::CATEGORY)->whereNotNull('old_id')->pluck('id', 'old_id');
    $tagMap = DB::table('categories')->where('type', CategoryType::TAG)->whereNotNull('old_id')->pluck('id', 'old_id');

    // old link table => [primary key, old term column, term map]
    $sources = [
        'category_content' => ['category_content_id', 'category_id', $categoryMap],
        'content_tag'      => ['content_tag_id', 'tag_id', $tagMap],
    ];

    $result = [];

    foreach ($sources as $table => [$primaryKey, $termColumn, $termMap]) {
        $stats = ['total' => 0, 'inserted' => 0, 'missing_post' => 0, 'missing_term' => 0];

        DB::connection('mysql_old')->table($table)
            ->chunkById(500, function ($links) use ($termColumn, $termMap, $postMap, &$stats) {
                $rows = [];

                foreach ($links as $link) {
                    $stats['total']++;

                    $postId = $postMap[$link->content_id] ?? null;
                    $termId = $termMap[$link->{$termColumn}] ?? null;

                    if ( !$postId ) {
                        $stats['missing_post']++;
                        continue;
                    }
                    if ( !$termId ) {
                        $stats['missing_term']++;
                        continue;
                    }

                    $rows[] = ['post_id' => $postId, 'category_id' => $termId];
                }

                if ( $rows ) {
                    $stats['inserted'] += DB::table('category_post')->insertOrIgnore($rows);
                }
            }, $primaryKey);

        $result[$table] = $stats;
    }

    return response()->json($result);

})->name('migrate.post_category');

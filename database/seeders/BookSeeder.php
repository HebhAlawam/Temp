<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Media;
use App\Models\Post;
use App\Models\Timeline;
use App\Models\TimelineType;
use App\Traits\MediaTraits;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookSeeder extends Seeder
{
    use MediaTraits;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $timeline_id = Timeline::where('type_id', 3)->first()->id;
        // Book::factory(100)->create()->each(function ($book) use ($timeline_id) {
        //     $user_id = rand(1, 3);
        //     $book->posts()->save(Post::factory()->create([
        //         'user_id' => $user_id,
        //         'type_id' => 2,
        //         'timeline_id' => $timeline_id,
        //     ]));
        //     $book->media()->save(Media::factory()->create([
        //         'user_id' => $user_id
        //     ]));
        // });
        DB::transaction(function () {
            $timeline_id = Timeline::where('type_id', 3)->first()->id;
            $posts = [];
            $media = [];
            for ($i = 1; $i <= 100; $i++) {
                $user_id = rand(1, 3);
                $posts[] = [
                    'user_id' => $user_id,
                    'type_id' => 2,
                    'timeline_id' => $timeline_id,
                    'book_id' => $i,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $media[] = [
                    'user_id' => $user_id,
                    'book_id' => $i,
                    'media' => $this->getRandomMediaFileName(),
                    'type' => 'image',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            Post::insert($posts);
            Media::insert($media);
            Book::factory(100)->create();
        });
    }
}
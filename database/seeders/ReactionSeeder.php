<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Media;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\ReactionType;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $reaction_types = [
            [
                'type' => 'like',
                'title' => 'إعجاب',
                'text_color' => '#278036',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'love',
                'title' => 'أحببته',
                'text_color' => '#e91e63',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'haha',
                'title' => 'أضحكني',
                'text_color' => '#fbc02d',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'wow',
                'title' => 'أدهشني',
                'text_color' => '#ff9800',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'sad',
                'title' => 'أحزنني',
                'text_color' => '#2196f3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'angry',
                'title' => 'أغضبني',
                'text_color' => '#f44336',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'care',
                'title' => 'أهتم به',
                'text_color' => '#9c27b0',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        $media = [];

        foreach ($reaction_types as $index => $reaction_type) {
            $media[] =
                [
                    'reaction_type_id' => $index + 1,
                    'type' => 'image',
                    'media' => 'reactions/' . $reaction_type['type'] . '.svg',
                    'user_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
        }
        ReactionType::insert($reaction_types);
        Media::insert($media);

        $posts = Post::inRandomOrder()->limit(100)->get();
        $users = User::inRandomOrder()->limit(100)->get();
        $comments = Comment::inRandomOrder()->limit(100)->get();

        $reactions = [];
        //generate 100 reactions on different posts 
        foreach ($posts as $post) {
            foreach ($users as $user) {
                $reactions[] = [
                    'user_id' => $user->id,
                    'type_id' => 1,
                    'post_id' => $post->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Reaction::insert($reactions);

        $reactions = [];
        //generate 100 reactions on different comments
        foreach ($comments as $comment) {
            foreach ($users as $user) {
                $reactions[] = [
                    'user_id' => $user->id,
                    'type_id' => 1,
                    'comment_id' => $comment->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Reaction::insert($reactions);
    }
}
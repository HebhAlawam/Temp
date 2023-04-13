<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(PermissionsSeeder::class);
        $this->call(TimelineTypeSeeder::class);
        $this->call(GroupSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(PostSeeder::class);
        $this->call(RateSeeder::class);
        $this->call(TypeSectionSeeder::class);
        $this->call(BookStatisticsSeeder::class);
        $this->call(BookTypeSeeder::class);
        $this->call(BookSeeder::class);
        $this->call(ThesisTypeSeeder::class);
        $this->call(ThesisSeeder::class);

        $this->call(FriendSeeder::class);

        // $this->call(UserGroupSeeder::class);
        // $this->call(RateSeeder::class);
        $this->call(ReactionSeeder::class);
        $this->call(ExceptionTypeSeeder::class);
        $this->call(GroupTypeSeeder::class);
        $this->call(PostTypeSeeder::class);

        $this->call(TimelineTypeSeeder::class);
        $this->call(BookStatisticsSeeder::class);
        $this->call(WeekSeeder::class);
        // $this->call(MarksSeeder::class);
        $this->call(ModificationReasonSeeder::class);
        $this->call(ModifiedThesesSeeder::class);
        // $this->call(InfographicSeeder::class);
        // $this->call(InfographicSeriesSeeder::class);
        // $this->call(ArticleSeeder::class);
        // $this->call(CommentSeeder::class);
    }
}
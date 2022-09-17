<?php

namespace Database\Factories;

use App\Traits\ThesisTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ThesisFactory extends Factory
{
    // use ThesisTraits;
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $max_length = $this->faker->randomElement([0, random_int(100, 200), random_int(201, 300), random_int(301, 400), random_int(401, 500)]);
        $type_id = $this->faker->numberBetween(1, 2);
        $total_screenshots = ($max_length > 0) ? 0 : $this->faker->numberBetween(1, 10);
        $total_pages = ($max_length > 0 || $total_screenshots > 0) ? random_int(3, 100) : 0;

        return [
            'comment_id' => 1,
            'user_id' => 1,
            'book_id' => 1,
            'mark_id' => 1,
            'max_length' => $max_length,
            'type_id' => $type_id,
            'total_pages' => $total_pages,
            'total_screenshots' =>  $total_screenshots,
        ];
    }
}
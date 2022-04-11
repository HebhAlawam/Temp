<?php

namespace Database\Seeders;

use App\Models\InfographicSeries;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;


class InfographicSeriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $section= ['علمي', 'تاريخي', 'ديني', 'سياسي' , 'انجليزي' , 'ثقافي' ,'تربوي' ,'تنمية'];
        for($i=0; $i<5; $i++){
            InfographicSeries::create([
                'title' => Str::random(15),
                'section' => $section[rand(0,7)],
            ]);
        }
    }
}

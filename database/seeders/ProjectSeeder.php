<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

use App\Models\Project;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Project::truncate();

        $json = File::get('database/data/projects.json');
        $projects = json_decode($json);

        $insertData = [];
        foreach ($projects as $key => $value) {
            $insertData[] = [
                'id' => $value->id,
                'name' => $value->name,
            ];
        }

        Project::insert($insertData);
    }
}

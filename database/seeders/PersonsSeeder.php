<?php

namespace Database\Seeders;

use App\Models\Person;
use Illuminate\Database\Seeder;

class PersonsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->warn( PHP_EOL . 'Creating Persons' );

        $count = 1000;

        $this->command->getOutput()->progressStart( $count );
        for( $i = 0; $i < $count; $i++ )
        {
            Person::factory(1)->create();

            $this->command->getOutput()->progressAdvance();
        }

        $this->command->getOutput()->progressFinish();
        $this->command->warn( PHP_EOL . 'Persons created' );
    }
}

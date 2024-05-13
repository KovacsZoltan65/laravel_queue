<?php

/*
    php artisan generate:csv -R 1000
 * php artisan generate:csv --record_count=1000
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:csv '
            . '{--R|record_count= : Rekordok száma}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adatfájl generálása';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $record_count = $this->option('record_count');
        $file_name = 'data_' . $record_count . '_' . date('Y-m-d-H-i-s') . '.csv';
        
        $header = 'name;email;password';
        $data = $header . PHP_EOL;
        for( $i = 0; $i <= $record_count; $i++ )
        {
            $name = fake()->name();
            $email = fake()->email();
            $password = 'Pa$$w0rd';
            
            $data .= $name . ';' . $email. ';'. $password . PHP_EOL;
        }
        //dd($data);
        $path = '/persons/' . $file_name;
        //file_put_contents($path, $data);
        \Storage::put($path, $data);
    }
}

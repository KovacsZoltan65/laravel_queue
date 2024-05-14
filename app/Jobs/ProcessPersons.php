<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPersons implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $personsData;
    /**
     * Create a new job instance.
     */
    public function __construct($personsData)
    {
        $this->personsData = $personsData;
        //dd($this->personsData);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try
        {
            foreach( $this->personsData as $person )
            {
\Log::info('$person: ' . print_r($person, true));
                \App\Models\Person::create($person);
                //$new_person = new Person();
                //$new_person->name = $person->name;
                //$new_person->email = $person->email;
                //$new_person->password = $person->password;
                //$new_person->save();
            }
        }
        catch( \Exception $e )
        {
            //
        }
    }
}

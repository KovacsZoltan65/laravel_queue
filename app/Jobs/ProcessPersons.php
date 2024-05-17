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

    /**
     * A feldolgozandó személyek adatai.
     *
     * @var array
     */
    public array $personsData;

    /**
     * Create a new job instance.
     */
    public function __construct($personsData)
    {
        /**
         * Tárolja a személyek adatait az osztálytulajdonban.
         *
         * @param array $personsData A kezelendő személyek adatai.
         */
        $this->personsData = $personsData;
    }

    public function handle(): void
    {
        // Ismételje meg az egyes személyek adatait, és hozzon létre egy új személymodell-példányt.
        // Ha kivétel történik, naplózza a hibaüzenetet, és állítsa le a parancsfájl végrehajtását.
        try
        {
            foreach( $this->personsData as $person )
            {
                try{
                    // Hozzon létre egy új személymodell példányt a megadott személyadatokkal.
                    $personModel = \App\Models\Person::create($person);
                }
                catch( \Exception $e ){
                    // Naplózza a hibaüzenetet, és állítsa le a szkript végrehajtását.
                    \Log::info('ProcessPersons@handle error: ' . print_r($e->getMessage(), true));
                    dd($e->getMessage());
                }
            }
        }
        catch( \Exception $e )
        {
            // Naplózza a hibaüzenetet, és állítsa le a szkript végrehajtását.
            \Log::info('ProcessPersons@handle error: ' . print_r($e->getMessage(), true));
            dd($e->getMessage());
        }
    }
}

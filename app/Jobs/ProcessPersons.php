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
    public $personsData;

    /**
     * Hozzon létre egy új feladatpéldányt.
     */
    public function __construct($personsData)
    {
        /**
         * Tárolja a személyek adatait az osztálytulajdonban.
         *
         * @param array $personsData A kezelendő személyek adatai.
         */
        $this->personsData = $personsData;
        //dd($this->personsData);
    }

    /**
     * Hajtsa végre a feladatot.
     */
    public function handle(): void
    {
        // Ismételje meg az egyes személyek adatait, és hozzon létre egy új személymodell-példányt.
        // Ha kivétel történik, naplózza a hibaüzenetet, és állítsa le a parancsfájl végrehajtását.
        try
        {
            foreach( $this->personsData as $person )
            {
                // Hozzon létre egy új személymodell példányt a megadott személyadatokkal.
                \App\Models\Person::create($person);
                
                // Létrehozás vagy frissítés
                //\App\Models\Person::updateOrCreate(
                //    ['name' => $person->name, 'email' => $person->email], 
                //    ['password' => $person->password]
                //);
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

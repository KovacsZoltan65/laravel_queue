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
\Log::info('ProcessPersons@handle  $person: ' . print_r($person, true));
                // Hozzon létre egy új személymodell példányt a megadott személyadatokkal.
                //\App\Models\Person::create($person);
                
                //$old_person = \App\Models\Person::get(['email' => $person->email]);
               
                
                /**
                 * Létrehozza vagy frissíti a személymodell-példányt a megadott személyadatokkal.
                 * A létrehozás vagy frissítés funkciót a Laravel Eloquent ORM updateOrCreate metódusa biztosítja.
                 * 
                 * @param array $attributes A létrehozandó vagy frissítendő személy adatai.
                 * @param array $values A frissítendő személy adatai.
                 * @return \App\Models\Person A létrehozott vagy frissített személymodell-példány.
                 */
                $person = \App\Models\Person::updateOrCreate(
                    // A létrehozás vagy frissítés alapját képezi a személy neve és email címe.
                    ['name' => $person['name'], 'email' => $person['email']], 
                    // A frissítendő személy adatai.
                    [
                        'name' => $person['name'], 
                        'email' => $person['email'], 
                        'password' => $person['password']
                    ]
                );
//\Log::info('ProcessPersons@handle  $person: ' . print_r($person, true));
            }
        }
        catch( \Exception $e )
        {
            // Naplózza a hibaüzenetet, és állítsa le a szkript végrehajtását.
            \Log::error('ProcessPersons@handle error: ' . print_r($e->getMessage(), true));
            //dd($e->getMessage());
        }
    }
}

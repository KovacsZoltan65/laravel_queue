<?php

namespace App\Imports;

use App\Models\Person;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PersonImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    /**
     * @param array $row
     *
     * @return Person|null
     */
    public function model(array $row)
    {
        /**
         * Térképezze fel az Excel sort a Person modell egy példányára.
         *
         * @return Person
         */
        return new Person([
            /**
             * A személy neve
             *
             * @var string
             */
            'name' => $row['name'],

            /**
             * A személy email címe
             *
             * @var string
             */
            'email' => $row['email'],

            /**
             * A személy jelszava. 
             * Megjegyzés: ez normál helyzetekben nem ajánlott, 
             * de az importálási folyamat során szükséges, 
             * mert a jelszó mező nem lehet null értékű, 
             * és nem akarunk kivételt dobni, ha az null.
             *
             * @var string
             */
            'password' => $row['password'],
        ]);
    }

    public function chunkSize (): int
    {
        return 1000;
    }

    public function headingRow(): int
    {
        return 1;
    }
}

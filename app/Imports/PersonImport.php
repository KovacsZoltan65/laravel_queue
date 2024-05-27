<?php

namespace App\Imports;

use App\Models\Person;
use Maatwebsite\Excel\Concerns\ToModel;

class PersonImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Person([
            'name' => '',
            'email' => '',
            'password' => '',
        ]);
    }

    public function chunkSize (): int
    {
        return 1000;
    }

}

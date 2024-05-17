<?php

namespace App\Http\Controllers;

use App\Models\JobBatch;
use Illuminate\Bus\Batch;
use App\Jobs\ProcessPersons;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;

class UploadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('upload');
    }

    public function progress()
    {
        return view('progress');
    }

    public function uploadAndStore(Request $request)
    {
        /**
         * A CSV-fájl mezőinek felosztására használt elválasztó.
         *
         * @var string
         */
        // Az alapértelmezett elválasztó ';'
        $separator = ',';

        /**
         * Állítsa be az elválasztót a kérés értékére, ha meg van adva.
         *
         * @param \Illuminate\Http\Request $request A HTTP kérés objektuma.
         * @return void
         */
        // Ellenőrizze, hogy az elválasztó megadva van-e a kérelemben
        if($request->has('separator')) {
            // Állítsa az elválasztót a kérés értékére
            $separator = $request->input('separator');
        }

        try {

            /**
             * Check if the request has a CSV file.
             *
             * @param \Illuminate\Http\Request $request The HTTP request object.
             * @return bool Returns true if the request has a CSV file, false otherwise.
             */
            // Check if the request has a CSV file
            if ($request->has('csvFile')) {

                // Get the original name of the uploaded CSV file
                /**
                 * The original name of the uploaded CSV file.
                 *
                 * @var string
                 */
                $fileName = $request->csvFile->getClientOriginalName();
                // Add comments to explain the purpose of the code
                // We use the getClientOriginalName() method to get the original name of the uploaded file
                // This is useful because it allows us to store the file with its original name
                // and not just use a randomly generated name
                
                // Build the file path by concatenating the uploads directory path
                // and the name of the uploaded file.
                /**
                 * The file path to the uploaded CSV file.
                 *
                 * @var string
                 */
                $fileWithPath = public_path('uploads') . '/' . $fileName;

                // Check if the file already exists in the specified path
                // If the file does not exist, move the uploaded file to the specified path
                if( !file_exists($fileWithPath) ) {
                    /**
                     * Moves the uploaded file to the specified path.
                     *
                     * @param string $destinationPath The path to move the file to.
                     * @param string $fileName The name of the file to be moved.
                     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException If the file is not found.
                     * @return void
                     */
                    $request->csvFile->move(public_path('uploads'), $fileName);
                }
                
                /**
                 * Tárolja a fejléc változót.
                 * A CSV-fájl fejlécének tárolására szolgál.
                 *
                 * @var null
                 */
                $header = null;

                /**
                 * Tárolja a CSV-fájl adatokat.
                 * A CSV-fájl adatait tároló tömb.
                 *
                 * @var array
                 */
                $dataFromCsv = [];

                //$records = array_map('str_getcsv', file($fileWithPath));
                
                /**
                 * Maps the CSV records to an array of arrays.
                 *
                 * @param string $separator The separator used in the CSV file.
                 * @return array An array of arrays, where each inner array represents a record.
                 */
                $records = array_map(function($v) use($separator) {
                    // Call str_getcsv with the separator to split the record into an array
                    return str_getcsv($v, $separator);
                }, file($fileWithPath));
                // The array_map function applies the given callback function to each element of the array.
                // In this case, the callback function is an anonymous function that uses the $separator variable
                // to split the record into an array of values.
                // The result is an array of arrays, where each inner array represents a record.


                // Iterate over each record in the CSV file
                foreach($records as $record) {
                    /**
                     * Check if the header is not set.
                     * If the header is not set, set it to the current record.
                     * Otherwise, add the record to the dataFromCsv array.
                     */
                    if( !$header ) {
                        // Set the header to the current record
                        $header = $record;
                    }
                    else {
                        // Add the record to the dataFromCsv array
                        $dataFromCsv[] = $record;
                    }
                }
            }
            
            // Felosztja az adattömböt kisebb, egyenként 300 rekordot tartalmazó tömbre
            $dataFromCsv = array_chunk($dataFromCsv, 300);
            
            // Hozzon létre egy új üres köteget, és küldje el a tételt.
            $batch = Bus::batch([])->dispatch();
            
            // A személyeket tartalmazó tömb.
            $personsData = [];
            
            /**
             * Böngéssze végig a CSV-fájl egyes adatcsomagjait, 
             * és hozzon létre egy tömböt a személyadatokból minden rekordhoz. 
             * Ezután adjon hozzá egy új ProcessPersons-feladatot a köteghez.
             *
             * @var int $index Kulcs az adattömbhöz
             * @var array $dataCsv A CSV-fájlból származó adatcsomag
             * @var array $data Egyetlen rekord az adattömbből
             */
            foreach( $dataFromCsv as $index => $dataCsv ) {
                foreach( $dataCsv as $data ) {
                    $personsData[$index][] = array_combine($header, $data);
                }
                
                /**
                 * Adjon hozzá egy új ProcessPersons-feladatot a köteghez. 
                 * Paraméterként adja át a személyadatok darabját.
                 */
                $batch->add(new ProcessPersons($personsData[$index]));
                //ProcessPersons::dispatch($personsData[$index]);
            }
            
            /**
             * Tárolja a munkamenet utolsó kötegazonosítóját.
             *
             * Ez a CSV-fájl feldolgozása után a felhasználó 
             * átirányítására szolgál a folyamatoldalra.
             *
             * @param \Illuminate\Support\Facades\Session $session A munkamenet-példány.
             * @param string                              $id      Az utolsó köteg azonosítója.
             * @return void
             */
            session()->put('lastBatchId', $batch->id);
            
            /**
             * A felhasználó átirányítása a folyamatoldalra, paraméterként a kötegazonosítóval.
             * A kötegazonosító a CSV-fájl feldolgozási folyamatának lekérésére szolgál.
             * 
             * @param string $batchId A CSV-fájlfeldolgozási feladatokat tartalmazó köteg azonosítója.
             * @return \Illuminate\Http\RedirectResponse
             */
            return redirect('/progress?id=' . $batch->id);
        }
        catch( \Exception $e ) {
            \Log::error( $e->getMessage() );
            dd($e->getMessage());
        }
    }

    /**
     * Tekintse meg a CSV-tárfolyamat előrehaladását.
     *
     * Ez a funkció lekéri a CSV tárolási folyamat előrehaladását.
     * Először ellenőrzi, hogy az 'id' paraméter szerepel-e a kérésben, 
     * és ha nem, akkor lekéri a munkamenet utolsó kötegazonosítóját.
     * Ezután ellenőrzi, hogy létezik-e a megadott azonosítóval rendelkező köteg.
     * Ha igen, akkor JSON-válaszként adja vissza a köteg részleteit.
     *
     * @param \Illuminate\Http\Request          $request A HTTP kérés objektuma.
     * @return \Illuminate\Http\JsonResponse    A köteg részleteit tartalmazó JSON-válasz.
     */
    public function progressForCsvStoreProcess(Request $request)
    {
        try{
            // Szerezze be a kötegazonosítót a kérelemből vagy a munkamenetből
            $batchId = $request->id ?? session()->get('lastBatchId');

            // Ellenőrizze, hogy létezik-e a megadott azonosítóval rendelkező köteg
            if(JobBatch::where('id', $batchId)->count()) {
                $response = JobBatch::where('id', $batchId)->first();
                
                // Adja vissza a köteg részleteit JSON-válaszként
                return response()->json($response);
            }
        }catch( \Exception $e ){
            // Naplózza a hibát, és jelenítsen meg egy hibakeresési üzenetet
            \Log::error($e);
            dd('UploadController@progressForCsvStoreProcess error: ', $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

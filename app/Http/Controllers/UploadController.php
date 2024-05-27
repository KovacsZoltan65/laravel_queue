<?php

namespace App\Http\Controllers;

use App\Imports\PersonImport;
use App\Jobs\ProcessPersons;
use App\Models\JobBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;

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
    
    public function show_import()
    {
        return view('import');
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
         * @param Request $request A HTTP kérés objektuma.
         * @return void
         */
        // Ellenőrizze, hogy az elválasztó megadva van-e a kérelemben
        if($request->has('separator')) {
            // Állítsa az elválasztót a kérés értékére
            $separator = $request->input('separator');
        }

        try {

            /**
             * Ellenőrizze, hogy a kérelem tartalmaz-e CSV-fájlt.
             *
             * @param Request $request A HTTP kérés objektuma.
             * @return bool Igaz értéket ad vissza, ha a kérés CSV-fájlt tartalmaz, egyébként false értéket.
             */
            // Ellenőrizze, hogy a kérelem tartalmaz-e CSV-fájlt
            if ($request->has('csvFile')) {

                /**
                 * A feltöltött CSV-fájl eredeti neve.
                 *
                 * @var string
                 */
                $fileName = $request->csvFile->getClientOriginalName();

                // A getClientOriginalName() metódust használjuk a feltöltött fájl 
                // eredeti nevének lekérésére. Ez azért hasznos, mert lehetővé teszi, 
                // hogy a fájlt az eredeti nevével tároljuk, és ne csak véletlenszerűen 
                // generált nevet használjunk
                
                // Építse fel a fájl elérési útját a feltöltési könyvtár elérési útjának 
                // és a feltöltött fájl nevének összefűzésével.
                /**
                 * A feltöltött CSV-fájl elérési útja.
                 *
                 * @var string
                 */
                $fileWithPath = public_path('uploads') . '/' . $fileName;

                // Ellenőrizze, hogy a fájl létezik-e már a megadott elérési úton
                // Ha a fájl nem létezik, helyezze át a feltöltött fájlt a megadott elérési útra
                if( !file_exists($fileWithPath) ) {
                    /**
                     * Áthelyezi a feltöltött fájlt a megadott útvonalra.
                     *
                     * @param string $destinationPath A fájl áthelyezésének elérési útja.
                     * @param string $fileName Az áthelyezendő fájl neve.
                     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException Ha a fájl nem található.
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
                 * A CSV-rekordokat tömbök tömbjéhez rendeli hozzá.
                 *
                 * @param string $separator A CSV-fájlban használt elválasztó.
                 * @return array Tömbök tömbje, ahol minden belső tömb egy rekordot jelöl.
                 */
                $records = array_map(function($v) use($separator) {
                    // Hívja az str_getcsv-t az elválasztóval a rekord tömbre való felosztásához
                    return str_getcsv($v, $separator);
                }, file($fileWithPath));
                // Az array_map függvény a megadott visszahívási függvényt alkalmazza a tömb minden elemére.
                // Ebben az esetben a visszahívási függvény egy névtelen függvény, amely a $separator változót használja
                // hogy a rekordot értékek tömbjére ossza fel.
                // Az eredmény egy tömbtömb, ahol minden belső tömb egy rekordot jelent.

                /**
                 * Tárolja a CSV-fájl fejlécét.
                 * A CSV-fájl fejlécének tárolására szolgál.
                 *
                 * @var array
                 */
                $header = array_shift($records);

                
                // Végigjárja a CSV-fájl minden rekordját
                foreach($records as $record) {
                    $dataFromCsv[] = array_combine($header, $record);
                    /**
                     * Check if the header is not set.
                     * Ha a fejléc nincs beállítva, állítsa be az aktuális rekordra.
                     * Ellenkező esetben adja hozzá a rekordot a dataFromCsv tömbhöz.
                     */
                //    if( !$header ) {
                        // Set the header to the current record
                //        $header = $record;
                //    }
                //    else {
                        // Add the record to the dataFromCsv array
                //        $dataFromCsv[] = $record;
                //    }
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
             * @param Session $session A munkamenet-példány.
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
     * @param Request          $request A HTTP kérés objektuma.
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

    public function import(Request $request)
    {
        if($request->has('file')) {
            $fileName = $request->file->getClientOriginalName();
            $fileWithPath = public_path('uploads') . '/' . $fileName;
            
            if( !file_exists($fileWithPath) ) {
                //
                $request->file->move(public_path('uploads'), $fileName);
            }
        }
        
        Excel::import(new PersonImport(), $fileWithPath);
        
        
        //$file = $request->file('file');
        //dd($file);
        //Excel::import(new PersonImport(), $file);
        
        return back()->with('success', 'CSV fájl importálása sikeres.');
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

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
     * Jelenítse meg a feltöltési űrlap nézetet.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        // A feltöltési űrlap nézetének visszaadása.
        return view('upload');
    }

    /**
     * Jelenítse meg a folyamat nézetét.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function progress()
    {
        // Visszatérés a folyamat nézetéhez.
        return view('progress');
    }

    /**
     * Kezelje a fájlfeltöltést és tárolja az adatbázisban.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function uploadAndStore(Request $request): RedirectResponse
    {
        /**
         * A CSV-fájl mezőinek felosztására használt elválasztó.
         *
         * @var string
         */
        // Az alapértelmezett elválasztó ';'
        $separator = ';';
        
        /**
         * Állítsa be az elválasztót a kérés értékére, ha meg van adva.
         *
         * @param \Illuminate\Http\Request $request A HTTP kérés objektuma.
         * @return void
         */
        // Ellenőrizze, hogy az elválasztó megadva van-e a kérelemben
        if ($request->has('separator')) {
            // Állítsa az elválasztót a kérés értékére
            $separator = $request->input('separator');
        }
        
        try {
            // Ha van feltöltött fájl, akkor ...
            if( $request->has('csvFile') ) {
                // Fájl neve
                $fileName = $request->csvFile->getClientOriginalName();
                // Fájl neve az elérési úttal
                $fileWithPath = public_path('uploads') . '/' . $fileName;
                // Ha még nem létezik a fájl, akkor ...
                if( !file_exists($fileWithPath) ) {
                    // Átmozgatom a public/uploads könyvtárba
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
                
                $records = array_map(function($v){ 
                    return str_getcsv($v, $separator);
                }, file($fileWithPath));
                
                /**
                 * Tárolja a CSV-fájl fejlécét.
                 * A CSV-fájl fejlécének tárolására szolgál.
                 *
                 * @var array
                 */
                $header = array_shift($records);
                
                /**
                 * Iteráljon minden rekordon, és egyesítse a fejlécet és a rekordot 
                 * egy asszociatív tömbbe.
                 *
                 * @param array $records A CSV-fájl rekordjainak tömbje.
                 * @param array $header A CSV-fájl fejléce.
                 * @return array A rekordok tömbje a fejlécekkel kulcsként.
                 */
                foreach( $records as $record ){
                    // Kombinálja a fejlécet és a rekordot egy asszociatív tömbbe.
                    // array_combine() egy tömböt ad vissza úgy, hogy egy tömböt használ a kulcsokhoz és egy másikat az értékekhez.
                    $dataFromCsv[] = array_combine($header, $record);
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
                /**
                 * Böngésszen végig minden rekordon az adattömbben, 
                 * és hozzon létre egy asszociatív tömböt a személyadatokból.
                 *
                 * @var array $dataSingleRecord Egyetlen rekord az adattömbből
                 */
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
            // Naplózza a hibát, és jelenítsen meg egy hibakeresési üzenetet
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
     * Mutassa meg az űrlapot az új erőforrás létrehozásához.
     */
    public function create()
    {
        //
    }

    /**
     * Tároljon egy újonnan létrehozott erőforrást a tárhelyen.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Jelenítse meg a megadott erőforrást.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Mutassa meg az űrlapot a megadott erőforrás szerkesztéséhez.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Frissítse a megadott erőforrást a tárhelyen.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Távolítsa el a megadott erőforrást a tárhelyről.
     */
    public function destroy(string $id)
    {
        //
    }
}

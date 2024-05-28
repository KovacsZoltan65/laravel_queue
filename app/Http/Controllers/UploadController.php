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
                 * A getClientOriginalName() metódust használjuk a feltöltött fájl
                 * eredeti nevének lekérésére. Ez azért hasznos, mert lehetővé teszi,
                 * hogy a fájlt az eredeti nevével tároljuk, és ne csak véletlenszerűen 
                 * generált nevet használjunk
                 *
                 * @var string
                 */
                $fileName = $request->csvFile->getClientOriginalName();

                /**
                 * A feltöltött CSV-fájl elérési útja.
                 * Felépíti a fájl elérési útját a feltöltési könyvtár elérési útjának 
                 * és a feltöltött fájl nevének összefűzésével.
                 *
                 * @var string
                 */
                $fileWithPath = public_path('uploads') . '/' . $fileName;

                /**
                 * Ellenőrzi, hogy a fájl létezik-e már a megadott elérési úton.
                 * Ha a fájl nem létezik, helyezze át a feltöltött fájlt a megadott elérési útra
                 */
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
                
                /**
                 * A CSV-rekordokat tömbök tömbjéhez rendeli hozzá.
                 * Az array_map függvény a megadott visszahívási függvényt alkalmazza a tömb minden elemére.
                 * Ebben az esetben a visszahívási függvény egy névtelen függvény, amely a $separator változót használja
                 * hogy a rekordot értékek tömbjére ossza fel.
                 * Az eredmény egy tömbtömb, ahol minden belső tömb egy rekordot jelent.
                 *
                 * @param string $separator A CSV-fájlban használt elválasztó.
                 * @return array Tömbök tömbje, ahol minden belső tömb egy rekordot jelöl.
                 */
                $records = array_map(function($v) use($separator) {
                    // Hívja az str_getcsv-t az elválasztóval a rekord tömbre való felosztásához
                    return str_getcsv($v, $separator);
                }, file($fileWithPath));
                

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
                }
            }
            
            /**
             * Felosztja az adattömböt kisebb, egyenként 300 rekordot tartalmazó tömbre.
             *
             * Az array_chunk függvény az adattömböt osztja fel 300 rekordonként. Ezzel biztosítjuk, hogy a
             * CSV-fájl tartalmát a munkamenetekben kezeljük, és ne egy nagy tömb alakjában.
             * A felosztott tömbökben minden rekordon egy személy adatokat tárolunk.
             *
             * @param array $dataFromCsv Az adattömb.
             * @return array Tömb, ahol minden belső tömb 300 rekordonként osztott adatokat tartalmaz.
             */
            $dataFromCsv = array_chunk($dataFromCsv, 300);
            
            /**
             * Hozzon létre egy új üres köteget, és küldje el a tételt.
             *
             * A Bus::batch függvény segítségével hozzuk létre a munkamenetet, amely a CSV-fájl tartalmát feldolgozza.
             * A dispatch függvényt használjuk a munkamenet tételére, amelyen keresztül a személyeket tartalmazó tömböt feldolgozza.
             * A tétel a ProcessPersons osztály egy példányára hivatkozik, amely a személyeket tartalmazó tömböt feldolgozza.
             *
             * @var \Illuminate\Contracts\Queue\Queue A munkamenet, amely a CSV-fájl tartalmát feldolgozza.
             */
            $batch = Bus::batch([])->dispatch();
            
            /**
             * A személyeket tartalmazó tömb. Ebben a tömbben minden rekordon egy személy adatokat tartalmaz.
             * A tömböt a CSV-fájl tartalmából konvertáljuk, és a munkamenetekben kezeljük.
             *
             * @var array $personsData
             */
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
                    /**
                     * Töltse fel a személyadatokat a $personsData tömbbe.
                     *
                     * A $header tömbben a CSV-fájl mezőinek nevei találhatók,
                     * míg a $data tömbben a mezők értékei.
                     * Az array_combine függvényt használjuk, hogy a mezőneveken alapuló kulcsokkal
                     * rendelje a személyadatokat a tömbbe.
                     *
                     * @param array $header A CSV-fájl mezőinek nevei.
                     * @param array $data A mezők értékei.
                     */
                    $personsData[$index][] = array_combine($header, $data);
                }
                
                /**
                 * Adjon hozzá egy új ProcessPersons-feladatot a köteghez. 
                  *
                  * Paraméterként átadja a személyadatok darabját, amelyet
                  * a ProcessPersons-jobb a feladatban kezel.
                  *
                  * @param array $personsData A személyadatok darabja, amelyet
                  *                            a ProcessPersons-jobb feldolgoz.
                  *
                 */
                $batch->add(new ProcessPersons($personsData[$index]));
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
            /**
             * Szerezze be a kötegazonosítót a kérelemből vagy a munkamenetből.
             * 
             * Ha a kérelemben megadott 'id' paraméter szerepel, akkor ezt használjuk.
             * Ha nem, akkor a munkamenetből szerezze be az utolsó kötegazonosítóját.
             * 
             * @var string $batchId A CSV-tárolási folyamatban részt vevő köteg azonosítója.
             */
            $batchId = $request->id ?? session()->get('lastBatchId');

            /**
             * Ellenőrizze, hogy létezik-e a megadott azonosítóval rendelkező köteg.
             * 
             * Ha igen, akkor számolja meg a kötegazonosítóval rendelkező kötegek számát.
             * 
             * @var int $count A kötegek száma a megadott azonosítóval rendelkező kötegek között.
             */
            if( ( $count = JobBatch::where('id', $batchId)->count() ) ) {
                /**
                 * Szerezze be a megadott azonosítóval rendelkező köteg részleteit.
                 * 
                 * Ha létezik a megadott azonosítóval rendelkező köteg, akkor ezt a köteget
                 * visszaadja a JSON-válaszként.
                 * 
                 * @var array $response A megadott azonosítóval rendelkező köteg részletei.
                 */
                $response = JobBatch::where('id', $batchId)->first();
                
                /**
                 * Visszaadja a megadott azonosítóval rendelkező köteg részleteit JSON-válaszként.
                 *
                 * @var array $response A megadott azonosítóval rendelkező köteg részletei.
                 *
                 * @return \Illuminate\Http\JsonResponse    A köteg részleteit tartalmazó JSON-válasz.
                 */
                return response()->json($response);
            }
        }catch( \Exception $e ){
            // Naplózza a hibát, és jelenítsen meg egy hibakeresési üzenetet
            \Log::error($e);
            dd('UploadController@progressForCsvStoreProcess error: ', $e->getMessage());
        }
    }

    /**
     * Importálja a megadott CSV fájlt.
     *
     * Ha a kérésben van fájl, akkor megkeresi a fájl nevet, és megpróbálja 
     * letenni azt a 'uploads' mappába. Ha a fájl már létezik, akkor nem teszi 
     * le újra. Ezután importálja a fájlt a PersonImport osztályhoz használva.
     *
     * @param Request $request A HTTP kérés objektuma.
     *
     * @return \Illuminate\Http\RedirectResponse Visszaadja az importálás eredményét.
     */
    public function import(Request $request)
    {
        /**
         * Ellenorizza, hogy van-e fájl a kérésben.
         *
         * @var \Illuminate\Http\Request $request A HTTP kérés objektuma.
         *
         * @return void
         */
        if($request->has('file')) {
            /**
             * A feltöltött CSV-fájl eredeti neve.
             * A getClientOriginalName() metódust használjuk a feltöltött fájl
             * eredeti nevének lekérésére. Ez azért hasznos, mert lehetővé teszi,
             * hogy a fájlt az eredeti nevével tároljuk, és ne csak véletlenszerűen 
             * generált nevet használjunk
             *
             * @var string
             */
            $fileName = $request->file->getClientOriginalName();
            /**
             * A feltöltött CSV-fájl elérési útja.
             * Felépíti a fájl elérési útját a feltöltési könyvtár elérési útjának 
             * és a feltöltött fájl nevének összefűzésével.
             *
             * @var string
             */
            $fileWithPath = public_path('uploads') . '/' . $fileName;
            
            /**
             * Ellenorizza, hogy a fájl már létezik-e a 'uploads' mappában.
             * Ha nem létezik, akkor tesszük le.
             *
             * @var string $fileWithPath A CSV-fájl elérési útja.
             *
             * @return void
             */
            if( !file_exists($fileWithPath) ) {
            
                /**
                 * Mozgassa a fájlt a 'uploads' mappába.
                 *
                 * @param \Illuminate\Http\Request $request A HTTP kérés objektuma.
                 * @param string $fileName A feltöltött fájl neve.
                 *
                 * @return void
                 */
                $request->file->move(public_path('uploads'), $fileName);
            }
        }
        
        /**
         * Importálja a megadott CSV fájlt a PersonImport osztályhoz használva.
         *
         * @param string $fileWithPath A CSV-fájl elérési útja.
         *
         * @return void
         */
        Excel::import(new PersonImport(), $fileWithPath);
        
        /**
         * Visszaadja az importálás eredményét, és jelenítsen meg egy sikeres üzenetet.
         *
         * @return \Illuminate\Http\RedirectResponse Visszaadja az importálás eredményét.
         */
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

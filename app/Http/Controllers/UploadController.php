<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPersons;
use Illuminate\Http\Request;
use Illuminate\Bus\Batch;
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
        try
        {
            if( $request->has('csvFile') )
            {
                $fileName = $request->csvFile->getClientOriginalName();
                $fileWithPath = public_path('uploads') . '/' . $fileName;
                if( !file_exists($fileWithPath) )
                {
                    $request->csvFile->move(public_path('uploads'), $fileName);
                }
                
                $header = null;
                $dataFromCsv = [];
                $records = array_map('str_getcsv', file($fileWithPath));
                dd($records);
                foreach($records as $record)
                {
\Log::info($record);
                    if( !$header )
                    {
                        $header = $record;
                    }
                    else
                    {
                        $dataFromCsv[] = $record;
                    }
                }
            }
            dd($dataFromCsv);
            $dataFromCsv = array_chunk($dataFromCsv, 300);
            
            $personsData = [];
            
            foreach( $dataFromCsv as $index => $dataCsv )
            {
                foreach( $dataCsv as $data )
                {
                    try
                    {
                        $personsData[$index][] = array_combine($header, $data);
                    }catch( \Exception $e )
                    {
                        print_r('error');
                        print_r($data);
                        continue;
                    }
                }
                
                ProcessPersons::dispatch($personsData[$index]);
            }
            
            //dd($personsData);
        }
        catch( \Exception $e )
        {
            \Log::error( $e->getMessage() );
            dd($e->getMessaget());
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

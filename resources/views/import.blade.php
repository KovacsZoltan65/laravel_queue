@extends('layouts.main')

@section('content')
    <div class="container mt-8">
        <div class="form">
            <form class="form row" 
                    action="{{ route('import') }}" 
                    method="POST" 
                    enctype="multipart/form-data">
                @csrf
                <div class="form-group col-md-6">
                    <input type="file" 
                            class="form-control" 
                            id="file" 
                            name="file"/>
                </div>

                <div class="form-group col-md-6">
                    <input type="submit" class="btn btn-info submit" />
                </div>
            </form>
        </div>
    </div>
@endsection
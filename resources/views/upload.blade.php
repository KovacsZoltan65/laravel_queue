@extends('layouts.main')

@section('content')
    <div class="container mt-8">
        <div class="form">
            <form class="form row" 
                    action="{{ route('processFile') }}" 
                    method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group col-md-6">
                    <input type="file" class="form-control" id="csvFile" name="csvFile"/>
                </div>

                <div class="form-group col-md-6">
                    <input type="submit" class="btn btn-info submit" />
                </div>
            </form>
        </div>
    </div>
@endsection

@section('styles')
@endsection

@section('scripts')
@endsection

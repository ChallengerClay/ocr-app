@extends('layouts.app')

@section('title', 'OCR de imágenes')

@section('content')
<div class="container d-flex flex-column justify-content-center align-items-center h-100">
    <h2 class="mt-2 text-light font-weight-bold">Subir imagen para OCR</h2>
    @if($errors->has('error'))
    <div class="alert alert-danger">
            {{ $errors->first('error') }}
        </div>
    @endif
    <div class="d-flex flex-row">
        <form method="POST" action="{{ route('ocr.process') }}" enctype="multipart/form-data">
            @csrf
            <input class="form-control" type="file" name="image" required>
            <button class="btn btn-success" type="submit">Extraer texto</button>
        </form>
    </div>
</div>

@endsection
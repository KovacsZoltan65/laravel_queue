<!-- resources/views/import.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>CSV Importálás</title>
</head>
<body>
    <form action="{{ route('import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" required>
        <button type="submit">Importálás</button>
    </form>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <title>pasha</title>
</head>
<body>
    <p><b>Hai !!!</b></p>
    <p>Selamat datang di Vokasi UB, Prodi kita :</p>
    <ul>
        @foreach($prodi as $item)
            <li>{{ $item }}</li>
        @endforeach
    </ul>
    <p>© 2025 Vokasi UB</p>
</body>
</html>
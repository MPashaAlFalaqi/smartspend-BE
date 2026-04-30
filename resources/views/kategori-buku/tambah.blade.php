@extends('template')
@section('title')
Kategori Buku
@endsection
@section('header')
<h4>Tambah Kategori Buku</h4>
@endsection
@section('main')
<form action ="{{ url('kategori-buku.data') }}" method="POST">
@csrf
<label>Nama Kategori Buku</label>
<input type="text" name="kategori_buku"><br>
<input type="submit" value="Simpan">
</form>
@endsection
@extends('template')

@section('title')
Kategori Buku
@endsection

@section('header')
<h4>Kategori Buku</h4>
@endsection

@section('main')
Ini halaman Kategori Buku
<a href="{{url('/kategori-buku.tambah')}}">tambah Kategori Buku</a>
@endsection
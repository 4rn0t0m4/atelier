@extends('admin.layouts.app')

@section('content')
    <x-admin.page-breadcrumb title="Nouveau produit" :breadcrumbs="['Produits' => route('admin.products.index'), 'Nouveau' => null]" />

    <form action="{{ route('admin.products.store') }}" method="POST">
        @csrf
        @include('admin.products._form')
    </form>
@endsection

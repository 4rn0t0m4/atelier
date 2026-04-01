@extends('admin.layouts.app')

@section('content')
    <x-admin.page-breadcrumb title="Modifier « {{ $product->name }} »" :breadcrumbs="['Produits' => route('admin.products.index'), $product->name => null]" />

    <form action="{{ route('admin.products.update', $product) }}" method="POST" data-turbo="false">
        @csrf
        @method('PUT')
        @include('admin.products._form')
    </form>
@endsection

@extends('admin.layouts.app')

@section('content')
<x-admin.page-breadcrumb title="Modifier la catégorie" :breadcrumbs="['Catalogue' => '', 'Catégories' => route('admin.categories.index'), $category->name => '']" />

<div class="p-6">
    <form action="{{ route('admin.categories.update', $category) }}" method="POST">
        @csrf @method('PUT')
        @include('admin.categories._form')
    </form>
</div>
@endsection

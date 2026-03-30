@extends('admin.layouts.app')

@section('content')
<x-admin.page-breadcrumb title="Nouvelle catégorie" :breadcrumbs="['Catalogue' => '', 'Catégories' => route('admin.categories.index'), 'Nouvelle' => '']" />

<div class="p-6">
    <form action="{{ route('admin.categories.store') }}" method="POST">
        @csrf
        @include('admin.categories._form')
    </form>
</div>
@endsection

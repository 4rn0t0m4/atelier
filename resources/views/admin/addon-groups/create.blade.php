@extends('admin.layouts.app')

@section('content')
<x-admin.page-breadcrumb title="Nouveau groupe d'options" :breadcrumbs="['Catalogue' => '', 'Options produit' => route('admin.addon-groups.index'), 'Nouveau' => '']" />

<div class="p-6">
    <form action="{{ route('admin.addon-groups.store') }}" method="POST" data-turbo="false">
        @csrf
        @include('admin.addon-groups._form')
    </form>
</div>
@endsection

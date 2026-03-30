@extends('admin.layouts.app')

@section('content')
<x-admin.page-breadcrumb title="Modifier le groupe d'options" :breadcrumbs="['Catalogue' => '', 'Options produit' => route('admin.addon-groups.index'), $addonGroup->name => '']" />

<div class="p-6">
    <x-admin.alert />

    <form action="{{ route('admin.addon-groups.update', $addonGroup) }}" method="POST" data-turbo="false">
        @csrf @method('PUT')
        @include('admin.addon-groups._form')
    </form>
</div>
@endsection

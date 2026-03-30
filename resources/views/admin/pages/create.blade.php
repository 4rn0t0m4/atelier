@extends('admin.layouts.app')

@section('content')
<x-admin.page-breadcrumb title="Nouvelle page" :breadcrumbs="['Contenu' => '', 'Pages' => route('admin.pages.index'), 'Nouvelle' => '']" />

<div class="p-6">
    <form action="{{ route('admin.pages.store') }}" method="POST">
        @csrf
        @include('admin.pages._form')
    </form>
</div>
@endsection

@extends('admin.layouts.app')

@section('content')
<x-admin.page-breadcrumb title="Modifier la page" :breadcrumbs="['Contenu' => '', 'Pages' => route('admin.pages.index'), $page->title => '']" />

<div class="p-6">
    <form action="{{ route('admin.pages.update', $page) }}" method="POST">
        @csrf @method('PUT')
        @include('admin.pages._form')
    </form>
</div>
@endsection

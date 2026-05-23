@extends('admin.layouts.app')

@section('content')
<x-admin.page-breadcrumb title="Nouveau tag" :breadcrumbs="['Catalogue' => '', 'Tags' => route('admin.tags.index'), 'Nouveau' => '']" />

<div class="p-6">
    <form action="{{ route('admin.tags.store') }}" method="POST">
        @csrf
        @include('admin.tags._form')
    </form>
</div>
@endsection

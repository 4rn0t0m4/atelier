@extends('admin.layouts.app')

@section('content')
<x-admin.page-breadcrumb title="Modifier le tag" :breadcrumbs="['Catalogue' => '', 'Tags' => route('admin.tags.index'), $tag->name => '']" />

<div class="p-6">
    <form action="{{ route('admin.tags.update', $tag) }}" method="POST">
        @csrf @method('PUT')
        @include('admin.tags._form')
    </form>
</div>
@endsection

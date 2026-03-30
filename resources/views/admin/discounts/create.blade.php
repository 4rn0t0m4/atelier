@extends('admin.layouts.app')

@section('content')
<x-admin.page-breadcrumb title="Nouvelle réduction" :breadcrumbs="['Boutique' => '', 'Codes promo' => route('admin.discounts.index'), 'Nouvelle' => '']" />

<div class="p-6">
    <form action="{{ route('admin.discounts.store') }}" method="POST">
        @csrf
        @include('admin.discounts._form')
    </form>
</div>
@endsection

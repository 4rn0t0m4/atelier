@extends('admin.layouts.app')

@section('content')
<x-admin.page-breadcrumb title="Modifier la réduction" :breadcrumbs="['Boutique' => '', 'Codes promo' => route('admin.discounts.index'), $discount->name => '']" />

<div class="p-6">
    <form action="{{ route('admin.discounts.update', $discount) }}" method="POST">
        @csrf @method('PUT')
        @include('admin.discounts._form')
    </form>
</div>
@endsection

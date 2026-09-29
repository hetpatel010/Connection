@extends('sales.invoices.templates._layout')

@section('content')
    <div class="doc">
        <div class="doc-inner">
            @include('sales.invoices.templates.partials.gst-invoice-minimal')
        </div>
    </div>
@endsection

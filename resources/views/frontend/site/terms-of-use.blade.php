@extends('frontend.layout.app')
@section('title', 'Terms Of Use | Perkinsons')
@section('content')

<!-- Bredcrumb and Header -->
<section class="banner_block">
    <div class="container">
        <div class="banner-middle">
            <div class="w-full max-w-[620px]">
                <div class="bannerdata"><div class="banner_head"><h1>Terms Of Use</h1></div></div>
                {{-- <div id="inner-particles-js"></div> --}}
            </div>
            <div class="w-full max-w-[620px]">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home.index') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Terms Of Use</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</section>

<!-- contact form section -->
<section class="lg:py-[80px] py-[60px] bg-white" id="contact-form">
    <div class="container">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
            <p>Content Coming Soon.</p>
        </div>
    </div>
</section>

@endsection
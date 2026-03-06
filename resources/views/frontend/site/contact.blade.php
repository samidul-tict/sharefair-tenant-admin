@extends('frontend.layout.app')
@section('title', 'Contact | Perkinsons')
@section('content')

<!-- Bredcrumb and Header -->
<section class="banner_block">
    <div class="container">
        <div class="banner-middle">
            <div class="w-full max-w-[620px]">
                <div class="bannerdata"><div class="banner_head"><h1>Contact Us</h1></div></div>
                {{-- <div id="inner-particles-js"></div> --}}
            </div>
            <div class="w-full max-w-[620px]">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home.index') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Contact Us</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</section>

<!-- emergency contact -->

<section class="lg:py-[10px] py-[10px] bg-red-50">
    <div class="container">
        <div class="text-center max-w-4xl mx-auto">
            
            <p class="text-lg text-gray-700 !mb-6" style="font-family: var(--custom_fn_inter);">
                If you are experiencing a medical emergency or a life-threatening health issue, <span class="text-xl font-semibold text-red-900 !mb-2">Call 911 immediately</span> or go to your nearest emergency room for immediate medical attention.
            </p>
            <!--<div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-6">-->
            <!--    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">-->
            <!--    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>-->
            <!--    </svg>-->
            <!--</div>-->
            <!--<h2 class="text-3xl font-regular text-gray-900 mb-4" style="font-family: var(--custom_fn_primary);">Medical Emergency?</h2>-->
            <!--<p class="text-lg text-gray-700 !mb-6" style="font-family: var(--custom_fn_inter);">-->
            <!--    If you are experiencing a medical emergency or a life-threatening health issue, <span class="text-xl font-semibold text-red-900 !mb-2">Call 911 immediately</span> or go to your nearest emergency room for immediate medical attention.-->
            <!--</p>-->
            <!--<div class="bg-white p-6 rounded-2xl shadow-lg inline-block">-->
            <!--    <span class="text-xl font-semibold text-red-900 !mb-2">Call 911 immediately</span>-->
            <!--    <p class="text-gray-600" style="font-family: var(--custom_fn_inter);">-->
            <!--    or go to your nearest emergency room for immediate medical attention.-->
            <!--    </p>-->
            <!--</div>-->
        </div>
    </div>
</section>

<!-- contact form section -->
<section class="lg:py-[10px] py-[10px] bg-white" id="contact-form">
    <div class="container">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
            <!-- Contact Form -->
            <div class="bg-gray-50 p-8 rounded-3xl">
                <div class="mb-8">
                    <!--<div class="content_chip bg_blue_chip mb-6"><span>Send us a message</span></div>-->
                    <h2 class="text-3xl font-regular text-gray-900 mb-4" style="font-family: var(--custom_fn_primary);">Schedule Your Appointment</h2>
                    <p class="text-gray-600" style="font-family: var(--custom_fn_inter);">Fill out the form below and our team
                    will get back to you within 24 hours to schedule your consultation.</p>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mb-4">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form class="space-y-6" id="appointmentForm" method="POST" action="{{ route('home.enquiry.store') }}">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2" style="font-family: var(--custom_fn_secondary);">First Name *</label>
                            <input
                                type="text"
                                id="firstName"
                                name="firstName"
                                value="{{ old('firstName') }}"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                style="font-family: var(--custom_fn_inter);"
                                placeholder="Enter your first name"
                            />
                        </div>
                        <div>
                            <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2" style="font-family: var(--custom_fn_secondary);">Last Name *</label>
                            <input
                                type="text"
                                id="lastName"
                                name="lastName"
                                value="{{ old('lastName') }}"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                style="font-family: var(--custom_fn_inter);"
                                placeholder="Enter your last name"
                            />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2" style="font-family: var(--custom_fn_secondary);">Email Address *</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                style="font-family: var(--custom_fn_inter);"
                                placeholder="your.email@example.com"
                            />
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2" style="font-family: var(--custom_fn_secondary);">Phone Number *</label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="{{ old('phone') }}"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                style="font-family: var(--custom_fn_inter);"
                                placeholder="(555) 123-4567"
                            />
                        </div>
                    </div>
                    <div>
                        <label for="service" class="block text-sm font-medium text-gray-700 mb-2" style="font-family: var(--custom_fn_secondary);">Service Needed</label>
                        <select
                            id="service"
                            name="service"
                            value="{{ old('service') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            style="font-family: var(--custom_fn_inter);"
                        >
                        <option value="">Select a service...</option>
                        <option value="Medical Treatments">Medical Treatments</option>
                        <option value="Deep Brain Stimulation (DBS) Management">Deep Brain Stimulation (DBS) Management</option>
                        <option value="Therapeutic Botox/ Myobloc Injections">Therapeutic Botox/ Myobloc Injections</option>
                        <option value="Virtual/Supportive Services">Virtual/Supportive Services</option>
                        </select>
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2" style="font-family: var(--custom_fn_secondary);">Additional Information</label>
                        <textarea
                        id="message"
                        name="message"
                        rows="4"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        style="font-family: var(--custom_fn_inter);"
                        placeholder="Please describe your symptoms, concerns, or any additional information that would help us prepare for your visit..."
                        >{{ old('message') }}</textarea>
                    </div>
                    <div class="flex items-start gap-3">
                        <input
                        type="checkbox"
                        id="consent"
                        name="consent"
                        {{ old('consent') ? 'checked' : '' }}
                        required
                        class="mt-1 h-4 !w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label for="consent" class="text-sm text-gray-600" style="font-family: var(--custom_fn_inter);">
                        I consent to being contacted by this practice via phone, email, or text message regarding my 
                        appointment and care. *
                        </label>
                    </div>
                    <button type="submit" class="btn btn_primary cta_btn w-full" style="width: 100%; justify-content: center;" >
                        <span>Submit appointment request</span>
                        <img src="{{ asset('frontend-assets/images/Vector.svg') }}" alt="arrow" />
                    </button>
                </form>
            </div>

            <!-- Contact Information -->
            <div class="space-y-8">
                <div>
                    <div class="content_chip bg_white_chip mb-6"> <span>Contact Information</span> </div>
                    <h2 class="text-3xl font-regular text-gray-900 mb-6" style="font-family: var(--custom_fn_primary);"> Get in Touch </h2>
                    <p class="text-gray-600 text-lg mb-8" style="font-family: var(--custom_fn_inter);">
                    If you're experiencing any neurological symptoms, get care from our trusted team. We're here to help 
                    you every step of the way.</p>
                </div>
                <!-- Office Hours -->
                <div class="bg-blue-50 p-6 rounded-2xl">
                    <h3 class="text-xl font-semibold !text-gray-900 mb-4" style="font-family: var(--custom_fn_secondary); color: var(--clr_secondary);">Office Hours</h3>
                    <div class="space-y-2 text-gray-700" style="font-family: var(--custom_fn_inter);">
                        <div class="flex justify-between">
                            <span>Monday - Friday:</span>
                            <span>8:00 AM - 4:00 PM</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Saturday:</span>
                            <span>Closed</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Sunday:</span>
                            <span>Closed</span>
                        </div>
                        <div class="mt-4 pt-4 border-t border-blue-200">
                        <p class="text-sm text-gray-600">
                            <strong>Emergency:</strong> For urgent neurological concerns outside office hours, please call our 
                            emergency line or visit the nearest emergency room.
                        </p>
                        </div>
                    </div>
                </div>
                <!-- Quick Contact Methods -->
                <div class="grid grid-cols-1 gap-6">
                    <div class="flex items-center gap-4 p-4 bg-white rounded-xl shadow-sm border">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <img src="{{ asset('frontend-assets/images/callcalling.svg') }}" class="w-6 h-6" alt="phone" />
                        </div>
                        <div>
                            <h4 class="font-semibold !text-gray-900" style="font-family: var(--custom_fn_secondary);">Phone</h4>
                            <a href="tel:7273137679" class="text-blue-600 hover:text-blue-700" style="color: var(--clr_secondary);">+1 727 313 7679</a>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-4 p-4 bg-white rounded-xl shadow-sm border">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold !text-gray-900" style="font-family: var(--custom_fn_secondary);">Email</h4>
                            <a href="mailto:info@parkinsonsneurology.com" class="text-blue-600 hover:text-blue-700" style="color: var(--clr_secondary);">info@parkinsonsneurology.com</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- office locations -->
<section class="lg:py-[80px] py-[60px] bg-gray-50">
    <div class="container">
        <div class="text-center mb-12">
            <div class="content_chip bg_blue_chip mx-auto"> <span>Our Locations</span> </div>
            <h2 class="text-3xl lg:text-4xl font-regular !mt-6 text-gray-900" style="font-family: var(--custom_fn_primary);"> Visit Us at Our Convenient Locations </h2>
            <p class="text-lg text-gray-600 mt-4 max-w-2xl mx-auto" style="font-family: var(--custom_fn_inter);">
            We have three convenient locations to serve you better. Choose the location that works best for your schedule. </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Location 1 -->
            <div class="bg-white p-8 rounded-3xl shadow-lg">
                <div class="flex items-start gap-6 sm:flex-row flex-col">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 rounded-full bg-blue-600 flex items-center justify-center" style="background-color: var(--clr_secondary);">
                            <img src="{{ asset('frontend-assets/images/location.svg') }}" class="w-8 h-8" alt="location" style="filter: brightness(0) saturate(100%) invert(100%);" />
                        </div>
                    </div>
                    <div class="flex-1 w-full">
                        <div class="flex items-center justify-between mb-4 flex-1 flex-wrap">
                            <h3 class="text-xl font-semibold !text-gray-900" style="font-family: var(--custom_fn_secondary);">St Peterburg office</h3>
                            <a href="https://maps.app.goo.gl/dfvW7uPZRa3K4pD79"  target="_blank"  class="text-blue-600 hover:text-blue-700 text-sm font-medium"  style="color: var(--clr_secondary);">View on Map</a>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <img src="{{ asset('frontend-assets/images/location.svg') }}" class="!w-4 h-4 mt-1" alt="address" />
                                <div>
                                    <p class="text-gray-700" style="font-family: var(--custom_fn_inter);">
                                        2299 9 th Avenue N, Suite 3D, <br>
                                        St Peterburg, Fl 33713
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <img src="{{ asset('frontend-assets/images/callcalling.svg') }}" class="!w-4 h-4" alt="phone" />
                                <a href="tel:7273137679" class="text-gray-700 hover:text-blue-600" style="font-family: var(--custom_fn_inter);">+1 727 313 7679</a>
                            </div>
                            <div class="flex items-center gap-3">
                                <svg class="!w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-gray-700" style="font-family: var(--custom_fn_inter);">Mon-Fri: 8:00 AM - 4:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="font-semibold !text-gray-900 mb-2" style="font-family: var(--custom_fn_secondary);">Services Available:</h4>
                    <ul class="text-sm text-gray-600 space-y-1" style="font-family: var(--custom_fn_inter);">
                        <li>• Medical Treatments</li>
                        <li>• Deep Brain Stimulation (DBS) Management</li>
                        <li>• Therapeutic Botox/ Myobloc Injections</li>
                        <li>• Virtual/Supportive Services</li>
                    </ul>
                </div>
            </div>

            <!-- Location 2 -->
            <div class="bg-white p-8 rounded-3xl shadow-lg">
                <div class="flex items-start gap-6 sm:flex-row flex-col">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 rounded-full bg-blue-600 flex items-center justify-center" style="background-color: var(--clr_secondary);">
                            <img src="{{ asset('frontend-assets/images/location.svg') }}" class="w-8 h-8" alt="location" style="filter: brightness(0) saturate(100%) invert(100%);" />
                        </div>
                    </div>
                    <div class="flex-1 w-full">
                        <div class="flex items-center justify-between mb-4 flex-1 flex-wrap">
                            <h3 class="text-xl font-semibold !text-gray-900" style="font-family: var(--custom_fn_secondary);">Wesley Chapel Office</h3>
                            <a href="https://maps.app.goo.gl/ZfjEg5vsbpQHjaPc8" target="_blank" class="text-blue-600 hover:text-blue-700 text-sm font-medium" style="color: var(--clr_secondary);">View on Map</a>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <img src="{{ asset('frontend-assets/images/location.svg') }}" class="!w-4 h-4 mt-1" alt="address" />
                                <div>
                                    <p class="text-gray-700" style="font-family: var(--custom_fn_inter);">
                                        27453 Cashford Cir, Suite 101, <br>
                                        Wesley Chapel, FL 33544
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <img src="{{ asset('frontend-assets/images/callcalling.svg') }}" class="!w-4 h-4" alt="phone" />
                                <a href="tel:7273137679" class="text-gray-700 hover:text-blue-600" style="font-family: var(--custom_fn_inter);">+1 727 313 7679</a>
                            </div>
                            <div class="flex items-center gap-3">
                                <svg class="!w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-gray-700" style="font-family: var(--custom_fn_inter);">Mon-Fri: 8:00 AM - 4:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="font-semibold !text-gray-900 mb-2" style="font-family: var(--custom_fn_secondary);">Services Available:</h4>
                    <ul class="text-sm text-gray-600 space-y-1" style="font-family: var(--custom_fn_inter);">
                        <li>• Medical Treatments</li>
                        <li>• Deep Brain Stimulation (DBS) Management</li>
                        <li>• Therapeutic Botox/ Myobloc Injections</li>
                        <li>• Virtual/Supportive Services</li>
                    </ul>
                </div>
            </div>
            
            <!-- Location 3 -->
            <div class="bg-white p-8 rounded-3xl shadow-lg">
                <div class="flex items-start gap-6 sm:flex-row flex-col">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 rounded-full bg-blue-600 flex items-center justify-center" style="background-color: var(--clr_secondary);">
                            <img src="{{ asset('frontend-assets/images/location.svg') }}" class="w-8 h-8" alt="location" style="filter: brightness(0) saturate(100%) invert(100%);" />
                        </div>
                    </div>
                    <div class="flex-1 w-full">
                        <div class="flex items-center justify-between mb-4 flex-1 flex-wrap">
                            <h3 class="text-xl font-semibold !text-gray-900" style="font-family: var(--custom_fn_secondary);">Bradenton Office</h3>
                            <a href="https://maps.app.goo.gl/eL73wXFKB92YzBS5A" target="_blank" class="text-blue-600 hover:text-blue-700 text-sm font-medium" style="color: var(--clr_secondary);">View on Map</a>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <img src="{{ asset('frontend-assets/images/location.svg') }}" class="!w-4 h-4 mt-1" alt="address" />
                                <div>
                                    <p class="text-gray-700" style="font-family: var(--custom_fn_inter);">
                                        2010 59 th Street West, Suite <br>
                                        3800, Bradenton, FL 34209
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <img src="{{ asset('frontend-assets/images/callcalling.svg') }}" class="!w-4 h-4" alt="phone" />
                                <a href="tel:7273137679" class="text-gray-700 hover:text-blue-600" style="font-family: var(--custom_fn_inter);">+1 727 313 7679</a>
                            </div>
                            <div class="flex items-center gap-3">
                                <svg class="!w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-gray-700" style="font-family: var(--custom_fn_inter);">Mon-Fri: 8:00 AM - 4:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="font-semibold !text-gray-900 mb-2" style="font-family: var(--custom_fn_secondary);">Services Available:</h4>
                    <ul class="text-sm text-gray-600 space-y-1" style="font-family: var(--custom_fn_inter);">
                        <li>• Medical Treatments</li>
                        <li>• Deep Brain Stimulation (DBS) Management</li>
                        <li>• Therapeutic Botox/ Myobloc Injections</li>
                        <li>• Virtual/Supportive Services</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>



@endsection
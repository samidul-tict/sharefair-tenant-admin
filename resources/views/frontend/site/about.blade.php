@extends('frontend.layout.app')
@section('title', 'About | Perkinsons')
@section('content')

<!-- Bredcrumb and Header -->
<section class="banner_block">
    <div class="container">
        <div class="banner-middle">
            <div class="w-full max-w-[620px]">
                <div class="bannerdata"><div class="banner_head"><h1>About Us</h1></div></div>
                {{-- <div id="particles-js"></div> --}}
            </div>
            <div class="w-full max-w-[620px]">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home.index') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">About</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</section>

<!-- our mission -->
<section class="lg:py-[80px] py-[60px] bg-white">
    <div class="container">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div class="space-y-6">
                <div class="content_chip bg_blue_chip"> <span>Our Mission</span> </div>
                <h2 class="text-3xl lg:text-4xl font-regular text-gray-900" style="font-family: var(--custom_fn_primary); color: var(--clr_text_dark_01);">Transforming Lives Through Expert Neurological Care </h2>
                <p class="text-lg text-gray-600 leading-relaxed" style="font-family: var(--custom_fn_inter);">
                Our mission is to provide world-class neurological care that combines cutting-edge medical expertise with 
                compassionate patient care. We believe every patient deserves personalized attention and the most advanced 
                treatment options available.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                    <!--<div class="flex items-start gap-4">-->
                    <!--    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">-->
                    <!--        <img src="{{ asset('frontend-assets/images/check-waves.svg') }}" alt="check" class="w-6 h-6" />-->
                    <!--    </div>-->
                    <!--    <div>-->
                    <!--        <h4 class="font-semibold !text-gray-900 mb-2" style="font-family: var(--custom_fn_secondary);">Expert Care</h4>-->
                    <!--        <p class="text-gray-600 text-sm" style="font-family: var(--custom_fn_inter);">Board-certified specialists with years of experience</p>-->
                    <!--    </div>-->
                    <!--</div>-->
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <img src="{{ asset('frontend-assets/images/check-waves.svg') }}" alt="check" class="w-6 h-6" />
                        </div>
                        <div>
                            <h4 class="font-semibold !text-gray-900 mb-2" style="font-family: var(--custom_fn_secondary);">Advanced Technology</h4>
                            <p class="text-gray-600 text-sm" style="font-family: var(--custom_fn_inter);">State-of-the-art diagnostic and treatment equipment</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <img src="{{ asset('frontend-assets/images/check-waves.svg') }}" alt="check" class="w-6 h-6" />
                        </div>
                        <div>
                            <h4 class="font-semibold !text-gray-900 mb-2" style="font-family: var(--custom_fn_secondary);">Personalized Treatment</h4>
                            <p class="text-gray-600 text-sm" style="font-family: var(--custom_fn_inter);">Tailored care plans for each patient's unique needs</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <img src="{{ asset('frontend-assets/images/check-waves.svg') }}" alt="check" class="w-6 h-6" />
                        </div>
                        <div>
                            <h4 class="font-semibold !text-gray-900 mb-2" style="font-family: var(--custom_fn_secondary);">Compassionate Care</h4>
                            <p class="text-gray-600 text-sm" style="font-family: var(--custom_fn_inter);">Patient-centered approach with empathy and understanding</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative">
                <div class="aspect-w-4 aspect-h-3 rounded-2xl overflow-hidden">
                <img
                    src="{{ asset('frontend-assets/images/consult.jpg') }}" alt="Medical consultation" class="w-full h-full object-cover rounded-2xl" />
                </div>
            </div>
        </div>
    </div>
</section>

<!-- detailed team section -->
<section class="our_team_blk lg:py-[100px] py-[60px] bg-gray-50">
    <div class="container">
        <div class="text-center mb-12">
            <div class="content_chip bg_white_chip mx-auto"> <span>Meet Our Doctor</span> </div>
            <h2 class="text-3xl lg:text-4xl font-regular !mt-6 text-gray-900" style="font-family: var(--custom_fn_primary);"> Expert Neurologists Dedicated to Your Health </h2>
            {{-- <p class="text-lg text-gray-600 mt-4 max-w-2xl mx-auto" style="font-family: var(--custom_fn_inter);">
                Our team of board-certified neurologists brings decades of combined experience in treating complex neurological conditions.
            </p> --}}
        </div>

        <!-- Main doctor profile -->
        <div class="team_container flex flex-col lg:flex-row flex-col-reverse bg-white rounded-3xl p-8 lg:p-12 shadow-lg mb-12">
            <div class="left_img">
                <img src="{{ asset('frontend-assets/images/ctscan.jpg') }}" alt="Dr. Israt Jahan" class="rounded-2xl" />
            </div>
            <div class="right_items w-full lg:max-w-[720px]">
                <h2 class="capitalize">Lead Neurologist</h2>
                <div class="details w-full lg:mt-[42px] md:mt-[32px] mt-[20px]">
                    <h3>Dr. Israt Jahan, MD, MSMS</h3>
                    <p>Neurologist – Movement Disorders Specialist</p>
                </div>
                <div class="team_details mb-6">
                    <p>
                        Dr. Israt Jahan is a neurologist
                         specializing in movement disorders
                         and neuromodulation. In addition to
                         her medical degree, Dr. Jahan also
                         earned a Master’s in Medical Sciences
                         (Neuroscience, Brain Repair & Aging)
                         at the University of South
                         Florida, where she also completed her
                         fellowship in movement disorders.
                    </p>
                    
                    <p>
                        Dr. Jahan provides expert care for
                         Parkinson’s disease, essential tremor,
                         dystonia, ataxia, gait
                         and balance issues, tardive dyskinesia,
                         and related conditions. She also
                         specializes in deep brain stimulation
                         (DBS) management and therapeutic
                         Botox/Myobloc injections.
                    </p>
                    
                    <p>
                        Known for her compassionate
                             approach, Dr. Jahan partners closely
                             with patients to deliver
                             personalized, high-quality care that
                             improve quality of life. She’s known for
                             her compassionate approach and
                             strong patient relationships. She is
                             also involved in research and has
                             published in the field.
                    </p>
                    
                    <p>
                        When she’s not in the clinic, Dr. Jahan
                         enjoys cooking, reading, volunteering,
                         and spending time with her family.
                    </p>
                </div>
                <!-- Expanded credentials and expertise -->
                <div class="space-y-6">
                    <!--<div class="w-full">-->
                    <!--    <h4 class="text-xl font-semibold !text-gray-900 mb-3" style="font-family: var(--custom_fn_secondary); color: var(--clr_secondary);">Areas of Expertise</h4>-->
                    <!--    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">-->
                    <!--        <div class="flex items-center gap-2">-->
                    <!--            <img src="{{ asset('frontend-assets/images/check-waves.svg') }}" alt="check" class="!w-4 h-4" />-->
                    <!--            <span class="text-gray-700" style="font-family: var(--custom_fn_inter);">Parkinson's Disease</span>-->
                    <!--        </div>-->
                    <!--        <div class="flex items-center gap-2">-->
                    <!--            <img src="{{ asset('frontend-assets/images/check-waves.svg') }}" alt="check" class="!w-4 h-4" />-->
                    <!--            <span class="text-gray-700" style="font-family: var(--custom_fn_inter);">Essential Tremor</span>-->
                    <!--        </div>-->
                    <!--        <div class="flex items-center gap-2">-->
                    <!--            <img src="{{ asset('frontend-assets/images/check-waves.svg') }}" alt="check" class="!w-4 h-4" />-->
                    <!--            <span class="text-gray-700" style="font-family: var(--custom_fn_inter);">Dystonia</span>-->
                    <!--        </div>-->
                    <!--        <div class="flex items-center gap-2">-->
                    <!--            <img src="{{ asset('frontend-assets/images/check-waves.svg') }}" alt="check" class="!w-4 h-4" />-->
                    <!--            <span class="text-gray-700" style="font-family: var(--custom_fn_inter);">Deep Brain Stimulation</span>-->
                    <!--        </div>-->
                    <!--        <div class="flex items-center gap-2">-->
                    <!--            <img src="{{ asset('frontend-assets/images/check-waves.svg') }}" alt="check" class="!w-4 h-4" />-->
                    <!--            <span class="text-gray-700" style="font-family: var(--custom_fn_inter);">Huntington's Disease</span>-->
                    <!--        </div>-->
                    <!--        <div class="flex items-center gap-2">-->
                    <!--            <img src="{{ asset('frontend-assets/images/check-waves.svg') }}" alt="check" class="!w-4 h-4" />-->
                    <!--            <span class="text-gray-700" style="font-family: var(--custom_fn_inter);">Ataxia Disorders</span>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--</div>-->
                    <div class="w-full">
                        <h4 class="text-xl font-semibold !text-gray-900 mb-3" style="font-family: var(--custom_fn_secondary); color: var(--clr_secondary);">Education & Training</h4>
                        <ul class="space-y-2 text-gray-700" style="font-family: var(--custom_fn_inter);">
                            <li>• Medical Degree - Mymensingh Medical College, Bangladesh</li>
                            <li>• Master's in Medical Sciences (Neuroscience) - University of South Florida</li>
                            <li>• Movement Disorders Fellowship - University of South Florida</li>
                            <li>• Board Certified in Neurology</li>
                        </ul>
                    </div>
                </div>
                
                <div class="banner_btnset flex items-center justify-start gap-4 mt-6 flex-wrap" >
                    <a href="{{ route('home.enquiry') }}"  class="btn btn_primary cta_btn">
                        <span>Request for an appointment</span>
                        <img src="{{ asset('frontend-assets/images/Vector.svg') }}" alt="arrow" />
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12">
            <div class="text-center bg-white p-8 rounded-2xl shadow-lg">
                <div class="text-4xl font-bold text-blue-600 mb-2" style="color: var(--clr_secondary);">25+</div>
                <div class="text-gray-600" style="font-family: var(--custom_fn_secondary);">Years of Combined Experience</div>
            </div>
            <div class="text-center bg-white p-8 rounded-2xl shadow-lg">
                <div class="text-4xl font-bold text-blue-600 mb-2" style="color: var(--clr_secondary);">1200+</div>
                <div class="text-gray-600" style="font-family: var(--custom_fn_secondary);">Patients Treated Successfully</div>
            </div>
            <div class="text-center bg-white p-8 rounded-2xl shadow-lg">
                <div class="text-4xl font-bold text-blue-600 mb-2" style="color: var(--clr_secondary);">98%</div>
                <div class="text-gray-600" style="font-family: var(--custom_fn_secondary);">Patient Satisfaction Rate</div>
            </div>
        </div>
    </div>
</section>

<!-- our values -->
<section class="lg:py-[80px] py-[60px] bg-white">
    <div class="container">
        <div class="text-center mb-12">
            <div class="content_chip bg_blue_chip mx-auto"><span>Our Values</span></div>
            <h2 class="text-3xl lg:text-4xl font-regular !mt-6 text-gray-900" style="font-family: var(--custom_fn_primary);">What Drives Us Every Day</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="text-center p-8 rounded-2xl bg-gray-50">
                <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-6">
                    <img src="{{ asset('frontend-assets/images/s-1.png') }}" alt="Excellence" class="w-8 h-8" />
                </div>
                <h3 class="text-xl font-semibold !text-gray-900 mb-4" style="font-family: var(--custom_fn_secondary);">Excellence</h3>
                <p class="text-gray-600" style="font-family: var(--custom_fn_inter);">We strive for the highest standards in medical care, continuously improving our knowledge and techniques.</p>
            </div>
        
            <div class="text-center p-8 rounded-2xl bg-gray-50">
                <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-6">
                    <img src="{{ asset('frontend-assets/images/s-2.png') }}" alt="Compassion" class="w-8 h-8" />
                </div>
                <h3 class="text-xl font-semibold !text-gray-900 mb-4" style="font-family: var(--custom_fn_secondary);">Compassion</h3>
                <p class="text-gray-600" style="font-family: var(--custom_fn_inter);">We treat every patient with empathy, understanding, and respect for their unique journey.</p>
            </div>
        
            <div class="text-center p-8 rounded-2xl bg-gray-50">
                <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-6">
                    <img src="{{ asset('frontend-assets/images/s-3.png') }}" alt="Innovation" class="w-8 h-8" />
                </div>
                <h3 class="text-xl font-semibold !text-gray-900 mb-4" style="font-family: var(--custom_fn_secondary);">Innovation</h3>
                <p class="text-gray-600" style="font-family: var(--custom_fn_inter);">We embrace cutting-edge technology and treatment methods to provide the best possible outcomes.</p>
            </div>
        </div>
    </div>
</section>

<!-- get in touch -->
<section class="lg:py-[80px] py-[60px] bg-white">
    <div class="container">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
            <!-- Left Text Block -->
            <div class="lg:col-span-1 space-y-6 get_in_touch lg:text-start lg:mb-0 mb-6">
                <h2 class="text-3xl font-regular"> Contact us<br /><span class="text-4xl">Get in touch</span> </h2>
                <p class="text-gray-600 text-lg lg:max-w-[310px]">
                    If you’re experiencing any neurological symptoms, get care from
                    the trusted team at Parkinson’s…
                </p>
            </div>

            <!-- Contact Details Cards -->
            <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Card: Address + Phone -->
                <div class="space-x-4">
                    <div class="flex gap-6">
                        <div class="flex-shrink-0">
                        <!-- Icon circle -->
                        <div
                            class="lg:h-20 lg:w-20 h-14 w-14 rounded-full bg-[#009CD4] add_icon flex items-center justify-center"
                        >
                            <img src="{{ asset('frontend-assets/images/location.svg') }}" class="w-6 h-6" />
                        </div>
                        </div>
                        <div class="space-y-1">
                        <div
                            class="flex items-center justify-between gap-2 add_content"
                        >
                            <h3 class="font-medium">Address</h3>
                            <a href="https://maps.app.goo.gl/dfvW7uPZRa3K4pD79" class="text-[#009CD4] hover:underline"  target="_blank"
                            >Show Map</a
                            >
                        </div>
                        <p class="add_content_para text-[#707070]">
                            2299 9 th Avenue N, Suite 3D  <br />
                            St Peterburg, Fl 33713
                        </p>
                        </div>
                    </div>
                    <div class="flex gap-6 mt-3">
                        <div class="flex-shrink-0">
                        <!-- Icon circle -->
                        <div
                            class="lg:h-20 lg:w-20 h-14 w-14 rounded-full bg-[#EAFAFF] call_icon flex items-center justify-center"
                        >
                            <img
                            src="{{ asset('frontend-assets/images/callcalling.svg') }}"
                            class="w-6 h-6"
                            />
                        </div>
                        </div>
                        <div class="space-y-1">
                        <div class="mt-2 text-gray-600">
                            <div class="add_content">
                            <h3 class="font-medium">Phone</h3>
                            </div>
                            <a class="block add_content_link">+1 727 313 7679</a>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- Card: Other Address + Fax -->
                <div class="space-x-4">
                    <div class="flex gap-6">
                        <div class="flex-shrink-0">
                        <!-- Icon circle -->
                        <div
                            class="lg:h-20 lg:w-20 h-14 w-14 rounded-full bg-[#009CD4] add_icon flex items-center justify-center"
                        >
                            <img src="{{ asset('frontend-assets/images/location.svg') }}" class="w-6 h-6" />
                        </div>
                        </div>
                        <div class="space-y-1">
                        <div
                            class="flex items-center justify-between gap-2 add_content"
                        >
                            <h3 class="font-medium">Address</h3>
                            <a href="https://maps.app.goo.gl/4aS8VcmnYz9TtuF57" class="text-[#009CD4] hover:underline"  target="_blank"
                            >Show Map</a
                            >
                        </div>
                        <p class="add_content_para text-[#707070]">
                            2252 Twelve Oaks Way, Suite 102 <br />
                            Wesley Chapel, FL 33544

                        </p>
                        </div>
                    </div>
                    <div class="flex gap-6 mt-3">
                        <div class="flex-shrink-0">
                        <!-- Icon circle -->
                        <div
                            class="lg:h-20 lg:w-20 h-14 w-14 rounded-full bg-[#EAFAFF] call_icon flex items-center justify-center"
                        >
                            <img
                            src="{{ asset('frontend-assets/images/callcalling.svg') }}"
                            class="w-6 h-6"
                            />
                        </div>
                        </div>
                        <div class="space-y-1">
                        <div class="mt-2 text-gray-600">
                            <div class="add_content">
                            <h3 class="font-medium">Phone</h3>
                            </div>
                            <a class="block add_content_link">+1 727 313 7679</a>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- Card: Other Address + Fax -->
                <div class="space-x-4">
                    <div class="flex gap-6">
                        <div class="flex-shrink-0">
                        <!-- Icon circle -->
                        <div
                            class="lg:h-20 lg:w-20 h-14 w-14 rounded-full bg-[#009CD4] add_icon flex items-center justify-center"
                        >
                            <img src="{{ asset('frontend-assets/images/location.svg') }}" class="w-6 h-6" />
                        </div>
                        </div>
                        <div class="space-y-1">
                        <div
                            class="flex items-center justify-between gap-2 add_content"
                        >
                            <h3 class="font-medium">Address</h3>
                            <a href="https://maps.app.goo.gl/eL73wXFKB92YzBS5A" class="text-[#009CD4] hover:underline" target="_blank"
                            >Show Map</a
                            >
                        </div>
                        <p class="add_content_para text-[#707070]">
                            2010 59 th Street West, Suite 3800 <br />
                            Bradenton, FL 34209

                        </p>
                        </div>
                    </div>
                    <div class="flex gap-6 mt-3">
                        <div class="flex-shrink-0">
                        <!-- Icon circle -->
                        <div
                            class="lg:h-20 lg:w-20 h-14 w-14 rounded-full bg-[#EAFAFF] call_icon flex items-center justify-center"
                        >
                            <img
                            src="{{ asset('frontend-assets/images/callcalling.svg') }}"
                            class="w-6 h-6"
                            />
                        </div>
                        </div>
                        <div class="space-y-1">
                        <div class="mt-2 text-gray-600">
                            <div class="add_content">
                            <h3 class="font-medium">Phone</h3>
                            </div>
                            <a class="block add_content_link">+1 727 313 7679</a>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
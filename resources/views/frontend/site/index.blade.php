@extends('frontend.layout.app')
@section('title', 'Home | Share Fair')
@section('tenant')

<section class="@container py-12 md:py-24 animate-fade-in-up delay-100 opacity-0">
    <div class="flex flex-col gap-6 px-4 @[480px]:gap-8 @[864px]:flex-row-reverse items-center">
        <div class="w-full bg-slate-200 dark:bg-slate-800 aspect-video rounded-xl shadow-lg relative overflow-hidden @[864px]:w-1/2 flex items-center justify-center group" data-alt="Abstract gradient showing balance and equity">
            <div class="absolute inset-0 bg-gradient-to-br from-primary/20 via-primary/10 to-transparent dark:from-primary/30 dark:via-primary/5 dark:to-transparent animate-pulse-slow"></div>
            <span class="material-symbols-outlined text-[120px] text-primary/50 relative z-10 group-hover:scale-110 group-hover:-rotate-3 transition-transform duration-500">handshake</span>
        </div>
        <div class="flex flex-col gap-6 @[480px]:min-w-[400px] @[480px]:gap-8 @[864px]:w-1/2 @[864px]:justify-center">
            <div class="flex flex-col gap-4 text-left">
                <h1 class="text-slate-900 dark:text-slate-100 text-4xl font-black leading-tight tracking-[-0.033em] @[480px]:text-5xl lg:text-6xl">
                    Equitable Asset Distribution, Simplified
                </h1>
                <h2 class="text-slate-600 dark:text-slate-400 text-lg font-normal leading-relaxed @[480px]:text-xl max-w-xl">
                    Share Fair empowers law firms to manage and distribute property and wealth fairly, securely, and transparently.
                </h2>
            </div>
            <div class="flex gap-4">
                <button class="flex min-w-[140px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-12 px-6 bg-primary hover:bg-primary/90 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg text-white text-base font-bold leading-normal tracking-[0.015em] shadow-md shadow-primary/20">
                    <span class="truncate">Get Started Today</span>
                </button>
                <button class="flex min-w-[140px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-12 px-6 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all duration-300 hover:-translate-y-1 hover:shadow-md text-base font-bold leading-normal tracking-[0.015em]">
                    <span class="truncate">Learn More</span>
                </button>
            </div>
        </div>
    </div>
</section>
<section class="flex flex-col gap-10 px-4 py-16 @container border-t border-slate-200 dark:border-slate-800 animate-fade-in-up delay-200 opacity-0" id="how-it-works">
    <div class="flex flex-col gap-4 text-center items-center">
        <h2 class="text-primary text-sm font-bold uppercase tracking-wider">Process</h2>
        <h3 class="text-slate-900 dark:text-slate-100 tracking-tight text-[32px] font-bold leading-tight @[480px]:text-4xl max-w-[720px]">
            How Share Fair Works
        </h3>
        <p class="text-slate-600 dark:text-slate-400 text-lg font-normal leading-relaxed max-w-[720px]">
            Our platform streamlines the complex process of asset distribution, ensuring fairness and compliance at every step.
        </p>
    </div>
    <div class="grid grid-cols-[repeat(auto-fit,minmax(250px,1fr))] gap-6 p-0 mt-8">
        <div class="flex flex-1 gap-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 flex-col hover:shadow-xl hover:-translate-y-2 transition-all duration-300 hover:border-primary/50 group">
            <div class="size-12 rounded-lg bg-primary/10 flex items-center justify-center text-primary group-hover:scale-110 group-hover:bg-primary/20 transition-all duration-300">
                <span class="material-symbols-outlined text-2xl group-hover:animate-bounce">shield_locked</span>
            </div>
            <div class="flex flex-col gap-2">
                <h4 class="text-slate-900 dark:text-slate-100 text-lg font-bold leading-tight group-hover:text-primary transition-colors duration-300">1. Secure Intake</h4>
                <p class="text-slate-600 dark:text-slate-400 text-base font-normal leading-relaxed">Securely input and categorize all assets, from real estate to digital wealth.</p>
            </div>
        </div>
        <div class="flex flex-1 gap-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 flex-col hover:shadow-xl hover:-translate-y-2 transition-all duration-300 hover:border-primary/50 group">
            <div class="size-12 rounded-lg bg-primary/10 flex items-center justify-center text-primary group-hover:scale-110 group-hover:bg-primary/20 transition-all duration-300">
                <span class="material-symbols-outlined text-2xl group-hover:animate-bounce">scale</span>
            </div>
            <div class="flex flex-col gap-2">
                <h4 class="text-slate-900 dark:text-slate-100 text-lg font-bold leading-tight group-hover:text-primary transition-colors duration-300">2. Equitable Valuation</h4>
                <p class="text-slate-600 dark:text-slate-400 text-base font-normal leading-relaxed">Utilize advanced algorithms and expert integrations for fair market valuations.</p>
            </div>
        </div>
        <div class="flex flex-1 gap-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 flex-col hover:shadow-xl hover:-translate-y-2 transition-all duration-300 hover:border-primary/50 group">
            <div class="size-12 rounded-lg bg-primary/10 flex items-center justify-center text-primary group-hover:scale-110 group-hover:bg-primary/20 transition-all duration-300">
                <span class="material-symbols-outlined text-2xl group-hover:animate-bounce">account_tree</span>
            </div>
            <div class="flex flex-col gap-2">
                <h4 class="text-slate-900 dark:text-slate-100 text-lg font-bold leading-tight group-hover:text-primary transition-colors duration-300">3. Transparent Distribution</h4>
                <p class="text-slate-600 dark:text-slate-400 text-base font-normal leading-relaxed">Execute distributions clearly, with full audit trails for all stakeholders.</p>
            </div>
        </div>
    </div>
</section>
<section class="flex flex-col gap-10 px-4 py-16 @container bg-slate-50 dark:bg-slate-900/50 rounded-2xl mx-2 md:mx-0 my-8 animate-fade-in-up delay-300 opacity-0 relative overflow-hidden" id="benefits">
    <div class="absolute inset-0 bg-gradient-to-tr from-primary/5 to-transparent dark:from-primary/10 animate-pulse-slow pointer-events-none"></div>
    <div class="flex flex-col gap-4 text-center items-center relative z-10">
        <h2 class="text-primary text-sm font-bold uppercase tracking-wider">Advantages</h2>
        <h3 class="text-slate-900 dark:text-slate-100 tracking-tight text-[32px] font-bold leading-tight @[480px]:text-4xl max-w-[720px]">
            Benefits for Law Firms
        </h3>
        <p class="text-slate-600 dark:text-slate-400 text-lg font-normal leading-relaxed max-w-[720px]">
            Designed specifically for legal professionals managing complex estates and settlements.
        </p>
    </div>
    <div class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-8 mt-6 relative z-10">
        <div class="flex flex-col gap-4 group">
            <div class="w-full bg-slate-200 dark:bg-slate-800 aspect-video rounded-xl shadow-sm relative overflow-hidden flex items-center justify-center group-hover:shadow-md transition-all duration-300" data-alt="Clock icon representing time savings">
                <span class="material-symbols-outlined text-5xl text-primary/40 group-hover:text-primary/70 group-hover:scale-125 transition-all duration-500">timer</span>
            </div>
            <div>
                <h4 class="text-slate-900 dark:text-slate-100 text-lg font-bold leading-normal mb-1 group-hover:text-primary transition-colors duration-300">Save Time</h4>
                <p class="text-slate-600 dark:text-slate-400 text-base font-normal leading-relaxed">Automate manual calculations and paperwork to focus on client strategy.</p>
            </div>
        </div>
        <div class="flex flex-col gap-4 group">
            <div class="w-full bg-slate-200 dark:bg-slate-800 aspect-video rounded-xl shadow-sm relative overflow-hidden flex items-center justify-center group-hover:shadow-md transition-all duration-300" data-alt="Handshake icon representing reduced disputes">
                <span class="material-symbols-outlined text-5xl text-primary/40 group-hover:text-primary/70 group-hover:scale-125 transition-all duration-500">gavel</span>
            </div>
            <div>
                <h4 class="text-slate-900 dark:text-slate-100 text-lg font-bold leading-normal mb-1 group-hover:text-primary transition-colors duration-300">Reduce Disputes</h4>
                <p class="text-slate-600 dark:text-slate-400 text-base font-normal leading-relaxed">Provide transparent, verifiable valuations to all parties to minimize conflict.</p>
            </div>
        </div>
        <div class="flex flex-col gap-4 group">
            <div class="w-full bg-slate-200 dark:bg-slate-800 aspect-video rounded-xl shadow-sm relative overflow-hidden flex items-center justify-center group-hover:shadow-md transition-all duration-300" data-alt="Checklist icon representing compliance">
                <span class="material-symbols-outlined text-5xl text-primary/40 group-hover:text-primary/70 group-hover:scale-125 transition-all duration-500">fact_check</span>
            </div>
            <div>
                <h4 class="text-slate-900 dark:text-slate-100 text-lg font-bold leading-normal mb-1 group-hover:text-primary transition-colors duration-300">Ensure Compliance</h4>
                <p class="text-slate-600 dark:text-slate-400 text-base font-normal leading-relaxed">Built-in adherence to legal and regulatory standards, automatically updated.</p>
            </div>
        </div>
        <div class="flex flex-col gap-4 group">
            <div class="w-full bg-slate-200 dark:bg-slate-800 aspect-video rounded-xl shadow-sm relative overflow-hidden flex items-center justify-center group-hover:shadow-md transition-all duration-300" data-alt="Star icon representing client trust">
                <span class="material-symbols-outlined text-5xl text-primary/40 group-hover:text-primary/70 group-hover:scale-125 transition-all duration-500">verified_user</span>
            </div>
            <div>
                <h4 class="text-slate-900 dark:text-slate-100 text-lg font-bold leading-normal mb-1 group-hover:text-primary transition-colors duration-300">Enhance Client Trust</h4>
                <p class="text-slate-600 dark:text-slate-400 text-base font-normal leading-relaxed">Deliver clear, professional reports and secure access via a client portal.</p>
            </div>
        </div>
    </div>
</section>
<section class="@container py-16 md:py-24 mb-10 animate-fade-in-up delay-400 opacity-0" id="security">
    <div class="flex flex-col justify-center items-center gap-8 px-6 py-16 @[480px]:px-12 bg-primary/5 dark:bg-primary/10 rounded-2xl border border-primary/10 dark:border-primary/20 hover:border-primary/30 dark:hover:border-primary/40 transition-colors duration-500 relative overflow-hidden group">
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-primary/5 to-transparent dark:via-primary/10 group-hover:translate-x-[100%] transition-transform duration-1000 -translate-x-[100%]"></div>
        <div class="flex flex-col gap-4 text-center items-center relative z-10">
            <div class="size-16 rounded-full bg-primary/20 flex items-center justify-center text-primary mb-2 group-hover:scale-110 transition-transform duration-300">
                <span class="material-symbols-outlined text-3xl">security</span>
            </div>
            <h2 class="text-slate-900 dark:text-slate-100 tracking-tight text-[32px] font-bold leading-tight @[480px]:text-4xl max-w-[720px]">
                Ready to transform your asset distribution process?
            </h2>
            <p class="text-slate-600 dark:text-slate-400 text-lg font-normal leading-relaxed max-w-[720px]">
                Join leading law firms using Share Fair for secure, equitable, and efficient settlements. Start providing a better experience for your clients today.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row gap-4 w-full justify-center max-w-md relative z-10">
            <button class="flex cursor-pointer items-center justify-center overflow-hidden rounded-lg h-12 px-8 bg-primary hover:bg-primary/90 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg text-white text-base font-bold leading-normal tracking-[0.015em] shadow-md w-full sm:w-auto">
                <span class="truncate">Request a Demo</span>
            </button>
        </div>
    </div>
</section>

@endsection
@extends('frontend.layout.app')
@section('title', 'Home | Share Fair')
@section('tenant')

{{-- Hero --}}
<section class="relative overflow-hidden bg-gradient-to-br from-blue-50 via-white to-blue-50 py-16 md:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div class="animate-fade-in-up">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-primary rounded-full text-sm font-semibold mb-6">
                    <span class="material-symbols-outlined text-lg">bolt</span>
                    AI-Powered Asset Distribution
                </div>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 mb-6 leading-tight">
                    Asset Distribution for Law Firms,
                    <span class="gradient-text block mt-2">Powered by AI</span>
                </h1>
                <p class="text-xl text-gray-600 mb-6 leading-relaxed">
                    Add assets via <strong>voice, image, or Excel</strong>. Let AI predict values.
                    Guide clients through <strong>state-compliant multi-party</strong> distribution with zero disputes.
                </p>
                <div class="grid grid-cols-3 gap-4 mb-8 p-6 bg-white rounded-xl border border-gray-200 shadow-sm">
                    <div class="text-center">
                        <div class="flex items-center justify-center gap-1 mb-1">
                            <span class="material-symbols-outlined text-primary text-xl">mic</span>
                            <span class="material-symbols-outlined text-primary text-xl">photo_camera</span>
                            <span class="material-symbols-outlined text-primary text-xl">table_chart</span>
                        </div>
                        <p class="text-2xl font-bold text-primary">4</p>
                        <p class="text-sm text-gray-600">Input Methods</p>
                    </div>
                    <div class="text-center border-x border-gray-200">
                        <span class="material-symbols-outlined text-primary text-2xl mx-auto mb-1 block">shield</span>
                        <p class="text-2xl font-bold text-primary">50</p>
                        <p class="text-sm text-gray-600">State Laws</p>
                    </div>
                    <div class="text-center">
                        <span class="material-symbols-outlined text-primary text-2xl mx-auto mb-1 block">psychology</span>
                        <p class="text-2xl font-bold text-primary">AI</p>
                        <p class="text-sm text-gray-600">Predictions</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-4">
                    <a href="#pricing" class="inline-flex items-center gap-2 px-8 py-4 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition-all shadow-lg hover:shadow-xl">
                        Request a Demo
                        <span class="material-symbols-outlined text-xl">arrow_forward</span>
                    </a>
                    <button type="button" id="watch-demo-btn" class="inline-flex items-center gap-2 px-8 py-4 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:border-primary hover:text-primary transition-all">
                        <span class="material-symbols-outlined">play_arrow</span>
                        Watch Demo (2 min)
                    </button>
                </div>
            </div>
            {{-- Product mockup --}}
            <div class="relative">
                <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 p-1 animate-float">
                    <div class="bg-gradient-to-br from-primary to-primary/90 rounded-t-xl px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-white/30 rounded-full"></span>
                            <span class="w-3 h-3 bg-white/30 rounded-full"></span>
                            <span class="w-3 h-3 bg-white/30 rounded-full"></span>
                        </div>
                        <span class="text-white text-sm font-medium">Share Fair Dashboard</span>
                    </div>
                    <div class="p-6 space-y-4 bg-gray-50">
                        <div class="flex items-center gap-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center animate-pulse">
                                <span class="material-symbols-outlined text-white text-2xl">mic</span>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900 text-sm">Voice Input Active</p>
                                <p class="text-sm text-gray-600">"Add dining table set, worth $3,000"</p>
                            </div>
                            <span class="material-symbols-outlined text-green-600 text-2xl">check_circle</span>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-gray-600">AI Price Prediction</span>
                                <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full font-medium">95% Confidence</span>
                            </div>
                            <p class="text-3xl font-bold text-gray-900 mb-2">$2,850</p>
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <span class="material-symbols-outlined text-green-600 text-lg">trending_up</span>
                                <span>Based on 15 comparable sales</span>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <p class="text-sm font-medium text-gray-600 mb-3">Distribution Preview</p>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 bg-primary rounded-full text-white text-xs flex items-center justify-center font-bold">A</span>
                                        <span>Alice - 40%</span>
                                    </div>
                                    <span class="font-semibold">$180,000</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 bg-green-600 rounded-full text-white text-xs flex items-center justify-center font-bold">B</span>
                                        <span>Bob - 35%</span>
                                    </div>
                                    <span class="font-semibold">$157,500</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 bg-purple-600 rounded-full text-white text-xs flex items-center justify-center font-bold">C</span>
                                        <span>Carol - 25%</span>
                                    </div>
                                    <span class="font-semibold">$112,500</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="absolute -bottom-6 -right-6 bg-white rounded-xl shadow-lg border border-gray-200 p-4">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-green-600 text-3xl">check_circle</span>
                        <div>
                            <p class="font-bold text-gray-900">500+ Law Firms</p>
                            <p class="text-sm text-gray-600">Trust Share Fair</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Social proof bar --}}
<section class="bg-primary py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center text-white">
            <div>
                <p class="text-3xl font-bold mb-1">500+</p>
                <p class="text-blue-100 text-sm">Law Firms</p>
            </div>
            <div>
                <p class="text-3xl font-bold mb-1">$2B+</p>
                <p class="text-blue-100 text-sm">Assets Distributed</p>
            </div>
            <div>
                <p class="text-3xl font-bold mb-1">95%</p>
                <p class="text-blue-100 text-sm">AI Accuracy</p>
            </div>
            <div>
                <p class="text-3xl font-bold mb-1">80%</p>
                <p class="text-blue-100 text-sm">Fewer Disputes</p>
            </div>
        </div>
    </div>
</section>

{{-- Features --}}
<section id="features" class="py-16 md:py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12 md:mb-16">
            <p class="text-primary font-semibold mb-2 uppercase tracking-wide">Features</p>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Add Assets Your Way</h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">Four powerful input methods. Your clients choose what's easiest for them.</p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            @foreach([
                ['icon' => 'mic', 'title' => 'Voice Input', 'desc' => 'Speak naturally to add assets', 'detail' => '"Add my car, a 2020 Tesla Model 3"', 'badge' => 'Hands-Free'],
                ['icon' => 'photo_camera', 'title' => 'Photo Upload', 'desc' => 'AI identifies assets from images', 'detail' => 'Point, shoot, done', 'badge' => 'AI-Powered'],
                ['icon' => 'table_chart', 'title' => 'Excel Import', 'desc' => 'Bulk upload hundreds of assets', 'detail' => '500+ items in 5 minutes', 'badge' => 'Bulk Processing'],
                ['icon' => 'description', 'title' => 'Manual Entry', 'desc' => 'Traditional form-based input', 'detail' => 'Full control over every field', 'badge' => 'Precise'],
            ] as $feature)
            <div class="bg-white rounded-xl border-2 border-gray-200 p-6 hover:border-primary hover:shadow-xl transition-all cursor-pointer group">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-14 h-14 bg-blue-100 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-primary text-3xl">{{ $feature['icon'] }}</span>
                    </div>
                    <span class="text-xs px-2 py-1 bg-blue-50 text-primary rounded-full font-medium">{{ $feature['badge'] }}</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $feature['title'] }}</h3>
                <p class="text-gray-600 mb-3">{{ $feature['desc'] }}</p>
                <p class="text-sm text-primary font-medium">{{ $feature['detail'] }} →</p>
            </div>
            @endforeach
        </div>
        {{-- AI valuation showcase --}}
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-6 md:p-8 border border-blue-200">
            <div class="grid md:grid-cols-2 gap-8 items-center">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-primary rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-white text-3xl">psychology</span>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900">AI-Powered Valuation</h3>
                    </div>
                    <p class="text-gray-700 mb-6 text-lg">Machine learning analyzes market data, comparables, condition, and location to predict fair values with 95%+ accuracy.</p>
                    <ul class="space-y-3">
                        @foreach(['Market data from 1M+ transactions', 'Real-time comparable sales analysis', 'Depreciation and condition factors', 'Location-based pricing adjustments', 'Attorney override capability', 'Learns from your adjustments'] as $item)
                        <li class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-green-600 text-xl flex-shrink-0">check_circle</span>
                            <span class="text-gray-700">{{ $item }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="bg-white rounded-xl p-6 shadow-xl border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-semibold text-gray-900">2020 Tesla Model 3</h4>
                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-semibold">95% Confidence</span>
                    </div>
                    <p class="text-sm text-gray-600 mb-2">AI Predicted Value</p>
                    <p class="text-5xl font-bold text-gray-900 mb-2">$28,500</p>
                    <div class="flex items-center gap-2 text-sm text-green-600 mb-6">
                        <span class="material-symbols-outlined text-lg">trending_up</span>
                        <span>Within market range</span>
                    </div>
                    <div class="space-y-3 pb-4 border-b border-gray-200">
                        <div class="flex justify-between text-sm"><span class="text-gray-600">Market Range:</span><span class="font-semibold">$26,000 - $31,000</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-600">Similar Sales:</span><span class="font-semibold">47 transactions</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-600">Condition Factor:</span><span class="font-semibold">Excellent (1.05x)</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-600">Location:</span><span class="font-semibold">San Francisco, CA</span></div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <button type="button" class="w-full py-2 bg-primary text-white rounded-lg font-medium text-sm hover:bg-primary/90">Accept AI Value</button>
                        <button type="button" class="w-full py-2 border border-gray-300 text-gray-700 rounded-lg font-medium text-sm hover:bg-gray-50">Override Value</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- How it works --}}
<section id="how-it-works" class="py-16 md:py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12 md:mb-16">
            <p class="text-primary font-semibold mb-2 uppercase tracking-wide">Process</p>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">How Share Fair Works</h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">From asset intake to final distribution—guided, compliant, and transparent.</p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach([
                ['num' => '1', 'title' => 'Smart Asset Intake', 'desc' => 'Add assets via voice, photo, Excel, or manual entry. AI auto-categorizes and validates.', 'icon' => 'mic', 'time' => '5 min'],
                ['num' => '2', 'title' => 'AI-Powered Valuation', 'desc' => 'Machine learning predicts fair market values. Override if needed.', 'icon' => 'psychology', 'time' => 'Instant'],
                ['num' => '3', 'title' => 'Guided Negotiation', 'desc' => 'Multi-party counter-offers with full audit trail until agreement.', 'icon' => 'chat', 'time' => '1-7 days'],
                ['num' => '4', 'title' => 'Compliant Distribution', 'desc' => 'State law verified distribution with court-ready documentation.', 'icon' => 'shield', 'time' => '1 hour'],
            ] as $step)
            <div class="relative bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                <div class="absolute -top-5 -left-5 w-14 h-14 bg-primary text-white rounded-full flex items-center justify-center font-bold text-2xl shadow-lg border-4 border-gray-50">{{ $step['num'] }}</div>
                <div class="mt-4">
                    <div class="w-14 h-14 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-primary text-3xl">{{ $step['icon'] }}</span>
                    </div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xl font-bold text-gray-900">{{ $step['title'] }}</h3>
                        <span class="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded-full font-medium">{{ $step['time'] }}</span>
                    </div>
                    <p class="text-gray-600">{{ $step['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Multi-party --}}
<section class="py-16 md:py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <p class="text-primary font-semibold mb-2 uppercase tracking-wide">Multi-Party Distribution</p>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
                    Not Just Two Parties—<br><span class="gradient-text">Handle Complex Estates</span>
                </h2>
                <p class="text-lg text-gray-600 mb-6">Most tools only handle binary 50/50 splits. Share Fair supports unlimited parties with custom percentages, priorities, and state-specific rules.</p>
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <p class="text-3xl font-bold text-primary mb-1">2-20+</p>
                        <p class="text-sm text-gray-700">Parties Supported</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                        <p class="text-3xl font-bold text-green-600 mb-1">100%</p>
                        <p class="text-sm text-gray-700">Custom Splits</p>
                    </div>
                </div>
                <ul class="space-y-4">
                    @foreach([['icon' => 'group', 'title' => 'Unlimited Parties', 'desc' => 'Handle estates with 2 to 20+ beneficiaries'], ['icon' => 'bar_chart', 'title' => 'Custom Percentages', 'desc' => 'Any split: 60/40, 33/33/33, or custom ratios'], ['icon' => 'check_circle', 'title' => 'Priority Preferences', 'desc' => 'Respect wishes within legal bounds'], ['icon' => 'shield', 'title' => 'State Law Compliance', 'desc' => 'Auto-applies spousal rights, forced heirship']] as $item)
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary text-2xl flex-shrink-0 mt-1">{{ $item['icon'] }}</span>
                        <div>
                            <p class="font-semibold text-gray-900">{{ $item['title'] }}</p>
                            <p class="text-gray-600 text-sm">{{ $item['desc'] }}</p>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-6 md:p-8 border border-blue-200">
                <div class="bg-white rounded-xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="font-bold text-gray-900">Estate Distribution</h4>
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Agreed</span>
                    </div>
                    <div class="space-y-3 mb-6">
                        @foreach([['name' => 'Alice Smith', 'percent' => 40, 'amount' => 180000, 'initial' => 'A'], ['name' => 'Bob Johnson', 'percent' => 35, 'amount' => 157500, 'initial' => 'B'], ['name' => 'Carol Davis', 'percent' => 25, 'amount' => 112500, 'initial' => 'C']] as $party)
                        <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-bold">{{ $party['initial'] }}</span>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $party['name'] }}</p>
                                    <p class="text-sm text-gray-600">{{ $party['percent'] }}% of estate</p>
                                </div>
                            </div>
                            <p class="font-bold text-gray-900">${{ number_format($party['amount']) }}</p>
                        </div>
                        @endforeach
                    </div>
                    <div class="pt-4 border-t border-gray-200">
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600">Total Estate Value:</span>
                            <span class="text-lg font-bold text-gray-900">$450,000</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Distribution Method:</span>
                            <span class="font-medium text-gray-900">Per California Law</span>
                        </div>
                    </div>
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <button type="button" class="py-2 bg-primary text-white rounded-lg font-medium text-sm hover:bg-primary/90 flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-lg">download</span> Download
                        </button>
                        <button type="button" class="py-2 border border-gray-300 text-gray-700 rounded-lg font-medium text-sm hover:bg-gray-50 flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-lg">description</span> View Details
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Pricing --}}
<section id="pricing" class="py-16 md:py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12 md:mb-16">
            <p class="text-primary font-semibold mb-2 uppercase tracking-wide">Pricing</p>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Plans for Every Firm Size</h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">Transparent pricing. No hidden fees. Cancel anytime.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
            @foreach([
                ['name' => 'Solo', 'price' => 299, 'desc' => 'For individual attorneys', 'features' => ['10 cases per month', 'All 4 input methods', 'AI price predictions', 'Up to 5 parties per case', 'State law compliance', 'Client portal', 'Email support', '1 user account'], 'cta' => 'Start Free Trial', 'popular' => false],
                ['name' => 'Firm', 'price' => 799, 'desc' => 'For small to mid-size firms', 'features' => ['50 cases per month', 'All 4 input methods', 'AI price predictions', 'Unlimited parties', 'State law compliance', 'Branded client portal', 'Priority support', '5 user accounts', 'API access', 'Custom integrations'], 'cta' => 'Start Free Trial', 'popular' => true],
                ['name' => 'Enterprise', 'price' => null, 'desc' => 'For large firms & organizations', 'features' => ['Unlimited cases', 'All 4 input methods', 'AI price predictions', 'Unlimited parties', 'State law compliance', 'White-label portal', 'Dedicated support', 'Unlimited users', 'API access', 'Custom integrations', 'SLA guarantee', 'On-premise option'], 'cta' => 'Contact Sales', 'popular' => false],
            ] as $plan)
            <div class="rounded-2xl p-8 {{ $plan['popular'] ? 'gradient-border bg-white shadow-2xl scale-105' : 'bg-white border-2 border-gray-200 shadow-sm' }}">
                @if($plan['popular'])
                <div class="text-center mb-4">
                    <span class="px-4 py-1 bg-primary text-white rounded-full text-sm font-semibold">Most Popular</span>
                </div>
                @endif
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan['name'] }}</h3>
                    <p class="text-gray-600 mb-4">{{ $plan['desc'] }}</p>
                    <div class="mb-4">
                        @if($plan['price'])
                        <span class="text-5xl font-bold text-gray-900">${{ $plan['price'] }}</span>
                        <span class="text-gray-600">/month</span>
                        @else
                        <span class="text-4xl font-bold text-gray-900">Custom</span>
                        @endif
                    </div>
                    <a href="{{ route('admin.login') }}" class="inline-block w-full py-3 rounded-lg font-semibold transition-all {{ $plan['popular'] ? 'bg-primary text-white hover:bg-primary/90 shadow-md' : 'border-2 border-gray-300 text-gray-700 hover:border-primary hover:text-primary' }}">{{ $plan['cta'] }}</a>
                </div>
                <ul class="space-y-3">
                    @foreach($plan['features'] as $f)
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-green-600 text-xl flex-shrink-0 mt-0.5">check</span>
                        <span class="text-gray-700 text-sm">{{ $f }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
        <div class="mt-12 text-center">
            <p class="text-gray-600 mb-4">All plans include 14-day free trial. No credit card required.</p>
            <a href="#faq" class="text-primary font-medium hover:underline">View detailed feature comparison →</a>
        </div>
    </div>
</section>

{{-- Testimonials --}}
<section id="testimonials" class="py-16 md:py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12 md:mb-16">
            <p class="text-primary font-semibold mb-2 uppercase tracking-wide">Testimonials</p>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Trusted by Leading Law Firms</h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">See what attorneys are saying about Share Fair.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8 mb-8">
            @foreach([
                ['name' => 'Sarah Mitchell', 'title' => 'Partner, Mitchell & Associates', 'firm' => 'Estate Planning Law Firm, Boston', 'photo' => 'SM', 'quote' => 'Share Fair cut our asset distribution time from 40 hours to 6 hours per case. The AI valuations are incredibly accurate, and clients love the transparent process.', 'stats' => 'Saved 34 hours per case'],
                ['name' => 'James Chen', 'title' => 'Managing Partner', 'firm' => 'Chen Family Law Group, San Francisco', 'photo' => 'JC', 'quote' => 'The voice input feature is a game-changer. Our elderly clients can add assets by speaking, and the Excel import handled a 500-item estate in minutes.', 'stats' => '500 items in 5 minutes'],
                ['name' => 'Maria Rodriguez', 'title' => 'Senior Attorney', 'firm' => 'Rodriguez Law Firm, Miami', 'photo' => 'MR', 'quote' => 'Multi-party distribution used to be a nightmare. Share Fair handles 5+ beneficiaries with custom percentages flawlessly. Disputes dropped by 80%.', 'stats' => '80% fewer disputes'],
            ] as $t)
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center gap-1 mb-4">
                    @for($i = 0; $i < 5; $i++) <span class="material-symbols-outlined text-yellow-400 text-xl" style="font-variation-settings: 'FILL' 1">star</span> @endfor
                </div>
                <p class="text-gray-700 mb-6 leading-relaxed">"{{ $t['quote'] }}"</p>
                <div class="pt-4 border-t border-gray-200">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white font-bold">{{ $t['photo'] }}</div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ $t['name'] }}</p>
                            <p class="text-sm text-gray-600">{{ $t['title'] }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600">{{ $t['firm'] }}</p>
                    <div class="mt-3 px-3 py-2 bg-blue-50 rounded-lg">
                        <p class="text-sm font-medium text-primary">{{ $t['stats'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="text-center">
            <a href="#pricing" class="inline-block px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:border-primary hover:text-primary transition-all">Read More Case Studies →</a>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section id="faq" class="py-16 md:py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12 md:mb-16">
            <p class="text-primary font-semibold mb-2 uppercase tracking-wide">FAQ</p>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h2>
            <p class="text-xl text-gray-600">Everything you need to know about Share Fair.</p>
        </div>
        <div class="space-y-4">
            @foreach([
                ['q' => 'How accurate is the AI price prediction?', 'a' => 'Our ML model has 95%+ accuracy based on market data, comparable sales, and asset condition. You can always override predictions if needed, and the system learns from attorney adjustments.'],
                ['q' => 'Which states are supported?', 'a' => 'All 50 US states plus DC. Our legal team updates the system quarterly to reflect new legislation and case law. State-specific rules are automatically applied based on jurisdiction.'],
                ['q' => 'Can I import existing asset lists?', 'a' => 'Yes! Upload Excel, CSV, or Google Sheets. Our system auto-maps columns and validates data. Most imports complete in under 5 minutes for 500+ items.'],
                ['q' => 'How long does setup take?', 'a' => 'Most firms are operational within 24 hours. We provide white-glove onboarding: data migration, team training, and custom branding for client portals.'],
                ['q' => 'Is client data secure?', 'a' => 'Bank-level security: SOC 2 Type II certified, end-to-end encryption, role-based access, and full audit trails. We\'re HIPAA and GDPR compliant.'],
                ['q' => 'What happens if parties can\'t agree on values?', 'a' => 'Built-in negotiation workflow: parties can counter-offer until agreement. The system tracks all offers and maintains a complete audit trail for court documentation.'],
                ['q' => 'Do you integrate with our practice management software?', 'a' => 'Yes. We integrate with Clio, MyCase, PracticePanther, and others via API. We can also build custom integrations for enterprise clients.'],
                ['q' => 'Can we customize the client portal?', 'a' => 'Absolutely. Add your logo, colors, and domain. Clients access via your-firm.com/portal with your branding throughout.'],
            ] as $idx => $faq)
            <div class="bg-white rounded-xl border-2 border-gray-200 overflow-hidden faq-item">
                <button type="button" class="faq-toggle w-full px-6 py-5 flex items-center justify-between hover:bg-gray-50 transition-colors text-left" data-faq-index="{{ $idx }}">
                    <div class="flex items-start gap-4">
                        <span class="material-symbols-outlined text-primary text-2xl flex-shrink-0 mt-0.5">help</span>
                        <span class="font-semibold text-gray-900">{{ $faq['q'] }}</span>
                    </div>
                    <span class="material-symbols-outlined text-gray-400 text-2xl flex-shrink-0 faq-chevron transition-transform">expand_more</span>
                </button>
                <div class="faq-content hidden px-6 pb-5 pl-16">
                    <p class="text-gray-600 leading-relaxed">{{ $faq['a'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-12 text-center bg-blue-50 rounded-xl p-8 border border-blue-200">
            <p class="text-gray-700 mb-4">Still have questions?</p>
            <a href="{{ route('admin.login') }}" class="inline-block px-6 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition-colors">Schedule a Call with Our Team</a>
        </div>
    </div>
</section>

{{-- Final CTA --}}
<section class="py-16 md:py-20 bg-gradient-to-br from-primary to-primary/90">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
        <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="material-symbols-outlined text-white text-5xl">balance</span>
        </div>
        <h2 class="text-3xl md:text-5xl font-bold text-white mb-6">Ready to Transform Your Asset Distribution Process?</h2>
        <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">Join 500+ law firms using Share Fair for secure, equitable, and efficient settlements. Start your 14-day free trial today—no credit card required.</p>
        <div class="flex flex-wrap gap-4 justify-center mb-8">
            <a href="{{ route('admin.login') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-white text-primary rounded-lg font-semibold hover:bg-blue-50 transition-all shadow-lg text-lg">Start Free Trial</a>
            <a href="#pricing" class="inline-flex items-center gap-2 px-8 py-4 border-2 border-white text-white rounded-lg font-semibold hover:bg-white/10 transition-all text-lg">Request a Demo</a>
        </div>
        <div class="flex flex-wrap items-center justify-center gap-8 text-blue-100 text-sm">
            <div class="flex items-center gap-2"><span class="material-symbols-outlined text-xl">check</span><span>No credit card required</span></div>
            <div class="flex items-center gap-2"><span class="material-symbols-outlined text-xl">check</span><span>14-day free trial</span></div>
            <div class="flex items-center gap-2"><span class="material-symbols-outlined text-xl">check</span><span>Cancel anytime</span></div>
        </div>
    </div>
</section>

{{-- Video modal --}}
<div id="video-modal" class="fixed inset-0 bg-black/80 z-50 hidden items-center justify-center p-4" aria-hidden="true">
    <div class="bg-white rounded-xl max-w-4xl w-full overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Product Demo</h3>
            <button type="button" id="video-modal-close" class="p-2 hover:bg-gray-100 rounded-lg" aria-label="Close">
                <span class="material-symbols-outlined text-2xl text-gray-600">close</span>
            </button>
        </div>
        <div class="aspect-video bg-gray-900 flex items-center justify-center">
            <p class="text-white">Video player would go here</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var videoModal = document.getElementById('video-modal');
    var watchDemoBtn = document.getElementById('watch-demo-btn');
    var videoModalClose = document.getElementById('video-modal-close');

    if (watchDemoBtn) {
        watchDemoBtn.addEventListener('click', function() {
            if (videoModal) {
                videoModal.classList.remove('hidden');
                videoModal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }
        });
    }
    if (videoModalClose && videoModal) {
        videoModalClose.addEventListener('click', function() {
            videoModal.classList.add('hidden');
            videoModal.classList.remove('flex');
            document.body.style.overflow = '';
        });
    }
    if (videoModal) {
        videoModal.addEventListener('click', function(e) {
            if (e.target === videoModal) {
                videoModal.classList.add('hidden');
                videoModal.classList.remove('flex');
                document.body.style.overflow = '';
            }
        });
    }

    document.querySelectorAll('.faq-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var item = this.closest('.faq-item');
            var content = item.querySelector('.faq-content');
            var chevron = item.querySelector('.faq-chevron');
            var isOpen = !content.classList.contains('hidden');
            document.querySelectorAll('.faq-content').forEach(function(c) { c.classList.add('hidden'); });
            document.querySelectorAll('.faq-chevron').forEach(function(c) { c.style.transform = ''; });
            if (!isOpen) {
                content.classList.remove('hidden');
                if (chevron) chevron.style.transform = 'rotate(180deg)';
            }
        });
    });
})();
</script>
@endpush

@endsection

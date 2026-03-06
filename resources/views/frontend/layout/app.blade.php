<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>@yield('title', 'Share Fair')</title>
    @vite(['resources/css/app.css'])
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;700;900&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Public Sans', sans-serif; }
        .delay-100 { animation-delay: 100ms; }
        .delay-200 { animation-delay: 200ms; }
        .delay-300 { animation-delay: 300ms; }
        .delay-400 { animation-delay: 400ms; }
        .gradient-text {
            background: linear-gradient(135deg, #137fec 0%, #0d5bb5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .gradient-border {
            background: linear-gradient(white, white) padding-box, linear-gradient(135deg, #137fec, #0d5bb5) border-box;
            border: 2px solid transparent;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-float { animation: float 3s ease-in-out infinite; }
    </style>
    @stack('styles')
</head>

<body class="bg-white text-gray-900 font-display min-h-screen flex flex-col antialiased">
    <div class="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
        {{-- Navigation --}}
        <nav class="sticky top-0 z-50 bg-white/95 backdrop-blur-sm border-b border-gray-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4">
                <div class="flex items-center justify-between">
                    <a href="{{ url('/') }}" class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-3xl text-primary">balance</span>
                        <span class="text-2xl font-bold text-gray-900">Share Fair</span>
                    </a>
                    <div class="hidden md:flex items-center gap-8">
                        <a href="#features" class="text-gray-700 hover:text-primary transition-colors font-medium">Features</a>
                        <a href="#how-it-works" class="text-gray-700 hover:text-primary transition-colors font-medium">How it Works</a>
                        <a href="#pricing" class="text-gray-700 hover:text-primary transition-colors font-medium">Pricing</a>
                        <a href="#testimonials" class="text-gray-700 hover:text-primary transition-colors font-medium">Testimonials</a>
                        <a href="#faq" class="text-gray-700 hover:text-primary transition-colors font-medium">FAQ</a>
                        <a href="{{ route('admin.login') }}" class="px-6 py-2.5 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition-colors shadow-sm">
                            Law Firm Login
                        </a>
                    </div>
                    <button type="button" class="md:hidden p-2" aria-label="Menu" id="mobile-menu-btn">
                        <span class="material-symbols-outlined text-2xl text-gray-700">menu</span>
                    </button>
                </div>
            </div>
        </nav>

        <main class="flex-1 w-full flex flex-col">
            @yield('tenant')
        </main>

        @hasSection('footer')
            @yield('footer')
        @else
            <footer class="bg-gray-900 text-gray-400 py-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6">
                    <div class="grid md:grid-cols-4 gap-8 mb-8">
                        <div>
                            <div class="flex items-center gap-2 mb-4">
                                <span class="material-symbols-outlined text-2xl text-primary">balance</span>
                                <span class="text-white font-bold text-lg">Share Fair</span>
                            </div>
                            <p class="text-sm">AI-powered asset distribution for law firms.</p>
                        </div>
                        <div>
                            <h4 class="text-white font-semibold mb-4">Product</h4>
                            <ul class="space-y-2 text-sm">
                                <li><a href="#features" class="hover:text-white transition-colors">Features</a></li>
                                <li><a href="#pricing" class="hover:text-white transition-colors">Pricing</a></li>
                                <li><a href="#how-it-works" class="hover:text-white transition-colors">How it Works</a></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="text-white font-semibold mb-4">Company</h4>
                            <ul class="space-y-2 text-sm">
                                <li><a href="#" class="hover:text-white transition-colors">About</a></li>
                                <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
                                <li><a href="#" class="hover:text-white transition-colors">Privacy</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="pt-8 border-t border-gray-800 text-center text-sm">
                        <p>© {{ date('Y') }} Share Fair. All rights reserved.</p>
                    </div>
                </div>
            </footer>
        @endif
    </div>
    @stack('scripts')
</body>

</html>
    
    
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Share Fair Landing Page</title>
    @vite(['resources/css/app.css'])
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;700;900&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Public Sans', sans-serif;
        }.delay-100 { animation-delay: 100ms; }
        .delay-200 { animation-delay: 200ms; }
        .delay-300 { animation-delay: 300ms; }
        .delay-400 { animation-delay: 400ms; }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-display min-h-screen flex flex-col antialiased">
    <div class="relative flex h-auto min-h-screen w-full flex-col bg-background-light dark:bg-background-dark group/design-root overflow-x-hidden">
        <div class="layout-container flex h-full grow flex-col">
            <div class="px-4 md:px-10 lg:px-40 flex flex-1 justify-center py-5">
                <div class="layout-content-container flex flex-col max-w-[960px] flex-1 w-full">
                    <header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-slate-200 dark:border-slate-800 px-4 md:px-10 py-3 rounded-xl bg-white/50 dark:bg-slate-900/50 backdrop-blur-md sticky top-4 z-50 animate-fade-in-up">
                        <div class="flex items-center gap-4 text-slate-900 dark:text-slate-100">
                            <div class="size-6 text-primary flex items-center justify-center hover:scale-110 transition-transform duration-300">
                                <span class="material-symbols-outlined text-2xl">balance</span>
                            </div>
                            <h2 class="text-lg font-bold leading-tight tracking-[-0.015em]">Share Fair</h2>
                        </div>
                        <div class="flex flex-1 justify-end gap-8">
                            <div class="hidden md:flex items-center gap-9">
                                <a class="text-slate-700 dark:text-slate-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal hover:-translate-y-0.5 transform duration-200" href="#how-it-works">How it Works</a>
                                <a class="text-slate-700 dark:text-slate-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal hover:-translate-y-0.5 transform duration-200" href="#benefits">Benefits</a>
                                <a class="text-slate-700 dark:text-slate-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal hover:-translate-y-0.5 transform duration-200" href="#security">Security</a>
                            </div>
                            <a href="{{ route('admin.login') }}" class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary hover:bg-primary/90 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 text-white text-sm font-bold leading-normal tracking-[0.015em]">
                                <span class="truncate">Law Firm Login</span>
                            </a>
                        </div>
                    </header>
                    <main class="flex-1 w-full flex flex-col">
                      @yield('tenant')
                    </main>
                    <footer class="py-8 text-center border-t border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 text-sm mt-auto animate-fade-in-up delay-400 opacity-0">
                        <p>© 2026 Share Fair. All rights reserved.</p>
                    </footer>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
    
    
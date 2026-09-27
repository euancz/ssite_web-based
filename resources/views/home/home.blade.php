@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-12">

    {{-- Section heading --}}
    <h1 class="font-display text-2xl sm:text-3xl md:text-4xl font-bold ssite-navy pb-4 border-b-2 border-[var(--ssite-navy)] mb-8 sm:mb-10">
        What's New at SSITE
    </h1>

    {{-- ===================== FEATURED ARTICLE ===================== --}}
    <article class="mb-10 sm:mb-14">
        <p class="text-sm font-semibold text-[var(--ssite-navy-soft)] mb-2">Article</p>

        <h2 class="font-display text-2xl sm:text-3xl md:text-[2.6rem] font-bold leading-tight ssite-navy mb-3">
            <a href="{{ url('/articles/featured-article-slug') }}" class="hover:opacity-80">
                MCC's Crimson ByteBusters Claim 2nd Place in Ethical Hacker CTF 2026
            </a>
        </h2>

        <p class="text-sm text-slate-500 mb-6">
            By <span class="font-semibold text-slate-600">Author Name</span> &middot; January 05, 2026
        </p>

        {{-- FEATURED IMAGE PLACEHOLDER --}}
        <a href="{{ url('/articles/featured-article-slug') }}" class="block rounded-xl overflow-hidden border border-[var(--ssite-line)]">
            <img src="https://placehold.co/1200x630/17324D/A7E1F5?text=Featured+Article+Image"
                 alt="Featured article image"
                 class="w-full h-auto object-cover">
        </a>
    </article>

    <hr class="border-[var(--ssite-line)] mb-10 sm:mb-12">

    {{-- ===================== SECONDARY ARTICLES ===================== --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 sm:gap-10 relative">

        {{-- vertical divider (desktop only) --}}
        <div class="hidden md:block absolute left-1/2 top-0 bottom-0 w-px bg-[var(--ssite-line)]"></div>

        {{-- Card 1 --}}
        <article class="md:pr-10">
            <a href="{{ url('/activities/second-article-slug') }}" class="block rounded-xl overflow-hidden border border-[var(--ssite-line)] mb-4">
                {{-- IMAGE PLACEHOLDER --}}
                <img src="https://placehold.co/700x460/17324D/A7E1F5?text=Activity+Image"
                     alt="Activity image"
                     class="w-full h-auto object-cover">
            </a>

            <p class="text-sm font-semibold text-[var(--ssite-navy-soft)] mb-2">Activities</p>

            <h3 class="font-display text-xl md:text-2xl font-bold leading-snug ssite-navy mb-2">
                <a href="{{ url('/activities/second-article-slug') }}" class="hover:opacity-80">
                    SSITE and ICS Bring Joy and Learning to Doña Maria Daycare Outreach Program
                </a>
            </h3>

            <p class="text-sm text-slate-500">
                By <span class="font-semibold text-slate-600">Author Name</span> &middot; January 05, 2026
            </p>
        </article>

        {{-- Card 2 --}}
        <article class="md:pl-10">
            <a href="{{ url('/achievements/third-article-slug') }}" class="block rounded-xl overflow-hidden border border-[var(--ssite-line)] mb-4">
                {{-- IMAGE PLACEHOLDER --}}
                <img src="https://placehold.co/700x460/17324D/A7E1F5?text=Achievement+Image"
                     alt="Achievement image"
                     class="w-full h-auto object-cover">
            </a>

            <p class="text-sm font-semibold text-[var(--ssite-navy-soft)] mb-2">Achievements</p>

            <h3 class="font-display text-xl md:text-2xl font-bold leading-snug ssite-navy mb-2">
                <a href="{{ url('/achievements/third-article-slug') }}" class="hover:opacity-80">
                    SSITE Ushers in New Leadership Era at Mabalacat City College Turnover Ceremony
                </a>
            </h3>

            <p class="text-sm text-slate-500">
                By <span class="font-semibold text-slate-600">Author Name</span> &middot; August 11, 2026
            </p>
        </article>
    </div>

</div>
@endsection
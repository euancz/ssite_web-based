@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="home-content">

    {{-- Section heading --}}
    <h1 class="home-heading">
        What's New at SSITE
    </h1>

    {{-- ===================== FEATURED ARTICLE ===================== --}}
    <article class="featured-article">
        <p class="article-category">Article</p>

        <h2 class="featured-title">
            <a href="{{ url('/articles/featured-article-slug') }}" class="article-title-link">
                MCC's Crimson ByteBusters Claim 2nd Place in Ethical Hacker CTF 2026
            </a>
        </h2>

        <p class="article-byline featured-byline">
            By <span class="article-author">Author Name</span> &middot; January 05, 2026
        </p>

        {{-- FEATURED IMAGE PLACEHOLDER --}}
        <a href="{{ url('/articles/featured-article-slug') }}" class="featured-image-link">
            <img src="https://placehold.co/1200x630/17324D/A7E1F5?text=Featured+Article+Image"
                 alt="Featured article image"
                 class="article-image">
        </a>
    </article>

    <hr class="home-divider">

    {{-- ===================== SECONDARY ARTICLES ===================== --}}
    <div class="secondary-articles">

        {{-- vertical divider (desktop only) --}}
        <div class="secondary-articles-divider"></div>

        {{-- Card 1 --}}
        <article class="secondary-article secondary-article-first">
            <a href="{{ url('/activities/second-article-slug') }}" class="secondary-article-image-link">
                {{-- IMAGE PLACEHOLDER --}}
                <img src="https://placehold.co/700x460/17324D/A7E1F5?text=Activity+Image"
                     alt="Activity image"
                     class="article-image">
            </a>

            <p class="article-category">Activities</p>

            <h3 class="secondary-article-title">
                <a href="{{ url('/activities/second-article-slug') }}" class="article-title-link">
                    SSITE and ICS Bring Joy and Learning to Doña Maria Daycare Outreach Program
                </a>
            </h3>

            <p class="article-byline">
                By <span class="article-author">Author Name</span> &middot; January 05, 2026
            </p>
        </article>

        {{-- Card 2 --}}
        <article class="secondary-article secondary-article-second">
            <a href="{{ url('/achievements/third-article-slug') }}" class="secondary-article-image-link">
                {{-- IMAGE PLACEHOLDER --}}
                <img src="https://placehold.co/700x460/17324D/A7E1F5?text=Achievement+Image"
                     alt="Achievement image"
                     class="article-image">
            </a>

            <p class="article-category">Achievements</p>

            <h3 class="secondary-article-title">
                <a href="{{ url('/achievements/third-article-slug') }}" class="article-title-link">
                    SSITE Ushers in New Leadership Era at Mabalacat City College Turnover Ceremony
                </a>
            </h3>

            <p class="article-byline">
                By <span class="article-author">Author Name</span> &middot; August 11, 2026
            </p>
        </article>
    </div>

</div>
@endsection
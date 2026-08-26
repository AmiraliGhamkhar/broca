{{-- Placeholder 3D-heart hero boundary. Replace MP4/WebM + poster when client asset arrives (SPEC §1.1, §15). --}}
<section class="hero-shell relative w-full flex items-center overflow-hidden bg-broca-sand">
    <video
        class="hero-poster absolute inset-0 w-full h-full object-cover opacity-60" aria-hidden="true"
        autoplay muted loop playsinline
        poster="{{ asset('images/heart-placeholder.svg') }}">
        @if (is_file(public_path('videos/heart-placeholder.mp4')))
            <source src="{{ asset('videos/heart-placeholder.mp4') }}" type="video/mp4">
        @endif
        @if (is_file(public_path('videos/heart-placeholder.webm')))
            <source src="{{ asset('videos/heart-placeholder.webm') }}" type="video/webm">
        @endif
    </video>

    <div class="hero-content">
        <span class="hero-kicker">یادگیری دقیق برای مسیر حرفه‌ای تو</span>
        <h1 class="text-4xl md:text-6xl font-bold text-broca-ink drop-shadow">
            {{ __('app.name') }}
        </h1>
        <p class="mt-4 text-lg md:text-2xl text-broca-ink/80">
            {{ __('app.tagline') }}
        </p>
        <div class="hero-actions mt-8">
            <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-md bg-broca-accent text-white font-medium">{{ __('app.cta_register') }}</a>
            <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-md border border-broca-ink/30 font-medium">{{ __('app.cta_login') }}</a>
        </div>
    </div>

    <p class="absolute bottom-3 inset-x-0 z-10 text-center text-xs text-broca-ink/60">
        {{ __('app.hero_placeholder_note') }}
    </p>
</section>

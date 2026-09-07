{{-- One line of inline feedback under an auth field. The framework's
     `$errors` bag is always present (ShareErrorsFromSession), but the
     `?? new MessageBag` guard keeps this partial usable outside the web
     middleware stack (components, previews) without throwing. --}}
@php($errors = $errors ?? new \Illuminate\Support\MessageBag)
@if ($errors->has($field))
    <p class="mt-1.5 text-[11px] font-bold text-error-text leading-6" role="alert">
        {{ $errors->first($field) }}
    </p>
@endif

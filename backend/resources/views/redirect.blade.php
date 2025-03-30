@extends('layouts.quvel')

@php
    $hasScheme = !empty($schemeUrl);
    $countdownSeconds = 3;
@endphp

@section('content')
    <h1 class="text-3xl font-bold mb-4">You’re all set!</h1>

    @if (!empty($message))
        <p class="text-gray-200 mb-4">{{ $message }}</p>
    @endif

    @if ($hasScheme)
        <p class="text-sm text-gray-400 mb-6" id="redirect-msg">
            Redirecting to the app in <span id="countdown">{{ $countdownSeconds }}</span> seconds...
        </p>

        <a
            href="{{ $schemeUrl }}"
            class="inline-flex items-center justify-center bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md shadow transition"
        >
            <i class="ti ti-device-mobile mr-2"></i> Return to App
        </a>
    @else
        <p class="text-gray-300 text-sm mb-6" id="close-msg">
            Please return to the app to continue.
            <span class="block mt-1 text-sm text-gray-500">
                This window will close in <span id="countdown">{{ $countdownSeconds }}</span> seconds...
            </span>
        </p>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            const seconds = {{ $countdownSeconds }};
            const redirectUrl = {!! $hasScheme ? json_encode($schemeUrl, JSON_THROW_ON_ERROR) : 'null' !!};
            const countdownEl = document.getElementById('countdown');
            const messageEl = document.getElementById('{{ $hasScheme ? 'redirect-msg' : 'close-msg' }}');

            let counter = seconds;

            if (redirectUrl && counter === 0) {
                window.location.href = redirectUrl;
                return;
            }

            const interval = setInterval(() => {
                counter--;
                if (countdownEl) countdownEl.textContent = counter;

                if (counter <= 0) {
                    clearInterval(interval);

                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    } else {
                        window.close();
                    }

                    setTimeout(() => {
                        messageEl.insertAdjacentHTML(
                            'beforeend',
                            '<br><span class="text-gray-400 text-sm">If this did not happen automatically, you may close this window manually.</span>'
                        );
                    }, 1000);
                }
            }, 1000);

            window.addEventListener('unload', () => {
                window.close();
            });
        })();
    </script>
@endpush

@extends('layouts.quvel')

@php
  $hasScheme = !empty($schemeUrl);
@endphp

@section('content')
  <h1 class="text-3xl font-bold mb-4">You’re all set!</h1>

  @if (!empty($message))
    <p class="text-gray-200 mb-4">{{ $message }}</p>
  @endif

  @if ($hasScheme)
    <p class="text-sm text-gray-400 mb-6" id="redirect-msg">
      You will be redirected back to the app shortly.
    </p>

    <a href="{{ $schemeUrl }}"
      class="inline-flex items-center justify-center bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md shadow transition">
      <i class="ti ti-device-mobile mr-2"></i> Return to App
    </a>
  @else
    <p class="text-gray-300 text-sm mb-6" id="close-msg">
      Please return to the app to continue.
      <span class="block mt-1 text-sm text-gray-500">
        This window will close in <span id="countdown">10</span> seconds...
      </span>
    </p>
  @endif
@endsection

@push('scripts')
  <script>
    (function() {
      const hasScheme = {{ $hasScheme ? 'true' : 'false' }};
      const schemeUrl = {!! json_encode($schemeUrl ?? '', JSON_THROW_ON_ERROR) !!};
      const redirectMsg = document.getElementById('redirect-msg');
      const countdownEl = document.getElementById('countdown');

      if (hasScheme) {
        // Attempt redirect immediately
        window.location.href = schemeUrl;

        // Fallback after 10s
        setTimeout(() => {
          if (redirectMsg) {
            redirectMsg.insertAdjacentHTML(
              'beforeend',
              '<br><span class="text-gray-400 text-sm">If the app didn’t open, you can tap the button above or close this tab manually.</span>'
            );
          }

          window.close();
        }, 10000);
      } else {
        let seconds = 10;

        const interval = setInterval(() => {
          seconds--;
          if (countdownEl) countdownEl.textContent = seconds;

          if (seconds <= 0) {
            clearInterval(interval);
            window.close();
          }
        }, 1000);
      }

      // Safari edge case: tab close
      window.addEventListener('unload', () => {
        window.close();
      });
    })();
  </script>
@endpush

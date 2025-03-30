@extends('layouts.quvel')

@section('content')
    <h1 class="text-4xl font-bold mb-4">
        Welcome to <span class="text-blue-500">Qu</span><span class="text-orange-600">Vel</span> Kit
    </h1>

    <p class="text-lg text-gray-200 mb-6">
        A full-stack hybrid starter kit for Laravel & Quasar.
        <br />
        Built for modern web, mobile, and desktop applications.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        <a href="https://github.com/ItsIrv/quvel-kit"
           class="flex items-center justify-center bg-white/20 hover:bg-white/30 text-white font-semibold py-3 px-5 rounded-md shadow-md transition">
            <i class="ti ti-brand-github mr-2"></i> GitHub Repo
        </a>

        <a href="https://github.com/ItsIrv/quvel-kit/blob/main/docs/README.md"
           class="flex items-center justify-center bg-white/20 hover:bg-white/30 text-white font-semibold py-3 px-5 rounded-md shadow-md transition">
            <i class="ti ti-file-text mr-2"></i> Documentation
        </a>

        <a href="https://quvel.127.0.0.1.nip.io"
           class="flex items-center justify-center bg-white/20 hover:bg-white/30 text-white font-semibold py-3 px-5 rounded-md shadow-md transition">
            <i class="ti ti-layout mr-2"></i> Frontend Playground
        </a>

        <a href="https://coverage.quvel.127.0.0.1.nip.io/__vitest__/"
           class="flex items-center justify-center bg-white/20 hover:bg-white/30 text-white font-semibold py-3 px-5 rounded-md shadow-md transition">
            <i class="ti ti-chart-pie mr-2"></i> Vitest UI
        </a>

        <a href="https://coverage-api.quvel.127.0.0.1.nip.io"
           class="flex items-center justify-center bg-white/20 hover:bg-white/30 text-white font-semibold py-3 px-5 rounded-md shadow-md transition">
            <i class="ti ti-chart-pie mr-2"></i> Laravel Coverage
        </a>

        <a href="http://localhost:8080"
           class="flex items-center justify-center bg-white/20 hover:bg-white/30 text-white font-semibold py-3 px-5 rounded-md shadow-md transition">
            <i class="ti ti-settings mr-2"></i> Traefik Dashboard
        </a>
    </div>
@endsection

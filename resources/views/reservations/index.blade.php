@extends('layouts.app')

@section('title', 'Riwayat Reservasi Saya')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-800">Riwayat Reservasi Saya</h2>
                <p class="text-gray-600 mt-1">Daftar semua reservasi ruang rapat yang telah Anda buat</p>
            </div>
        </div>

        <!-- Reservations List -->
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="p-6">
                @if($reservations->count() > 0)
                    <div class="space-y-4">
                        @foreach($reservations as $reservation)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            <h3 class="text-lg font-semibold text-gray-900">
                                                {{ $reservation->room->name }}
                                            </h3>
                                            <span class="ml-3 px-2 py-1 text-xs font-medium rounded 
                                                @if($reservation->start_time->isPast())
                                                    bg-gray-200 text-gray-700
                                                @else
                                                    bg-green-200 text-green-700
                                                @endif">
                                                {{ $reservation->start_time->isPast() ? 'Selesai' : 'Akan Datang' }}
                                            </span>
                                        </div>
                                        <div class="mt-2 text-sm text-gray-600">
                                            <p class="flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <strong>Tanggal:</strong> {{ $reservation->start_time->format('d M Y') }}
                                            </p>
                                            <p class="flex items-center mt-1">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <strong>Waktu:</strong> {{ $reservation->start_time->format('H:i') }} - {{ $reservation->end_time->format('H:i') }}
                                            </p>
                                            @if($reservation->notes)
                                                <p class="mt-2 text-gray-500">
                                                    <strong>Catatan:</strong> {{ $reservation->notes }}
                                                </p>
                                            @endif
                                            <p class="mt-2 text-xs text-gray-400">
                                                Dibuat: {{ $reservation->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete Button -->
                                    @if($reservation->start_time->isFuture())
                                        <form action="{{ route('reservations.destroy', $reservation) }}" method="POST" 
                                              onsubmit="return confirm('Apakah Anda yakin ingin membatalkan reservasi ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="ml-4 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                                Batalkan
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada reservasi</h3>
                        <p class="mt-1 text-sm text-gray-500">Mulai dengan membuat reservasi ruang rapat dari dashboard.</p>
                        <div class="mt-6">
                            <a href="{{ route('dashboard') }}" 
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Lihat Dashboard
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

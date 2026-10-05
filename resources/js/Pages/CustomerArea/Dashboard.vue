<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    customer: Object,
    latestInvoice: Object,
    recentTickets: Array
});

const formatRupiah = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
};
</script>

<template>
    <Head title="Beranda" />

    <CustomerLayout>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Selamat datang, {{ customer.name }}!</h1>
            <p class="mt-1 text-gray-500">Kelola layanan internet Anda dan lihat informasi terbaru.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Paket Info -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-900">Paket Saat Ini</h3>
                </div>
                <div class="flex-1">
                    <p class="text-xl font-bold text-indigo-700">{{ customer.package ? customer.package.name : 'Belum Ada Paket' }}</p>
                    <p class="text-sm text-gray-500 mt-1">Status: 
                        <span v-if="customer.status === 'active'" class="text-green-600 font-medium">Aktif</span>
                        <span v-else class="text-yellow-600 font-medium capitalize">{{ customer.status }}</span>
                    </p>
                </div>
            </div>

            <!-- Tagihan Alert -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col md:col-span-2 relative overflow-hidden">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-red-50 rounded-full opacity-50"></div>
                <div class="flex items-center gap-3 mb-4 relative">
                    <div class="p-3 bg-red-50 text-red-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-900">Informasi Tagihan</h3>
                </div>
                <div v-if="latestInvoice" class="flex-1 relative">
                    <p class="text-sm text-gray-600 mb-1">Tagihan Bulan Ini</p>
                    <p class="text-2xl font-bold text-red-600 mb-2">{{ formatRupiah(latestInvoice.amount) }}</p>
                    <div class="flex items-center justify-between mt-4">
                        <p class="text-sm text-gray-500">Jatuh Tempo: {{ new Date(latestInvoice.due_date).toLocaleDateString('id-ID') }}</p>
                        <Link href="/my/billing" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 transition-colors">Lihat Detail</Link>
                    </div>
                </div>
                <div v-else class="flex-1 flex flex-col items-center justify-center relative">
                    <p class="text-gray-500">Tidak ada tagihan yang tertunda saat ini.</p>
                </div>
            </div>
        </div>

        <!-- Tiket Bantuan -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-gray-900 text-lg">Tiket Bantuan Terakhir</h3>
                <Link href="/my/tickets" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Lihat Semua</Link>
            </div>
            
            <div v-if="recentTickets && recentTickets.length > 0" class="divide-y divide-gray-100">
                <div v-for="ticket in recentTickets" :key="ticket.id" class="py-4 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">{{ ticket.ticket_number }} - {{ ticket.category }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ ticket.description.substring(0, 50) }}...</p>
                    </div>
                    <div>
                        <span v-if="ticket.status === 'open'" class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">Open</span>
                        <span v-else-if="ticket.status === 'in_progress'" class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">In Progress</span>
                        <span v-else-if="ticket.status === 'resolved'" class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Selesai</span>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-8">
                <p class="text-gray-500">Belum ada tiket bantuan.</p>
            </div>
        </div>
    </CustomerLayout>
</template>

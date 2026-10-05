<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    tickets: Object
});
</script>

<template>
    <Head title="Tiket Bantuan" />

    <CustomerLayout>
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Tiket Bantuan</h1>
                <p class="mt-1 text-gray-500">Daftar laporan keluhan atau gangguan Anda.</p>
            </div>
            <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm">
                + Buat Tiket Baru
            </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">No. Tiket</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kategori</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Prioritas</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Lapor</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ ticket.ticket_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ ticket.category }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="ticket.priority === 'high'" class="px-2.5 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Tinggi</span>
                                <span v-else-if="ticket.priority === 'medium'" class="px-2.5 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Sedang</span>
                                <span v-else class="px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Rendah</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ new Date(ticket.created_at).toLocaleDateString('id-ID') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="ticket.status === 'open'" class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Open</span>
                                <span v-else-if="ticket.status === 'in_progress'" class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Diproses</span>
                                <span v-else class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Selesai</span>
                            </td>
                        </tr>
                        <tr v-if="tickets.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                Tidak ada riwayat tiket gangguan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="tickets.links && tickets.links.length > 3" class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <div class="flex flex-wrap gap-1">
                    <template v-for="(link, p) in tickets.links" :key="p">
                        <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-1 text-sm text-gray-400 border border-gray-200 rounded-lg bg-gray-50" v-html="link.label" />
                        <Link v-else :href="link.url" class="mr-1 mb-1 px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 focus:border-indigo-500 focus:text-indigo-500 transition-colors" :class="{ 'bg-indigo-50 border-indigo-200 text-indigo-600 font-medium': link.active, 'border-gray-200 text-gray-600': !link.active }" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

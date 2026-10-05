<template>
    <AppLayout title="Invoice Pelanggan" subtitle="Kelola tagihan pelanggan dan status pembayarannya">
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <!-- Status Filter Tabs -->
                <div class="bg-white border border-gray-200 rounded-lg p-1 inline-flex shadow-sm">
                    <button @click="filterStatus('')" :class="['px-4 py-1.5 text-sm font-semibold rounded-md transition-colors', !filterStatusVal ? 'bg-indigo-50 text-indigo-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50']">Semua</button>
                    <button @click="filterStatus('unpaid')" :class="['px-4 py-1.5 text-sm font-semibold rounded-md transition-colors', filterStatusVal === 'unpaid' ? 'bg-amber-50 text-amber-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50']">Unpaid</button>
                    <button @click="filterStatus('paid')" :class="['px-4 py-1.5 text-sm font-semibold rounded-md transition-colors', filterStatusVal === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50']">Paid</button>
                    <button @click="filterStatus('partial')" :class="['px-4 py-1.5 text-sm font-semibold rounded-md transition-colors', filterStatusVal === 'partial' ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50']">Partial</button>
                </div>
            </div>
        </div>

        <DataTable
            :columns="columns"
            :data="invoices.data"
            :pagination="invoices"
            searchPlaceholder="Cari nomor invoice atau pelanggan..."
            searchRoute="/invoices"
            :filters="filters"
        >
            <template #filters>
                <div class="flex flex-col flex-1 min-w-[120px]">
                    <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-0.5 leading-none mt-1">Bulan</span>
                    <select v-model="filterMonth" @change="applyFilters" class="form-select border-0 p-0 h-auto text-sm bg-transparent focus:ring-0 text-gray-700 font-medium w-full pb-1">
                        <option value="">Semua Bulan</option>
                        <option v-for="(m, index) in months" :key="index+1" :value="index+1">{{ m }}</option>
                    </select>
                </div>
                <div class="flex flex-col flex-1 min-w-[100px]">
                    <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-0.5 leading-none mt-1">Tahun</span>
                    <select v-model="filterYear" @change="applyFilters" class="form-select border-0 p-0 h-auto text-sm bg-transparent focus:ring-0 text-gray-700 font-medium w-full pb-1">
                        <option value="">Semua Tahun</option>
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
            </template>

            <template #row="{ row, index }">
                <td class="text-gray-500 text-xs text-center">
                    {{ (invoices.current_page - 1) * invoices.per_page + index + 1 }}
                </td>
                <td>
                    <div class="flex flex-col">
                        <span class="font-bold text-gray-900 text-sm font-mono">{{ row.invoice_number }}</span>
                        <span class="text-[10px] text-gray-500 font-medium">{{ formatDate(row.issued_date) }}</span>
                    </div>
                </td>
                <td>
                    <div v-if="row.customer" class="flex flex-col">
                        <Link :href="`/customers/${row.customer.id}`" class="text-blue-600 font-semibold text-sm hover:underline">
                            {{ row.customer.name }}
                        </Link>
                        <span class="text-[10px] text-gray-500 font-mono">{{ row.customer.customer_code }}</span>
                    </div>
                    <span v-else class="text-gray-400 italic text-xs">Pelanggan Dihapus</span>
                </td>
                <td class="text-sm font-medium text-gray-700">{{ row.period_label }}</td>
                <td>
                    <div class="flex flex-col text-right pr-4">
                        <span class="font-bold text-gray-900 text-sm">{{ formatCurrency(row.amount) }}</span>
                    </div>
                </td>
                <td>
                    <span :class="['px-2.5 py-1 text-[10px] font-bold uppercase rounded-md border', statusClass(row.status)]">
                        {{ row.status }}
                    </span>
                </td>
                <td>
                    <div class="flex flex-col">
                        <span class="text-xs font-semibold" :class="row.remaining > 0 ? 'text-red-600' : 'text-emerald-600'">
                            {{ formatCurrency(row.remaining) }}
                        </span>
                        <span v-if="row.total_paid > 0" class="text-[10px] text-gray-500">Terbayar: {{ formatCurrency(row.total_paid) }}</span>
                    </div>
                </td>
                <td class="text-center">
                    <span v-if="row.due_date" class="text-xs font-medium text-gray-700 whitespace-nowrap">{{ formatDate(row.due_date) }}</span>
                    <span v-else class="text-gray-400">-</span>
                </td>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-2">
                    <button class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Lihat Detail">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </template>
        </DataTable>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    invoices: Object,
    filters: Object,
});

const columns = [
    { key: 'index', label: '#' },
    { key: 'invoice_number', label: 'NO INVOICE' },
    { key: 'customer', label: 'PELANGGAN' },
    { key: 'period', label: 'PERIODE' },
    { key: 'amount', label: 'TOTAL TAGIHAN' },
    { key: 'status', label: 'STATUS' },
    { key: 'remaining', label: 'SISA TAGIHAN' },
    { key: 'due_date', label: 'JATUH TEMPO' },
];

const filterStatusVal = ref(props.filters?.status || '');
const filterMonth = ref(props.filters?.period_month || '');
const filterYear = ref(props.filters?.period_year || '');

const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const currentYear = new Date().getFullYear();
const years = Array.from({length: 5}, (_, i) => currentYear - i);

function applyFilters() {
    router.get('/invoices', {
        search: props.filters?.search || undefined,
        status: filterStatusVal.value || undefined,
        period_month: filterMonth.value || undefined,
        period_year: filterYear.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function filterStatus(status) {
    filterStatusVal.value = status;
    applyFilters();
}

function formatCurrency(value) {
    if (!value) return 'Rp 0';
    return 'Rp ' + Number(value).toLocaleString('id-ID');
}

function formatDate(dateString) {
    if (!dateString) return '';
    const d = new Date(dateString);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

function statusClass(status) {
    switch (status) {
        case 'paid': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'unpaid': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'partial': return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'canceled': return 'bg-red-50 text-red-700 border-red-200';
        default: return 'bg-gray-50 text-gray-700 border-gray-200';
    }
}
</script>

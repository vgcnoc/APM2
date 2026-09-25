<template>
    <AppLayout title="Dashboard" subtitle="Ringkasan statistik jaringan & pelanggan ISP">
        <!-- Stat Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <StatCard
                title="Pelanggan Aktif"
                :value="customerStats.total_active"
                color="blue"
                icon="users"
                :subtitle="`Total ${customerStats.total_all} pelanggan`"
                class="delay-1"
            />
            <StatCard
                title="ODP Tersedia"
                :value="infraStats.total_odp_available"
                color="green"
                icon="network"
                :subtitle="`Dari ${infraStats.total_odp} ODP`"
                class="delay-2"
            />
            <StatCard
                title="Tiket Gangguan"
                :value="ticketStats.open"
                color="red"
                icon="ticket"
                :subtitle="`${ticketStats.in_progress} sedang dikerjakan`"
                class="delay-3"
            />
            <StatCard
                title="Pendapatan Bulan Ini"
                :value="monthlyRevenue"
                color="purple"
                icon="money"
                :isCurrency="true"
                class="delay-4"
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Revenue Chart -->
            <div class="lg:col-span-2 glass-card p-6 animate-fade-in-up">
                <h3 class="text-lg font-semibold text-white mb-4">📊 Pendapatan 6 Bulan Terakhir</h3>
                <div class="h-64 flex items-end gap-3">
                    <div
                        v-for="(item, idx) in revenueChart"
                        :key="idx"
                        class="flex-1 flex flex-col items-center gap-2"
                    >
                        <span class="text-xs text-gray-400">Rp {{ formatShort(item.total) }}</span>
                        <div
                            class="w-full rounded-t-lg bg-gradient-to-t from-blue-600 to-cyan-400 transition-all duration-700 ease-out"
                            :style="{ height: getBarHeight(item.total) + '%' }"
                        ></div>
                        <span class="text-xs text-gray-500">{{ getMonthLabel(item.period_month) }}</span>
                    </div>
                    <div v-if="!revenueChart || revenueChart.length === 0" class="flex-1 flex items-center justify-center text-gray-600 text-sm">
                        Belum ada data pendapatan
                    </div>
                </div>
            </div>

            <!-- Booking Pipeline -->
            <div class="glass-card p-6 animate-fade-in-up">
                <h3 class="text-lg font-semibold text-white mb-4">📋 Pipeline Pelanggan</h3>
                <div class="space-y-4">
                    <PipelineItem label="Booking Baru" :count="customerStats.total_booking" color="yellow" />
                    <PipelineItem label="Proses Survey" :count="customerStats.total_survey" color="blue" />
                    <PipelineItem label="Aktif" :count="customerStats.total_active" color="green" />
                </div>

                <div class="mt-6 pt-4 border-t border-white/10">
                    <h4 class="text-sm font-medium text-gray-400 mb-3">Pelanggan Terbaru</h4>
                    <div class="space-y-2">
                        <div
                            v-for="cust in recentCustomers"
                            :key="cust.id"
                            class="flex items-center justify-between p-2 rounded-lg hover:bg-white/5 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-xs font-bold text-white">
                                    {{ cust.name.charAt(0) }}
                                </div>
                                <div>
                                    <p class="text-sm text-white font-medium">{{ cust.name }}</p>
                                    <p class="text-xs text-gray-500">{{ cust.package?.name || 'Belum pilih paket' }}</p>
                                </div>
                            </div>
                            <StatusBadge :status="cust.status" :dot="false" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Tickets -->
        <div class="mt-6 glass-card p-6 animate-fade-in-up">
            <h3 class="text-lg font-semibold text-white mb-4">🎫 Tiket Gangguan Terbaru</h3>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No. Tiket</th>
                            <th>Pelanggan</th>
                            <th>Kategori</th>
                            <th>Prioritas</th>
                            <th>Status</th>
                            <th>Teknisi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="ticket in recentTickets" :key="ticket.id">
                            <td class="font-mono text-blue-400 text-xs">{{ ticket.ticket_number }}</td>
                            <td>{{ ticket.customer?.name }}</td>
                            <td class="capitalize">{{ ticket.category?.replace('_', ' ') }}</td>
                            <td>
                                <span :class="priorityClass(ticket.priority)" class="badge">
                                    {{ ticket.priority }}
                                </span>
                            </td>
                            <td><StatusBadge :status="ticket.status" /></td>
                            <td>{{ ticket.assignee?.name || '-' }}</td>
                        </tr>
                        <tr v-if="!recentTickets || recentTickets.length === 0">
                            <td colspan="6" class="text-center text-gray-600 py-8">Tidak ada tiket terbuka 🎉</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    customerStats: Object,
    infraStats: Object,
    ticketStats: Object,
    monthlyRevenue: Number,
    revenueChart: Array,
    recentCustomers: Array,
    recentTickets: Array,
});

function formatShort(val) {
    if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
    if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
    return val;
}

function getBarHeight(val) {
    if (!props.revenueChart || props.revenueChart.length === 0) return 0;
    const max = Math.max(...props.revenueChart.map(r => r.total));
    return max > 0 ? Math.max(5, (val / max) * 90) : 5;
}

function getMonthLabel(m) {
    const months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return months[m] || '';
}

function priorityClass(p) {
    const map = {
        low: 'bg-gray-500/20 text-gray-400',
        medium: 'bg-blue-500/20 text-blue-400',
        high: 'bg-orange-500/20 text-orange-400',
        critical: 'bg-red-500/20 text-red-400 ring-1 ring-red-500/50',
    };
    return map[p] || map.medium;
}
</script>

<script>
// Pipeline sub-component
export default {
    components: {
        PipelineItem: {
            props: ['label', 'count', 'color'],
            template: `
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full" :class="dotColor"></div>
                        <span class="text-sm text-gray-300">{{ label }}</span>
                    </div>
                    <span class="text-xl font-bold text-white">{{ count }}</span>
                </div>
            `,
            computed: {
                dotColor() {
                    const map = { yellow: 'bg-yellow-400', blue: 'bg-blue-400', green: 'bg-emerald-400' };
                    return map[this.color] || 'bg-gray-400';
                }
            }
        }
    }
};
</script>

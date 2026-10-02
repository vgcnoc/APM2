<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineProps({
    stats: Object,
    surveyTasks: Array,
    installationTasks: Array,
    ticketTasks: Array,
});
</script>

<template>
    <AppLayout title="Dashboard Teknisi" subtitle="Ringkasan tugas dan jadwal yang ditugaskan kepada Anda">
        <!-- Statistik Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <StatCard 
                title="Tugas Survey" 
                :value="stats.survey_assigned || 0" 
                icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" 
                color="blue" 
            />
            <StatCard 
                title="Tugas Instalasi" 
                :value="stats.installation_assigned || 0" 
                icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" 
                color="emerald" 
            />
            <StatCard 
                title="Tiket Gangguan" 
                :value="stats.tickets_assigned || 0" 
                icon="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" 
                color="rose" 
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Daftar Tugas Instalasi -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Tugas Instalasi ({{ installationTasks.length }})
                    </h3>
                    <Link href="/customers/installed" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Lihat Semua &rarr;</Link>
                </div>
                <div class="p-0">
                    <div v-if="installationTasks.length === 0" class="p-8 text-center text-gray-500 text-sm">
                        Tidak ada tugas instalasi.
                    </div>
                    <ul v-else class="divide-y divide-gray-100">
                        <li v-for="task in installationTasks" :key="task.id" class="p-4 hover:bg-gray-50 transition-colors flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-gray-800">{{ task.name }}</h4>
                                <p class="text-xs text-gray-500 mt-1 line-clamp-1">{{ task.address }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-1 rounded-md">{{ task.schedule_date }}</span>
                                <Link :href="`/customers/${task.id}?source=instalasi`" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 bg-white border border-indigo-200 px-3 py-1 rounded-md hover:bg-indigo-50 transition-colors">Buka</Link>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Daftar Tugas Survey -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Tugas Survey ({{ surveyTasks.length }})
                    </h3>
                    <Link href="/customers/survey" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Lihat Semua &rarr;</Link>
                </div>
                <div class="p-0">
                    <div v-if="surveyTasks.length === 0" class="p-8 text-center text-gray-500 text-sm">
                        Tidak ada tugas survey.
                    </div>
                    <ul v-else class="divide-y divide-gray-100">
                        <li v-for="task in surveyTasks" :key="task.id" class="p-4 hover:bg-gray-50 transition-colors flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-gray-800">{{ task.name }}</h4>
                                <p class="text-xs text-gray-500 mt-1 line-clamp-1">{{ task.address }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded-md">{{ task.schedule_date }}</span>
                                <Link :href="`/customers/${task.id}?source=survey`" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 bg-white border border-indigo-200 px-3 py-1 rounded-md hover:bg-indigo-50 transition-colors">Buka</Link>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Daftar Tiket Gangguan -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden lg:col-span-2">
                <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Tiket Gangguan ({{ ticketTasks.length }})
                    </h3>
                    <Link href="/tickets" class="text-xs font-semibold text-rose-600 hover:text-rose-700">Lihat Semua &rarr;</Link>
                </div>
                <div class="p-0 overflow-x-auto">
                    <div v-if="ticketTasks.length === 0" class="p-8 text-center text-gray-500 text-sm">
                        Tidak ada tiket gangguan yang ditugaskan.
                    </div>
                    <table v-else class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="p-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">No Tiket</th>
                                <th class="p-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pelanggan</th>
                                <th class="p-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Prioritas</th>
                                <th class="p-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="p-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="task in ticketTasks" :key="task.id" class="hover:bg-gray-50 transition-colors">
                                <td class="p-3 text-sm font-medium text-gray-800">{{ task.ticket_number }}</td>
                                <td class="p-3 text-sm text-gray-600">{{ task.customer_name }}</td>
                                <td class="p-3">
                                    <span v-if="task.priority === 'high'" class="px-2 py-1 text-xs font-bold rounded-md bg-rose-100 text-rose-700">Tinggi</span>
                                    <span v-else-if="task.priority === 'medium'" class="px-2 py-1 text-xs font-bold rounded-md bg-amber-100 text-amber-700">Sedang</span>
                                    <span v-else class="px-2 py-1 text-xs font-bold rounded-md bg-blue-100 text-blue-700">Rendah</span>
                                </td>
                                <td class="p-3 text-sm"><StatusBadge :status="task.status" /></td>
                                <td class="p-3 text-right">
                                    <Link :href="`/tickets/${task.id}`" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 bg-white border border-indigo-200 px-3 py-1 rounded-md hover:bg-indigo-50 transition-colors">Buka</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </AppLayout>
</template>

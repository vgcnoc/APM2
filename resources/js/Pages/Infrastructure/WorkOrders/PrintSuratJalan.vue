<template>
    <div class="min-h-screen bg-gray-100 py-6 px-4 sm:px-6 print:bg-white print:p-0">
        <!-- Top Action Bar (Hidden during Print) -->
        <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between print:hidden">
            <Link :href="route('infrastructure.work-orders.show', workOrder.id)"
                  class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900 bg-white px-4 py-2 rounded-xl shadow-sm border border-gray-200 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Detail Penugasan
            </Link>

            <div class="flex items-center gap-3">
                <button @click="triggerPrint"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-md hover:shadow-lg transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Cetak Surat Jalan (Ctrl+P)
                </button>
            </div>
        </div>

        <!-- Surat Jalan Sheet -->
        <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden print:shadow-none print:border-none print:rounded-none print:max-w-none print:m-0">
            <div class="p-8 sm:p-12 print:p-4 text-gray-800">
                <!-- Header / Kop Surat -->
                <div class="border-b-2 border-gray-900 pb-6 mb-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white font-black text-xl shadow">
                                    V
                                </div>
                                <div>
                                    <h1 class="text-2xl font-black tracking-tight text-gray-900 uppercase">VIRUZS NETWORK</h1>
                                    <p class="text-xs font-semibold tracking-wider text-gray-500 uppercase">Divisi Pembangunan Jaringan & Infrastruktur Fiber Optik</p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                Standard Operating Procedure: Rollout ODC & ODP Distribution System
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-3 py-1 bg-gray-900 text-white rounded font-mono font-black text-sm tracking-wider uppercase mb-1">
                                SURAT JALAN MATERIAL
                            </span>
                            <p class="text-lg font-black text-blue-600 font-mono">{{ workOrder.wo_number }}</p>
                            <p class="text-xs text-gray-500">Tgl: {{ formatDate(workOrder.created_at) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-6 p-4 bg-gray-50 rounded-xl border border-gray-200 mb-6 print:bg-white print:border-gray-900">
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Informasi Penugasan Rollout</h3>
                        <table class="w-full text-xs">
                            <tbody>
                                <tr>
                                    <td class="text-gray-500 py-1 w-28 font-medium">Tipe Deployment</td>
                                    <td class="font-bold py-1">
                                        <span v-if="workOrder.type === 'odc_deployment'" class="text-purple-700">Pemasangan ODC Baru</span>
                                        <span v-else class="text-emerald-700">Pemasangan ODP Baru</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-gray-500 py-1 font-medium">Target Perangkat</td>
                                    <td class="font-bold text-gray-900 py-1">{{ workOrder.target_name }} (Kapasitas {{ workOrder.capacity }} Core)</td>
                                </tr>
                                <tr>
                                    <td class="text-gray-500 py-1 font-medium">Wilayah / Area</td>
                                    <td class="font-bold text-gray-900 py-1">{{ workOrder.area?.name || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-gray-500 py-1 font-medium">Titik Uplink</td>
                                    <td class="font-bold text-gray-900 py-1">
                                        <span v-if="workOrder.type === 'odc_deployment'">OLT: {{ workOrder.olt?.name }} (PON {{ workOrder.pon_port }})</span>
                                        <span v-else>ODC: {{ workOrder.parent_odc?.name }} (Port {{ workOrder.odc_splitter_port }})</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-gray-500 py-1 font-medium">Titik Lokasi</td>
                                    <td class="font-medium text-gray-800 py-1">{{ workOrder.location || 'Sesuai koordinat rencana' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Personel & Jadwal Eksekusi</h3>
                        <table class="w-full text-xs">
                            <tbody>
                                <tr>
                                    <td class="text-gray-500 py-1 w-28 font-medium">Teknisi Bertugas</td>
                                    <td class="font-bold text-blue-700 py-1 text-sm">{{ workOrder.technician?.name || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-gray-500 py-1 font-medium">Jadwal Pasang</td>
                                    <td class="font-bold text-gray-900 py-1">{{ workOrder.scheduled_date }}</td>
                                </tr>
                                <tr>
                                    <td class="text-gray-500 py-1 font-medium">Dibuat Oleh</td>
                                    <td class="font-medium text-gray-800 py-1">{{ workOrder.creator?.name || 'NOC Engineering' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-gray-500 py-1 font-medium">Status Dokumen</td>
                                    <td class="font-bold py-1">
                                        <span v-if="workOrder.material_status === 'released'" class="text-emerald-600">Material Telah Dikeluarkan</span>
                                        <span v-else class="text-amber-600">Menunggu Serah Terima</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Table Material -->
                <div class="mb-6">
                    <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-2 flex items-center justify-between">
                        <span>Daftar Material Yang Dikeluarkan Dari Gudang (Bill of Materials)</span>
                        <span class="text-gray-500 font-normal">Total: {{ workOrder.materials?.length || 0 }} Item</span>
                    </h3>
                    
                    <div class="border border-gray-300 rounded-lg overflow-hidden print:border-gray-900">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-100 border-b border-gray-300 print:bg-gray-200 print:border-gray-900">
                                    <th class="py-2.5 px-3 font-bold text-gray-700 uppercase w-10 text-center">No</th>
                                    <th class="py-2.5 px-3 font-bold text-gray-700 uppercase">Nama Barang / Material</th>
                                    <th class="py-2.5 px-3 font-bold text-gray-700 uppercase">Kategori & Brand</th>
                                    <th class="py-2.5 px-3 font-bold text-gray-700 uppercase text-center w-24">Satuan</th>
                                    <th class="py-2.5 px-3 font-bold text-gray-700 uppercase text-right w-24">Qty Keluar</th>
                                    <th class="py-2.5 px-3 font-bold text-gray-700 uppercase text-center w-28">Cek Fisik</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 print:divide-gray-400">
                                <tr v-for="(item, idx) in workOrder.materials" :key="item.id">
                                    <td class="py-2.5 px-3 text-center font-bold text-gray-600">{{ idx + 1 }}</td>
                                    <td class="py-2.5 px-3">
                                        <p class="font-bold text-gray-900">{{ item.material?.name || 'Material Tidak Diketahui' }}</p>
                                    </td>
                                    <td class="py-2.5 px-3 text-gray-600">
                                        <span>{{ item.material?.category || '-' }}</span>
                                        <span v-if="item.material?.brand" class="text-gray-400"> ({{ item.material.brand }})</span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-medium">{{ item.unit || item.material?.unit || 'Pcs' }}</td>
                                    <td class="py-2.5 px-3 text-right font-black text-gray-900 text-sm">{{ item.quantity }}</td>
                                    <td class="py-2.5 px-3 text-center">
                                        <div class="w-5 h-5 mx-auto border-2 border-gray-400 rounded"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Petunjuk Lapangan & QC OPM -->
                <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-900 mb-8 print:bg-white print:border-gray-900 print:text-black">
                    <p class="font-bold mb-1">⚠️ INSTRUKSI KESELAMATAN & KUALITAS TEKNISI LAPANGAN:</p>
                    <ol class="list-decimal pl-4 space-y-0.5 text-xs">
                        <li>Catat angka meteran kabel awal (Start Point) dan akhir (End Point) pada kabel drop/feeder yang ditarik.</li>
                        <li>Sisa potongan material (terutama sisa kabel spanduk > 20m & splitter cadangan) <strong>WAJIB dikembalikan</strong> ke gudang saat rekonsiliasi.</li>
                        <li>Wajib ukur nilai redaman OPM di seluruh port keluaran ODC / ODP:
                            <span class="font-bold">ODC (-18 s/d -22 dBm)</span>, 
                            <span class="font-bold">ODP (-20 s/d -24 dBm)</span>. Foto display OPM bersama serial number perangkat.
                        </li>
                    </ol>
                </div>

                <!-- Kolom Tanda Tangan 3 Pihak -->
                <div class="grid grid-cols-3 gap-6 pt-4 text-center text-xs">
                    <div>
                        <p class="text-gray-600 mb-16 font-medium">Petugas Gudang (Menyerahkan),</p>
                        <p class="font-bold text-gray-900 uppercase border-b border-gray-900 inline-block px-6 pb-0.5">
                            ( ........................................ )
                        </p>
                        <p class="text-[10px] text-gray-500 mt-1">Gudang Logistik & Asset</p>
                    </div>

                    <div>
                        <p class="text-gray-600 mb-16 font-medium">Teknisi Lapangan (Menerima),</p>
                        <p class="font-bold text-gray-900 uppercase border-b border-gray-900 inline-block px-6 pb-0.5">
                            {{ workOrder.technician?.name || '( ........................................ )' }}
                        </p>
                        <p class="text-[10px] text-gray-500 mt-1">Tim Rollout Eksekusi</p>
                    </div>

                    <div>
                        <p class="text-gray-600 mb-16 font-medium">NOC / Pengawas (Memverifikasi),</p>
                        <p class="font-bold text-gray-900 uppercase border-b border-gray-900 inline-block px-6 pb-0.5">
                            {{ workOrder.creator?.name || '( ........................................ )' }}
                        </p>
                        <p class="text-[10px] text-gray-500 mt-1">Network Operation Center</p>
                    </div>
                </div>

                <!-- Footer Document -->
                <div class="mt-8 pt-4 border-t border-gray-200 flex justify-between text-[10px] text-gray-400 print:border-gray-900">
                    <span>Dokumen dicetak secara digital oleh sistem APM 2.0</span>
                    <span>Waktu Cetak: {{ currentDateTime() }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    workOrder: {
        type: Object,
        required: true,
    },
})

const triggerPrint = () => {
    window.print()
}

const formatDate = (dateString) => {
    if (!dateString) return '-'
    const d = new Date(dateString)
    return d.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

const currentDateTime = () => {
    return new Date().toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    })
}
</script>

<style>
@media print {
    body {
        background-color: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    @page {
        size: A4 portrait;
        margin: 10mm;
    }
}
</style>

<template>
    <div class="split-connection rounded-2xl border border-slate-200/90 bg-gradient-to-br from-white via-slate-50/50 to-indigo-50/20 p-4 md:p-5 shadow-xs transition-all">
        <!-- Top Heading & Metrics -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-200/70">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                        Topologi FTTH • Split {{ kind }} 1:2
                    </span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                        ⚡ Jalur Pasif
                    </span>
                </div>
                <h3 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <span>Hubungan Distribusi Induk</span>
                    <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    <span>Lanjutan</span>
                </h3>
            </div>

            <!-- Summary Slot Metrics -->
            <div class="flex items-center gap-2 flex-wrap text-xs">
                <slot />
            </div>
        </div>

        <!-- Node Pair Cards with Fiber Optic Link Indicator -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 relative">
            <div
                v-for="(node, nIdx) in nodes"
                :key="node.id"
                class="split-node rounded-xl p-3.5 md:p-4 border-2 transition-all flex items-start gap-3 relative overflow-hidden group shadow-xs hover:shadow-md"
                :class="[
                    node.role === 'induk'
                        ? 'bg-gradient-to-br from-emerald-50/90 via-teal-50/40 to-white border-emerald-300 hover:border-emerald-500'
                        : 'bg-gradient-to-br from-purple-50/90 via-fuchsia-50/40 to-white border-purple-300 hover:border-purple-500'
                ]"
            >
                <!-- Glowing corner accent -->
                <div
                    class="absolute -right-6 -bottom-6 w-20 h-20 rounded-full opacity-20 pointer-events-none group-hover:scale-125 transition-transform duration-500"
                    :class="node.role === 'induk' ? 'bg-emerald-500' : 'bg-purple-500'"
                ></div>

                <!-- Status Icon Dot -->
                <div
                    class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-xs border"
                    :class="[
                        node.role === 'induk'
                            ? 'bg-emerald-100/80 border-emerald-300 text-emerald-700'
                            : 'bg-purple-100/80 border-purple-300 text-purple-700'
                    ]"
                >
                    <svg v-if="node.role === 'induk'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>

                <!-- Node Identity Details -->
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2 flex-wrap mb-1">
                        <span
                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider shadow-2xs"
                            :class="[
                                node.role === 'induk'
                                    ? 'bg-emerald-600 text-white'
                                    : 'bg-purple-600 text-white'
                            ]"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                            {{ node.role === 'induk' ? `Unit Induk (Master ${kind})` : `Unit Lanjutan (Extension ${kind})` }}
                        </span>

                        <span
                            class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-md border"
                            :class="[
                                node.role === 'induk'
                                    ? 'bg-emerald-100/60 text-emerald-800 border-emerald-200'
                                    : 'bg-purple-100/60 text-purple-800 border-purple-200'
                            ]"
                        >
                            {{ node.role === 'induk' ? 'PORT 1 ➔ INDUK' : 'PORT 2 ➔ LANJUTAN' }}
                        </span>
                    </div>

                    <h4 class="text-slate-900 font-extrabold text-base leading-tight break-words group-hover:text-indigo-600 transition-colors">
                        {{ node.name }}
                    </h4>

                    <div class="flex items-center gap-2 mt-1.5 text-xs">
                        <span
                            class="font-semibold flex items-center gap-1"
                            :class="node.role === 'induk' ? 'text-emerald-800' : 'text-purple-800'"
                        >
                            <svg class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            {{ node.detail }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    kind: { type: String, required: true },
    nodes: { type: Array, required: true },
});
</script>

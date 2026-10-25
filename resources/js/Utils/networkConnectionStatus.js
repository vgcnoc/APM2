const states = {
    online: { label: 'Online', color: '#10b981', badge: 'bg-emerald-100 text-emerald-800' },
    offline: { label: 'Offline', color: '#f43f5e', badge: 'bg-rose-100 text-rose-800' },
    unknown: { label: 'Belum diketahui', color: '#94a3b8', badge: 'bg-slate-100 text-slate-700' },
};

export function connectionPresentation(row, now = Date.now()) {
    const checked = Date.parse(row?.connection_checked_at);
    const fresh = Number.isFinite(checked) && now - checked <= 90_000 && checked - now <= 30_000;
    const status = fresh && ['online', 'offline'].includes(row?.connection_status) ? row.connection_status : 'unknown';
    return {
        ...states[status], status,
        message: status === 'unknown' && row?.connection_status && row.connection_status !== 'unknown'
            ? 'Status terakhir sudah kedaluwarsa. Menunggu pemeriksaan baru.'
            : row?.connection_message || 'Menunggu pemeriksaan koneksi pelanggan.',
        source: row?.connection_source === 'routeros' ? 'API MikroTik' : row?.connection_source === 'radius' ? 'RADIUS' : '—',
        checkedAt: Number.isFinite(checked) ? new Date(checked).toLocaleTimeString('id-ID') : '—',
    };
}

export const affiliateSections = [
    { id: 'overview', label: 'Dashboard Sales', group: 'workspace', icon: 'overview' },
    { id: 'booking', label: 'Booking Baru', group: 'workspace', icon: 'booking' },
    { id: 'customers', label: 'Pelanggan Saya', group: 'workspace', icon: 'customers' },
    { id: 'downlines', label: 'Afiliasi & Downline', group: 'workspace', icon: 'downlines' },
    { id: 'commissions', label: 'Riwayat Komisi', group: 'finance', icon: 'commissions' },
    { id: 'withdrawals', label: 'Pencairan Dana', group: 'finance', icon: 'withdrawals' },
    { id: 'bank', label: 'Rekening & E-Wallet', group: 'finance', icon: 'bank' },
];

export function affiliateHref(section) {
    if (section === 'booking') return '/affiliate/booking/create';
    return section === 'overview' ? '/affiliate/dashboard' : `/affiliate/dashboard?tab=${section}`;
}

export function affiliateSection(url = '', tab) {
    if (url.split('?')[0].startsWith('/affiliate/booking')) return 'booking';
    const requested = tab || new URL(url, 'https://portal.invalid').searchParams.get('tab');
    return affiliateSections.some(item => item.id !== 'booking' && item.id === requested) ? requested : 'overview';
}

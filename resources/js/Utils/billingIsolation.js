// Calendar dates are interpreted in the billing calendar; the configured time is WIB.
export function billingIsolationDate(issued, settings) {
    const date = new Date(issued.getFullYear(), issued.getMonth(), issued.getDate());
    const day = Number(settings.isolate_day);
    if (settings.isolate_day != null && settings.isolate_day !== '' && day >= 1 && day <= 31) {
        let month = date.getMonth();
        let year = date.getFullYear();
        let target = new Date(year, month, Math.min(day, new Date(year, month + 1, 0).getDate()));
        if (target < date) {
            month++;
            target = new Date(year, month, Math.min(day, new Date(year, month + 1, 0).getDate()));
        }
        date.setTime(target.getTime());
    } else {
        date.setDate(date.getDate() + Math.max(0, Number(settings.isolate_days ?? 3)));
    }
    const [hour, minute] = /^([01]\d|2[0-3]):([0-5]\d)$/.test(settings.isolate_time || '') ? settings.isolate_time.split(':').map(Number) : [0, 0];
    date.setHours(hour, minute, 0, 0);
    return date;
}

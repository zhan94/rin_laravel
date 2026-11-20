export function formatEur(amount: number, localeTag = 'en-GB'): string {
    return new Intl.NumberFormat(localeTag, {
        style: 'currency',
        currency: 'EUR',
    }).format(Number.isFinite(amount) ? amount : 0);
}

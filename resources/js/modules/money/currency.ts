export const BGN_PER_EUR = 1.95583;

export function bgnToEur(amount: number | null): number | null {
    if (amount === null) {
        return null;
    }

    return roundMoney(amount / BGN_PER_EUR);
}

export function eurToBgn(amount: number | null): number | null {
    if (amount === null) {
        return null;
    }

    return roundMoney(amount * BGN_PER_EUR);
}

export function formatEurBgnPairFromBgn(
    bgnValue: number,
    localeTag?: string,
): string {
    const safeBgn = Number.isFinite(bgnValue) ? bgnValue : 0;
    const eur = bgnToEur(safeBgn) ?? 0;

    return `${formatMoneyNumber(eur, localeTag)}€ (${formatMoneyNumber(safeBgn, localeTag)} BGN)`;
}

export function formatEurBgnPairFromEur(
    eurValue: number,
    localeTag?: string,
): string {
    const safeEur = Number.isFinite(eurValue) ? eurValue : 0;
    const bgn = eurToBgn(safeEur) ?? 0;

    return `${formatMoneyNumber(safeEur, localeTag)}€ (${formatMoneyNumber(bgn, localeTag)} BGN)`;
}

function roundMoney(amount: number): number {
    return Math.round(amount * 100) / 100;
}

function formatMoneyNumber(amount: number, localeTag?: string): string {
    if (localeTag === undefined) {
        return amount.toFixed(2);
    }

    return new Intl.NumberFormat(localeTag, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}

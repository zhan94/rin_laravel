import * as Sentry from '@sentry/vue';
import type { App as VueApp } from 'vue';

export function initSentry(app: VueApp): void {
    const dsn = import.meta.env.VITE_SENTRY_DSN;
    if (!dsn) {
        return;
    }

    const tracesSampleRate = Number(
        import.meta.env.VITE_SENTRY_TRACES_SAMPLE_RATE ?? 0.1,
    );

    Sentry.init({
        app,
        dsn,
        environment: import.meta.env.MODE,
        release: import.meta.env.VITE_SENTRY_RELEASE,
        tracesSampleRate: Number.isFinite(tracesSampleRate)
            ? tracesSampleRate
            : 0.1,
    });
}

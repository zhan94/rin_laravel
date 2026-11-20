<script setup lang="ts">
import { Spinner } from '@/components/ui/spinner';
import { useLoginPasskey } from '@/pages/auth/useLoginPasskey';
import { home } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const {
    t,
    passkeySupported,
    passkeyProcessing,
    passkeyError,
    passkeySupportMessage,
    loginAlertClass,
    loginAlertMessage,
    loginWithPasskey,
} = useLoginPasskey(props.status);
</script>

<template>
    <Head :title="t('auth.login_head')" />

    <div class="login-ui">
        <div class="login-shell">
            <div class="login-card">
                <section class="login-visual">
                    <div class="login-brand">
                        <Link
                            :href="home().url"
                            class="login-brand-link"
                            :aria-label="t('auth.open_home')"
                        >
                            <img src="/img/big.jpeg" alt="RIN Sports" />
                        </Link>
                    </div>

                    <div class="login-visual-copy">
                        <h2>{{ t('auth.welcome_back') }}</h2>
                        <p>{{ t('auth.login_subline') }}</p>
                    </div>

                    <ul class="login-highlights">
                        <li>{{ t('auth.highlight_fast') }}</li>
                        <li>{{ t('auth.highlight_history') }}</li>
                        <li>{{ t('auth.highlight_search') }}</li>
                    </ul>
                </section>

                <section class="login-form">
                    <div class="login-header">
                        <div class="eyebrow">{{ t('auth.sign_in') }}</div>
                        <h1>{{ t('auth.access_system') }}</h1>
                    </div>

                    <Form
                        v-bind="store.form()"
                        :reset-on-success="['password']"
                        v-slot="{ errors, processing }"
                        class="login-form-shell"
                    >
                        <div :class="['login-alert', loginAlertClass(errors)]">
                            {{ loginAlertMessage(errors) }}
                        </div>

                        <div class="login-form-grid">
                            <div class="field">
                                <label for="identifier">{{
                                    t('auth.identifier_label')
                                }}</label>
                                <input
                                    id="identifier"
                                    type="text"
                                    name="email"
                                    class="form-control"
                                    required
                                    autofocus
                                    :tabindex="1"
                                    autocomplete="username"
                                    :placeholder="
                                        t('auth.identifier_placeholder')
                                    "
                                />
                            </div>

                            <div class="field">
                                <label for="password">{{
                                    t('auth.password_label')
                                }}</label>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    required
                                    :tabindex="2"
                                    autocomplete="current-password"
                                    :placeholder="
                                        t('auth.password_placeholder')
                                    "
                                />

                                <div
                                    v-if="canResetPassword"
                                    class="login-inline-link"
                                >
                                    <Link :href="request().url">{{
                                        t('auth.forgot_password')
                                    }}</Link>
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-solid"
                                :tabindex="3"
                                :disabled="processing || passkeyProcessing"
                                data-test="login-button"
                            >
                                <Spinner
                                    v-if="processing"
                                    class="login-spinner"
                                />
                                <span>{{
                                    processing
                                        ? t('auth.logging_in')
                                        : t('auth.log_in')
                                }}</span>
                            </button>

                            <button
                                type="button"
                                class="btn btn-ghost login-passkey-btn"
                                :tabindex="4"
                                :disabled="
                                    processing ||
                                    passkeyProcessing ||
                                    !passkeySupported
                                "
                                @click="loginWithPasskey"
                            >
                                <Spinner
                                    v-if="passkeyProcessing"
                                    class="login-spinner"
                                />
                                <span>{{
                                    passkeyProcessing
                                        ? t('auth.passkey_preparing')
                                        : t('auth.passkey_button')
                                }}</span>
                            </button>

                            <div class="login-passkey-note">
                                {{
                                    passkeySupported
                                        ? t('auth.passkey_note')
                                        : passkeySupportMessage
                                }}
                            </div>

                            <div
                                v-if="passkeyError"
                                class="login-passkey-feedback is-error"
                            >
                                {{ passkeyError }}
                            </div>
                        </div>
                    </Form>
                </section>
            </div>
        </div>
    </div>
</template>

<style scoped>
.login-ui {
    --ink: #171923;
    --surface: rgba(9, 17, 35, 0.3);
    --glass: rgba(255, 255, 255, 0.9);
    --glass-stroke: rgba(255, 255, 255, 0.5);
    --stroke: rgba(15, 23, 42, 0.12);
    --accent: #ff6a3d;
    --accent-2: #2f80ed;
    --success: #1f9d66;
    --muted: #5b6170;
    color: var(--ink);
    min-height: 100vh;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 100px 18px 48px;
    position: relative;
    overflow: hidden;
    z-index: 0;
    background:
        linear-gradient(180deg, rgba(12, 17, 29, 0.08), rgba(12, 17, 29, 0.16)),
        url('/img/bg.jpeg') center / cover no-repeat;
    font-family: 'Space Grotesk', 'IBM Plex Sans', 'Segoe UI', sans-serif;
}

.login-ui::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(
            circle at 15% 20%,
            rgba(255, 106, 61, 0.35),
            transparent 55%
        ),
        radial-gradient(
            circle at 80% 0%,
            rgba(47, 128, 237, 0.35),
            transparent 50%
        ),
        radial-gradient(
            circle at 70% 80%,
            rgba(244, 169, 64, 0.28),
            transparent 60%
        );
    z-index: -2;
}

.login-ui::after {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(
            circle at 18% 16%,
            rgba(255, 106, 61, 0.22),
            transparent 42%
        ),
        radial-gradient(
            circle at 86% 6%,
            rgba(47, 128, 237, 0.2),
            transparent 46%
        ),
        linear-gradient(
            120deg,
            rgba(255, 255, 255, 0.1),
            rgba(255, 255, 255, 0)
        );
    pointer-events: none;
    z-index: -1;
}

.login-ui h1,
.login-ui h2 {
    margin: 0;
    font-family: 'Fraunces', 'Times New Roman', serif;
    letter-spacing: 0.2px;
}

.login-shell {
    width: min(720px, 100%);
}

.login-card {
    display: grid;
    grid-template-columns: 1fr;
    background: var(--surface);
    border-radius: 28px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.26);
    box-shadow: 0 36px 72px rgba(0, 0, 0, 0.35);
    -webkit-backdrop-filter: blur(8px) saturate(1.1);
    backdrop-filter: blur(8px) saturate(1.1);
}

.login-visual {
    padding: 30px;
    background: linear-gradient(
        145deg,
        rgba(17, 24, 39, 0.88),
        rgba(31, 41, 55, 0.76)
    );
    color: #fff;
    display: flex;
    flex-direction: column;
    gap: 20px;
    position: relative;
    overflow: hidden;
    text-align: center;
}

.login-visual::before {
    content: '';
    position: absolute;
    width: 240px;
    height: 240px;
    top: -120px;
    right: -120px;
    background: radial-gradient(
        circle,
        rgba(255, 106, 61, 0.6),
        transparent 70%
    );
    opacity: 0.85;
}

.login-brand {
    display: flex;
    align-items: center;
    gap: 14px;
    justify-content: center;
}

.login-brand-link {
    display: inline-flex;
}

.login-brand img {
    height: 240px;
    width: auto;
    max-width: 420px;
    border-radius: 18px;
    object-fit: contain;
    background: rgba(255, 255, 255, 0.12);
    padding: 6px;
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.35);
}

.login-visual-copy h2 {
    font-size: clamp(2.9rem, 5vw, 4rem);
    line-height: 1.06;
}

.login-visual-copy p {
    margin: 8px 0 0;
    opacity: 0.75;
    font-size: 1rem;
    line-height: 1.5;
}

.login-highlights {
    list-style: none;
    padding: 0;
    margin-left: 30%;
    display: grid;
    gap: 10px;
    font-size: 13px;
    opacity: 0.85;
    width: 100%;
    max-width: 360px;
    text-align: left;
}

.login-highlights li {
    display: grid;
    grid-template-columns: 16px 1fr;
    align-items: center;
    gap: 8px;
}

.login-highlights li::before {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 50%;
    justify-self: center;
    background: var(--accent);
    box-shadow: 0 0 0 4px rgba(255, 106, 61, 0.2);
}

.login-form {
    padding: 34px 32px;
    background: var(--glass);
    border-top: 1px solid var(--glass-stroke);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    -webkit-backdrop-filter: blur(14px) saturate(1.2);
    backdrop-filter: blur(14px) saturate(1.2);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4);
}

.login-header {
    width: 100%;
    max-width: 420px;
    margin: 0 auto;
    padding-top: 0;
    height: auto;
}

.login-header .eyebrow {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: var(--muted);
    margin-bottom: 6px;
}

.login-header h1 {
    font-size: clamp(2.75rem, 5vw, 3.9rem);
    line-height: 1.05;
}

.login-form-shell {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.login-alert {
    margin: 18px 0 20px;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 13px;
    border: 1px solid transparent;
    width: 100%;
    max-width: 420px;
}

.login-alert--info {
    background: rgba(47, 128, 237, 0.12);
    color: var(--accent-2);
    border-color: rgba(47, 128, 237, 0.2);
}

.login-alert--danger {
    background: rgba(224, 86, 74, 0.12);
    color: #e0564a;
    border-color: rgba(224, 86, 74, 0.2);
}

.login-alert--success {
    background: rgba(31, 157, 102, 0.12);
    color: var(--success);
    border-color: rgba(31, 157, 102, 0.2);
}

.login-form-grid {
    display: grid;
    gap: 14px;
    max-width: 420px;
    width: 100%;
    margin: 0 auto;
}

.login-form-grid .field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.login-form-grid label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    color: var(--muted);
}

.login-ui .form-control {
    border-radius: 14px;
    border: 1px solid var(--stroke);
    box-shadow: none;
    padding: 12px 14px;
    background: #fff;
    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease;
}

.login-ui .form-control:focus {
    border-color: rgba(47, 128, 237, 0.7);
    box-shadow: 0 0 0 3px rgba(47, 128, 237, 0.18);
}

.login-inline-link {
    margin-top: 6px;
    text-align: right;
}

.login-inline-link :deep(a),
.login-secondary-link :deep(a) {
    font-size: 12px;
    color: var(--accent-2);
    text-decoration: none;
}

.login-inline-link :deep(a:hover),
.login-secondary-link :deep(a:hover) {
    text-decoration: underline;
}

.login-ui .btn {
    border-radius: 999px;
    padding: 12px 18px;
    font-weight: 600;
    border: 1px solid transparent;
}

.login-ui .btn-solid {
    color: #fff;
    background: linear-gradient(135deg, var(--accent), #ff9a3d);
}

.login-ui .btn-solid:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(255, 106, 61, 0.3);
}

.login-ui .btn-ghost.login-passkey-btn {
    background: rgba(47, 128, 237, 0.08);
    color: var(--accent-2);
    border-color: rgba(47, 128, 237, 0.2);
}

.login-ui .btn-ghost.login-passkey-btn:hover {
    background: rgba(47, 128, 237, 0.14);
}

.login-spinner {
    width: 18px;
    height: 18px;
}

.login-passkey-note {
    font-size: 12px;
    color: var(--muted);
    text-align: center;
}

.login-passkey-feedback {
    margin-top: 2px;
    padding: 10px 12px;
    border-radius: 12px;
    font-size: 13px;
    border: 1px solid transparent;
}

.login-passkey-feedback.is-info {
    background: rgba(47, 128, 237, 0.12);
    color: var(--accent-2);
    border-color: rgba(47, 128, 237, 0.2);
}

.login-passkey-feedback.is-success {
    background: rgba(31, 157, 102, 0.12);
    color: var(--success);
    border-color: rgba(31, 157, 102, 0.2);
}

.login-passkey-feedback.is-error {
    background: rgba(224, 86, 74, 0.12);
    color: #e0564a;
    border-color: rgba(224, 86, 74, 0.2);
}

.login-secondary-link {
    margin-top: 16px;
    text-align: center;
    color: var(--muted);
    font-size: 12px;
}

@media (max-width: 900px) {
    .login-ui {
        padding-top: env(safe-area-inset-top, 0px);
    }

    .login-visual {
        padding: 24px;
    }

    .login-form {
        padding-top: 0;
    }
}

@media (max-width: 720px) {
    .login-ui {
        padding: 84px 12px 24px;
    }

    .login-card {
        border-radius: 24px;
    }

    .login-visual,
    .login-form {
        padding: 24px 18px;
    }

    .login-brand img {
        height: auto;
        width: min(100%, 420px);
    }

    .login-highlights {
        margin-left: 0;
        width: 100%;
    }

    .login-header h1,
    .login-visual-copy h2 {
        font-size: 2.35rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .login-ui .btn-solid:hover {
        transform: none;
        box-shadow: none;
    }
}
</style>

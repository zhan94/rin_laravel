import { useUiI18n } from '@/composables/useUiI18n';
import {
    detectPasskeySupport,
    normalizeRequestOptions,
    postPasskeyForm,
    resolvePasskeyErrorMessage,
    serializeAssertion,
} from '@/modules/passkeys/passkeyClient';
import { onMounted, ref } from 'vue';

export function useLoginPasskey(status: string | undefined) {
    const passkeySupported = ref(false);
    const passkeyProcessing = ref(false);
    const passkeyError = ref<string | null>(null);
    const { t } = useUiI18n();
    const passkeySupportMessage = ref(t('auth.passkey.unsupported_browser'));

    onMounted((): void => {
        const support = detectPasskeySupport(t, 'get', t('auth.passkey_note'));

        passkeySupported.value = support.supported;
        passkeySupportMessage.value = support.message;
    });

    function normalizeFormError(value: unknown): string | null {
        if (typeof value === 'string' && value.trim() !== '') {
            return value;
        }

        if (
            Array.isArray(value) &&
            typeof value[0] === 'string' &&
            value[0].trim() !== ''
        ) {
            return value[0];
        }

        return null;
    }

    function loginAlertClass(errors: Record<string, unknown>): string {
        return normalizeFormError(errors.email) !== null ||
            normalizeFormError(errors.password) !== null
            ? 'login-alert--danger'
            : status
              ? 'login-alert--success'
              : 'login-alert--info';
    }

    function loginAlertMessage(errors: Record<string, unknown>): string {
        return (
            normalizeFormError(errors.email) ??
            normalizeFormError(errors.password) ??
            status ??
            t('auth.alert_default')
        );
    }

    async function loginWithPasskey(): Promise<void> {
        passkeyError.value = null;

        if (!passkeySupported.value) {
            passkeyError.value = passkeySupportMessage.value;

            return;
        }

        const identifierInput =
            document.querySelector<HTMLInputElement>('#identifier');
        const identifier = identifierInput?.value.trim() ?? '';

        if (identifier === '') {
            passkeyError.value = t('auth.passkey.enter_identifier');
            identifierInput?.focus();

            return;
        }

        passkeyProcessing.value = true;

        try {
            const optionsResponse = await postPasskeyForm(
                t,
                '/passkeys/login/options',
                {
                    username: identifier,
                    email: identifier,
                },
            );

            if (
                !optionsResponse.ok ||
                typeof optionsResponse.json.publicKey !== 'object' ||
                optionsResponse.json.publicKey === null
            ) {
                throw new Error(
                    String(
                        optionsResponse.json.error ??
                            t('auth.passkey.login_failed'),
                    ),
                );
            }

            const publicKey = normalizeRequestOptions(
                optionsResponse.json.publicKey as Record<string, unknown>,
            );
            const credential = await window.navigator.credentials.get({
                publicKey,
            });

            if (!credential || credential.type !== 'public-key') {
                throw new Error(t('auth.passkey.login_failed'));
            }

            const verifyResponse = await postPasskeyForm(
                t,
                '/passkeys/login/verify',
                serializeAssertion(credential as PublicKeyCredential),
            );

            if (!verifyResponse.ok) {
                throw new Error(
                    String(
                        verifyResponse.json.error ??
                            t('auth.passkey.login_failed'),
                    ),
                );
            }

            const redirect =
                typeof verifyResponse.json.redirect === 'string' &&
                verifyResponse.json.redirect !== ''
                    ? verifyResponse.json.redirect
                    : '/dashboard';

            window.location.assign(redirect);
        } catch (error) {
            passkeyError.value = resolvePasskeyErrorMessage(
                t,
                error,
                t('auth.passkey.login_failed'),
                t('auth.passkey.login_cancelled'),
            );
        } finally {
            passkeyProcessing.value = false;
        }
    }

    return {
        t,
        passkeySupported,
        passkeyProcessing,
        passkeyError,
        passkeySupportMessage,
        loginAlertClass,
        loginAlertMessage,
        loginWithPasskey,
    };
}

import { getInitials } from '@/composables/useInitials';
import { usePageAuth } from '@/composables/usePageAuth';
import { useUiI18n } from '@/composables/useUiI18n';
import {
    detectPasskeySupport,
    normalizeRegistrationOptions,
    postPasskeyForm,
    resolvePasskeyErrorMessage,
    serializeAttestation,
} from '@/modules/passkeys/passkeyClient';
import { update as updateProfile } from '@/routes/profile';
import { edit as editPassword } from '@/routes/user-password';
import { send } from '@/routes/verification';
import { formatPersonName } from '@/utils/personName';
import { router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, reactive } from 'vue';

interface PasskeyRow {
    id: number;
    device_name: string;
    created_at: string | null;
    last_used_at: string | null;
}

type FeedbackTone = 'info' | 'success' | 'error';

export function useProfilePage() {
    const { t } = useUiI18n();
    const auth = usePageAuth();
    const user = computed(() => auth.value.user);

    const profileForm = useForm({
        username: user.value?.username ?? '',
        firstname: user.value?.firstname ?? '',
        surname: user.value?.surname ?? '',
        lastname: user.value?.lastname ?? '',
        email: user.value?.email ?? '',
    });

    const formFeedback = reactive({
        message: '',
        tone: 'info' as FeedbackTone,
    });

    const passkeys = reactive({
        supported: false,
        supportMessage: t('auth.passkey.unsupported_browser'),
        loading: false,
        adding: false,
        removingId: 0,
        deviceName: '',
        rows: [] as PasskeyRow[],
        feedback: '',
        feedbackTone: 'info' as FeedbackTone,
    });

    const displayName = computed(() =>
        formatPersonName(user.value, t('roles.user')),
    );

    const avatarText = computed(() => {
        const label = formatPersonName(user.value);

        if (label !== '') {
            return getInitials(label);
        }

        return (user.value?.username ?? 'U').slice(0, 2).toUpperCase();
    });

    const roleLabel = computed(() => {
        switch (user.value?.role_key ?? '') {
            case 'super_admin':
            case 'admin':
                return t('roles.administrator');
            case 'manager':
                return t('roles.manager');
            case 'group_leader':
                return t('roles.group_leader');
            case 'driver':
                return t('roles.driver');
            default:
                return t('roles.user');
        }
    });

    function setFormFeedback(
        message: string,
        tone: FeedbackTone = 'info',
    ): void {
        formFeedback.message = message;
        formFeedback.tone = tone;
    }

    function setPasskeyFeedback(
        message: string,
        tone: FeedbackTone = 'info',
    ): void {
        passkeys.feedback = message;
        passkeys.feedbackTone = tone;
    }

    function resolveProfilePasskeyError(
        error: unknown,
        fallback: string,
    ): string {
        return resolvePasskeyErrorMessage(
            t,
            error,
            fallback,
            t('profile.passkey_registration_cancelled'),
        );
    }

    async function loadPasskeys(): Promise<void> {
        passkeys.loading = true;

        try {
            const response = await postPasskeyForm(t, '/passkeys/list', {});

            if (!response.ok || !Array.isArray(response.json.rows)) {
                throw new Error(
                    String(
                        response.json.error ??
                            t('profile.passkeys_load_failed'),
                    ),
                );
            }

            passkeys.rows = response.json.rows as PasskeyRow[];

            if (passkeys.rows.length === 0) {
                setPasskeyFeedback(t('profile.passkeys_none_yet'), 'info');
            } else {
                setPasskeyFeedback('', 'info');
            }
        } catch (error) {
            passkeys.rows = [];
            setPasskeyFeedback(
                resolveProfilePasskeyError(
                    error,
                    t('profile.passkeys_load_failed'),
                ),
                'error',
            );
        } finally {
            passkeys.loading = false;
        }
    }

    async function addPasskey(): Promise<void> {
        if (!passkeys.supported) {
            setPasskeyFeedback(passkeys.supportMessage, 'error');

            return;
        }

        passkeys.adding = true;
        setPasskeyFeedback('', 'info');

        try {
            const optionsResponse = await postPasskeyForm(
                t,
                '/passkeys/register/options',
                {},
            );

            if (optionsResponse.status === 423) {
                router.visit('/settings/passkeys/confirm');
                return;
            }

            if (
                !optionsResponse.ok ||
                typeof optionsResponse.json.publicKey !== 'object' ||
                optionsResponse.json.publicKey === null
            ) {
                throw new Error(
                    String(
                        optionsResponse.json.error ??
                            t('profile.passkey_prepare_failed'),
                    ),
                );
            }

            const publicKey = normalizeRegistrationOptions(
                optionsResponse.json.publicKey as Record<string, unknown>,
            );
            const credential = await window.navigator.credentials.create({
                publicKey,
            });

            if (!(credential instanceof PublicKeyCredential)) {
                throw new Error(t('profile.passkey_registration_failed'));
            }

            const verifyResponse = await postPasskeyForm(
                t,
                '/passkeys/register/verify',
                serializeAttestation(credential, passkeys.deviceName.trim()),
            );

            if (!verifyResponse.ok) {
                throw new Error(
                    String(
                        verifyResponse.json.error ??
                            t('profile.passkey_registration_failed'),
                    ),
                );
            }

            passkeys.deviceName = '';
            await loadPasskeys();
            setPasskeyFeedback(
                String(
                    verifyResponse.json.message ?? t('profile.passkey_added'),
                ),
                'success',
            );
        } catch (error) {
            setPasskeyFeedback(
                resolveProfilePasskeyError(
                    error,
                    t('profile.passkey_registration_failed'),
                ),
                'error',
            );
        } finally {
            passkeys.adding = false;
        }
    }

    async function removePasskey(id: number): Promise<void> {
        if (!window.confirm(t('profile.passkey_remove_confirm'))) {
            return;
        }

        passkeys.removingId = id;
        setPasskeyFeedback('', 'info');

        try {
            const response = await postPasskeyForm(t, '/passkeys/delete', {
                id: String(id),
            });

            if (response.status === 423) {
                router.visit('/settings/passkeys/confirm');
                return;
            }

            if (!response.ok) {
                throw new Error(
                    String(
                        response.json.error ??
                            t('profile.passkey_remove_failed'),
                    ),
                );
            }

            await loadPasskeys();
            setPasskeyFeedback(
                String(response.json.message ?? t('profile.passkey_removed')),
                'success',
            );
        } catch (error) {
            setPasskeyFeedback(
                resolveProfilePasskeyError(
                    error,
                    t('profile.passkey_remove_failed'),
                ),
                'error',
            );
        } finally {
            passkeys.removingId = 0;
        }
    }

    function submitProfile(): void {
        setFormFeedback('', 'info');

        profileForm.patch(updateProfile.url(), {
            preserveScroll: true,
            onSuccess: () => {
                setFormFeedback(t('profile.form_saved'), 'success');
            },
            onError: () => {
                setFormFeedback(t('profile.form_save_failed'), 'error');
            },
        });
    }

    onMounted(() => {
        const support = detectPasskeySupport(
            t,
            'create',
            t('profile.passkey_supported_note'),
        );

        passkeys.supported = support.supported;
        passkeys.supportMessage = support.message;
        void loadPasskeys();
    });

    return {
        addPasskey,
        avatarText,
        displayName,
        editPassword,
        formFeedback,
        passkeys,
        profileForm,
        removePasskey,
        roleLabel,
        send,
        submitProfile,
        t,
        user,
    };
}

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useProfilePage } from './useProfilePage';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

defineProps<Props>();

const {
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
} = useProfilePage();
</script>

<template>
    <AppLayout>
        <Head :title="t('profile.head')" />

        <div
            class="dashboard customer-form customer-ui user-form user-form--self"
        >
            <div class="customer-shell">
                <div class="form-hero">
                    <div class="user-form-hero-main">
                        <Link href="/dashboard" class="btn btn-ghost">
                            <span class="glyphicon glyphicon-chevron-left" />
                            {{ t('profile.back_to_dashboard') }}
                        </Link>
                        <div class="eyebrow">{{ t('profile.eyebrow') }}</div>
                        <h1>{{ t('profile.title') }}</h1>
                        <div class="subline">{{ t('profile.subtitle') }}</div>
                    </div>

                    <div class="profile-hero-card">
                        <div class="profile-avatar" aria-hidden="true">
                            {{ avatarText }}
                        </div>
                        <div class="profile-hero-meta">
                            <strong>{{ displayName }}</strong>
                            <span class="profile-hero-username"
                                >@{{ user?.username }}</span
                            >
                            <span class="badge badge--teal">{{
                                roleLabel
                            }}</span>
                        </div>
                    </div>
                </div>

                <div
                    v-if="formFeedback.message"
                    :class="['passkeys-feedback', `is-${formFeedback.tone}`]"
                    style="margin-bottom: 16px"
                >
                    {{ formFeedback.message }}
                </div>

                <form
                    class="customer-form-grid"
                    @submit.prevent="submitProfile"
                >
                    <div class="form-layout">
                        <div class="form-card">
                            <div class="card-head">
                                <h3>{{ t('profile.personal_details') }}</h3>
                            </div>

                            <div class="form-grid">
                                <div class="field">
                                    <label for="firstname">{{
                                        t('profile.first_name')
                                    }}</label>
                                    <input
                                        id="firstname"
                                        v-model="profileForm.firstname"
                                        type="text"
                                        class="form-control"
                                        required
                                    />
                                    <div
                                        v-if="profileForm.errors.firstname"
                                        class="issues-help-text"
                                        style="color: #e0564a"
                                    >
                                        {{ profileForm.errors.firstname }}
                                    </div>
                                </div>

                                <div class="field">
                                    <label for="surname">{{
                                        t('profile.middle_name')
                                    }}</label>
                                    <input
                                        id="surname"
                                        v-model="profileForm.surname"
                                        type="text"
                                        class="form-control"
                                    />
                                    <div
                                        v-if="profileForm.errors.surname"
                                        class="issues-help-text"
                                        style="color: #e0564a"
                                    >
                                        {{ profileForm.errors.surname }}
                                    </div>
                                </div>

                                <div class="field">
                                    <label for="lastname">{{
                                        t('profile.last_name')
                                    }}</label>
                                    <input
                                        id="lastname"
                                        v-model="profileForm.lastname"
                                        type="text"
                                        class="form-control"
                                        required
                                    />
                                    <div
                                        v-if="profileForm.errors.lastname"
                                        class="issues-help-text"
                                        style="color: #e0564a"
                                    >
                                        {{ profileForm.errors.lastname }}
                                    </div>
                                </div>

                                <div class="field">
                                    <label for="username">{{
                                        t('profile.username')
                                    }}</label>
                                    <input
                                        id="username"
                                        v-model="profileForm.username"
                                        type="text"
                                        class="form-control"
                                        required
                                    />
                                    <div
                                        v-if="profileForm.errors.username"
                                        class="issues-help-text"
                                        style="color: #e0564a"
                                    >
                                        {{ profileForm.errors.username }}
                                    </div>
                                </div>

                                <div class="field">
                                    <label for="email">{{
                                        t('profile.email')
                                    }}</label>
                                    <input
                                        id="email"
                                        v-model="profileForm.email"
                                        type="email"
                                        class="form-control"
                                        required
                                    />
                                    <div
                                        v-if="profileForm.errors.email"
                                        class="issues-help-text"
                                        style="color: #e0564a"
                                    >
                                        {{ profileForm.errors.email }}
                                    </div>
                                    <div
                                        v-if="
                                            mustVerifyEmail &&
                                            !user?.email_verified_at
                                        "
                                        class="issues-help-text"
                                    >
                                        {{ t('profile.email_unverified') }}
                                        <Link
                                            :href="send()"
                                            as="button"
                                            style="text-decoration: underline"
                                            >{{
                                                t('profile.resend_verification')
                                            }}</Link
                                        >
                                    </div>
                                </div>

                                <div class="field field--readonly">
                                    <label>{{ t('profile.password') }}</label>
                                    <div class="readonly-value">
                                        <Link
                                            :href="editPassword()"
                                            style="
                                                color: inherit;
                                                text-decoration: none;
                                            "
                                            >{{
                                                t(
                                                    'profile.open_password_settings',
                                                )
                                            }}</Link
                                        >
                                    </div>
                                    <div class="issues-help-text">
                                        {{
                                            t('profile.password_settings_note')
                                        }}
                                    </div>
                                </div>

                                <div class="field field--readonly">
                                    <label>{{ t('profile.user_level') }}</label>
                                    <div class="readonly-value">
                                        {{ roleLabel }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-card passkeys-card">
                            <div class="card-head">
                                <h3>{{ t('profile.passkeys') }}</h3>
                                <p class="card-note">
                                    {{ t('profile.passkeys_note') }}
                                </p>
                            </div>

                            <div class="passkeys-panel">
                                <div class="passkeys-toolbar">
                                    <input
                                        v-model="passkeys.deviceName"
                                        type="text"
                                        class="form-control"
                                        maxlength="120"
                                        :placeholder="
                                            t(
                                                'profile.passkey_device_placeholder',
                                            )
                                        "
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-solid"
                                        :disabled="
                                            passkeys.adding ||
                                            !passkeys.supported
                                        "
                                        @click="addPasskey"
                                    >
                                        {{
                                            passkeys.adding
                                                ? t('profile.adding_passkey')
                                                : t('profile.add_passkey')
                                        }}
                                    </button>
                                </div>
                                <p class="passkeys-hint">
                                    {{ passkeys.supportMessage }}
                                </p>
                                <div
                                    v-if="passkeys.feedback"
                                    :class="[
                                        'passkeys-feedback',
                                        `is-${passkeys.feedbackTone}`,
                                    ]"
                                >
                                    {{ passkeys.feedback }}
                                </div>
                                <ul class="passkeys-list">
                                    <li
                                        v-if="passkeys.loading"
                                        class="passkeys-empty"
                                    >
                                        {{ t('profile.loading_passkeys') }}
                                    </li>
                                    <li
                                        v-else-if="passkeys.rows.length === 0"
                                        class="passkeys-empty"
                                    >
                                        {{ t('profile.no_passkeys') }}
                                    </li>
                                    <li
                                        v-for="row in passkeys.rows"
                                        :key="row.id"
                                        class="passkey-item"
                                    >
                                        <div class="passkey-item-body">
                                            <strong>{{
                                                row.device_name
                                            }}</strong>
                                            <div class="passkey-item-meta">
                                                {{ row.created_at ?? '-' }} ·
                                                {{ row.last_used_at ?? '-' }}
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="btn btn-ghost passkey-remove-btn"
                                            :disabled="
                                                passkeys.removingId === row.id
                                            "
                                            @click="removePasskey(row.id)"
                                        >
                                            {{
                                                passkeys.removingId === row.id
                                                    ? t('profile.removing')
                                                    : t('profile.remove')
                                            }}
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <Link href="/dashboard" class="btn btn-ghost">{{
                            t('common.cancel')
                        }}</Link>
                        <button
                            type="submit"
                            class="btn btn-solid"
                            :disabled="profileForm.processing"
                        >
                            {{
                                profileForm.processing
                                    ? t('profile.saving')
                                    : t('profile.save_profile')
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

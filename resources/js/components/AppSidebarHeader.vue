<script setup lang="ts">
import { useUiI18n } from '@/composables/useUiI18n';
import type { SupportedLocale } from '@/modules/localization';
import { dashboard, logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{ menuOpen: boolean }>();
defineEmits<{ toggleMenu: [] }>();

const page = usePage();

const localeMeta = computed(() => {
    return (page.props.locale ?? {
        current: 'en',
        fallback: 'en',
        available: ['en', 'de', 'es'],
    }) as {
        current: SupportedLocale;
        fallback: SupportedLocale;
        available: SupportedLocale[];
    };
});

const currentLocale = computed<SupportedLocale>(() => localeMeta.value.current);
const availableLocales = computed<SupportedLocale[]>(
    () => localeMeta.value.available,
);
const manualUrl = computed<string>(() => `/manual/${currentLocale.value}`);
const { t } = useUiI18n();

function withLang(lang: SupportedLocale): string {
    const [path, query = ''] = page.url.split('?');
    const params = new URLSearchParams(query);

    params.set('lang', lang);

    const queryString = params.toString();

    return queryString === '' ? path : `${path}?${queryString}`;
}
</script>

<template>
    <header class="topbar">
        <div class="topbar-left">
            <Link
                :href="dashboard()"
                class="topbar-logo"
                :aria-label="t('common.home')"
            >
                <img src="/css/logo.jpeg" alt="" />
            </Link>
            <Link :href="dashboard()" class="topbar-title-link">{{
                t('common.home')
            }}</Link>
        </div>

        <div class="topbar-right">
            <div class="topbar-lang">
                <Link
                    v-for="locale in availableLocales"
                    :key="`topbar-locale-${locale}`"
                    :href="withLang(locale)"
                    class="topbar-link"
                    :class="{ 'is-active': currentLocale === locale }"
                    :aria-current="
                        currentLocale === locale ? 'true' : undefined
                    "
                    :lang="locale"
                >
                    {{ locale.toUpperCase() }}
                </Link>
            </div>

            <div class="topbar-actions-desktop">
                <a
                    class="topbar-link topbar-link--manual"
                    :href="manualUrl"
                    target="_blank"
                    rel="noopener"
                >
                    <span class="glyphicon glyphicon-book" aria-hidden="true" />
                    <span>{{ t('common.manual') }}</span>
                </a>

                <details class="topbar-notify">
                    <summary
                        class="topbar-notify-toggle"
                        :title="t('common.notifications')"
                    >
                        <span
                            class="glyphicon glyphicon-bell"
                            aria-hidden="true"
                        />
                        <span class="topbar-notify-label">{{
                            t('common.notifications')
                        }}</span>
                    </summary>
                    <div
                        class="topbar-notify-menu"
                        role="menu"
                        :aria-label="t('common.notifications')"
                    >
                        <div class="topbar-notify-empty">
                            {{ t('common.no_notifications') }}
                        </div>
                    </div>
                </details>

                <Link :href="editProfile()" class="topbar-link">{{
                    t('common.profile')
                }}</Link>
                <Link
                    :href="logout()"
                    as="button"
                    class="topbar-link topbar-link--ghost"
                >
                    {{ t('common.logout') }}
                </Link>
            </div>

            <details class="topbar-notify topbar-notify-mobile">
                <summary
                    class="topbar-notify-toggle"
                    :title="t('common.notifications')"
                    :aria-label="t('common.notifications')"
                >
                    <span class="glyphicon glyphicon-bell" aria-hidden="true" />
                    <span class="topbar-notify-label">{{
                        t('common.notifications')
                    }}</span>
                </summary>
                <div
                    class="topbar-notify-menu"
                    role="menu"
                    :aria-label="t('common.notifications')"
                >
                    <div class="topbar-notify-empty">
                        {{ t('common.no_notifications') }}
                    </div>
                </div>
            </details>

            <button
                type="button"
                class="topbar-menu-btn"
                :aria-label="t('common.menu')"
                id="mobile-nav-toggle"
                :aria-expanded="menuOpen"
                aria-controls="mobile-nav"
                @click="$emit('toggleMenu')"
            >
                <span
                    class="glyphicon glyphicon-menu-hamburger"
                    aria-hidden="true"
                />
                <span>{{ t('common.menu') }}</span>
            </button>
        </div>
    </header>
</template>

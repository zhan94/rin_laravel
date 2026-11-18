<script setup lang="ts">
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { usePageAuth } from '@/composables/usePageAuth';
import { useUiI18n } from '@/composables/useUiI18n';
import horizon from '@/routes/horizon';
import { type AuthPermissions, type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type SidebarItem = NavItem & {
    permission?: keyof AuthPermissions;
    iconClass: string;
};

type SidebarSection = {
    title: string;
    items: SidebarItem[];
};

const auth = usePageAuth();
const { isCurrentUrl } = useCurrentUrl();
const { t } = useUiI18n();
const permissions = computed<Partial<AuthPermissions>>(
    () => auth.value.permissions ?? {},
);

const sections = computed<SidebarSection[]>(() => [
    {
        title: t('nav.main'),
        items: [
            {
                title: t('nav.services'),
                href: '/crm/services',
                permission: 'readCustomerData',
                iconClass: 'glyphicon-calendar',
            },
            {
                title: t('customers.add_customer'),
                href: '/crm/bookings',
                permission: 'manageBookings',
                iconClass: 'glyphicon-plus',
            },
            {
                title: t('nav.reports'),
                href: '/crm/reports',
                iconClass: 'glyphicon-stats',
            },
            {
                title: t('nav.seasons'),
                href: '/crm/seasons',
                permission: 'readCustomerData',
                iconClass: 'glyphicon-leaf',
            },
        ],
    },
    {
        title: t('nav.catalog'),
        items: [
            {
                title: t('nav.bonus'),
                href: '/crm/bonus',
                permission: 'manageBookings',
                iconClass: 'glyphicon-gift',
            },
            {
                title: t('nav.locations'),
                href: '/crm/locations',
                permission: 'manageBookings',
                iconClass: 'glyphicon-map-marker',
            },
            {
                title: t('nav.equipment'),
                href: '/crm/equips',
                permission: 'manageBookings',
                iconClass: 'glyphicon-briefcase',
            },
            {
                title: t('nav.cards'),
                href: '/crm/cards',
                permission: 'manageBookings',
                iconClass: 'glyphicon-credit-card',
            },
        ],
    },
    {
        title: t('nav.admin'),
        items: [
            {
                title: t('nav.customers'),
                href: '/crm/customers',
                permission: 'readCustomerData',
                iconClass: 'glyphicon-list-alt',
            },
            {
                title: t('nav.users'),
                href: '/crm/users',
                iconClass: 'glyphicon-user',
                permission: 'users',
            },
            {
                title: t('nav.issues'),
                href: '/crm/issues',
                iconClass: 'glyphicon-wrench',
                permission: 'issues',
            },
            {
                title: t('nav.logs'),
                href: '/crm/logs',
                iconClass: 'glyphicon-time',
                permission: 'logs',
            },
            {
                title: t('nav.horizon'),
                href: horizon.index().url,
                iconClass: 'glyphicon-dashboard',
                permission: 'horizon',
            },
        ],
    },
]);

const visibleSections = computed<SidebarSection[]>(() =>
    sections.value
        .map((section) => ({
            ...section,
            items: section.items.filter((item) =>
                item.permission === undefined
                    ? true
                    : Boolean(permissions.value[item.permission]),
            ),
        }))
        .filter((section) => section.items.length > 0),
);
</script>

<template>
    <nav class="sidebar" :aria-label="t('common.menu')">
        <div
            v-for="section in visibleSections"
            :key="section.title"
            class="sidebar-group"
        >
            <p class="sidebar-title">{{ section.title }}</p>
            <ul class="sidebar-nav">
                <li
                    v-for="item in section.items"
                    :key="`${section.title}-${item.title}`"
                >
                    <Link
                        :href="item.href"
                        class="sidebar-link"
                        :data-active="isCurrentUrl(item.href)"
                        :aria-current="
                            isCurrentUrl(item.href) ? 'page' : undefined
                        "
                    >
                        <span
                            aria-hidden="true"
                            class="glyphicon sidebar-icon"
                            :class="item.iconClass"
                        />
                        <span class="sidebar-text">{{ item.title }}</span>
                    </Link>
                </li>
            </ul>
        </div>
    </nav>
</template>

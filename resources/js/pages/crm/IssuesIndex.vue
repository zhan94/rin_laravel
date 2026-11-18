<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import IssueDetailPanel from '@/pages/crm/components/IssueDetailPanel.vue';
import IssuesCreatePanel from '@/pages/crm/components/IssuesCreatePanel.vue';
import IssuesListPanel from '@/pages/crm/components/IssuesListPanel.vue';
import { provideIssuePageContext } from '@/pages/crm/composables/issuePageContext';
import { useIssuesPage } from '@/pages/crm/composables/useIssuesPage';
import { Head } from '@inertiajs/vue3';

const issuePage = useIssuesPage();
provideIssuePageContext(issuePage);

const { t, state, scopeLabel } = issuePage;
</script>

<template>
    <Head :title="t('issues.head')" />

    <AppLayout>
        <div class="dashboard customer-list customer-ui issues-ui">
            <div class="customer-shell">
                <section class="list-hero">
                    <div>
                        <div class="eyebrow">{{ t('issues.eyebrow') }}</div>
                        <h1>{{ t('issues.title') }}</h1>
                        <div class="subline">{{ t('issues.subtitle') }}</div>
                    </div>

                    <div class="hero-actions">
                        <span class="badge badge--info">{{ scopeLabel }}</span>
                    </div>
                </section>

                <div v-if="state.errors.length > 0" class="alert alert-danger">
                    <p
                        v-for="error in state.errors"
                        :key="`${error.code}-${error.message}`"
                    >
                        {{ error.message }}
                    </p>
                </div>

                <div class="stat-grid">
                    <div class="stat-card">
                        <span class="stat-label">{{ t('issues.total') }}</span>
                        <span class="stat-value">{{
                            state.summary.total_count
                        }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">{{ t('issues.open') }}</span>
                        <span class="stat-value">{{
                            state.summary.open_count
                        }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">{{
                            t('issues.in_progress')
                        }}</span>
                        <span class="stat-value">{{
                            state.summary.in_progress_count
                        }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">{{
                            t('issues.resolved')
                        }}</span>
                        <span class="stat-value">{{
                            state.summary.resolved_count
                        }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">{{ t('issues.urgent') }}</span>
                        <span class="stat-value">{{
                            state.summary.urgent_count
                        }}</span>
                    </div>
                </div>

                <IssuesCreatePanel />

                <IssuesListPanel />

                <IssueDetailPanel />
            </div>
        </div>
    </AppLayout>
</template>

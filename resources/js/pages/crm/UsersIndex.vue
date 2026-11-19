<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useUsersPage } from '@/pages/crm/composables/useUsersPage';
import { Head } from '@inertiajs/vue3';

const {
    t,
    state,
    summaryLabel,
    userStats,
    isEditing,
    openCreateForm,
    closeForm,
    loadUsers,
    startEdit,
    submitUser,
    removeUser,
    goToPage,
} = useUsersPage();
</script>

<template>
    <Head :title="t('users.head')" />

    <AppLayout>
        <div class="dashboard customer-ui customer-form customer-list users-ui">
            <div class="customer-shell">
                <section class="list-hero">
                    <div>
                        <div class="eyebrow">{{ t('users.eyebrow') }}</div>
                        <h1>{{ t('users.title') }}</h1>
                        <div class="subline">{{ t('users.subtitle') }}</div>
                    </div>

                    <div class="hero-actions">
                        <button
                            type="button"
                            class="btn btn-solid"
                            @click="openCreateForm"
                        >
                            <span class="glyphicon glyphicon-plus" />
                            {{ t('users.add_user') }}
                        </button>
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
                        <span class="stat-label">{{ t('users.total') }}</span>
                        <span class="stat-value">{{
                            state.pagination.total
                        }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">{{ t('users.admins') }}</span>
                        <span class="stat-value">{{ userStats.admins }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">{{
                            t('users.managers')
                        }}</span>
                        <span class="stat-value">{{ userStats.managers }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">{{
                            t('users.group_leaders')
                        }}</span>
                        <span class="stat-value">{{
                            userStats.groupLeaders
                        }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">{{ t('users.drivers') }}</span>
                        <span class="stat-value">{{ userStats.drivers }}</span>
                    </div>
                </div>

                <section v-if="state.formOpen" class="form-card">
                    <div class="card-head">
                        <h3>
                            {{
                                isEditing()
                                    ? t('users.edit_user', {
                                          id: state.editingId ?? '',
                                      })
                                    : t('users.add_user_form')
                            }}
                        </h3>
                    </div>

                    <form
                        class="customer-form-grid grid gap-3 md:grid-cols-4"
                        @submit.prevent="submitUser"
                    >
                        <input
                            v-model="state.form.username"
                            type="text"
                            :placeholder="t('users.username')"
                            class="form-control"
                            required
                        />
                        <input
                            v-model="state.form.firstname"
                            type="text"
                            :placeholder="t('users.first_name')"
                            class="form-control"
                            required
                        />
                        <input
                            v-model="state.form.surname"
                            type="text"
                            :placeholder="t('customers.surname')"
                            class="form-control"
                        />
                        <input
                            v-model="state.form.lastname"
                            type="text"
                            :placeholder="t('users.last_name')"
                            class="form-control"
                            required
                        />
                        <input
                            v-model="state.form.email"
                            type="email"
                            :placeholder="t('profile.email')"
                            class="form-control md:col-span-2"
                        />
                        <input
                            v-model="state.form.password"
                            type="password"
                            :placeholder="
                                isEditing()
                                    ? t('users.password_optional')
                                    : t('users.password')
                            "
                            class="form-control"
                            :required="!isEditing()"
                        />
                        <select
                            v-model.number="state.form.role_id"
                            class="form-control"
                            required
                        >
                            <option :value="0" disabled>
                                {{ t('users.select_role') }}
                            </option>
                            <option
                                v-for="role in state.roles"
                                :key="role.id"
                                :value="role.id"
                            >
                                {{ role.title }} ({{ role.role_key }})
                            </option>
                        </select>

                        <div class="flex gap-2 md:col-span-4">
                            <button
                                type="submit"
                                class="btn btn-solid"
                                :disabled="state.submitting"
                            >
                                {{
                                    state.submitting
                                        ? t('users.saving')
                                        : isEditing()
                                          ? t('users.update_user')
                                          : t('users.create_user')
                                }}
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline"
                                @click="closeForm"
                            >
                                {{ t('common.cancel') }}
                            </button>
                        </div>
                    </form>
                </section>

                <section class="table-shell">
                    <div class="table-head">
                        <span class="badge badge--info">{{
                            t('users.active_users')
                        }}</span>
                        <span class="filters-note">{{
                            t('users.sort_search')
                        }}</span>
                    </div>

                    <div class="table-controls">
                        <div class="field">
                            <label for="usersSearch">{{
                                t('users.search_label')
                            }}</label>
                            <input
                                id="usersSearch"
                                v-model="state.query"
                                type="text"
                                :placeholder="t('users.search_placeholder')"
                                class="form-control"
                            />
                        </div>
                        <div class="table-meta">{{ summaryLabel }}</div>
                        <button
                            type="button"
                            class="btn btn-outline"
                            @click="loadUsers"
                        >
                            {{ t('common.search') }}
                        </button>
                    </div>

                    <div class="table-wrap">
                        <table
                            class="table-hover table-bordered table-responsive customer-table table-stack-mobile table"
                        >
                            <thead>
                                <tr>
                                    <th>{{ t('users.first_name') }}</th>
                                    <th>{{ t('users.middle_name') }}</th>
                                    <th>{{ t('users.last_name') }}</th>
                                    <th>{{ t('users.username') }}</th>
                                    <th>{{ t('users.user_level') }}</th>
                                    <th>{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="state.loading">
                                    <td colspan="6" class="empty-row">
                                        {{ t('users.loading') }}
                                    </td>
                                </tr>
                                <tr v-for="user in state.rows" :key="user.id">
                                    <td :data-label="t('users.first_name')">
                                        {{ user.firstname ?? '-' }}
                                    </td>
                                    <td :data-label="t('users.middle_name')">
                                        {{ user.surname ?? '-' }}
                                    </td>
                                    <td :data-label="t('users.last_name')">
                                        {{ user.lastname ?? '-' }}
                                    </td>
                                    <td :data-label="t('users.username')">
                                        {{ user.username }}
                                    </td>
                                    <td :data-label="t('users.user_level')">
                                        {{
                                            user.role?.title ??
                                            user.role?.role_key ??
                                            '-'
                                        }}
                                    </td>
                                    <td
                                        class="text-center"
                                        :data-label="t('common.actions')"
                                    >
                                        <div class="action-group">
                                            <button
                                                type="button"
                                                class="icon-btn icon-btn--primary"
                                                :title="t('common.edit')"
                                                @click="startEdit(user)"
                                            >
                                                <span
                                                    class="glyphicon glyphicon-edit"
                                                />
                                            </button>
                                            <button
                                                type="button"
                                                v-if="user.can_delete"
                                                class="icon-btn icon-btn--danger disabled:opacity-60"
                                                :disabled="
                                                    state.deletingId === user.id
                                                "
                                                :title="t('common.delete')"
                                                @click="removeUser(user.id)"
                                            >
                                                <span
                                                    class="glyphicon glyphicon-trash"
                                                />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    v-if="
                                        !state.loading &&
                                        state.rows.length === 0
                                    "
                                >
                                    <td colspan="6" class="empty-row">
                                        {{ t('users.summary_empty') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pager">
                        <button
                            type="button"
                            class="btn btn-ghost"
                            :disabled="state.pagination.current_page <= 1"
                            @click="goToPage(state.pagination.current_page - 1)"
                        >
                            {{ t('common.previous') }}
                        </button>
                        <span id="servicePageInfo">
                            {{
                                t('common.page_of', {
                                    current: state.pagination.current_page,
                                    last: state.pagination.last_page,
                                })
                            }}
                        </span>
                        <button
                            type="button"
                            class="btn btn-ghost"
                            :disabled="
                                state.pagination.current_page >=
                                state.pagination.last_page
                            "
                            @click="goToPage(state.pagination.current_page + 1)"
                        >
                            {{ t('common.next') }}
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>

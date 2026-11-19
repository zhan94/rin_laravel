import { useProfilePage } from '@/pages/settings/useProfilePage';
import { mount } from '@vue/test-utils';
import { expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';

const page = vi.hoisted(() => ({
    props: {
        auth: { user: { role_key: '' } },
        locale: { current: 'en', fallback: 'en' },
    },
}));

vi.mock('@inertiajs/vue3', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
    usePage: () => page,
}));

vi.mock('@/modules/passkeys/passkeyClient', async (importOriginal) => ({
    ...(await importOriginal<
        typeof import('@/modules/passkeys/passkeyClient')
    >()),
    postPasskeyForm: vi
        .fn()
        .mockResolvedValue({ ok: true, json: { rows: [] } }),
}));

const ProfileRole = defineComponent({
    setup() {
        const { roleLabel } = useProfilePage();
        return () => h('span', roleLabel.value);
    },
});

it.each([
    ['en', 'super_admin', 'Super Admin'],
    ['de', 'super_admin', 'Super-Administrator'],
    ['es', 'super_admin', 'Superadministrador'],
    ['de', 'admin', 'Administrator'],
])('shows the %s profile label for %s', (locale, roleKey, label) => {
    page.props.locale.current = locale;
    page.props.auth.user.role_key = roleKey;

    const wrapper = mount(ProfileRole);
    expect(wrapper.text()).toBe(label);
    wrapper.unmount();
});

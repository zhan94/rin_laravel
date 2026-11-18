export type User = {
    id: number;
    username?: string;
    name: string;
    firstname?: string | null;
    surname?: string | null;
    lastname?: string | null;
    email: string | null;
    avatar?: string;
    email_verified_at: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    role_key?: string | null;
    [key: string]: unknown;
};

export type AuthPermissions = {
    manageBookings: boolean;
    readCustomerData: boolean;
    users: boolean;
    logs: boolean;
    issues: boolean;
    horizon: boolean;
};

export type Auth = {
    user: User;
    permissions?: Partial<AuthPermissions>;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};

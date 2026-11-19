import { expect, test, type Page } from '@playwright/test';
import { mkdir, readFile } from 'node:fs/promises';
import { join } from 'node:path';

const username = process.env.RIN_BROWSER_USERNAME ?? 'demo.super_admin';

// The last scenario writes to the shared synthetic demo after the read-only checks.
test.describe.configure({ mode: 'default' });
test.use({ actionTimeout: 10_000 });

async function signIn(page: Page, account = username): Promise<void> {
    const password = process.env.RIN_BROWSER_PASSWORD;
    if (!password)
        throw new Error(
            'RIN_BROWSER_PASSWORD must contain the isolated demo account password.',
        );

    await page.goto('/login?lang=en');
    await page.locator('input[name="email"]').fill(account);
    await page.locator('input[name="password"]').fill(password);
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/(dashboard|crm\/customers)$/);
}

test('protects staff pages and serves the synthetic dashboard and catalogs', async ({
    page,
}) => {
    const browserErrors: string[] = [];
    page.on('pageerror', (error) => browserErrors.push(error.message));
    const login = await page.goto('/crm/customers');
    await expect(page).toHaveURL(/\/login$/);
    expect(login?.headers()['content-security-policy']).toContain(
        "frame-ancestors 'none'",
    );
    expect((await page.request.get('/register')).status()).toBe(404);
    await signIn(page);
    await page.goto('/dashboard?lang=en');
    await expect(page.locator('.dashboard .subline')).toContainText(
        /60\s*\/\s*60/,
    );
    await expect(page.locator('.dashboard h1')).toContainText(
        /Season \d{4}\/\d{4}/,
    );

    for (const [path, value] of [
        ['customers', 'Sample Customer 060'],
        ['locations', 'Example Central Station'],
        ['cards', 'Ski school day pass'],
    ]) {
        const api = page.waitForResponse(
            (response) =>
                response.url().includes('/api/v1/' + path) &&
                response.request().method() === 'GET',
        );
        await page.goto('/crm/' + path);
        expect((await api).status()).toBe(200);
        await expect(
            page.getByText(value, { exact: false }).first(),
        ).toBeVisible();
    }

    await page.goto('/crm/customers?lang=de');
    await expect(page.locator('html')).toHaveAttribute('lang', 'de');
    await expect(
        page.getByRole('heading', { name: 'Kunden', exact: true }),
    ).toBeVisible();
    expect(browserErrors).toEqual([]);
});

test('exports a real CSV and opens an independent print window', async ({
    page,
}) => {
    await signIn(page);
    await page.goto('/crm/reports?lang=en');
    await expect(
        page.getByText('Sample Customer 001', { exact: false }).first(),
    ).toBeVisible();

    const [download] = await Promise.all([
        page.waitForEvent('download'),
        page.getByRole('button', { name: 'CSV (All)', exact: true }).click(),
    ]);
    expect(download.suggestedFilename()).toBe('report-all.csv');
    const contents = await readFile(await download.path(), 'utf8');
    expect(contents).toContain('Sample Customer 001');
    expect(contents).toContain('\r\n');

    const [popup] = await Promise.all([
        page.waitForEvent('popup'),
        page.getByRole('button', { name: 'Print (All)', exact: true }).click(),
    ]);
    await expect(popup.locator('table th').first()).toHaveAttribute(
        'scope',
        'col',
    );
    await expect(
        popup.getByText('Sample Customer 001', { exact: false }).first(),
    ).toBeVisible();
    expect(await popup.evaluate(() => window.opener === null)).toBe(true);
    await popup.close();
});

test('mobile navigation supports Escape, announces state, and restores focus', async ({
    page,
}) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await signIn(page);
    await page.goto('/crm/customers?lang=en');
    const toggle = page.getByRole('button', { name: 'Menu', exact: true });
    const navigation = page.locator('#mobile-nav');
    await expect(toggle).toHaveAttribute('aria-controls', 'mobile-nav');
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(navigation).toBeHidden();
    await toggle.click();
    await expect(toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(navigation).toBeVisible();
    const bounds = await navigation.boundingBox();
    const main = await page.locator('.app-main').boundingBox();
    expect(bounds?.width).toBeGreaterThan(300);
    expect(main?.width).toBeGreaterThan(300);
    await expect(navigation.getByRole('link').first()).toBeFocused();
    await page.keyboard.press('Escape');
    await expect(navigation).toBeHidden();
    await expect(toggle).toBeFocused();
    expect(
        await page.evaluate(() => document.documentElement.scrollWidth),
    ).toBeLessThanOrEqual(375);
});

test('renders all staff modules and the German Super Admin profile', async ({
    page,
}) => {
    test.skip(
        username !== 'demo.super_admin',
        'Requires the synthetic Super Admin account',
    );
    test.setTimeout(90_000);

    const failures: string[] = [];
    page.on('pageerror', (error) => failures.push(error.message));
    page.on('response', (response) => {
        if (response.status() >= 400) {
            failures.push(
                `${response.status()} ${new URL(response.url()).pathname}`,
            );
        }
    });

    await signIn(page);
    for (const [index, module] of [
        'bookings',
        'bonus',
        'cards',
        'customers',
        'equips',
        'issues',
        'locations',
        'logs',
        'reports',
        'seasons',
        'services',
        'users',
    ].entries()) {
        await page.setViewportSize({
            width: [1440, 820, 390][index % 3],
            height: 900,
        });
        const result = page.waitForResponse(
            (response) =>
                new URL(response.url()).pathname === `/api/v1/${module}` &&
                response.request().method() === 'GET',
        );
        expect((await page.goto(`/crm/${module}?lang=en`))?.ok()).toBe(true);
        expect((await result).status()).toBe(200);
        await expect(
            page.locator('main').getByRole('heading', { level: 1 }).first(),
        ).toBeVisible();
        await expect(
            page.getByText('Unauthenticated.', { exact: true }),
        ).toHaveCount(0);
    }
    await page.goto('/settings/profile?lang=de');
    const roleField = page
        .locator('.field--readonly')
        .filter({ hasText: 'Benutzerebene' });
    await expect(roleField).toBeVisible();
    await expect(roleField.locator('.readonly-value')).toHaveText(
        'Super-Administrator',
    );
    expect(failures).toEqual([]);
});

test('captures approved synthetic demo views when requested', async ({
    page,
}) => {
    const directory = process.env.RIN_SCREENSHOT_DIR;
    test.skip(
        !directory,
        'Set RIN_SCREENSHOT_DIR to capture synthetic screenshots',
    );
    if (!directory) return;

    // Keep screenshot output limited to the isolated, identified demo.
    expect(
        new URL(process.env.PLAYWRIGHT_BASE_URL ?? 'http://localhost:8081')
            .hostname,
    ).toBe('localhost');
    await signIn(page);
    await page.goto('/dashboard?lang=en');
    await expect(page.locator('.dashboard .subline')).toContainText(
        /60\s*\/\s*60/,
    );
    await expect(
        page.getByText('Sample Customer 001', { exact: false }).first(),
    ).toBeVisible();
    await mkdir(directory, { recursive: true });
    await page.screenshot({
        path: join(directory, 'dashboard.png'),
        animations: 'disabled',
    });

    await page.goto('/crm/customers?lang=en');
    await expect(
        page.getByText('customer.60@example.test', { exact: false }).first(),
    ).toBeVisible();
    await page.screenshot({
        path: join(directory, 'customers.png'),
        animations: 'disabled',
    });

    await page.setViewportSize({ width: 375, height: 812 });
    await page.getByRole('button', { name: 'Menu', exact: true }).click();
    await expect(page.locator('#mobile-nav')).toBeVisible();
    await page.screenshot({
        path: join(directory, 'mobile-navigation.png'),
        animations: 'disabled',
    });
});

function dateOffset(days: number): Date {
    const date = new Date();
    date.setDate(date.getDate() + days);
    return date;
}

function isoDate(date: Date): string {
    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
}

async function toggleDate(page: Page, date: Date): Promise<void> {
    const month = page.locator('.ui-datepicker-month');
    const target = new Intl.DateTimeFormat('en-US', {
        month: 'long',
        year: 'numeric',
    }).format(date);
    const visible = await month.innerText();
    if (visible !== target) {
        const backward = new Date(`1 ${visible}`) > date;
        await page
            .locator(backward ? '.ui-datepicker-prev' : '.ui-datepicker-next')
            .click();
    }
    await expect(month).toHaveText(target);
    await page
        .locator(`.ui-datepicker-calendar a[data-date="${isoDate(date)}"]`)
        .click();
}

function apiResponse(page: Page, path: string, method: string) {
    return page.waitForResponse(
        (response) =>
            new URL(response.url()).pathname === `/api/v1/${path}` &&
            response.request().method() === method,
        { timeout: 10_000 },
    );
}

test('completes the customer and booking workflow with the synthetic ski service', async ({
    page,
}) => {
    test.setTimeout(90_000);
    await signIn(page, 'demo.manager');
    await page.goto('/dashboard?lang=en');
    const todayRecords = page
        .locator('.stat-card')
        .filter({ hasText: 'Today records' })
        .locator('.stat-value');
    const before = Number(await todayRecords.innerText());
    expect(before).toBeGreaterThan(0);

    const surname = `Acceptance${Date.now()}`;
    const fullName = `Synthetic ${surname}`;
    await page.goto('/crm/customers?lang=en');
    await expect(
        page.getByText('Sample Customer 060', { exact: true }),
    ).toBeVisible();
    await page
        .locator('.list-hero .hero-actions button')
        .filter({ hasText: 'Add customer' })
        .click();
    const customerForm = page.locator('form.customer-form-grid');
    await customerForm.getByPlaceholder('First name').fill('Synthetic');
    await customerForm.getByPlaceholder('Last name').fill(surname);
    const customerResponse = apiResponse(page, 'customers', 'POST');
    await customerForm.locator('button[type="submit"]').click();
    expect((await customerResponse).status()).toBe(201);
    await expect(page.getByText(fullName, { exact: true })).toBeVisible();

    await page.goto('/crm/bookings?lang=en');
    await page
        .getByPlaceholder('First and last name', { exact: true })
        .fill(fullName);
    await page
        .locator('.booking-suggest-item')
        .filter({ hasText: fullName })
        .click();
    await page
        .getByRole('button', { name: 'Continue to form', exact: true })
        .click();
    const form = page.locator('.booking-details-card form');
    await form.locator('select').first().selectOption({ label: 'Ski school' });
    await page.locator('.booking-location-trigger').click();
    await page
        .locator('.booking-location-option')
        .filter({ hasText: 'Example Central Station' })
        .click();
    const today = dateOffset(0);
    const tomorrow = dateOffset(1);
    const gapDay = dateOffset(17);
    await toggleDate(page, today);
    await toggleDate(page, gapDay);
    await form.locator('input[name="skill"][value="1"]').check();
    await form.getByPlaceholder('Total amount (€)', { exact: true }).fill('84');
    const bookingResponse = apiResponse(page, 'bookings', 'POST');
    await form.locator('button[type="submit"]').click();
    const creation = await bookingResponse;
    expect(creation.status()).toBe(201);
    const booking = (await creation.json()).data;
    expect(
        booking.booking_dates.map(
            (day: { slot_date: string }) => day.slot_date,
        ),
    ).toEqual([isoDate(today), isoDate(gapDay)]);

    const row = page.getByRole('row').filter({ hasText: fullName });
    await row.getByPlaceholder('Amount', { exact: true }).fill('24');
    const paymentResponse = apiResponse(
        page,
        `bookings/${booking.id}/payments/apply`,
        'POST',
    );
    await row.getByRole('button', { name: 'Apply', exact: true }).click();
    const payment = await paymentResponse;
    expect(payment.status()).toBe(200);
    expect((await payment.json()).data.payment.paid_bgn).toBe(47);

    await row.getByRole('button', { name: 'Edit', exact: true }).click();
    await expect(
        page.getByRole('heading', { name: 'Edit booking', exact: true }),
    ).toBeVisible();
    await expect(form.locator('button[type="submit"]')).toBeEnabled();
    await toggleDate(page, tomorrow);
    await toggleDate(page, gapDay);
    await form.getByRole('checkbox', { name: 'Card', exact: true }).check();
    await form
        .getByRole('checkbox', { name: 'Equipment', exact: true })
        .check();
    await form
        .getByRole('checkbox', { name: 'Scanned parental consent form' })
        .check();
    await form
        .getByPlaceholder('Note', { exact: true })
        .fill('Synthetic acceptance update');
    await form
        .getByPlaceholder('Total amount (€)', { exact: true })
        .fill('144');
    const updateResponse = apiResponse(page, `bookings/${booking.id}`, 'PUT');
    await form.getByRole('button', { name: 'Save', exact: true }).click();
    const updated = await updateResponse;
    expect(updated.status()).toBe(200);
    const beforeCancellation = (await updated.json()).data;
    expect(beforeCancellation.approved).toBe(true);
    expect(beforeCancellation.paid).toBe(47);
    expect(beforeCancellation.sum_paid).toBe(282);
    expect(beforeCancellation.winter_detail.card_id).toBeGreaterThan(0);
    expect(beforeCancellation.winter_detail.equip_id).toBeGreaterThan(0);
    expect(
        beforeCancellation.booking_dates.map(
            (day: { slot_date: string }) => day.slot_date,
        ),
    ).toEqual([isoDate(today), isoDate(tomorrow)]);
    await expect(row).toContainText('Consent received');

    await page.goto('/dashboard?lang=en');
    await expect(todayRecords).toHaveText(String(before + 1));
    await expect(page.getByText(fullName, { exact: true })).toBeVisible();

    await page.goto('/crm/reports?lang=en');
    await expect(page.getByText(fullName, { exact: true })).toBeVisible();
    const report = apiResponse(page, 'reports', 'GET');
    await page
        .locator('.report-filters')
        .getByRole('button', { name: 'Generate', exact: true })
        .click();
    expect((await report).status()).toBe(200);
    await expect(page.getByText(fullName, { exact: true })).toBeVisible();

    await page.goto('/crm/bookings?lang=en');
    page.once('dialog', (dialog) => dialog.accept());
    const cancellationResponse = apiResponse(
        page,
        `bookings/${booking.id}`,
        'DELETE',
    );
    await row
        .getByRole('button', { name: 'Cancel booking', exact: true })
        .click();
    const cancellation = await cancellationResponse;
    expect(cancellation.status()).toBe(200);
    expect((await cancellation.json()).data).toMatchObject({
        active: false,
        paid: 47,
    });
    await expect(row).toContainText('Cancelled');

    await page
        .getByPlaceholder('First and last name', { exact: true })
        .fill(fullName);
    await page
        .locator('.booking-suggest-item')
        .filter({ hasText: fullName })
        .click();
    await page
        .getByRole('button', { name: 'Continue to form', exact: true })
        .click();
    await form
        .locator('select')
        .first()
        .selectOption({ label: 'Mountain hiking' });
    await page.locator('.booking-location-trigger').click();
    await page
        .locator('.booking-location-option')
        .filter({ hasText: 'Example Central Station' })
        .click();
    await toggleDate(page, today);
    const hikingResponse = apiResponse(page, 'bookings', 'POST');
    await form.locator('button[type="submit"]').click();
    const hiking = await hikingResponse;
    expect(hiking.status()).toBe(201);
    const hikingBooking = (await hiking.json()).data;
    expect(hikingBooking.service_id).not.toBe(booking.service_id);
    expect(hikingBooking.booking_dates[0].slot_date).toBe(isoDate(today));
});

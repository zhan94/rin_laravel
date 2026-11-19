import { expect, test, type Locator, type Page } from '@playwright/test';
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
    await expect(page).toHaveURL(/\/(dashboard|crm\/customers|crm\/reports)$/);
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
    await expect(page.locator('.dashboard .subline')).toContainText(
        germanDate(dateOffset(0)),
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
    const loadedFonts = await page.evaluate(async () => [
        (await document.fonts.load('600 24px Fraunces')).length,
        (await document.fonts.load('400 16px "Space Grotesk"')).length,
    ]);
    expect(loadedFonts.every((count) => count > 0)).toBe(true);
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
    expect(contents).toMatch(/\b\d{2}\.\d{2}\.\d{4}\b/);
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
    await expect(popup.locator('body')).toContainText(
        germanDate(dateOffset(0)),
    );
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
    await toggle.click();
    await expect(
        navigation.getByRole('link', { name: 'Profile', exact: true }),
    ).toBeVisible();
    await expect(
        navigation.getByRole('link', { name: 'Manual', exact: true }),
    ).toBeVisible();
    await navigation
        .getByRole('button', { name: 'Logout', exact: true })
        .click();
    await expect(
        page.getByRole('heading', { name: 'Access the system' }),
    ).toBeVisible();
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

test('keeps the selected service pickup options when an older response arrives late', async ({
    page,
}) => {
    await signIn(page, 'demo.manager');
    await page.goto('/crm/bookings?lang=en');
    await page
        .getByRole('checkbox', { name: 'New customer', exact: true })
        .check();
    await page
        .getByRole('button', { name: 'Continue to form', exact: true })
        .click();
    const service = page.locator('.booking-details-card select').first();
    const skiId = await service
        .getByRole('option', { name: 'Ski school', exact: true })
        .getAttribute('value');
    const hikingId = await service
        .getByRole('option', { name: 'Mountain hiking', exact: true })
        .getAttribute('value');
    const skiPath = `booking-support/services/${skiId}/availability`;
    const hikingPath = `booking-support/services/${hikingId}/availability`;
    const held = Promise.withResolvers<void>();
    const intercepted = Promise.withResolvers<void>();

    await page.route(
        (url) => url.pathname === `/api/v1/${skiPath}`,
        async (route) => {
            const response = await route.fetch();
            intercepted.resolve();
            await held.promise;
            await route.fulfill({ response });
        },
    );

    try {
        await service.selectOption({ label: 'Ski school' });
        await intercepted.promise;
        const hikingResponse = apiResponse(page, hikingPath, 'GET');
        await service.selectOption({ label: 'Mountain hiking' });
        expect((await hikingResponse).status()).toBe(200);
        const trigger = page.locator('.booking-location-trigger');
        await expect(trigger).toBeEnabled();
        const lateResponse = apiResponse(page, skiPath, 'GET');
        held.resolve();
        await (await lateResponse).finished();

        await trigger.click();
        const options = page.locator('.booking-location-option');
        await expect(
            options.filter({ hasText: 'Mountain hiking Centre' }),
        ).toBeVisible();
        await expect(
            options.filter({ hasText: 'Ski school Centre' }),
        ).toHaveCount(0);
    } finally {
        held.resolve();
    }
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

function germanDate(date: Date): string {
    return isoDate(date).split('-').reverse().join('.');
}

async function expectServiceActionsToFit(row: Locator): Promise<void> {
    await expect(
        row.getByRole('button', { name: 'Availability' }),
    ).toBeVisible();
    for (const action of await row.locator('.btn-xs').all()) {
        expect(
            await action.evaluate((element) => {
                const range = document.createRange();
                range.selectNodeContents(element);
                const label = range.getBoundingClientRect();
                const button = element.getBoundingClientRect();

                return (
                    button.height >= 32 &&
                    label.top > button.top &&
                    label.bottom < button.bottom &&
                    label.left > button.left &&
                    label.right < button.right
                );
            }),
        ).toBe(true);
    }
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

async function openAvailability(page: Page, service: string, date: Date) {
    await page.goto('/crm/services?lang=en');
    await page
        .getByLabel('Service', { exact: true })
        .selectOption({ label: service });
    await page
        .getByLabel('Date within season', { exact: true })
        .fill(germanDate(date));
    const seasons = page.getByRole('table', {
        name: 'Service seasons',
        exact: true,
    });
    const rows = seasons.locator('tbody tr');
    await expect(rows).toHaveCount(1);
    await rows
        .getByRole('button', { name: 'Availability', exact: true })
        .click();
    const detail = page.getByRole('dialog');
    const days = detail.getByRole('table', {
        name: 'Daily availability',
        exact: true,
    });
    const day = days.locator('tbody tr');
    await expect(day).toHaveCount(1);
    await expect(day.getByRole('cell').first()).toHaveText(germanDate(date));

    return {
        detail,
        day,
        booked: day.getByRole('cell').nth(1),
        remaining: day.getByRole('cell').nth(2),
    };
}

test('shows distinct service seasons with prices, status filters, sorting, and mobile availability', async ({
    page,
}) => {
    await signIn(page, 'demo.manager');
    await page.goto('/crm/services?lang=en');
    const seasons = page.getByRole('table', {
        name: 'Service seasons',
        exact: true,
    });
    const rows = seasons.locator('tbody tr');
    await expect(rows).toHaveCount(6);
    await expect(rows.first()).toContainText(
        /\d{2}\.\d{2}\.\d{4} – \d{2}\.\d{2}\.\d{4}/,
    );
    await expectServiceActionsToFit(rows.first());
    await page
        .getByRole('searchbox', { name: 'Search', exact: true })
        .fill('Ski school');
    await expect(rows).toHaveCount(3);
    await expect(rows.filter({ hasText: 'Active' })).toContainText('42.00€');
    await expect(rows.filter({ hasText: 'Upcoming' })).toContainText('12');
    await expect(rows.filter({ hasText: 'Expired' })).toHaveCount(1);

    await page.getByLabel('Sort by', { exact: true }).selectOption('date');
    await page.getByLabel('Sort order', { exact: true }).selectOption('desc');
    await expect(rows.first()).toContainText('Upcoming');
    await page.getByLabel('Status', { exact: true }).selectOption('expired');
    await expect(rows).toHaveCount(1);
    await expect(rows).toContainText('Expired');

    await page.setViewportSize({ width: 390, height: 844 });
    const { detail, day, booked, remaining } = await openAvailability(
        page,
        'Ski school',
        dateOffset(0),
    );
    await expect(
        detail.getByRole('heading', { name: /Ski school · Activity block/ }),
    ).toBeVisible();
    await expect(day).toBeVisible();
    await expect(booked).toHaveText('3');
    await expect(remaining).toHaveText('9');
    await expect(detail.getByText('Sample Customer 001')).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(detail).toHaveCount(0);
    await expect(
        rows.getByRole('button', { name: 'Availability', exact: true }),
    ).toBeFocused();
    await expectServiceActionsToFit(rows.first());
});

test('Driver can open a linked report and exports the generated range after editing draft filters', async ({
    page,
}) => {
    const unexpected: string[] = [];
    page.on('response', (response) => {
        if (response.url().includes('/api/v1/') && response.status() >= 400) {
            unexpected.push(
                `${response.status()} ${new URL(response.url()).pathname}`,
            );
        }
    });
    await signIn(page, 'demo.driver');
    const service = page.getByLabel('Select service', { exact: true });
    const ski = service.getByRole('option', {
        name: 'Ski school',
        exact: true,
    });
    await expect(ski).toBeAttached();
    const id = await ski.getAttribute('value');
    const day = dateOffset(0);
    const url = `/crm/reports?lang=en&service_id=${id}&from=${isoDate(day)}&to=${isoDate(day)}`;
    await page.goto(url);
    await expect(service).toHaveValue(id!);
    await expect(page.getByLabel('From', { exact: true })).toHaveValue(
        germanDate(day),
    );
    await expect(
        page.getByText('Sample Customer 001', { exact: true }),
    ).toBeVisible();
    await expect(
        page.getByRole('columnheader', { name: 'Birth date', exact: true }),
    ).toHaveCount(0);

    await page
        .getByLabel('From', { exact: true })
        .fill(germanDate(dateOffset(10)));
    await page
        .getByLabel('To', { exact: true })
        .fill(germanDate(dateOffset(10)));
    await expect(page.locator('.report-title')).toContainText(germanDate(day));

    const [download] = await Promise.all([
        page.waitForEvent('download'),
        page.getByRole('button', { name: 'CSV (All)', exact: true }).click(),
    ]);
    const csv = await readFile(await download.path(), 'utf8');
    expect(csv).toContain('Sample Customer 001');
    expect(csv).toContain(germanDate(day));
    expect(csv).not.toContain(isoDate(day));
    expect(csv).not.toContain('@example.test');
    expect(unexpected).toEqual([]);
});

test('Group Leader sees customer and season records with read-only controls', async ({
    page,
}) => {
    await signIn(page, 'demo.group_leader');
    await page.goto('/crm/customers?lang=en');
    await expect(
        page.getByText('Sample Customer 060', { exact: true }),
    ).toBeVisible();
    await expect(
        page.getByRole('button', { name: 'Add customer', exact: true }),
    ).toHaveCount(0);
    await expect(
        page.getByRole('button', { name: 'Delete', exact: true }),
    ).toHaveCount(0);
    await page
        .getByRole('button', { name: 'Unpaid fees', exact: true })
        .click();
    await expect(
        page
            .getByRole('button', { name: 'Open customer', exact: true })
            .first(),
    ).toBeVisible();
    await expect(page.locator('.pay-stack')).toHaveCount(0);

    await page.goto('/crm/seasons?lang=en');
    await expect(page.locator('.season-range').first()).toBeVisible();
    await expect(page.locator('main form')).toHaveCount(0);
});

test('Admin sees permitted staff targets and roles only', async ({ page }) => {
    await signIn(page, 'demo.admin');
    await page.goto('/crm/users?lang=en');
    await expect(page.getByText('demo.manager', { exact: true })).toBeVisible();
    await expect(
        page.getByText('demo.super_admin', { exact: true }),
    ).toHaveCount(0);
    await page.getByRole('button', { name: /Add user/i }).click();
    await expect(
        page.getByRole('option', { name: 'Manager (manager)', exact: true }),
    ).toBeAttached();
    await expect(page.getByRole('option', { name: /Super Admin/ })).toHaveCount(
        0,
    );
});

test('records a payment from the Customer unpaid view and refreshes its balance', async ({
    page,
}) => {
    await signIn(page, 'demo.admin');
    await page.goto('/crm/customers?lang=en');
    await page
        .getByRole('button', { name: 'Unpaid fees', exact: true })
        .click();
    const row = page
        .getByRole('row')
        .filter({ hasText: 'Sample Customer 002' });
    await expect(row).toHaveCount(1);
    const before = await row.innerText();

    const response = page.waitForResponse(
        (result) =>
            result.url().includes('/payments/apply') &&
            result.request().method() === 'POST',
    );
    await row.getByRole('spinbutton').fill('5');
    await row.getByRole('button', { name: 'Pay', exact: true }).click();
    const applied = await response;
    expect(applied.status()).toBe(200);
    expect(applied.request().postDataJSON().amount).toBe(5);
    await expect(row).not.toHaveText(before);
    await expect(row.getByRole('spinbutton')).toHaveValue('');
});

test('completes the customer and booking workflow with the synthetic ski service', async ({
    page,
    browser,
    baseURL,
}) => {
    test.setTimeout(90_000);
    await signIn(page, 'demo.manager');
    await using observerContext = await browser.newContext({ baseURL });
    const observer = await observerContext.newPage();
    await signIn(observer, 'demo.group_leader');
    await page.goto('/dashboard?lang=en');
    const todayRecords = page
        .locator('.stat-card')
        .filter({ hasText: 'Today records' })
        .locator('.stat-value');
    const before = Number(await todayRecords.innerText());
    expect(before).toBeGreaterThan(0);

    const today = dateOffset(0);
    const tomorrow = dateOffset(1);
    const gapDay = dateOffset(17);
    const currentDay = await openAvailability(observer, 'Ski school', today);
    const remainingToday = Number(await currentDay.remaining.innerText());
    const nextDay = await openAvailability(observer, 'Ski school', tomorrow);
    const remainingTomorrow = Number(await nextDay.remaining.innerText());

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
    const birthDate = customerForm.getByLabel('Date of birth', { exact: true });
    await birthDate.fill('31.02.2012');
    expect(
        await birthDate.evaluate((input: HTMLInputElement) =>
            input.checkValidity(),
        ),
    ).toBe(false);
    await birthDate.fill('29.02.2012');
    expect(
        await birthDate.evaluate((input: HTMLInputElement) =>
            input.checkValidity(),
        ),
    ).toBe(true);
    const customerResponse = apiResponse(page, 'customers', 'POST');
    await customerForm.locator('button[type="submit"]').click();
    const savedCustomer = await customerResponse;
    expect(savedCustomer.status()).toBe(201);
    expect(savedCustomer.request().postDataJSON().date_birth).toBe(
        '2012-02-29',
    );
    await expect(
        page.getByRole('row').filter({ hasText: fullName }),
    ).toContainText('29.02.2012');
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
    await expect(form.getByLabel('Birth date', { exact: true })).toHaveValue(
        '29.02.2012',
    );
    await form.locator('select').first().selectOption({ label: 'Ski school' });
    await page.locator('.booking-location-trigger').click();
    await page
        .locator('.booking-location-option')
        .filter({ hasText: 'Example Central Station' })
        .click();
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

    const createdToday = await openAvailability(observer, 'Ski school', today);
    await expect(createdToday.remaining).toHaveText(String(remainingToday - 1));
    await expect(
        createdToday.detail.getByText(fullName, { exact: true }),
    ).toBeVisible();
    const gap = await openAvailability(observer, 'Ski school', gapDay);
    await expect(gap.booked).toHaveText('1');
    await expect(gap.detail.getByText(fullName, { exact: true })).toBeVisible();

    await page.goto('/crm/bookings?lang=en');
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

    const added = await openAvailability(observer, 'Ski school', tomorrow);
    await expect(added.remaining).toHaveText(String(remainingTomorrow - 1));
    await expect(
        added.detail.getByText(fullName, { exact: true }),
    ).toBeVisible();
    const removed = await openAvailability(observer, 'Ski school', gapDay);
    await expect(removed.booked).toHaveText('0');
    await expect(
        removed.detail.getByText('No bookings for this date.'),
    ).toBeVisible();

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

    const cancelledToday = await openAvailability(
        observer,
        'Ski school',
        today,
    );
    await expect(cancelledToday.remaining).toHaveText(String(remainingToday));
    await expect(
        cancelledToday.detail.locator('li').filter({ hasText: fullName }),
    ).toContainText('Cancelled');
    const cancelledTomorrow = await openAvailability(
        page,
        'Ski school',
        tomorrow,
    );
    await expect(cancelledTomorrow.remaining).toHaveText(
        String(remainingTomorrow),
    );

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

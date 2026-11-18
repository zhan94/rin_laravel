export function reportCsv(headings: string[], rows: string[][]): string {
    return [headings, ...rows]
        .map((row) =>
            row
                .map((value) => {
                    const formulaLike =
                        /^[=+\-@＝＋－＠]/u.test(value.trimStart()) ||
                        /^[\u0000-\u001f\u007f]/u.test(value);
                    const safeValue = formulaLike ? `'${value}` : value;

                    return `"${safeValue.replaceAll('"', '""')}"`;
                })
                .join(','),
        )
        .join('\r\n');
}

export function renderPrintReport(
    document: Document,
    title: string,
    period: string,
    headings: string[],
    rows: string[][],
): void {
    document.title = title;
    const heading = document.createElement('h2');
    heading.textContent = title;
    const description = document.createElement('p');
    description.textContent = period;
    const style = document.createElement('style');
    style.textContent =
        'body { font: 14px Arial, sans-serif; padding: 12px; } table { width: 100%; border-collapse: collapse; } th, td { padding: 8px; border: 1px solid #d7dde9; text-align: left; }';
    document.head.append(style);

    const table = document.createElement('table');
    const header = table.createTHead().insertRow();
    for (const label of headings) {
        const cell = document.createElement('th');
        cell.scope = 'col';
        cell.textContent = label;
        header.append(cell);
    }

    const body = table.createTBody();
    for (const values of rows) {
        const row = body.insertRow();
        for (const value of values) {
            row.insertCell().textContent = value;
        }
    }

    document.body.replaceChildren(heading, description, table);
}

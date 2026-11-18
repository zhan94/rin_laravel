import { renderPrintReport, reportCsv } from '@/modules/reports/reportOutput';
import { describe, expect, it } from 'vitest';

describe('report output', () => {
    it('quotes CSV data and opens formula-like customer text as text', () => {
        expect(
            reportCsv(
                ['Name', 'Location'],
                [
                    ['  +1234', 'Чавдар, "A"'],
                    ['\tName', 'Line 1\nLine 2'],
                ],
            ),
        ).toBe(
            '"Name","Location"\r\n"\'  +1234","Чавдар, ""A"""\r\n"\'\tName","Line 1\nLine 2"',
        );
    });

    it('renders every field as text in a semantic print table', () => {
        const printDocument = document.implementation.createHTMLDocument();
        renderPrintReport(
            printDocument,
            'Report',
            'Winter',
            ['Customer'],
            [['<strong>Name</strong>']],
        );

        expect(printDocument.querySelector('th')?.scope).toBe('col');
        expect(printDocument.querySelector('td')?.textContent).toBe(
            '<strong>Name</strong>',
        );
        expect(printDocument.querySelector('td strong')).toBeNull();
    });
});

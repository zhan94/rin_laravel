import { httpRequest, type ApiEnvelope } from '@/services/http';
import { withQuery } from '@/services/queryString';
import type {
    ReportListParams,
    ReportOverview,
    ReportServiceOption,
} from './types';

const basePath = '/api/v1/reports';

export function listReportServices(): Promise<
    ApiEnvelope<ReportServiceOption[]>
> {
    return httpRequest<ReportServiceOption[]>(`${basePath}/services`);
}

export function listReports(
    params: ReportListParams = {},
): Promise<ApiEnvelope<ReportOverview>> {
    return httpRequest<ReportOverview>(
        withQuery(basePath, {
            service_id: params.service_id,
            from: params.from,
            to: params.to,
            q: params.q,
            page: params.page && params.page > 0 ? params.page : undefined,
            per_page:
                params.per_page && params.per_page > 0
                    ? params.per_page
                    : undefined,
            sort: params.sort,
            dir: params.dir,
        }),
    );
}

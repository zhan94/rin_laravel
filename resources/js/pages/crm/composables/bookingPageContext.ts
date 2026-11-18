import { createStrictContext } from '@/composables/createStrictContext';
import type { useBookingsPage } from '@/pages/crm/composables/useBookingsPage';

export type BookingPageContext = ReturnType<typeof useBookingsPage>;

const bookingPageContext = createStrictContext<BookingPageContext>(
    'Booking page context',
);

export const provideBookingPageContext = bookingPageContext.provideContext;
export const useBookingPageContext = bookingPageContext.useContext;

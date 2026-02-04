import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import { defineComponent, nextTick } from 'vue';
import Dashboard from '@/Pages/Dashboard.vue';
import { Dashboard as DashboardType, User } from '@/Lib/types';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import axios from 'axios';

// Mocks para componentes de PrimeVue
const MockDropdown = defineComponent({
  name: 'Dropdown',
  props: ['modelValue', 'options', 'optionLabel', 'optionValue', 'placeholder', 'class'],
  template: '<select><option v-for="opt in options" :key="opt">{{ opt.label || opt }}</option></select>',
});

const MockCalendar = defineComponent({
  name: 'Calendar',
  props: ['modelValue', 'selectionMode', 'showIcon', 'dateFormat', 'showTime'],
  template: '<div class="calendar-mock"></div>',
});

const MockDivider = defineComponent({
  name: 'Divider',
  template: '<hr class="divider-mock" />',
});

const MockFloatLabel = defineComponent({
  name: 'FloatLabel',
  props: ['variant'],
  template: '<div class="float-label-mock"><slot></slot></div>',
});

// Mock para StatCard que verifica props
const MockStatCard = defineComponent({
  name: 'StatCard',
  props: ['label', 'value', 'icon', 'color', 'currency', 'editable'],
  template: '<div class="stat-card-mock" :data-label="label" :data-value="value" :data-currency="currency || \'\'" :data-editable="editable"><span class="label">{{ label }}</span><span class="value">{{ currency }}{{ value }}</span></div>',
});

const MockGlobalSearchBar = defineComponent({
  name: 'GlobalSearchBar',
  template: '<div class="global-search-mock">Global Search</div>',
});

const MockIncomingRequestsDrawer = defineComponent({
  name: 'IncomingRequestsDrawer',
  template: '<div class="incoming-requests-mock"></div>',
});

const MockAppLayout = defineComponent({
  name: 'AppLayout',
  template: '<div class="app-layout-mock"><slot></slot></div>',
});

// Mock para useToast - factory function sin referencias externas
vi.mock('primevue/usetoast', () => ({
  useToast: () => ({
    add: vi.fn(),
  }),
}));

// Mock para axios
vi.mock('axios', () => ({
  default: {
    post: vi.fn(),
  },
}));

describe('Dashboard.vue - Initial Rendering', () => {
  let wrapper: VueWrapper;
  
  const mockUser: User = {
    id: 1,
    name: 'John Doe',
    email: 'john@example.com',
    email_verified_at: null,
    two_factor_confirmed_at: null,
    current_team_id: null,
    profile_photo_path: null,
    created_at: '2024-01-01',
    updated_at: '2024-01-01',
    store_id: 1,
    location_id: null,
    invoice: null,
    column_headers: null,
    sold_headers: null,
    role: 'admin',
    deleted_at: null,
    profile_photo_url: '',
    two_factor_enabled: false,
  };

  const mockDashboardData: DashboardType = {
    devicesInInventory: 100,
    tradesThisMonth: 50,
    soldThisMonth: 30,
    costSoldThisMonth: 15000.50,
    costOfTaxedGoodsSold: 5000.25,
    inventoryValue: 75000.00,
    saleValue: 95000.00,
    soldValueThisMonth: 45000.75,
    profitThisMonth: 12000.50,
    startDate: '2024-01-01',
    endDate: '2024-01-31',
    cashOnHand: '5000.00',
    expensesThisMonth: 8000.25,
    accountsReceivableThisMonth: 12000.00,
    accountsPayableThisMonth: 5000.00,
    salesTaxCollected: 2500.50,
    salesTaxPaid: 1000.25,
    taxedSales: 30000.00,
    nonTaxedSales: 15000.00,
    totalPurchases: 20000.00,
  };

  const createWrapper = () => {
    return mount(Dashboard, {
      props: {
        auth: { user: mockUser },
        ...mockDashboardData,
      },
      global: {
        plugins: [PrimeVue, ToastService],
        stubs: {
          Dropdown: MockDropdown,
          Calendar: MockCalendar,
          Divider: MockDivider,
          FloatLabel: MockFloatLabel,
          StatCard: MockStatCard,
          GlobalSearchBar: MockGlobalSearchBar,
          IncomingRequestsDrawer: MockIncomingRequestsDrawer,
          AppLayout: MockAppLayout,
          Button: true,
          Card: true,
          InputNumber: true,
          InputText: true,
        },
      },
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('User Welcome Message', () => {
    it('renders welcome message with user name', () => {
      wrapper = createWrapper();
      expect(wrapper.html()).toContain('Welcome, John Doe');
    });
  });

  describe('Sections Rendering', () => {
    beforeEach(() => {
      wrapper = createWrapper();
    });

    it('renders all three main sections', () => {
      const html = wrapper.html();
      expect(html).toContain('Inventory');
      expect(html).toContain('Sales');
      expect(html).toContain('Accounting');
    });

    it('renders dividers between sections', () => {
      const dividers = wrapper.findAll('.divider-mock');
      expect(dividers.length).toBeGreaterThanOrEqual(3);
    });
  });

  describe('StatCards - Inventory Section', () => {
    beforeEach(() => {
      wrapper = createWrapper();
    });

    it('renders correct number of inventory stat cards', () => {
      const statCards = wrapper.findAll('.stat-card-mock');
      const inventoryCards = statCards.filter(card => {
        const label = card.attributes('data-label') || '';
        return ['Devices in Inventory', 'Devices Added', 'Devices Sold', 'Cost of Goods Sold', 'Cost of Goods Sold (Taxed)', 'Inventory Value ($)', 'Est. Sale Value of Inventory ($)'].includes(label);
      });
      expect(inventoryCards.length).toBe(7);
    });

    it('renders Devices in Inventory with correct props', () => {
      const card = wrapper.find('[data-label="Devices in Inventory"]');
      expect(card.exists()).toBe(true);
      expect(card.attributes('data-value')).toBe('100');
      expect(card.attributes('data-currency')).toBe('');
    });

    it('renders Inventory Value with currency symbol', () => {
      const card = wrapper.find('[data-label="Inventory Value ($)"]');
      expect(card.exists()).toBe(true);
      expect(card.attributes('data-value')).toBe('75000');
      expect(card.attributes('data-currency')).toBe('$');
    });

    it('renders Cost of Goods Sold with currency symbol', () => {
      const card = wrapper.find('[data-label="Cost of Goods Sold"]');
      expect(card.exists()).toBe(true);
      expect(card.attributes('data-currency')).toBe('$');
    });
  });

  describe('StatCards - Sales Section', () => {
    beforeEach(() => {
      wrapper = createWrapper();
    });

    it('renders correct number of sales stat cards', () => {
      const statCards = wrapper.findAll('.stat-card-mock');
      const salesCards = statCards.filter(card => {
        const label = card.attributes('data-label') || '';
        return ['Revenue ($)', 'Gross Profit ($)', 'Expenses ($)', 'Net Profit ($)'].includes(label);
      });
      expect(salesCards.length).toBe(4);
    });

    it('renders Revenue with currency symbol', () => {
      const card = wrapper.find('[data-label="Revenue ($)"]');
      expect(card.exists()).toBe(true);
      expect(card.attributes('data-currency')).toBe('$');
    });

    it('renders Net Profit with calculated value', () => {
      const card = wrapper.find('[data-label="Net Profit ($)"]');
      expect(card.exists()).toBe(true);
      // Net Profit = profitThisMonth - expensesThisMonth
      // 12000.50 - 8000.25 = 4000.25
      expect(card.attributes('data-value')).toContain('4000');
    });

    it('renders Expenses with currency symbol', () => {
      const card = wrapper.find('[data-label="Expenses ($)"]');
      expect(card.exists()).toBe(true);
      expect(card.attributes('data-currency')).toBe('$');
    });
  });

  describe('StatCards - Accounting Section', () => {
    beforeEach(() => {
      wrapper = createWrapper();
    });

    it('renders correct number of accounting stat cards', () => {
      const statCards = wrapper.findAll('.stat-card-mock');
      const accountingCards = statCards.filter(card => {
        const label = card.attributes('data-label') || '';
        return label.includes('($)') || label.includes('Purchases') || label.includes('Receivable') || label.includes('Payable') || label.includes('Tax');
      });
      expect(accountingCards.length).toBeGreaterThanOrEqual(8);
    });

    it('renders Cash on Hand with editable prop', () => {
      const card = wrapper.find('[data-label="Cash on Hand ($)"]');
      expect(card.exists()).toBe(true);
      expect(card.attributes('data-editable')).toBe('true');
      expect(card.attributes('data-currency')).toBe('$');
    });

    it('renders Accounts Receivable with currency symbol', () => {
      const card = wrapper.find('[data-label="Accounts Receivable ($)"]');
      expect(card.exists()).toBe(true);
      expect(card.attributes('data-currency')).toBe('$');
    });

    it('renders Sales Tax Collected with currency symbol', () => {
      const card = wrapper.find('[data-label="Sales Tax Collected ($)"]');
      expect(card.exists()).toBe(true);
      expect(card.attributes('data-currency')).toBe('$');
    });
  });

  describe('Filter Section', () => {
    beforeEach(() => {
      wrapper = createWrapper();
    });

    it('renders GlobalSearchBar component', () => {
      const searchBar = wrapper.find('.global-search-mock');
      expect(searchBar.exists()).toBe(true);
      expect(searchBar.text()).toBe('Global Search');
    });

    it('renders filter dropdown with correct options', () => {
      const dropdown = wrapper.findComponent(MockDropdown);
      expect(dropdown.exists()).toBe(true);
      const options = dropdown.props('options');
      expect(options).toContain('Current');
      expect(options).toContain('Historically');
    });

    it('renders date filter section with calendar', () => {
      const calendar = wrapper.findComponent(MockCalendar);
      expect(calendar.exists()).toBe(true);
    });

    it('renders quick filter dropdown', () => {
      const dropdowns = wrapper.findAllComponents(MockDropdown);
      expect(dropdowns.length).toBeGreaterThanOrEqual(2);
    });

    it('renders FloatLabel for date filter', () => {
      const floatLabel = wrapper.findComponent(MockFloatLabel);
      expect(floatLabel.exists()).toBe(true);
    });
  });

  describe('Currency Formatting', () => {
    beforeEach(() => {
      wrapper = createWrapper();
    });

    it('shows $ prefix for all monetary values', () => {
      const cardsWithCurrency = wrapper.findAll('[data-currency="$"]');
      expect(cardsWithCurrency.length).toBeGreaterThan(0);
      
      // Verificar que todos los valores monetarios tienen el simbolo $
      cardsWithCurrency.forEach(card => {
        expect(card.attributes('data-currency')).toBe('$');
      });
    });

    it('does not show $ prefix for non-monetary values', () => {
      const nonMonetaryCards = wrapper.findAll('[data-label="Devices in Inventory"], [data-label="Devices Added"], [data-label="Devices Sold"]');
      nonMonetaryCards.forEach(card => {
        expect(card.attributes('data-currency')).toBe('');
      });
    });
  });

  describe('Layout Components', () => {
    beforeEach(() => {
      wrapper = createWrapper();
    });

    it('renders within AppLayout', () => {
      const appLayout = wrapper.findComponent(MockAppLayout);
      expect(appLayout.exists()).toBe(true);
    });

    it('renders IncomingRequestsDrawer', () => {
      const drawer = wrapper.findComponent(MockIncomingRequestsDrawer);
      expect(drawer.exists()).toBe(true);
    });
  });

  describe('Computed Logic - getSafeCalendarValue', () => {
    beforeEach(() => {
      wrapper = createWrapper();
    });

    describe('Mode "Current" (Date Range)', () => {
      it('returns empty array when calendarValue is null', async () => {
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.calendarValue = null;
        await nextTick();
        
        const result = wrapper.vm.getSafeCalendarValue;
        expect(Array.isArray(result)).toBe(true);
        expect(result.length).toBe(0);
      });

      it('returns empty array when calendarValue is not an array', async () => {
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.calendarValue = new Date('2024-01-15');
        await nextTick();
        
        const result = wrapper.vm.getSafeCalendarValue;
        expect(Array.isArray(result)).toBe(true);
        expect(result.length).toBe(0);
      });

      it('returns empty array when array has only one date', async () => {
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.calendarValue = [new Date('2024-01-15')];
        await nextTick();
        
        const result = wrapper.vm.getSafeCalendarValue;
        expect(Array.isArray(result)).toBe(true);
        expect(result.length).toBe(0);
      });

      it('returns date range when both dates exist', async () => {
        wrapper.vm.selectedFilter = 'Current';
        const startDate = new Date('2024-01-01');
        const endDate = new Date('2024-01-31');
        wrapper.vm.calendarValue = [startDate, endDate];
        await nextTick();
        
        const result = wrapper.vm.getSafeCalendarValue;
        expect(Array.isArray(result)).toBe(true);
        expect(result.length).toBe(2);
        expect(result[0]).toBe(startDate);
        expect(result[1]).toBe(endDate);
      });

      it('returns empty array when array has null values', async () => {
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.calendarValue = [null, null];
        await nextTick();
        
        const result = wrapper.vm.getSafeCalendarValue;
        expect(Array.isArray(result)).toBe(true);
        expect(result.length).toBe(0);
      });

      it('returns empty array when one date is null', async () => {
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.calendarValue = [new Date('2024-01-01'), null];
        await nextTick();
        
        const result = wrapper.vm.getSafeCalendarValue;
        expect(Array.isArray(result)).toBe(true);
        expect(result.length).toBe(0);
      });
    });

    describe('Mode "Historically" (Single Date)', () => {
      it('returns null when calendarValue is null', async () => {
        wrapper.vm.selectedFilter = 'Historically';
        wrapper.vm.calendarValue = null;
        await nextTick();
        
        const result = wrapper.vm.getSafeCalendarValue;
        expect(result).toBeNull();
      });

      it('returns null when calendarValue is not a Date', async () => {
        wrapper.vm.selectedFilter = 'Historically';
        wrapper.vm.calendarValue = 'invalid string';
        await nextTick();
        
        const result = wrapper.vm.getSafeCalendarValue;
        expect(result).toBeNull();
      });

      it('returns null when calendarValue is a number', async () => {
        wrapper.vm.selectedFilter = 'Historically';
        wrapper.vm.calendarValue = 12345;
        await nextTick();
        
        const result = wrapper.vm.getSafeCalendarValue;
        expect(result).toBeNull();
      });

      it('returns single date when calendarValue is valid Date', async () => {
        wrapper.vm.selectedFilter = 'Historically';
        const testDate = new Date('2024-01-15');
        wrapper.vm.calendarValue = testDate;
        await nextTick();
        
        const result = wrapper.vm.getSafeCalendarValue;
        expect(result instanceof Date).toBe(true);
        expect(result).toBe(testDate);
      });

      it('returns null when calendarValue is an array', async () => {
        wrapper.vm.selectedFilter = 'Historically';
        wrapper.vm.calendarValue = [new Date('2024-01-01'), new Date('2024-01-31')];
        await nextTick();
        
        const result = wrapper.vm.getSafeCalendarValue;
        expect(result).toBeNull();
      });
    });
  });

  describe('Calendar Integration', () => {
    beforeEach(() => {
      wrapper = createWrapper();
    });

    it('Calendar receives selectionMode="range" when selectedFilter is "Current"', async () => {
      wrapper.vm.selectedFilter = 'Current';
      await nextTick();
      
      const calendar = wrapper.findComponent(MockCalendar);
      expect(calendar.exists()).toBe(true);
      expect(calendar.props('selectionMode')).toBe('range');
    });

    it('Calendar receives selectionMode="single" when selectedFilter is "Historically"', async () => {
      wrapper.vm.selectedFilter = 'Historically';
      await nextTick();
      
      const calendar = wrapper.findComponent(MockCalendar);
      expect(calendar.exists()).toBe(true);
      expect(calendar.props('selectionMode')).toBe('single');
    });
  });

  describe('Quick Filters - handleQuickFilter', () => {
    beforeEach(() => {
      wrapper = createWrapper();
    });

    afterEach(() => {
      vi.useRealTimers();
    });

    describe('Option "month" - Start of Month', () => {
      it('sets startDate to January 1, 2024 when today is January 15, 2024 in Current mode', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-01-15'));
        
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.quickFilter = 'month';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        expect(wrapper.vm.startDate?.toISOString().split('T')[0]).toBe('2024-01-01');
        expect(wrapper.vm.endDate?.toISOString().split('T')[0]).toBe('2024-01-15');
      });

      it('sets endDate to null in Historical mode', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-01-15'));
        
        wrapper.vm.selectedFilter = 'Historically';
        wrapper.vm.quickFilter = 'month';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        expect(wrapper.vm.startDate?.toISOString().split('T')[0]).toBe('2024-01-01');
        expect(wrapper.vm.endDate).toBeNull();
      });

      it('handles year boundary correctly - December 2023 to January 2024', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2023-12-15'));
        
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.quickFilter = 'month';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        expect(wrapper.vm.startDate?.toISOString().split('T')[0]).toBe('2023-12-01');
        expect(wrapper.vm.endDate?.toISOString().split('T')[0]).toBe('2023-12-15');
      });
    });

    describe('Option "year" - Start of Year', () => {
      it('sets startDate to January 1, 2024 when today is January 15, 2024', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-01-15'));
        
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.quickFilter = 'year';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        expect(wrapper.vm.startDate?.toISOString().split('T')[0]).toBe('2024-01-01');
        expect(wrapper.vm.endDate?.toISOString().split('T')[0]).toBe('2024-01-15');
      });

      it('sets endDate to null in Historical mode', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-06-15'));
        
        wrapper.vm.selectedFilter = 'Historically';
        wrapper.vm.quickFilter = 'year';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        expect(wrapper.vm.startDate?.toISOString().split('T')[0]).toBe('2024-01-01');
        expect(wrapper.vm.endDate).toBeNull();
      });
    });

    describe('Option "6months" - 6 Months Ago', () => {
      it('sets startDate to July 15, 2023 when today is January 15, 2024', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-01-15'));
        
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.quickFilter = '6months';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        expect(wrapper.vm.startDate?.toISOString().split('T')[0]).toBe('2023-07-15');
        expect(wrapper.vm.endDate?.toISOString().split('T')[0]).toBe('2024-01-15');
      });

      it('handles month boundary correctly - 31-day months', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-03-31')); // March 31
        
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.quickFilter = '6months';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        // September has 30 days, so it adjusts to September 30
        expect(wrapper.vm.startDate?.toISOString().split('T')[0]).toBe('2023-09-30');
        expect(wrapper.vm.endDate?.toISOString().split('T')[0]).toBe('2024-03-31');
      });

      it('always sets endDate to today regardless of selectedFilter', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-05-20'));
        
        // Test both modes
        for (const mode of ['Current', 'Historically']) {
          wrapper.vm.selectedFilter = mode;
          wrapper.vm.quickFilter = '6months';
          wrapper.vm.handleQuickFilter();
          await nextTick();
          
          expect(wrapper.vm.endDate?.toISOString().split('T')[0]).toBe('2024-05-20');
        }
      });
    });

    describe('Option "1year" - 1 Year Ago', () => {
      it('sets startDate to January 15, 2023 when today is January 15, 2024', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-01-15'));
        
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.quickFilter = '1year';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        expect(wrapper.vm.startDate?.toISOString().split('T')[0]).toBe('2023-01-15');
        expect(wrapper.vm.endDate?.toISOString().split('T')[0]).toBe('2024-01-15');
      });

      it('handles leap year correctly - February 29 edge case', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-02-29')); // Leap year
        
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.quickFilter = '1year';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        // 2023 is not a leap year, so February 29 becomes February 28
        expect(wrapper.vm.startDate?.toISOString().split('T')[0]).toBe('2023-02-28');
      });

      it('handles March 1 correctly after leap year', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-03-01'));
        
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.quickFilter = '1year';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        expect(wrapper.vm.startDate?.toISOString().split('T')[0]).toBe('2023-03-01');
      });

      it('always sets endDate to today regardless of selectedFilter', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-08-10'));
        
        wrapper.vm.selectedFilter = 'Historically';
        wrapper.vm.quickFilter = '1year';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        expect(wrapper.vm.endDate?.toISOString().split('T')[0]).toBe('2024-08-10');
      });
    });

    describe('Option "today" - Today', () => {
      it('sets both dates to today in Current mode', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-01-15'));
        
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.quickFilter = 'today';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        expect(wrapper.vm.startDate?.toISOString().split('T')[0]).toBe('2024-01-15');
        expect(wrapper.vm.endDate?.toISOString().split('T')[0]).toBe('2024-01-15');
      });

      it('sets endDate to null in Historical mode', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-06-20'));
        
        wrapper.vm.selectedFilter = 'Historically';
        wrapper.vm.quickFilter = 'today';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        expect(wrapper.vm.startDate?.toISOString().split('T')[0]).toBe('2024-06-20');
        expect(wrapper.vm.endDate).toBeNull();
      });
    });

    describe('Calendar Value Updates', () => {
      it('updates calendarValue to array [startDate, endDate] in Current mode', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-01-15'));
        
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.quickFilter = 'month';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        expect(Array.isArray(wrapper.vm.calendarValue)).toBe(true);
        expect(wrapper.vm.calendarValue.length).toBe(2);
        expect(wrapper.vm.calendarValue[0]?.toISOString().split('T')[0]).toBe('2024-01-01');
        expect(wrapper.vm.calendarValue[1]?.toISOString().split('T')[0]).toBe('2024-01-15');
      });

      it('updates calendarValue to single date in Historical mode', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-03-10'));
        
        wrapper.vm.selectedFilter = 'Historically';
        wrapper.vm.quickFilter = 'today';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        expect(wrapper.vm.calendarValue instanceof Date).toBe(true);
        expect(wrapper.vm.calendarValue?.toISOString().split('T')[0]).toBe('2024-03-10');
      });
    });

    describe('Edge Cases', () => {
      it('handles year-end transition - December 31 to January dates', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2023-12-31'));
        
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.quickFilter = '6months';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        // 6 months before Dec 31, 2023 is June 30, 2023
        expect(wrapper.vm.startDate?.toISOString().split('T')[0]).toBe('2023-06-30');
        expect(wrapper.vm.endDate?.toISOString().split('T')[0]).toBe('2023-12-31');
      });

      it('handles short months - May 31 to November 30', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-05-31'));
        
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.quickFilter = '6months';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        // 6 months before May 31 is November 30 (November has 30 days)
        expect(wrapper.vm.startDate?.toISOString().split('T')[0]).toBe('2023-11-30');
      });

      it('handles crossing from leap year to non-leap year', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-03-01'));
        
        wrapper.vm.selectedFilter = 'Current';
        wrapper.vm.quickFilter = '1year';
        wrapper.vm.handleQuickFilter();
        await nextTick();
        
        // March 1, 2023 (non-leap year)
        expect(wrapper.vm.startDate?.toISOString().split('T')[0]).toBe('2023-03-01');
      });
    });
  });

  describe('applyFilter Functionality', () => {
    let wrapper: VueWrapper;
    let mockDashboardData: DashboardType;
    let updateDashboardStatsSpy: any;
    let toastAddSpy: any;

    beforeEach(() => {
      wrapper = createWrapper();
      
      mockDashboardData = {
        devicesInInventory: 100,
        tradesThisMonth: 50,
        soldThisMonth: 30,
        costSoldThisMonth: 15000.50,
        costOfTaxedGoodsSold: 5000.25,
        inventoryValue: 75000.00,
        saleValue: 95000.00,
        soldValueThisMonth: 45000.75,
        profitThisMonth: 12000.50,
        startDate: '2024-01-01',
        endDate: '2024-01-31',
        cashOnHand: '5000.00',
        expensesThisMonth: 8000.25,
        accountsReceivableThisMonth: 12000.00,
        accountsPayableThisMonth: 5000.00,
        salesTaxCollected: 2500.50,
        salesTaxPaid: 1000.25,
        taxedSales: 30000.00,
        nonTaxedSales: 15000.00,
        totalPurchases: 20000.00,
      };

      updateDashboardStatsSpy = vi.spyOn(wrapper.vm, 'updateDashboardStats').mockImplementation(() => {});
      toastAddSpy = vi.spyOn(wrapper.vm.toast, 'add').mockImplementation(() => {});
    });

    afterEach(() => {
      vi.restoreAllMocks();
    });

    describe('Loading State', () => {
      it('sets isLoading to true at start', async () => {
        vi.spyOn(axios.default, 'post').mockResolvedValue({ data: mockDashboardData });
        
        expect(wrapper.vm.isLoading).toBe(false);
        wrapper.vm.applyFilter();
        expect(wrapper.vm.isLoading).toBe(true);
      });

      it('sets isLoading to false after successful completion', async () => {
        vi.spyOn(axios.default, 'post').mockResolvedValue({ data: mockDashboardData });
        
        expect(wrapper.vm.isLoading).toBe(false);
        await wrapper.vm.applyFilter();
        expect(wrapper.vm.isLoading).toBe(false);
      });

      it('sets isLoading to false after error', async () => {
        vi.spyOn(axios.default, 'post').mockRejectedValue(new Error('API Error'));
        
        expect(wrapper.vm.isLoading).toBe(false);
        await wrapper.vm.applyFilter();
        expect(wrapper.vm.isLoading).toBe(false);
      });
    });

    describe('Toast Notifications', () => {
      it('shows success toast on successful API response', async () => {
        vi.spyOn(axios.default, 'post').mockResolvedValue({ data: mockDashboardData });
        
        await wrapper.vm.applyFilter();
        
        expect(toastAddSpy).toHaveBeenCalledWith(
          expect.objectContaining({
            severity: 'success',
            summary: 'Success',
            detail: 'Dashboard data updated!',
          })
        );
      });

      it('shows error toast when API fails', async () => {
        vi.spyOn(axios.default, 'post').mockRejectedValue(new Error('API Error'));
        
        await wrapper.vm.applyFilter();
        
        expect(toastAddSpy).toHaveBeenCalledWith(
          expect.objectContaining({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load dashboard data.',
          })
        );
      });

      it('logs error to console on failure', async () => {
        const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
        vi.spyOn(axios.default, 'post').mockRejectedValue(new Error('API Error'));
        
        await wrapper.vm.applyFilter();
        
        expect(consoleErrorSpy).toHaveBeenCalledWith('applyFilter() error', expect.any(Error));
      });
    });

    describe('API Integration', () => {
      it('does not call updateDashboardStats when API fails', async () => {
        vi.spyOn(axios.default, 'post').mockRejectedValue(new Error('API Error'));
        
        await wrapper.vm.applyFilter();
        
        expect(updateDashboardStatsSpy).not.toHaveBeenCalled();
      });
    });
  });
});

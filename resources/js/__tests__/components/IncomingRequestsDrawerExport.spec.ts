import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import PrimeVue from 'primevue/config';
import IncomingRequestsDrawer from '@/Components/IncomingRequestsDrawer.vue';
import axios from 'axios';
import { exportToCSV } from '@/Utils/csvExport';

vi.mock('axios');
vi.mock('@/Utils/csvExport', () => ({
  exportToCSV: vi.fn(),
}));

const toastAdd = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({
    props: { auth: { user: { role: 'OWNER' } } },
  }),
}));

vi.mock('primevue/usetoast', () => ({
  useToast: () => ({ add: toastAdd }),
}));

vi.mock('primevue/usedialog', () => ({
  useDialog: () => ({ open: vi.fn() }),
}));

const SidebarStub = {
  name: 'Sidebar',
  props: ['visible'],
  template: '<div><slot v-if="visible" /></div>',
};

const DialogStub = {
  name: 'Dialog',
  props: ['visible'],
  template: '<div><slot v-if="visible" /></div>',
};

const ButtonStub = {
  name: 'Button',
  props: ['label', 'icon'],
  template: '<button :data-label="label" @click="$emit(\'click\')"><slot /></button>',
};

const AppendStub = {
  name: 'AppendIncomingRequestToSale',
  template: '<div />',
};

async function flushPromises() {
  await Promise.resolve();
  await Promise.resolve();
}

describe('IncomingRequestsDrawer Export', () => {
  const mockRequests = [
    {
      id: 1,
      name: 'John Doe',
      store: 'Main Store',
      items: [
        { id: 101, model: 'iPhone 13', selling_price: 500, currency: 'USD' }
      ]
    },
    {
      id: 2,
      name: 'Jane Smith',
      store: 'Side Store',
      items: [
        { id: 102, model: 'iPhone 14', selling_price: 600, currency: 'CAD' }
      ]
    }
  ];

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(axios.get).mockResolvedValue({ data: mockRequests } as any);
  });

  function mountDrawer() {
    return mount(IncomingRequestsDrawer, {
      global: {
        plugins: [PrimeVue],
        stubs: {
          Sidebar: SidebarStub,
          Dialog: DialogStub,
          Button: ButtonStub,
          Toast: true,
          AppendIncomingRequestToSale: AppendStub
        }
      }
    });
  }

  it('triggers exportRequestToCSV when "Export to CSV" is clicked for a single request', async () => {
    const wrapper = mountDrawer();

    // Open drawer
    await wrapper.findAll('button')[0].trigger('click');
    await flushPromises();

    // Open first request
    await wrapper.find('.request-row').trigger('click');
    await flushPromises();

    const exportButton = wrapper.find('button[data-label="Export to CSV"]');
    expect(exportButton.exists()).toBe(true);

    await exportButton.trigger('click');

    expect(exportToCSV).toHaveBeenCalledWith(
      mockRequests[0].items,
      expect.objectContaining({
        filenamePrefix: 'request_1'
      })
    );
  });

  it('triggers exportAllToCSV when "Export All" is clicked', async () => {
    const wrapper = mountDrawer();

    // Open drawer
    await wrapper.findAll('button')[0].trigger('click');
    await flushPromises();

    const exportAllButton = wrapper.find('button[data-label="Export All"]');
    expect(exportAllButton.exists()).toBe(true);

    await exportAllButton.trigger('click');

    expect(exportToCSV).toHaveBeenCalledWith(
      expect.arrayContaining([
        expect.objectContaining({ id: 101, request_name: 'John Doe' }),
        expect.objectContaining({ id: 102, request_name: 'Jane Smith' })
      ]),
      expect.objectContaining({
        filenamePrefix: 'all_pending_requests'
      })
    );
  });
});


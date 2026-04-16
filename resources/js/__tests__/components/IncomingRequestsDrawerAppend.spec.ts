import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import IncomingRequestsDrawer from '@/Components/IncomingRequestsDrawer.vue';
import axios from 'axios';

vi.mock('axios');

const toastAdd = vi.fn();
const dialogOpen = vi.fn();

const mockUserRef = ref({
  role: 'OWNER',
  page_permissions: null,
});

vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({
    props: {
      auth: {
        user: mockUserRef.value,
      },
    },
  }),
}));

vi.mock('primevue/usetoast', () => ({
  useToast: () => ({ add: toastAdd }),
}));

vi.mock('primevue/usedialog', () => ({
  useDialog: () => ({ open: dialogOpen }),
}));

const AppendIncomingRequestToSaleStub = {
  name: 'AppendIncomingRequestToSale',
  props: ['visible', 'submitting'],
  emits: ['update:visible', 'submit'],
  template: '<div data-test="append-modal-stub" />',
};

const SidebarStub = {
  name: 'Sidebar',
  props: ['visible'],
  emits: ['update:visible'],
  template: '<div><slot /></div>',
};

const DialogStub = {
  name: 'Dialog',
  props: ['visible'],
  emits: ['update:visible'],
  template: '<div><slot /></div>',
};

const ButtonStub = {
  name: 'Button',
  props: ['label', 'icon', 'severity'],
  emits: ['click'],
  template: '<button :data-label="label" @click="$emit(\'click\')"><slot /></button>',
};

async function flushPromises() {
  await Promise.resolve();
  await Promise.resolve();
}

function buildIncomingRequestResponse() {
  return [
    {
      id: 101,
      name: 'Requester',
      email: 'requester@example.com',
      store: 'Main',
      created_at: '2026-04-14 10:00:00',
      items: [
        {
          id: 9001,
          original_item_id: 4001,
          model: 'iPhone 15',
          manufacturer: 'Apple',
          imei: '123',
          selling_price: 100,
          cost: 50,
          currency: 'CAD',
        },
      ],
      shipping: {
        label: 'Express',
        value: 10,
      },
    },
  ];
}

function mountDrawer() {
  return mount(IncomingRequestsDrawer, {
    global: {
      stubs: {
        Sidebar: SidebarStub,
        Dialog: DialogStub,
        Button: ButtonStub,
        Toast: true,
        AppendIncomingRequestToSale: AppendIncomingRequestToSaleStub,
      },
    },
  });
}

describe('IncomingRequestsDrawer append CTA behavior', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUserRef.value = { role: 'OWNER', page_permissions: null };

    vi.mocked(axios.get).mockResolvedValue({ data: buildIncomingRequestResponse() } as any);
    vi.mocked(axios.post).mockResolvedValue({
      data: {
        counts: { appended: 1, skipped: 0, pending: 0 },
        state_transition: 'full',
        idempotent_replay: false,
      },
    } as any);
  });

  it('shows append CTA for authorized user and wires submit payload to append API', async () => {
    const wrapper = mountDrawer();

    await wrapper.findAll('button')[0].trigger('click');
    await flushPromises();

    await wrapper.find('.request-row').trigger('click');
    await flushPromises();

    const appendButton = wrapper.find('button[data-label="Add to existing invoice"]');
    expect(appendButton.exists()).toBe(true);

    await appendButton.trigger('click');

    const modal = wrapper.findComponent(AppendIncomingRequestToSaleStub);
    expect(modal.exists()).toBe(true);

    await modal.vm.$emit('submit', {
      sale_id: 777,
      idempotency_key: 'idem-append-ui-1',
    });

    await flushPromises();

    expect(axios.post).toHaveBeenCalledWith(
      expect.stringContaining('/items.incomingRequests.appendToInvoice'),
      {
        sale_id: 777,
        idempotency_key: 'idem-append-ui-1',
      }
    );
  });

  it('hides append CTA for unauthorized user while keeping create invoice visible', async () => {
    mockUserRef.value = {
      role: 'USER',
      page_permissions: { Inventory: ['Sold'] },
    };

    const wrapper = mountDrawer();

    await wrapper.findAll('button')[0].trigger('click');
    await flushPromises();

    await wrapper.find('.request-row').trigger('click');
    await flushPromises();

    expect(wrapper.find('button[data-label="Add to existing invoice"]').exists()).toBe(false);
    expect(wrapper.find('button[data-label="Create invoice"]').exists()).toBe(true);
  });
});

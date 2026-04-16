import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import axios from 'axios';
import AppendIncomingRequestToSale from '@/Pages/Inventory/Modals/AppendIncomingRequestToSale.vue';

vi.mock('axios');

const DropdownStub = {
  name: 'Dropdown',
  props: ['modelValue', 'options', 'loading', 'disabled'],
  emits: ['update:modelValue', 'filter'],
  template: '<div><input data-test="filter" @input="$emit(\'filter\', { filter: $event.target.value })" /></div>',
};

const ButtonStub = {
  name: 'Button',
  props: ['label', 'disabled'],
  emits: ['click'],
  template: '<button :data-label="label" :disabled="disabled" @click="$emit(\'click\')" />',
};

const DialogStub = {
  name: 'Dialog',
  props: ['visible'],
  emits: ['update:visible'],
  template: '<div><slot /><slot name="footer" /></div>',
};

function mountModal(visible = true) {
  return mount(AppendIncomingRequestToSale, {
    props: {
      visible,
      submitting: false,
    },
    global: {
      stubs: {
        Dialog: DialogStub,
        Dropdown: DropdownStub,
        Button: ButtonStub,
      },
    },
  });
}

async function flushPromises() {
  await Promise.resolve();
  await Promise.resolve();
}

function deferred<T>() {
  let resolve!: (value: T) => void;
  let reject!: (reason?: unknown) => void;
  const promise = new Promise<T>((res, rej) => {
    resolve = res;
    reject = rej;
  });

  return { promise, resolve, reject };
}

describe('AppendIncomingRequestToSale modal', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    vi.mocked(axios.get).mockResolvedValue({
      data: [
        { sale_id: 101, date: '2026-04-14T10:00:00Z', customer: 'John Doe', total: 500 },
      ],
    } as any);

    vi.stubGlobal('crypto', {
      randomUUID: vi.fn(() => 'uuid-append-1'),
    });
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
  });

  it('loads invoices when opened and debounces search by 300ms', async () => {
    vi.useFakeTimers();

    const wrapper = mountModal(false);
    await wrapper.setProps({ visible: true });
    await flushPromises();

    expect(axios.get).toHaveBeenCalledWith(
      expect.stringContaining('/payments.simpleList'),
      expect.objectContaining({ params: expect.objectContaining({ q: '', limit: 25 }) })
    );

    vi.mocked(axios.get).mockClear();

    await wrapper.find('[data-test="filter"]').setValue('john');
    vi.advanceTimersByTime(299);
    expect(axios.get).not.toHaveBeenCalled();

    vi.advanceTimersByTime(1);
    await flushPromises();

    expect(axios.get).toHaveBeenCalledWith(
      expect.stringContaining('/payments.simpleList'),
      expect.objectContaining({ params: expect.objectContaining({ q: 'john', limit: 25 }) })
    );
  });

  it('emits submit with selected sale_id and generated idempotency_key', async () => {
    const wrapper = mountModal(true);
    await flushPromises();

    const dropdown = wrapper.findComponent(DropdownStub);
    await dropdown.vm.$emit('update:modelValue', 101);
    await flushPromises();

    const addButton = wrapper.find('button[data-label="Add to invoice"]');
    expect(addButton.attributes('disabled')).toBeUndefined();

    await addButton.trigger('click');

    expect(wrapper.emitted('submit')).toEqual([
      [
        {
          sale_id: 101,
          idempotency_key: 'uuid-append-1',
        },
      ],
    ]);
  });

  it('stays idle without selection and does not request preview', async () => {
    const wrapper = mountModal(false);
    await wrapper.setProps({ visible: true });
    await flushPromises();

    expect(wrapper.find('[data-test="preview-idle"]').exists()).toBe(true);
    expect(axios.get).toHaveBeenCalledTimes(1);
    expect(vi.mocked(axios.get).mock.calls[0][0]).toContain('/payments.simpleList');
  });

  it('shows loading then loaded preview with max four items and overflow hint', async () => {
    const previewDeferred = deferred<any>();

    vi.mocked(axios.get)
      .mockResolvedValueOnce({
        data: [{ sale_id: 101, date: '2026-04-14T10:00:00Z', customer: 'John Doe', total: 500 }],
      } as any)
      .mockImplementationOnce(() => previewDeferred.promise as any);

    const wrapper = mountModal(false);
    await wrapper.setProps({ visible: true });
    await flushPromises();

    const dropdown = wrapper.findComponent(DropdownStub);
    await dropdown.vm.$emit('update:modelValue', 101);
    await flushPromises();

    expect(wrapper.find('[data-test="preview-loading"]').exists()).toBe(true);

    previewDeferred.resolve({
      data: {
        sale_id: 101,
        customer: 'John Doe',
        date: '2026-04-14',
        paid_status: 'Paid',
        total_items: 8,
        items_preview: [
          { item_id: 1, label: 'A', type: 'device', identifier: 'I1', selling_price: 10, currency: 'CAD' },
          { item_id: 2, label: 'B', type: 'device', identifier: 'I2', selling_price: 20, currency: 'CAD' },
          { item_id: 3, label: 'C', type: 'device', identifier: 'I3', selling_price: 30, currency: 'CAD' },
          { item_id: 4, label: 'D', type: 'device', identifier: 'I4', selling_price: 40, currency: 'CAD' },
          { item_id: 5, label: 'E', type: 'device', identifier: 'I5', selling_price: 50, currency: 'CAD' },
        ],
      },
    });
    await flushPromises();

    expect(wrapper.findAll('[data-test="preview-item"]').length).toBe(4);
    expect(wrapper.text()).toContain('View full invoice for full details');
    expect(vi.mocked(axios.get).mock.calls[1][0]).toContain('/items.incomingRequests.invoices.preview');
  });

  it('renders empty state when selected invoice has no sold items', async () => {
    vi.mocked(axios.get)
      .mockResolvedValueOnce({
        data: [{ sale_id: 101, date: '2026-04-14T10:00:00Z', customer: 'John Doe', total: 500 }],
      } as any)
      .mockResolvedValueOnce({
        data: {
          sale_id: 101,
          customer: 'John Doe',
          date: '2026-04-14',
          paid_status: 'Paid',
          total_items: 0,
          items_preview: [],
        },
      } as any);

    const wrapper = mountModal(false);
    await wrapper.setProps({ visible: true });
    await flushPromises();

    await wrapper.findComponent(DropdownStub).vm.$emit('update:modelValue', 101);
    await flushPromises();

    expect(wrapper.find('[data-test="preview-empty"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('No sold items to preview');
  });

  it('renders error state and retries same sale id without losing selection', async () => {
    vi.mocked(axios.get)
      .mockResolvedValueOnce({
        data: [{ sale_id: 101, date: '2026-04-14T10:00:00Z', customer: 'John Doe', total: 500 }],
      } as any)
      .mockRejectedValueOnce(new Error('preview failed'))
      .mockResolvedValueOnce({
        data: {
          sale_id: 101,
          customer: 'John Doe',
          date: '2026-04-14',
          paid_status: 'Paid',
          total_items: 1,
          items_preview: [
            { item_id: 1, label: 'A', type: 'device', identifier: 'I1', selling_price: 10, currency: 'CAD' },
          ],
        },
      } as any);

    const wrapper = mountModal(false);
    await wrapper.setProps({ visible: true });
    await flushPromises();

    await wrapper.findComponent(DropdownStub).vm.$emit('update:modelValue', 101);
    await flushPromises();

    expect(wrapper.find('[data-test="preview-error"]').exists()).toBe(true);

    const retry = wrapper.find('button[data-label="Retry preview"]');
    await retry.trigger('click');
    await flushPromises();

    expect(wrapper.findAll('[data-test="preview-item"]').length).toBe(1);
    expect(vi.mocked(axios.get).mock.calls[1][0]).toContain('/items.incomingRequests.invoices.preview');
    expect(vi.mocked(axios.get).mock.calls[2][0]).toContain('/items.incomingRequests.invoices.preview');
  });

  it('maps 403 preview error to access message and keeps selection for retry', async () => {
    vi.mocked(axios.get)
      .mockResolvedValueOnce({
        data: [{ sale_id: 101, date: '2026-04-14T10:00:00Z', customer: 'John Doe', total: 500 }],
      } as any)
      .mockRejectedValueOnce({ response: { status: 403 } });

    const wrapper = mountModal(false);
    await wrapper.setProps({ visible: true });
    await flushPromises();

    await wrapper.findComponent(DropdownStub).vm.$emit('update:modelValue', 101);
    await flushPromises();

    expect(wrapper.find('[data-test="preview-error"]').exists()).toBe(true);
    expect(wrapper.text()).toContain("You don’t have access to preview this invoice");

    const retry = wrapper.find('button[data-label="Retry preview"]');
    expect(retry.attributes('disabled')).toBeUndefined();
  });

  it('maps 404 preview error to invoice not found message', async () => {
    vi.mocked(axios.get)
      .mockResolvedValueOnce({
        data: [{ sale_id: 101, date: '2026-04-14T10:00:00Z', customer: 'John Doe', total: 500 }],
      } as any)
      .mockRejectedValueOnce({ response: { status: 404 } });

    const wrapper = mountModal(false);
    await wrapper.setProps({ visible: true });
    await flushPromises();

    await wrapper.findComponent(DropdownStub).vm.$emit('update:modelValue', 101);
    await flushPromises();

    expect(wrapper.find('[data-test="preview-error"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Invoice not found');
  });

  it('maps 401 preview error to session expired message', async () => {
    vi.mocked(axios.get)
      .mockResolvedValueOnce({
        data: [{ sale_id: 101, date: '2026-04-14T10:00:00Z', customer: 'John Doe', total: 500 }],
      } as any)
      .mockRejectedValueOnce({ response: { status: 401 } });

    const wrapper = mountModal(false);
    await wrapper.setProps({ visible: true });
    await flushPromises();

    await wrapper.findComponent(DropdownStub).vm.$emit('update:modelValue', 101);
    await flushPromises();

    expect(wrapper.find('[data-test="preview-error"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Session expired. Please sign in again.');
  });

  it('switching A to B resets state and never shows stale A as loaded B', async () => {
    const secondPreviewDeferred = deferred<any>();

    vi.mocked(axios.get)
      .mockResolvedValueOnce({
        data: [
          { sale_id: 101, date: '2026-04-14T10:00:00Z', customer: 'John Doe', total: 500 },
          { sale_id: 102, date: '2026-04-13T10:00:00Z', customer: 'Jane Doe', total: 600 },
        ],
      } as any)
      .mockResolvedValueOnce({
        data: {
          sale_id: 101,
          customer: 'John Doe',
          date: '2026-04-14',
          paid_status: 'Paid',
          total_items: 1,
          items_preview: [
            { item_id: 1, label: 'A-Only', type: 'device', identifier: 'A1', selling_price: 10, currency: 'CAD' },
          ],
        },
      } as any)
      .mockImplementationOnce(() => secondPreviewDeferred.promise as any);

    const wrapper = mountModal(false);
    await wrapper.setProps({ visible: true });
    await flushPromises();

    const dropdown = wrapper.findComponent(DropdownStub);
    await dropdown.vm.$emit('update:modelValue', 101);
    await flushPromises();
    expect(wrapper.text()).toContain('A-Only');

    await dropdown.vm.$emit('update:modelValue', 102);
    await flushPromises();
    expect(wrapper.find('[data-test="preview-loading"]').exists()).toBe(true);
    expect(wrapper.text()).not.toContain('A-Only');

    secondPreviewDeferred.resolve({
      data: {
        sale_id: 102,
        customer: 'Jane Doe',
        date: '2026-04-13',
        paid_status: 'Paid',
        total_items: 1,
        items_preview: [
          { item_id: 2, label: 'B-Only', type: 'device', identifier: 'B1', selling_price: 20, currency: 'CAD' },
        ],
      },
    });

    await flushPromises();
    expect(wrapper.text()).toContain('B-Only');
  });

  it('clearing selection resets preview to idle state', async () => {
    vi.mocked(axios.get)
      .mockResolvedValueOnce({
        data: [{ sale_id: 101, date: '2026-04-14T10:00:00Z', customer: 'John Doe', total: 500 }],
      } as any)
      .mockResolvedValueOnce({
        data: {
          sale_id: 101,
          customer: 'John Doe',
          date: '2026-04-14',
          paid_status: 'Paid',
          total_items: 1,
          items_preview: [
            { item_id: 1, label: 'A-Only', type: 'device', identifier: 'A1', selling_price: 10, currency: 'CAD' },
          ],
        },
      } as any);

    const wrapper = mountModal(false);
    await wrapper.setProps({ visible: true });
    await flushPromises();

    const dropdown = wrapper.findComponent(DropdownStub);
    await dropdown.vm.$emit('update:modelValue', 101);
    await flushPromises();
    expect(wrapper.findAll('[data-test="preview-item"]').length).toBe(1);

    await dropdown.vm.$emit('update:modelValue', null);
    await flushPromises();

    expect(wrapper.find('[data-test="preview-idle"]').exists()).toBe(true);
    expect(wrapper.find('[data-test="preview-loaded"]').exists()).toBe(false);
  });

  it('does not render post-append estimation or customer mismatch notice in preview', async () => {
    vi.mocked(axios.get)
      .mockResolvedValueOnce({
        data: [{ sale_id: 101, date: '2026-04-14T10:00:00Z', customer: 'John Doe', total: 500 }],
      } as any)
      .mockResolvedValueOnce({
        data: {
          sale_id: 101,
          customer: 'John Doe',
          date: '2026-04-14',
          paid_status: 'Paid',
          total_items: 1,
          items_preview: [
            { item_id: 1, label: 'A-Only', type: 'device', identifier: 'A1', selling_price: 10, currency: 'CAD' },
          ],
        },
      } as any);

    const wrapper = mountModal(false);
    await wrapper.setProps({ visible: true });
    await flushPromises();

    await wrapper.findComponent(DropdownStub).vm.$emit('update:modelValue', 101);
    await flushPromises();

    expect(wrapper.text()).not.toContain('post-append');
    expect(wrapper.text()).not.toContain('customer mismatch');
    expect(wrapper.text()).not.toContain('Estimated total after append');
  });
});

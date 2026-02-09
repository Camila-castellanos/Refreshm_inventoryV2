import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import MarketEdit from '@/Pages/Ecommerce/MarketEdit.vue';
import PrimeVue from 'primevue/config';
import { nextTick, reactive } from 'vue';

// Mock Inertia useForm with transform support
const mockPost = vi.fn();
const mockTransform = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
  Link: { template: '<a><slot /></a>' },
  useForm: (initialData: any) => {
    const form = reactive({
      ...initialData,
      post: mockPost,
      processing: false,
      errors: {},
      reset: vi.fn(),
      transform: (callback: Function) => {
        // Apply transform callback to data
        const transformedData = callback(form);
        // Return object with post method that uses transformed data (simulated)
        return {
          post: (url: string, options: any) => {
            mockPost(url, { ...options, data: transformedData });
          }
        };
      }
    });
    return form;
  }
}));

// Route Mock
const routeMock = vi.fn((name, params) => `/${name}/${params}`);
global.route = routeMock as any;

describe('Ecommerce/MarketEdit.vue', () => {
  let wrapper: VueWrapper;

  const mockMarket = {
    id: 123,
    name: 'Existing Market',
    slug: 'existing-market',
    shop_id: 1,
    currency: 'EUR',
    description: 'Old Description',
    tagline: 'Old Tagline',
    show_inventory_count: true,
    is_active: true,
    media_banners: [
      { id: 10, url: 'banner1.jpg', thumb: 'thumb1.jpg' },
      { id: 11, url: 'banner2.jpg', thumb: 'thumb2.jpg' }
    ],
    faq: {
      title: 'FAQ',
      questions: [
        { id: 'q1', question: 'Q1?', answer: 'A1', order: 1 },
        { id: 'q2', question: 'Q2?', answer: 'A2', order: 2 }
      ]
    }
  };

  const mockShops = [{ id: 1, name: 'Main Shop' }];

  const createWrapper = () => {
    return mount(MarketEdit, {
      props: {
        market: mockMarket,
        shops: mockShops,
        appUrl: 'http://test.app'
      },
      global: {
        plugins: [PrimeVue],
        mocks: {
          route: routeMock
        },
        stubs: {
          AppLayout: { template: '<div><slot /><slot name="header" /></div>' },
          Button: { template: '<button type="button" @click="$emit(\'click\')"><slot />{{ label }}</button>', props: ['label'] },
          InputText: { 
            props: ['modelValue', 'id'], 
            emits: ['update:modelValue'],
            template: '<input :id="id" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />' 
          },
          Textarea: { 
            props: ['modelValue', 'id'], 
            emits: ['update:modelValue'],
            template: '<textarea :id="id" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />' 
          },
          Dropdown: { template: '<select></select>' },
          Checkbox: { template: '<input type="checkbox" />' }
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
    // Reset URL mocks
    global.URL.createObjectURL = vi.fn(() => 'blob:new-image');
    global.URL.revokeObjectURL = vi.fn();
  });

  describe('Initialization', () => {
    it('populates form with market data', () => {
      wrapper = createWrapper();
      
      const vm = wrapper.vm as any;
      expect(vm.form.name).toBe('Existing Market');
      expect(vm.form.currency).toBe('EUR');
      expect(vm.existingBanners).toHaveLength(2);
      expect(vm.form.faq.questions).toHaveLength(2);
    });
  });

  describe('Banner Management', () => {
    it('renders existing banners', () => {
      wrapper = createWrapper();
      const images = wrapper.findAll('img');
      // Should find at least 2 images (banners)
      expect(images.length).toBeGreaterThanOrEqual(2);
      expect(images[0].attributes('src')).toBe('thumb1.jpg');
    });

    it('marks banner for deletion when removed', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Initial state
      expect(vm.existingBanners).toHaveLength(2);
      expect(vm.form.deleted_banners).toHaveLength(0);
      
      // Trigger removal of first banner (ID 10)
      vm.markBannerForDeletion(10);
      await nextTick();
      
      // Check state
      expect(vm.existingBanners).toHaveLength(1);
      expect(vm.form.deleted_banners).toContain(10);
    });

    it('handles new banner upload', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Simulate file upload logic directly as Input file is hard to trigger
      const file = new File([''], 'new.jpg', { type: 'image/jpeg' });
      const event = { target: { files: [file], value: '' } };
      
      vm.handleBannerUpload(event);
      
      expect(vm.newBanners).toHaveLength(1);
      expect(vm.form.banners).toHaveLength(1);
      expect(URL.createObjectURL).toHaveBeenCalledWith(file);
    });
  });

  describe('FAQ Management', () => {
    it('adds a new question', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      expect(vm.form.faq.questions).toHaveLength(2);
      
      vm.addQuestion();
      
      expect(vm.form.faq.questions).toHaveLength(3);
      expect(vm.form.faq.questions[2].question).toBe('');
    });

    it('removes a question', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      vm.removeQuestion(0); // Remove first question
      
      expect(vm.form.faq.questions).toHaveLength(1);
      expect(vm.form.faq.questions[0].id).toBe('q2'); // Q2 remains
    });

    it('reorders questions (move up)', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Move Q2 (index 1) Up
      vm.moveQuestionUp(1);
      
      expect(vm.form.faq.questions[0].id).toBe('q2');
      expect(vm.form.faq.questions[1].id).toBe('q1');
    });
  });

  describe('Form Submission', () => {
    it('submits form with PUT method spoofing', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Modify a field
      vm.form.name = 'Updated Market Name';
      
      // Trigger submit
      await wrapper.find('form').trigger('submit.prevent');
      
      // Check if post was called (via the transformed object return)
      expect(mockPost).toHaveBeenCalledWith(
        expect.stringContaining('ecommerce.markets.update/123'),
        expect.objectContaining({
          data: expect.objectContaining({
            name: 'Updated Market Name',
            _method: 'PUT' // Critical check
          })
        })
      );
    });
  });
});

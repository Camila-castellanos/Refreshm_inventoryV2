import { ref, shallowRef } from 'vue';

const isOpen = ref(false); 
const modalComponent = shallowRef<null | any>(null);
const modalProps = ref<Record<string, any>>({});

const open = (component: any, props = {}) => {
    modalComponent.value = component;
    modalProps.value = props;
    isOpen.value = true;
};

const close = () => {
    isOpen.value = false;
    modalComponent.value = null;
    modalProps.value = {};
};

export function useModalService() {
    return {
        isOpen,
        modalComponent,
        modalProps,
        open,
        close
    };
}

import { ref, Ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { useDialog } from 'primevue/usedialog';
import axios from 'axios';
import { Item, Tab as ITab } from '@/Lib/types';
import ItemsSell from '@/Pages/Inventory/Modals/ItemsSell.vue';
import MoveItem from '@/Pages/Inventory/Modals/MoveItem.vue';

export function useInventoryActions(
  selectedItems: Ref<Item[]>,
  tabs: ITab[],
  assignStorageRef?: Ref<any>,
  onRefreshTable?: () => void
) {
  const confirm = useConfirm();
  const toast = useToast();
  const dialog = useDialog();

  const toggleAssignStorageVisible = () => {
    if (assignStorageRef?.value) {
      assignStorageRef.value.openDialog();
    }
  };

  const openSellItemsModal = () => {
    console.log('Opening ItemsSell dialog with items:', selectedItems.value);
    dialog.open(ItemsSell, {
      data: {
        items: selectedItems,
      },
      props: {
        modal: true,
      },
      onClose: (result) => {
        if (result?.data?.sold) {
          console.log('Items sold successfully');
          selectedItems.value.length = 0;
          router.reload({ only: ['items'] });
        }
      },
    });
  };

  const openMoveItemsModal = () => {
    dialog.open(MoveItem, {
      data: {
        tabs: tabs,
        items: selectedItems.value,
      },
      props: {
        modal: true,
        header: 'Move items',
      },
      onClose: () => {
        router.reload({ only: ['items'] });
      },
    });
  };

  const onEdit = () => {
    const currentPaginate = document.getElementById('currentPaginate')?.getAttribute('data-id') || '';
    const filter = document.getElementsByClassName('filter--value')[0]?.value || '';

    document.cookie = `paginate=${currentPaginate}`;
    document.cookie = `pagefilter=${filter}`;

    let items = selectedItems.value.map((item: any) => item.id).join(';');

    router.get(route('items.edit', btoa(items)));
  };

  const onDeleteMultiple = () => {
    confirm.require({
      message: 'Are you sure? You won\'t be able to revert this!',
      header: 'Delete Confirmation',
      icon: 'pi pi-exclamation-triangle',
      accept: async () => {
        try {
          const response = await axios.delete(route('items.obliterate'), { data: selectedItems.value });
          if (response.status >= 200 && response.status < 400) {
            toast.add({ severity: 'success', summary: 'Deleted', detail: 'Items deleted successfully', life: 3000 });
            location.reload();
          }
        } catch (error: any) {
          toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data || error.message || 'An error occurred',
            life: 5000,
          });
        }
      },
    });
  };

  const openLabels = async () => {
    if (selectedItems.value.length === 0) return;
    const itemsParam = selectedItems.value.map(item => item.id).join(',');
    const res = await axios.get(route('items.labels', { items: itemsParam }), {
      responseType: 'blob'
    });
    const blob = new Blob([res.data], { type: 'application/pdf' });
    const url = URL.createObjectURL(blob);
    window.open(url, '_blank');
    URL.revokeObjectURL(url);
  };

  const exportToCSV = async () => {
    if (selectedItems.value.length === 0) {
      toast.add({ severity: 'warn', summary: 'Warning', detail: 'No items selected', life: 3000 });
      return;
    }
    
    try {
      const headers = ['ID', 'Model', 'Manufacturer', 'Color', 'Grade', 'Battery', 'Issues', 'Price'];
      const rows = selectedItems.value.map(item => [
        item.id,
        item.model,
        item.manufacturer,
        item.colour,
        item.grade,
        item.battery,
        item.issues,
        item.selling_price
      ]);
      
      const csv = [headers, ...rows].map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      
      link.setAttribute('href', url);
      link.setAttribute('download', `items_${new Date().toISOString().split('T')[0]}.csv`);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      
      toast.add({ severity: 'success', summary: 'Success', detail: 'Items exported to CSV', life: 3000 });
    } catch (error) {
      toast.add({ severity: 'error', summary: 'Error', detail: 'Could not export items', life: 3000 });
    }
  };

  const duplicateItems = async () => {
    if (selectedItems.value.length === 0) {
      toast.add({ severity: 'warn', summary: 'Warning', detail: 'No items selected', life: 3000 });
      return;
    }
    
    try {
      const response = await axios.post(route('items.duplicate'), { items: selectedItems.value });
      toast.add({ severity: 'success', summary: 'Success', detail: `${response.data.count} items duplicated`, life: 3000 });
      router.reload({ only: ['items'] });
    } catch (error: any) {
      toast.add({ 
        severity: 'error', 
        summary: 'Error', 
        detail: error.response?.data?.message || 'Could not duplicate items', 
        life: 3000 
      });
    }
  };

  const getDefaultTableActions = (options: {
    showAddItems?: boolean;
    showCustomFields?: () => void;
    showAddItemsToSale?: () => void;
    hideExport?: boolean;
    hideDuplicate?: boolean;
    customActions?: Array<any & { position?: 'start' | 'end' | number }>;
  } = {}) => {
    const baseActions = [
      {
        label: 'Add Items',
        important: true,
        icon: 'pi pi-plus',
        action: () => {
          router.visit('/inventory/items/excel/create');
        },
      },
      {
        label: 'Sell',
        icon: 'pi pi-dollar',
        important: true,
        action: () => {
          openSellItemsModal();
        },
      },
      {
        label: 'Edit',
        icon: 'pi pi-pencil',
        important: true,
        action: () => {
          onEdit();
        },
        disable: (selectedItems: Item[]) => selectedItems.length === 0,
      },
      {
        label: 'Edit fields',
        icon: 'pi pi-pen-to-square',
        action: () => {
          options.showCustomFields?.();
        },
      },
      {
        label: 'Delete selected',
        icon: 'pi pi-trash',
        severity: 'danger',
        important: true,
        action: () => {
          onDeleteMultiple();
        },
        disable: (selectedItems: Item[]) => selectedItems.length == 0,
      },
      {
        label: 'Reassign location',
        icon: 'pi pi-arrow-up',
        action: () => {
          toggleAssignStorageVisible();
        },
        disable: (selectedItems: Item[]) => selectedItems.length == 0,
      },
      {
        label: 'Move Tab',
        icon: 'pi pi-arrow-right-arrow-left',
        extraClasses: '!font-black',
        action: () => {
          openMoveItemsModal();
        },
        disable: (selectedItems: Item[]) => selectedItems.length == 0,
      },
      {
        label: 'Print Items Labels',
        icon: 'pi pi-print',
        action: () => openLabels(),
        disable: (selectedItems: Item[]) => selectedItems.length == 0,
      },
    ];

    // Agregar Export si no está oculto
    if (!options.hideExport) {
      baseActions.push({
        label: 'Export to CSV',
        icon: 'pi pi-download',
        action: () => exportToCSV(),
        disable: (selectedItems: Item[]) => selectedItems.length == 0,
      });
    }

    // Agregar Duplicate si no está oculto
    if (!options.hideDuplicate) {
      baseActions.push({
        label: 'Duplicate Items',
        icon: 'pi pi-copy',
        action: () => duplicateItems(),
        disable: (selectedItems: Item[]) => selectedItems.length == 0,
      });
    }

    // Agregar Add to Invoice si está habilitado
    if (options.showAddItemsToSale) {
      baseActions.push({
        label: 'Add To Existing Invoice',
        icon: 'pi pi-credit-card',
        action: () => {
          options.showAddItemsToSale?.();
        },
        disable: (selectedItems: Item[]) => selectedItems.length == 0,
      });
    }

    // Procesar acciones personalizadas con posición
    if (options.customActions && Array.isArray(options.customActions)) {
      const startActions: any[] = [];
      const endActions: any[] = [];
      const indexedActions: Array<{index: number, action: any}> = [];

      options.customActions.forEach(action => {
        const { position, ...cleanAction } = action;
        
        if (position === 'start') {
          startActions.push(cleanAction);
        } else if (typeof position === 'number') {
          indexedActions.push({ index: position, action: cleanAction });
        } else {
          // 'end' o undefined por defecto
          endActions.push(cleanAction);
        }
      });

      // Agregar al inicio
      if (startActions.length > 0) {
        baseActions.unshift(...startActions);
      }

      // Agregar al final primero
      if (endActions.length > 0) {
        baseActions.push(...endActions);
      }

      // Agregar acciones con índice específico (en orden descendente para no afectar índices)
      indexedActions
        .sort((a, b) => b.index - a.index)
        .forEach(({ index, action }) => {
          if (index >= 0 && index <= baseActions.length) {
            baseActions.splice(index, 0, action);
          }
        });
    }

    return baseActions;
  };

  return {
    toggleAssignStorageVisible,
    openSellItemsModal,
    openMoveItemsModal,
    onEdit,
    onDeleteMultiple,
    openLabels,
    exportToCSV,
    duplicateItems,
    getDefaultTableActions,
  };
}

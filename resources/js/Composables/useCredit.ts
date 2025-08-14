import { ref, computed, Ref } from 'vue';
import { useToast } from 'primevue/usetoast';

interface CreditForm {
  credit: number;
  customer_credit: number;
  tax?: {
    percentage: number;
  };
  removed_credit?: number;
}

export function useCredit(form: Ref<CreditForm>, payment?: Ref<any>) {
  const toast = useToast();
  
  // Estados del diálogo de crédito
  const creditDialogVisible = ref(false);
  const creditInputValue = ref(0);
  const creditDialogMode = ref<'add' | 'edit'>('add');
  const creditAdded = ref(0);

  // Crédito final sin IVA (para almacenamiento)
  const final_credit = computed(() => {
    const credit = parseFloat(form.value.credit?.toString() || '0');
    const removed = parseFloat(form.value.removed_credit?.toString() || '0');
    return (credit - removed).toFixed(2);
  });

  // Crédito final con IVA (para visualización)
  const final_credit_with_tax = computed(() => {
    const credit = parseFloat(form.value.credit?.toString() || '0');
    const removed = parseFloat(form.value.removed_credit?.toString() || '0');
    const taxPct = parseFloat(form.value.tax?.percentage?.toString() || '0');
    const creditWithTax = credit + (credit * taxPct) / 100;
    return (creditWithTax - removed).toFixed(2);
  });

  // Crédito utilizable para agregar
  const usableCredit = computed(() => {
    const customerCreditNum = parseFloat(form.value.customer_credit.toString());
    return customerCreditNum;
  });

  // Función para abrir diálogo de agregar crédito
  const addCredit = (balanceRemaining?: number) => {
    const maxCredit = balanceRemaining 
      ? Math.min(form.value.customer_credit, balanceRemaining)
      : form.value.customer_credit;
      
    if (form.value.customer_credit > 0) {
      creditDialogMode.value = 'add';
      creditInputValue.value = 0;
      creditDialogVisible.value = true;
    }
  };

  // Función para abrir diálogo de editar crédito
  const editCredit = () => {
    creditDialogMode.value = 'edit';
    creditInputValue.value = parseFloat(form.value.credit?.toString() || '0');
    creditDialogVisible.value = true;
  };

  // Confirmar aplicación de crédito
  const confirmCreditApplication = () => {
    if (creditDialogMode.value === 'add') {
      if (creditInputValue.value > 0) {
        // Agregar al crédito existente
        const sum = parseFloat(creditInputValue.value.toString()) + parseFloat(form.value.credit?.toString() || '0');
        form.value.credit = sum;
        
        // Actualizar crédito agregado
        creditAdded.value += creditInputValue.value;
        
        // Reducir crédito del cliente
        form.value.customer_credit = form.value.customer_credit - creditInputValue.value;
        
        toast.add({ 
          severity: "success", 
          summary: "Credit Applied", 
          detail: `$${creditInputValue.value.toFixed(2)} credit has been applied successfully.`, 
          life: 3000 
        });
      }
    } else {
      // Modo edición
      const originalCredit = payment?.value?.credit ? parseFloat(payment.value.credit.toString()) : 0;
      const newCredit = creditInputValue.value;
      
      form.value.credit = newCredit;
      creditAdded.value = newCredit - originalCredit;
      
      toast.add({ 
        severity: "success", 
        summary: "Credit Updated", 
        detail: `Credit has been updated to $${creditInputValue.value.toFixed(2)}.`, 
        life: 3000 
      });
    }
    
    creditDialogVisible.value = false;
  };

  return {
    // Estados reactivos
    creditDialogVisible,
    creditInputValue,
    creditDialogMode,
    creditAdded,
    
    // Computeds
    final_credit,
    final_credit_with_tax,
    usableCredit,
    
    // Métodos
    addCredit,
    editCredit,
    confirmCreditApplication,
  };
}

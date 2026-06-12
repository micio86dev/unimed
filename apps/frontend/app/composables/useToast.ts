export type ToastVariant = 'default' | 'success' | 'error' | 'info'

export interface Toast {
  id: number
  title: string
  description?: string
  variant: ToastVariant
}

let counter = 0

/** Minimal global toast queue rendered by <AppToaster />. */
export function useToast() {
  const toasts = useState<Toast[]>('toasts', () => [])

  function push(toast: Omit<Toast, 'id'>, timeout = 4000) {
    const id = ++counter
    toasts.value = [...toasts.value, { id, ...toast }]
    if (import.meta.client) {
      setTimeout(() => dismiss(id), timeout)
    }
    return id
  }

  function dismiss(id: number) {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  return {
    toasts,
    dismiss,
    toast: (title: string, description?: string) =>
      push({ title, description, variant: 'default' }),
    success: (title: string, description?: string) =>
      push({ title, description, variant: 'success' }),
    error: (title: string, description?: string) => push({ title, description, variant: 'error' }),
    info: (title: string, description?: string) => push({ title, description, variant: 'info' }),
  }
}

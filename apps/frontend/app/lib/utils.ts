import { type ClassValue, clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'

/** Merge Tailwind class lists with conflict resolution (shadcn-vue helper). */
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

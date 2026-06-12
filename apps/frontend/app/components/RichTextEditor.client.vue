<script setup lang="ts">
import StarterKit from '@tiptap/starter-kit'
import { useEditor } from '@tiptap/vue-3'
import { Bold, Italic, List, ListOrdered } from 'lucide-vue-next'

const props = defineProps<{ modelValue?: string | null; placeholder?: string }>()
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const editor = useEditor({
  content: props.modelValue ?? '',
  extensions: [StarterKit],
  editorProps: {
    attributes: {
      class: 'prose-question min-h-[120px] w-full px-3.5 py-2.5 text-sm focus:outline-none',
    },
  },
  onUpdate: ({ editor }) => {
    const html = editor.getHTML()
    emit('update:modelValue', html === '<p></p>' ? '' : html)
  },
})

watch(
  () => props.modelValue,
  (value) => {
    if (editor.value && (value ?? '') !== editor.value.getHTML()) {
      editor.value.commands.setContent(value ?? '', { emitUpdate: false })
    }
  },
)

onBeforeUnmount(() => editor.value?.destroy())

const tools = computed(() => [
  {
    icon: Bold,
    action: () => editor.value?.chain().focus().toggleBold().run(),
    active: () => editor.value?.isActive('bold'),
  },
  {
    icon: Italic,
    action: () => editor.value?.chain().focus().toggleItalic().run(),
    active: () => editor.value?.isActive('italic'),
  },
  {
    icon: List,
    action: () => editor.value?.chain().focus().toggleBulletList().run(),
    active: () => editor.value?.isActive('bulletList'),
  },
  {
    icon: ListOrdered,
    action: () => editor.value?.chain().focus().toggleOrderedList().run(),
    active: () => editor.value?.isActive('orderedList'),
  },
])
</script>

<template>
  <div class="overflow-hidden rounded-lg border border-input bg-background focus-within:ring-2 focus-within:ring-ring">
    <div class="flex items-center gap-0.5 border-b border-border bg-muted/40 p-1">
      <button
        v-for="(tool, i) in tools"
        :key="i"
        type="button"
        :class="cn('rounded-md p-1.5 text-muted-foreground hover:bg-background hover:text-foreground', tool.active?.() && 'bg-background text-primary')"
        @click="tool.action"
      >
        <component :is="tool.icon" class="size-4" />
      </button>
    </div>
    <EditorContent :editor="editor" />
  </div>
</template>
